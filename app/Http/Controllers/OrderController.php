<?php

namespace App\Http\Controllers;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Profile;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');

        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id')
            ->get(['id', 'name']);

        $tables = Table::query()
            ->when($areas->isNotEmpty(), fn($q) => $q->whereIn('area_id', $areas->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'area_id', 'capacity', 'situation', 'opened_at']);

        $tablesPayload = $tables->map(function (Table $table) {
            $elapsed = '--:--';
            if ($table->opened_at instanceof \DateTimeInterface) {
                $elapsed = $table->opened_at->format('H:i');
            } elseif (!empty($table->opened_at)) {
                $elapsed = (string) $table->opened_at;
            }

            return [
                'id' => $table->id,
                'name' => $table->name,
                'area_id' => (int) $table->area_id,
                'situation' => $table->situation ?? 'libre',
                'diners' => (int) ($table->capacity ?? 0),
                'waiter' => '-',
                'client' => '-',
                'total' => 0,
                'elapsed' => $elapsed,
            ];
        })->values();

        // Convertir áreas a array para asegurar compatibilidad con Alpine.js
        $areasArray = $areas->map(function ($area) {
            return [
                'id' => (int) $area->id,
                'name' => $area->name,
            ];
        })->values();

        return view('orders.index', [
            'areas' => $areasArray,
            'tables' => $tablesPayload,
        ]);
    }

    public function create(Request $request)
    {
        $tableId = $request->query('table_id');
        $branchId = session('branch_id');
        $profileId = session('profile_id');
        $personId = session('person_id');
        $userId = session('user_id');
        
        $user = User::find($userId);
        $person = Person::find($personId);
        $profile = Profile::find($profileId);
        $branch = Branch::find($branchId);
        
        // Buscar la mesa y cargar su área relacionada
        $table = Table::with('area')->find($tableId);
        
        if (!$table) {
            abort(404, 'Mesa no encontrada');
        }
        
        // Obtener el área de la relación de la mesa o buscar por área_id si no está relacionada
        $area = $table->area;
        if (!$area && $request->has('area_id')) {
            $area = Area::find($request->query('area_id'));
        }
        
        $products = Product::where('type', 'PRODUCT')
            ->with('category')
            ->get()
            ->map(function($product) {
                $imageUrl = ($product->image && !empty($product->image))
                    ? asset('storage/' . $product->image) 
                    : null;
                return [
                    'id' => $product->id,
                    'name' => $product->description,
                    'img' => $imageUrl,
                    'category' => $product->category ? $product->category->description : 'Sin categoría'
                ];
            });
        
        $productBranches = ProductBranch::where('branch_id', $branchId)
            ->with('product')
            ->get()
            ->map(function($productBranch) {
                return [
                    'id' => $productBranch->id,
                    'product_id' => $productBranch->product_id,
                    'price' => (float) $productBranch->price,
                ];
            });
        $categories = Category::orderBy('description')->get();
        $units = Unit::orderBy('description')->get();
        
        return view('orders.create', [
            'user' => $user,
            'person' => $person,
            'profile' => $profile,
            'branch' => $branch,
            'area' => $area,
            'table' => $table,
            'products' => $products,
            'productBranches' => $productBranches,
            'categories' => $categories,
            'units' => $units,
        ]);
    }
}
