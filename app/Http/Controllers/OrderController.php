<?php

namespace App\Http\Controllers;
use App\Models\Area;
use App\Models\Branch;
use App\Models\Card;
use App\Models\CashMovements;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\DocumentType;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\PaymentConcept;
use App\Models\PaymentGateways;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\Profile;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use App\Models\Shift;
use App\Models\Table;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');

        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id')
            ->get(['id', 'name']);

        // Si hay áreas, filtrar mesas por área. Si no hay áreas pero hay branch_id, no mostrar mesas.
        // Si no hay branch_id, mostrar todas las mesas.
        $tables = Table::query()
            ->when($areas->isNotEmpty(), fn($q) => $q->whereIn('area_id', $areas->pluck('id')))
            ->when($branchId && $areas->isEmpty(), fn($q) => $q->whereRaw('1 = 0')) // No mostrar mesas si hay branch_id pero no hay áreas
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
            ->map(function($product) use ($table, $tableId, $branchId) {
                $imageUrl = ($product->image && !empty($product->image))
                    ? asset('storage/' . $product->image) 
                    : null;
                return [
                    'id' => $product->id,
                    'name' => $product->description,
                    'img' => $imageUrl,
                    'category' => $product->category ? $product->category->description : 'Sin categoría',
                    'table_id' => $tableId,
                    'branch_id' => $branchId
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

    public function charge(Request $request)
    {
        $documentTypes = DocumentType::query()
            ->orderBy('name')
            ->where('movement_type_id', 2)
            ->get(['id', 'name']);
        
        $paymentMethods = PaymentMethod::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'order_num']);
        
        $paymentGateways = PaymentGateways::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'order_num']);
        
        $cards = Card::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'type', 'icon', 'order_num']);
        
        // Si se pasa un movement_id, cargar la orden pendiente o con pago parcial
        $draftOrder = null;
        $pendingAmount = 0;
        if ($request->has('movement_id')) {
            $movement = Movement::with(['salesMovement.details.product', 'cashMovement'])
                ->where('id', $request->movement_id)
                ->whereIn('status', ['P', 'A']) // Cargar si está pendiente o activo (puede tener deuda)
                ->first();
            
            if ($movement && $movement->salesMovement) {
                // Calcular el monto pendiente si hay una deuda
                if ($movement->cashMovement) {
                    $debt = DB::table('cash_movement_details')
                        ->where('cash_movement_id', $movement->cashMovement->id)
                        ->where('type', 'DEUDA')
                        ->where('status', 'A')
                        ->sum('amount');
                    $pendingAmount = $debt ?? 0;
                }
                
                $draftOrder = [
                    'id' => $movement->id,
                    'number' => $movement->number,
                    'items' => $movement->salesMovement->details->map(function($detail) {
                        return [
                            'pId' => $detail->product_id,
                            'name' => $detail->product->description ?? 'Producto #' . $detail->product_id,
                            'qty' => (float) $detail->quantity,
                            'price' => (float) $detail->original_amount / (float) $detail->quantity,
                            'note' => $detail->comment ?? '',
                        ];
                    })->toArray(),
                    'clientName' => $movement->person_name ?? 'Público General',
                    'notes' => $movement->comment ?? '',
                    'pendingAmount' => $pendingAmount,
                ];
            }
        }
        
        // Obtener todos los productos para poder mostrar sus nombres cuando se carga desde localStorage
        $products = Product::pluck('description', 'id')->toArray();
        
        return view('orders.charge', [
            'documentTypes' => $documentTypes,
            'paymentMethods' => $paymentMethods,
            'paymentGateways' => $paymentGateways,
            'cards' => $cards,
            'draftOrder' => $draftOrder,
            'pendingAmount' => $pendingAmount,
            'products' => $products, // Mapa de ID => descripción
        ]);
    }

    public function processOrder(Request $request)
    {
        // Procesar el pedido utilizando la misma lógica de ventas
        // Las órdenes del restaurante se procesan como ventas en el sistema
        
        // Modificar el comentario para identificar que es una orden del restaurante
        $requestData = $request->all();
        if (empty($requestData['notes'])) {
            $requestData['notes'] = 'Pedido del restaurante';
        } else {
            $requestData['notes'] = 'Pedido del restaurante - ' . $requestData['notes'];
        }
        
        // Crear un nuevo request con los datos modificados
        $modifiedRequest = new Request($requestData);
        $modifiedRequest->setUserResolver($request->getUserResolver());
        
        $salesController = new \App\Http\Controllers\SalesController();
        
        return $salesController->processSale($modifiedRequest);
    }
}
