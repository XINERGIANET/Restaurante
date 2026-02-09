<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\TaxRate;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
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
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);
        
        if ($request->hasFile('image')) {
    
            $path = $request->file('image')->store('product', 'public');
            $data['image'] = $path;
        }
        
        Product::create($data);
        
        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Producto creado correctamente.');
    }

    public function edit(Product $product)
    {
        $categories = Category::query()->orderBy('description')->get();
        $units = Unit::query()->orderBy('description')->get();

        return view('products.edit', [
            'product' => $product,
            'categories' => $categories,
            'units' => $units,
        ]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request);
        
        Log::info('=== UPDATE PRODUCT IMAGE DEBUG ===');
        Log::info('Has file image: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('File is valid: ' . ($file->isValid() ? 'YES' : 'NO'));
            Log::info('File size: ' . $file->getSize() . ' bytes');
            Log::info('File real path: ' . $file->getRealPath());
            Log::info('File is readable: ' . (is_readable($file->getRealPath()) ? 'YES' : 'NO'));
            Log::info('File original name: ' . $file->getClientOriginalName());
            Log::info('File mime type: ' . $file->getMimeType());
        }
        
        // Manejar la actualización de imagen
        if ($request->hasFile('image')) {
    
            $path = $request->file('image')->store('product', 'public');
            $data['image'] = $path;
        }
        
        $product->update($data);
        
        Log::info('Product updated. Image in DB: ' . ($product->image ?? 'NULL'));
        Log::info('=== END DEBUG ===');

        return redirect()
            ->route('admin.products.index')
            ->with('status', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product)
    {
        // Eliminar la imagen si existe
        if ($product->image && !empty($product->image) && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }
        
        $product->delete();

        return redirect()
            ->route('admin.products.index')
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
            'is_compound' => ['required', 'string', 'in:S,N'],
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
}
