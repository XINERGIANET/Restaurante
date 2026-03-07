<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Operation;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductType;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        $viewId = $request->input('view_id');
        $branchId = \effective_branch_id();
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $operaciones = collect();
        if ($viewId && $branchId && $profileId) {
            $operaciones = Operation::query()
                ->select('operations.*')
                ->join('branch_operation', function ($join) use ($branchId) {
                    $join->on('branch_operation.operation_id', '=', 'operations.id')
                        ->where('branch_operation.branch_id', $branchId)
                        ->where('branch_operation.status', 1)
                        ->whereNull('branch_operation.deleted_at');
                })
                ->join('operation_profile_branch', function ($join) use ($branchId, $profileId) {
                    $join->on('operation_profile_branch.operation_id', '=', 'operations.id')
                        ->where('operation_profile_branch.branch_id', $branchId)
                        ->where('operation_profile_branch.profile_id', $profileId)
                        ->where('operation_profile_branch.status', 1)
                        ->whereNull('operation_profile_branch.deleted_at');
                })
                ->where('operations.status', 1)
                ->where('operations.view_id', $viewId)
                ->whereNull('operations.deleted_at')
                ->orderBy('operations.id')
                ->distinct()
                ->get();
        }
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $products = Product::query()
            ->with(['category', 'baseUnit', 'productBranches.branch', 'productBranches.taxRate'])
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('productBranches', fn ($q) => $q->where('branch_id', $branchId));
            })
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'ILIKE', "%{$search}%")
                    ->orWhere('code', 'ILIKE', "%{$search}%")
                    ->orWhere('abbreviation', 'ILIKE', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::query()
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereExists(function ($sub) use ($branchId) {
                    $sub->select(DB::raw(1))
                        ->from('category_branch')
                        ->whereColumn('category_branch.category_id', 'categories.id')
                        ->where('category_branch.branch_id', $branchId)
                        ->whereNull('category_branch.deleted_at');
                });
            })
            ->orderBy('description')
            ->get();
        $units = Unit::query()->orderBy('description')->get();
        $taxRates = TaxRate::query()->where('status', true)->orderBy('order_num')->get();
        $currentBranch = Branch::find(session('branch_id'));
        $branches = $currentBranch
            ? Branch::query()->where('company_id', $currentBranch->company_id)->orderBy('legal_name')->get(['id', 'legal_name'])
            : Branch::query()->orderBy('legal_name')->get(['id', 'legal_name']);
        $productTypes = $branchId
            ? ProductType::query()->where('branch_id', $branchId)->orderByRaw("CASE behavior WHEN 'SELLABLE' THEN 1 ELSE 2 END")->orderBy('name')->get()
            : collect();
        $igvByBranchId = DB::table('branch_parameters as bp')
            ->join('parameters as p', 'p.id', '=', 'bp.parameter_id')
            ->whereNull('bp.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.description', 'igv_defecto')
            ->pluck('bp.value', 'bp.branch_id')
            ->map(fn ($v) => is_numeric($v) ? (int) $v : null)
            ->filter()
            ->all();

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'taxRates' => $taxRates,
            'currentBranch' => $currentBranch,
            'branches' => $branches,
            'productTypes' => $productTypes,
            'igvByBranchId' => $igvByBranchId,
            'search' => $search,
            'perPage' => $perPage,
            'operaciones' => $operaciones,
        ]);
    }

    public function store(Request $request)
    {
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');

            if ($file->isValid() && $file->getRealPath() && is_readable($file->getRealPath())) {
                try {
                    // Asegurar que el directorio existe
                    $directory = storage_path('app/public/product');
                    if (!is_dir($directory)) {
                        $created = @mkdir($directory, 0755, true);
                        if (!$created) {
                            Log::error(message: 'Failed to create directory: ' . $directory);
                        }
                    }
                    
                    // Verificar permisos del directorio
                    if (is_dir($directory)) {
                    }   
                    $path = $file->store('product', 'public');
                    
                    if ($path && !empty($path)) {
                        $imagePath = $path;
                    } else {
                            Log::warning(message: 'El path de la imagen está vacío después de guardar');
                    }
                } catch (\Exception $e) {
                    Log::error(message: 'Error al guardar imagen del producto: ' . $e->getMessage());
                }
            } else {
                Log::warning(message: 'El archivo de imagen no es válido o no tiene path');
            }
        } else {
            Log::info(message: 'No image file in request');
        }
        
        $validated = $this->validateProduct($request);
        $productType = ProductType::find($validated['product_type_id']);
        $productData = $this->prepareProductData($validated, $productType);
        $branchData = $this->prepareBranchData($validated, $productType);

        if ($imagePath !== null && $imagePath !== '') {
            $productData['image'] = is_string($imagePath) ? $imagePath : (string) $imagePath;
            Log::info('Image path added to data: ' . $productData['image']);
        }

        $product = Product::create($productData);
        
        // Crear ProductBranch para la sucursal actual
        $branchId = (int) ($request->input('branch_id') ?: $request->session()->get('branch_id'));
        if ($branchId) {
            $branchData['product_id'] = $product->id;
            $branchData['branch_id'] = $branchId;
            $branchData['status'] = 'A';
            ProductBranch::create($branchData);
        }
        
        $viewId = $request->input('view_id');
        
        return redirect()
            ->route('products.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Producto creado correctamente.');
    }

    public function edit(Request $request, Product $product)
    {
        $product->load(['category', 'productType', 'productBranches']);
        $branchId = $request->session()->get('branch_id');
        $categoryBranchId = \effective_branch_id();
        $categories = Category::query()
            ->when($categoryBranchId !== null, function ($query) use ($categoryBranchId) {
                $query->whereExists(function ($sub) use ($categoryBranchId) {
                    $sub->select(DB::raw(1))
                        ->from('category_branch')
                        ->whereColumn('category_branch.category_id', 'categories.id')
                        ->where('category_branch.branch_id', $categoryBranchId)
                        ->whereNull('category_branch.deleted_at');
                });
            })
            ->orderBy('description')
            ->get();
        $units = Unit::query()->orderBy('description')->get();
        $taxRates = TaxRate::query()->where('status', true)->orderBy('order_num')->get();
        $branchId = $request->session()->get('branch_id');
        $productBranch = $product->productBranches()
            ->where('branch_id', $branchId)
            ->first();

        $productTypes = $branchId
            ? ProductType::query()->where('branch_id', $branchId)->orderByRaw("CASE behavior WHEN 'SELLABLE' THEN 1 ELSE 2 END")->orderBy('name')->get()
            : collect();
        if ($product->product_type_id === null && $productTypes->isNotEmpty()) {
            $product->product_type_id = $product->type === 'INGREDENT'
                ? $productTypes->firstWhere('behavior', ProductType::BEHAVIOR_SUPPLY)?->id ?? $productTypes->first()->id
                : $productTypes->firstWhere('behavior', ProductType::BEHAVIOR_SELLABLE)?->id ?? $productTypes->first()->id;
        }

        $currentBranch = Branch::find(session('branch_id'));
        $branches = $currentBranch
            ? Branch::query()->where('company_id', $currentBranch->company_id)->orderBy('legal_name')->get(['id', 'legal_name'])
            : Branch::query()->orderBy('legal_name')->get(['id', 'legal_name']);
        $igvByBranchId = DB::table('branch_parameters as bp')
            ->join('parameters as p', 'p.id', '=', 'bp.parameter_id')
            ->whereNull('bp.deleted_at')
            ->whereNull('p.deleted_at')
            ->where('p.description', 'igv_defecto')
            ->pluck('bp.value', 'bp.branch_id')
            ->map(fn ($v) => is_numeric($v) ? (int) $v : null)
            ->filter()
            ->all();
        $productBranchesByBranchId = $product->productBranches
            ->keyBy('branch_id')
            ->map(fn (ProductBranch $pb) => [
                'price' => (float) ($pb->price ?? 0),
                'purchase_price' => (float) ($pb->purchase_price ?? 0),
                'stock' => (float) ($pb->stock ?? 0),
                'stock_minimum' => (float) ($pb->stock_minimum ?? 0),
                'stock_maximum' => (float) ($pb->stock_maximum ?? 0),
                'minimum_sell' => (float) ($pb->minimum_sell ?? 0),
                'minimum_purchase' => (float) ($pb->minimum_purchase ?? 0),
                'tax_rate_id' => $pb->tax_rate_id,
                'unit_sale' => is_numeric($pb->unit_sale) ? (int) $pb->unit_sale : null,
                'expiration_date' => $pb->expiration_date,
                'supplier_id' => $pb->supplier_id,
                'favorite' => $pb->favorite ?? 'N',
                'duration_minutes' => (float) ($pb->duration_minutes ?? 0),
            ]);

        return view('products.edit', [
            'product' => $product,
            'productBranch' => $productBranch,
            'categories' => $categories,
            'units' => $units,
            'taxRates' => $taxRates,
            'productTypes' => $productTypes,
            'suppliers' => collect(),
            'branches' => $branches,
            'igvByBranchId' => $igvByBranchId,
            'productBranchesByBranchId' => $productBranchesByBranchId,
            'viewId' => $request->input('view_id'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $validated = $this->validateProduct($request, $product->id);
        $productType = ProductType::find($validated['product_type_id']);
        $productData = $this->prepareProductData($validated, $productType);
        $branchData = $this->prepareBranchData($validated, $productType);
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            if ($file->isValid() && $file->getRealPath()) {
                try {
                    if ($product->image && !empty($product->image) && Storage::disk('public')->exists($product->image)) {
                        Storage::disk('public')->delete($product->image);
                    }
                    $directory = storage_path('app/public/product');
                    if (!is_dir($directory)) {
                        mkdir($directory, 0755, true);
                    }
                    $path = $file->store('product', 'public');
                    if ($path && $path !== '') {
                        $productData['image'] = is_string($path) ? $path : (string) $path;
                    } else {
                        Log::warning(message: 'El path de la imagen está vacío después de guardar');
                    }
                } catch (\Exception $e) {
                    Log::error(message: 'Error al actualizar imagen del producto: ' . $e->getMessage());
                }
            }
        }
        
        // Actualizar producto
        $product->update($productData);
        
        // Actualizar o crear ProductBranch para la sucursal seleccionada
        $branchId = (int) ($validated['branch_id'] ?? $request->session()->get('branch_id') ?? 0);
        if ($branchId) {
            $productBranch = $product->productBranches()
                ->where('branch_id', $branchId)
                ->first();
            
            if ($productBranch) {
                $productBranch->update($branchData);
            } else {
                $branchData['product_id'] = $product->id;
                $branchData['branch_id'] = $branchId;
                $branchData['status'] = 'E';
                ProductBranch::create($branchData);
            }
        }
        
        $viewId = $request->input('view_id');
        
        return redirect()
            ->route('products.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Request $request, Product $product)
    {
        // Eliminar la imagen si existe
        if ($product->image && !empty($product->image) && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();
        $viewId = $request->input('view_id');

        return redirect()
            ->route('products.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Producto eliminado correctamente.');
    }

    private function validateProduct(Request $request, ?int $excludeId = null): array
    {
        $productType = $request->filled('product_type_id')
            ? ProductType::find($request->input('product_type_id'))
            : null;
        $isSupply = $productType && $productType->isSupply();

        $branchRules = [
            'price' => $isSupply ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'purchase_price' => $isSupply ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'stock' => $isSupply
                ? ['nullable', 'numeric', 'min:0']
                : array_values(array_filter([
                    'required', 'numeric', 'min:0',
                    'gte:stock_minimum',
                    $request->input('stock_maximum', 0) > 0 ? 'lte:stock_maximum' : null,
                ])),
            'stock_minimum' => $isSupply ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'stock_maximum' => $isSupply ? ['nullable', 'numeric', 'min:0', 'gte:stock_minimum'] : ['required', 'numeric', 'min:0', 'gte:stock_minimum'],
            'minimum_sell' => $isSupply ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'minimum_purchase' => $isSupply ? ['nullable', 'numeric', 'min:0'] : ['required', 'numeric', 'min:0'],
            'tax_rate_id' => $isSupply ? ['nullable', 'integer', 'exists:tax_rates,id'] : ['required', 'integer', 'exists:tax_rates,id'],
            'unit_sale' => $isSupply ? ['nullable', 'integer', 'exists:units,id'] : ['required', 'integer', 'exists:units,id'],
            'expiration_date' => $isSupply ? ['nullable', 'date'] : ['required', 'date'],
        ];

        $validated = $request->validate(array_merge([
            // Datos del Producto
            'code' => ['required', 'string', 'max:50', 'unique:products,code,' . ($excludeId ?? 'NULL')],
            'description' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'product_type_id' => ['required', 'integer', 'exists:product_types,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'kardex' => ['required', 'string', 'in:S,N'],
            'status' => ['required', 'string', 'in:A,I'],
            'image' => ['nullable', 'sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'],
            'complement' => ['required', 'string', 'in:NO,HAS,IS'],
            'complement_mode' => ['nullable', 'string', 'in:,ALL,QUANTITY'],
            'classification' => ['required', 'string', 'in:GOOD,SERVICE'],
            'features' => ['nullable', 'string'],
            'recipe' => ['required', 'boolean'],
            'branch_id' => $isSupply ? ['nullable', 'integer', 'exists:branches,id'] : ['required', 'integer', 'exists:branches,id'],
            'favorite' => ['required', 'string', 'in:S,N'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'supplier_id' => ['nullable', 'integer'],
        ], $branchRules));

        if (isset($validated['image']) && empty($validated['image'])) {
            unset($validated['image']);
        }

        return $validated;
    }

    private function prepareProductData(array $validated, ?ProductType $productType): array
    {
        $type = 'PRODUCT';
        if ($productType) {
            $type = $productType->isSellable() ? 'PRODUCT' : 'INGREDENT';
        }
        return [
            'code' => $validated['code'],
            'description' => $validated['description'],
            'abbreviation' => $validated['abbreviation'],
            'type' => $type,
            'product_type_id' => $validated['product_type_id'],
            'category_id' => $validated['category_id'],
            'base_unit_id' => $validated['base_unit_id'],
            'kardex' => $validated['kardex'],
            'complement' => $validated['complement'],
            'complement_mode' => $validated['complement_mode'],
            'classification' => $validated['classification'],
            'features' => $validated['features'],
            'recipe' => (bool) $validated['recipe'],
        ];
    }

    private function prepareBranchData(array $validated, ?ProductType $productType): array
    {
        if ($productType && $productType->isSupply()) {
            return [
                'status' => $validated['status'],
                'expiration_date' => null,
                'stock_minimum' => 0,
                'stock_maximum' => 0,
                'minimum_sell' => 0,
                'minimum_purchase' => 0,
                'favorite' => $validated['favorite'],
                'tax_rate_id' => null,
                'unit_sale' => 'N',
                'duration_minutes' => $validated['duration_minutes'] ?? 0,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'stock' => 0,
                'price' => 0,
                'purchase_price' => 0,
            ];
        }
        return [
            'status' => $validated['status'],
            'expiration_date' => $validated['expiration_date'] ?? null,
            'stock_minimum' => $validated['stock_minimum'],
            'stock_maximum' => $validated['stock_maximum'],
            'minimum_sell' => $validated['minimum_sell'],
            'minimum_purchase' => $validated['minimum_purchase'] ?? 0,
            'favorite' => $validated['favorite'],
            'tax_rate_id' => $validated['tax_rate_id'] ?? null,
            'unit_sale' => $validated['unit_sale'] ?? $validated['base_unit_id'] ?? 'N',
            'duration_minutes' => $validated['duration_minutes'] ?? 0,
            'supplier_id' => $validated['supplier_id'] ?? null,
            'stock' => $validated['stock'] ?? 0,
            'price' => $validated['price'] ?? 0,
            'purchase_price' => $validated['purchase_price'] ?? 0,
        ];
    }
}
