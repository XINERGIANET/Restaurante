<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\SaleLines;
use App\Models\Size;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        //
        $products = Product::with('category')->where('deleted', 0)->paginate(10);
        return view('products.index', compact('products'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create(): View
    {
        //
        $categories = Category::where('deleted', 0)->get();
        // $sizes = Size::where('deleted', 0)->get(); <-Talla
        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request): RedirectResponse
    {
        // Validar el campo 'name' requerido y único
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'unit_price' => 'required|numeric|min:0.01',
            'category_id' => 'required|integer|exists:categories,id',
        ]);

        $products = Product::create([
            'name' => $validated['name'],
            'unit_price' => $validated['unit_price'],
            'quantity' => 0,
            'category_id' => $validated['category_id'],
        ]);


        // Redirigir con mensaje de éxito
        return redirect()->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        try {
            $product = Product::with('category')
                ->where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'message' => 'Producto encontrado',
                'data' => $product
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id): JsonResponse
    {
        try {
            $product = Product::with('category')
                ->where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Obtener las opciones para los selects
            $categories = Category::where('deleted', 0)->get();
            return response()->json([
                'status' => true,
                'message' => 'Datos del producto para edición',
                'data' => [
                    'product' => $product,
                    'categories' => $categories,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al obtener los datos para edición: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Buscar el producto
            $product = Product::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Validar los campos
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'unit_price' => 'required|numeric|min:0.01',
                'category_id' => 'required|integer|exists:categories,id',
                'quantity' => 'required|integer|min:0',
            ]);

            // Actualizar el producto
            $product->update([
                'name' => $validated['name'],
                'unit_price' => $validated['unit_price'],
                'quantity' => $validated['quantity'],
                'category_id' => $validated['category_id'],
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Producto actualizado correctamente',
                'data' => $product->fresh(['category'])
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Errores de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al actualizar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id): JsonResponse
    {
        try {
            $product = Product::where('id', $id)
                ->where('deleted', 0)
                ->first();

            if (!$product) {
                return response()->json([
                    'status' => false,
                    'message' => 'Producto no encontrado'
                ], 404);
            }

            // Verificar si el producto está siendo usado en ventas/pedidos
            $isUsedInSales = DB::table('sale_details')
                ->where('product_id', $id)
                ->exists();

            $isUsedInOrders = DB::table('order_details')
                ->where('product_id', $id)
                ->exists();

            $isUsedInPurchases = DB::table('purchase_details')
                ->where('product_id', $id)
                ->exists();

            if ($isUsedInSales || $isUsedInOrders || $isUsedInPurchases) {
                return response()->json([
                    'status' => false,
                    'message' => 'No se puede eliminar el producto porque está siendo utilizado en ventas, pedidos o compras'
                ], 400);
            }

            // Soft delete - marcar como eliminado
            $product->update(['deleted' => 1]);

            return response()->json([
                'status' => true,
                'message' => 'Producto eliminado correctamente'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al eliminar el producto: ' . $e->getMessage()
            ], 500);
        }
    }

    public function searchpv(Request $request)
    {
        $query = $request->input('query'); // Obtener el término de búsqueda

        // Buscar productos que coincidan con el término
        // Filtrar por productos cuya categoría tenga sale_line 'Ropa'
        $products = Product::with('category')
            ->where('name', 'LIKE', "%{$query}%")
            ->where('deleted', 0) // Solo productos no eliminados
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->where('deleted', 0)
                    ->whereHas('sale_line', function ($saleLineQuery) {
                        $saleLineQuery->where('name', 'Ropa');
                    });
            })
            ->select('id', 'name', 'unit_price', 'quantity')
            ->limit(10)
            ->get();

        return response()->json($products); // Devolver resultados en JSON
    }

    public function searchrs(Request $request)
    {
        $query = $request->input('query'); // Obtener el término de búsqueda

        // Buscar productos que coincidan con el término
        // Filtrar por productos cuya categoría tenga sale_line 'Restaurante'
        $products = Product::with('category')
            ->where('name', 'LIKE', "%{$query}%")
            ->where('deleted', 0) // Solo productos no eliminados
            ->whereHas('category', function ($categoryQuery) {
                $categoryQuery->where('deleted', 0);
            })
            ->select('id', 'name', 'unit_price', 'quantity')
            ->limit(10)
            ->get();

        return response()->json($products); // Devolver resultados en JSON
    }
}
