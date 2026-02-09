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
        Log::info('=== PRODUCT STORE DEBUG START ===');
        Log::info('Request has file image: ' . ($request->hasFile('image') ? 'YES' : 'NO'));
        Log::info('Request all keys: ' . json_encode(array_keys($request->all())));
        
        // Guardar el archivo ANTES de validar, porque la validación puede afectar el archivo
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('File object received: ' . get_class($file));
            Log::info('File isValid: ' . ($file->isValid() ? 'YES' : 'NO'));
            Log::info('File getRealPath: ' . ($file->getRealPath() ?? 'NULL'));
            Log::info('File getPathname: ' . ($file->getPathname() ?? 'NULL'));
            Log::info('File getClientOriginalName: ' . ($file->getClientOriginalName() ?? 'NULL'));
            Log::info('File getSize: ' . ($file->getSize() ?? 'NULL'));
            Log::info('File getMimeType: ' . ($file->getMimeType() ?? 'NULL'));
            
            if ($file->getRealPath()) {
                Log::info('File isReadable: ' . (is_readable($file->getRealPath()) ? 'YES' : 'NO'));
            }
            
            // Verificar configuración del disco
            try {
                $diskRoot = Storage::disk('public')->path('');
                Log::info('Storage disk public root: ' . $diskRoot);
                Log::info('Storage disk public exists: ' . (Storage::disk('public')->exists('.') ? 'YES' : 'NO'));
            } catch (\Exception $e) {
                Log::error('Error checking storage disk: ' . $e->getMessage());
            }
            
            if ($file->isValid() && $file->getRealPath() && is_readable($file->getRealPath())) {
                try {
                    // Asegurar que el directorio existe
                    $directory = storage_path('app/public/product');
                    Log::info('Directory path: ' . $directory);
                    Log::info('Directory exists: ' . (is_dir($directory) ? 'YES' : 'NO'));
                    
                    if (!is_dir($directory)) {
                        $created = @mkdir($directory, 0755, true);
                        Log::info('Directory created: ' . ($created ? 'YES' : 'NO'));
                        if (!$created) {
                            Log::error('Failed to create directory: ' . $directory);
                        }
                    }
                    
                    // Verificar permisos del directorio
                    if (is_dir($directory)) {
                        Log::info('Directory is writable: ' . (is_writable($directory) ? 'YES' : 'NO'));
                    }
                    
                    Log::info('Attempting to store file...');
                    Log::info('File real path before store: ' . $file->getRealPath());
                    Log::info('File exists before store: ' . (file_exists($file->getRealPath()) ? 'YES' : 'NO'));
                    
                    $path = $file->store('product', 'public');
                    Log::info('Store returned path: ' . ($path ?? 'NULL'));
                    
                    if ($path && !empty($path)) {
                        $imagePath = $path;
                        Log::info('Image path saved: ' . $imagePath);
                    } else {
                        Log::warning('El path de la imagen está vacío después de guardar');
                    }
                } catch (\Exception $e) {
                    Log::error('Error al guardar imagen del producto: ' . $e->getMessage());
                    Log::error('Exception class: ' . get_class($e));
                    Log::error('Exception trace: ' . $e->getTraceAsString());
                }
            } else {
                Log::warning('El archivo de imagen no es válido o no tiene path');
                Log::warning('isValid: ' . ($file->isValid() ? 'YES' : 'NO'));
                Log::warning('getRealPath: ' . ($file->getRealPath() ?? 'NULL'));
                if ($file->getRealPath()) {
                    Log::warning('isReadable: ' . (is_readable($file->getRealPath()) ? 'YES' : 'NO'));
                }
            }
        } else {
            Log::info('No image file in request');
        }
        
        // Ahora validar los datos (sin el campo image si ya lo procesamos)
        $data = $this->validateProduct($request);
        Log::info('Data after validation: ' . json_encode(array_keys($data)));
        
        // No pasar nunca el archivo subido ni rutas temporales a la BD. Solo path relativo de storage (string).
        unset($data['image']);
        if ($imagePath !== null && $imagePath !== '') {
            $data['image'] = is_string($imagePath) ? $imagePath : (string) $imagePath;
            Log::info('Image path added to data: ' . $data['image']);
        }
        
        Log::info('Final data image: ' . ($data['image'] ?? 'NOT SET'));
        Log::info('=== PRODUCT STORE DEBUG END ===');
        
        $product = Product::create($data);
        if (! empty($data['image'])) {
            Log::info('Product created with image in DB: ' . $product->image);
        }
        
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
        
        // No pasar nunca el archivo subido ni rutas temporales a la BD
        unset($data['image']);
        
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            Log::info('File is valid: ' . ($file->isValid() ? 'YES' : 'NO'));
            Log::info('File size: ' . $file->getSize() . ' bytes');
            Log::info('File real path: ' . $file->getRealPath());
            Log::info('File is readable: ' . (is_readable($file->getRealPath()) ? 'YES' : 'NO'));
            Log::info('File original name: ' . $file->getClientOriginalName());
            Log::info('File mime type: ' . $file->getMimeType());
            
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
                        Log::warning('El path de la imagen está vacío después de guardar');
                    }
                } catch (\Exception $e) {
                    Log::error('Error al actualizar imagen del producto: ' . $e->getMessage());
                    unset($data['image']);
                }
            } else {
                Log::warning('El archivo de imagen no es válido o no tiene path');
            }
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
