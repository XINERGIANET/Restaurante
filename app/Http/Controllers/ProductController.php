<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Operation;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\Request;
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
        $branchId = $request->session()->get('branch_id');
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
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $categories = Category::query()->orderBy('description')->get();
        $units = Unit::query()->orderBy('description')->get();
        $taxRates = TaxRate::query()->where('status', true)->orderBy('order_num')->get();
        $currentBranch = Branch::find(session('branch_id'));

        return view('products.index', [
            'products' => $products,
            'categories' => $categories,
            'units' => $units,
            'taxRates' => $taxRates,
            'currentBranch' => $currentBranch,
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
        
        // Ahora validar los datos (sin el campo image si ya lo procesamos)
        $data = $this->validateProduct($request);
        
        // No pasar nunca el archivo subido ni rutas temporales a la BD. Solo path relativo de storage (string).
        unset($data['image']);
        if ($imagePath !== null && $imagePath !== '') {
            $data['image'] = is_string($imagePath) ? $imagePath : (string) $imagePath;
            Log::info('Image path added to data: ' . $data['image']);
        }
        
        $product = Product::create($data);
        $this->syncProductBranch($request, $product, null);
        $viewId = $request->input('view_id');
        
        return redirect()
            ->route('admin.products.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Producto creado correctamente.');
    }

    public function edit(Request $request, Product $product)
    {
        $categories = Category::query()->orderBy('description')->get();
        $units = Unit::query()->orderBy('description')->get();
        $taxRates = TaxRate::query()->where('status', true)->orderBy('order_num')->get();
        $branchId = session('branch_id');
        $currentBranch = $branchId ? Branch::find($branchId) : null;
        $productBranch = ($branchId && $product->id)
            ? ProductBranch::where('product_id', $product->id)->where('branch_id', $branchId)->first()
            : null;

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
            'taxRates' => $taxRates,
            'currentBranch' => $currentBranch,
            'productBranch' => $productBranch,
            'viewId' => $request->input('view_id'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request);
        
        // No pasar nunca el archivo subido ni rutas temporales a la BD
        unset($data['image']);
        
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
                        $data['image'] = is_string($path) ? $path : (string) $path;
                    } else {
                        Log::warning(message: 'El path de la imagen está vacío después de guardar');
                    }
                } catch (\Exception $e) {
                    Log::error(message: 'Error al actualizar imagen del producto: ' . $e->getMessage());
                    unset($data['image']);
                }
            }
        }
        
        $product->update($data);
        $this->syncProductBranch($request, $product, $product);
        $viewId = $request->input('view_id');
        
        return redirect()
            ->route('admin.products.index', $viewId ? ['view_id' => $viewId] : [])
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
            ->route('admin.products.index', $viewId ? ['view_id' => $viewId] : [])
            ->with('status', 'Producto eliminado correctamente.');
    }

    private function validateProduct(Request $request): array
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
            'description' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:PRODUCT,COMPONENT'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'base_unit_id' => ['required', 'integer', 'exists:units,id'],
            'kardex' => ['required', 'string', 'in:S,N'],
            'image' => ['nullable', 'sometimes', 'image', 'mimes:jpeg,png,jpg,gif,webp', 'max:2048'], // Máximo 2MB
            'complement' => ['required', 'string', 'in:NO,HAS,IS'],
            'complement_mode' => ['nullable', 'string', 'max:255'],
            'classification' => ['required', 'string', 'in:GOOD,SERVICE'],
            'features' => ['nullable', 'string'],
        ]);
        
        // Eliminar el campo image si está vacío o es null
        if (isset($validated['image']) && empty($validated['image'])) {
            unset($validated['image']);
        }
        
        return $validated;
    }

    private function syncProductBranch(Request $request, Product $product, ?Product $existingProduct): void
    {
        $branchId = session('branch_id');
        if (!$branchId) {
            return;
        }

        $price = $request->input('product_branch_price');
        $stock = $request->input('product_branch_stock');
        $taxRateId = $request->input('product_branch_tax_rate_id');

        $productBranch = ProductBranch::where('product_id', $product->id)
            ->where('branch_id', $branchId)
            ->first();

        $stockMinimum = (float) ($request->input('product_branch_stock_minimum', 0) ?? 0);
        $stockMaximum = (float) ($request->input('product_branch_stock_maximum', 0) ?? 0);
        $stockValue = (float) ($stock ?? 0);

        // Validar: stock debe estar entre stock_minimum y stock_maximum
        if ($stockMaximum > 0 && $stockValue > $stockMaximum) {
            throw ValidationException::withMessages([
                'product_branch_stock' => ['El stock actual no puede ser mayor que el stock máximo (' . $stockMaximum . ').'],
            ]);
        }
        if ($stockValue < $stockMinimum) {
            throw ValidationException::withMessages([
                'product_branch_stock' => ['El stock actual no puede ser menor que el stock mínimo (' . $stockMinimum . ').'],
            ]);
        }

        if ($productBranch) {
            $productBranch->update([
                'stock' => (int) ($stock ?? $productBranch->stock),
                'price' => (float) ($price ?? $productBranch->price),
                'tax_rate_id' => $taxRateId ?: $productBranch->tax_rate_id,
                'stock_minimum' => $stockMinimum,
                'stock_maximum' => $stockMaximum,
                'minimum_sell' => (float) ($request->input('product_branch_minimum_sell', 0) ?? 0),
                'minimum_purchase' => (float) ($request->input('product_branch_minimum_purchase', 0) ?? 0),
            ]);
        } elseif ($price !== null && $price !== '' && (float) $price >= 0) {
            ProductBranch::create([
                'product_id' => $product->id,
                'branch_id' => $branchId,
                'stock' => (int) ($stock ?? 0),
                'price' => (float) $price,
                'tax_rate_id' => $taxRateId ?: null,
                'stock_minimum' => $stockMinimum,
                'stock_maximum' => $stockMaximum,
                'minimum_sell' => (float) ($request->input('product_branch_minimum_sell', 0) ?? 0),
                'minimum_purchase' => (float) ($request->input('product_branch_minimum_purchase', 0) ?? 0),
                'unit_sale' => 'N',
                'status' => 'E',
                'favorite' => 'N',
                'duration_minutes' => 0,
            ]);
        }
    }
}
