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
use App\Models\OrderMovement;
use App\Models\OrderMovementDetail;
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
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function list(Request $request)
    {
        $search = $request->input('search');
        $viewId = $request->input('view_id');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

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

        $orders = OrderMovement::query()
            ->with([
                'movement.branch',
                'movement.person',
                'movement.movementType',
                'movement.documentType',
                'table',
                'area',
            ])
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('movement', function ($movementQuery) use ($search) {
                        $movementQuery->where(function ($movementInner) use ($search) {
                            $movementInner->where('number', 'like', "%{$search}%")
                                ->orWhere('person_name', 'like', "%{$search}%")
                                ->orWhere('user_name', 'like', "%{$search}%");
                        });
                    })
                    ->orWhere('status', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
      
        return view('orders.list', [
            'orders' => $orders,
            'search' => $search,
            'perPage' => $perPage,
            'operaciones' => $operaciones,
        ]);
    }

    public function index(Request $request)
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

            $rawSituation = $table->situation ?? 'libre';
            $situation = strtolower((string) $rawSituation);
            if ($situation !== 'libre' && $situation !== 'ocupada') {
                $situation = (in_array($rawSituation, ['PENDIENTE', 'OCUPADA', 'ocupada', 'Pendiente'], true)) ? 'ocupada' : 'libre';
            }

            $orderMovement = OrderMovement::with('movement')
                ->where('table_id', $table->id)
                ->whereIn('status', ['PENDIENTE', 'P'])
                ->orderByDesc('id')
                ->first();
            $totalAmount = $orderMovement ? (float) $orderMovement->subtotal : 0;
            $taxAmount = $orderMovement ? (float) ($orderMovement->tax ?? 0) : 0;
            $totalWithTax = round($totalAmount + $taxAmount, 2);

            return [
                'id' => $table->id,
                'name' => $table->name,
                'area_id' => (int) $table->area_id,
                'situation' => $situation,
                'diners' => (int) ($table->capacity ?? 0),
                'waiter' => $orderMovement?->movement?->user_name ?? '-',
                'client' => $orderMovement?->movement?->person_name ?? '-',
                'total' => $totalWithTax,
                'order_movement_id' => $orderMovement?->id ?? null,
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
            'user' => $request->user(),
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
        
        // Si se pasa un movement_id, cargar la orden pendiente (pedido o venta)
        $draftOrder = null;
        $pendingAmount = 0;
        if ($request->has('movement_id')) {
            $movement = Movement::with(['salesMovement.details.product', 'cashMovement', 'orderMovement.details'])
                ->where('id', $request->movement_id)
                ->whereIn('status', ['P', 'A'])
                ->first();

            // Pedido: OrderMovement + detalles
            if ($movement && $movement->orderMovement && $movement->orderMovement->details) {
                $om = $movement->orderMovement;
                $draftOrder = [
                    'id' => $movement->id,
                    'number' => $movement->number,
                    'items' => $om->details->map(function ($detail) {
                        return [
                            'pId' => $detail->product_id,
                            'name' => $detail->description ?? 'Producto #' . $detail->product_id,
                            'qty' => (float) $detail->quantity,
                            'price' => $detail->quantity > 0 ? (float) $detail->amount / (float) $detail->quantity : 0,
                            'note' => $detail->comment ?? '',
                        ];
                    })->toArray(),
                    'clientName' => $movement->person_name ?? 'Público General',
                    'notes' => $movement->comment ?? '',
                    'pendingAmount' => $pendingAmount,
                ];
            }
            // Venta: SalesMovement + detalles
            if (!$draftOrder && $movement && $movement->salesMovement) {
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
                    'items' => $movement->salesMovement->details->map(function ($detail) {
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
        $items = $request->input('items', []);
        $branchId = session('branch_id');
        $user = $request->user();

        // Subtotal: usar el enviado por el front o recalcular desde items
        $subtotal = $request->has('subtotal') ? (float) $request->subtotal : 0;
        if ($subtotal == 0 && !empty($items)) {
            foreach ($items as $rawItem) {
                $qty = (float) ($rawItem['quantity'] ?? $rawItem['qty'] ?? 1);
                $price = (float) ($rawItem['price'] ?? 0);
                $subtotal += $qty * $price;
            }
        }
        $subtotal = round($subtotal, 6);

        // Tax y total: usar los enviados por el front o calcular (10% impuesto)
        $tax = $request->has('tax') ? (float) $request->tax : round($subtotal * 0.10, 6);
        $total = $request->has('total') ? (float) $request->total : round($subtotal + $tax, 6);
        $tax = round($tax, 6);
        $total = round($total, 6);

        $tableId = $request->filled('table_id') ? $request->table_id : null;
        $areaId = $request->filled('area_id') ? $request->area_id : null;
        $peopleCount = max(0, (int) $request->input('people_count', 0));
        $deliveryAmount = round((float) ($request->input('delivery_amount', 0) ?: 0), 6);

        DB::beginTransaction();

        try {
            // Si hay mesa, buscar pedido pendiente existente para actualizar en lugar de crear uno nuevo
            $existingOrderMovement = $tableId
                ? OrderMovement::where('table_id', $tableId)
                    ->whereIn('status', ['PENDIENTE', 'P'])
                    ->orderByDesc('id')
                    ->first()
                : null;

            if ($existingOrderMovement && !empty($items)) {
                // ACTUALIZAR pedido existente
                $existingOrderMovement->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'people_count' => $peopleCount,
                    'delivery_amount' => $deliveryAmount,
                    'contact_phone' => $request->filled('contact_phone') ? $request->contact_phone : null,
                    'delivery_address' => $request->filled('delivery_address') ? $request->delivery_address : null,
                    'delivery_time' => $request->filled('delivery_time') ? $request->delivery_time : null,
                ]);

                $existingOrderMovement->movement?->update([
                    'moved_at' => now(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Sistema',
                ]);

                // Eliminar detalles antiguos y crear los nuevos
                $existingOrderMovement->details()->forceDelete();

                $orderMovement = $existingOrderMovement;
                $movement = $orderMovement->movement;
            } else {
                // CREAR nuevo pedido
                $movementType = MovementType::where('description', 'like', '%pedido%')
                    ->orWhere('description', 'like', '%orden%')
                    ->first() ?? MovementType::first();

                $documentType = DocumentType::first();

                if (!$movementType || !$documentType) {
                    throw new \Exception('No hay tipo de movimiento o tipo de documento configurado para pedidos.');
                }

                $todayCount = Movement::whereDate('created_at', now()->toDateString())->count() + 1;
                $number = 'ORD-' . now()->format('Ymd') . '-' . str_pad($todayCount, 4, '0', STR_PAD_LEFT);

                $movement = Movement::create([
                    'number' => $number,
                    'moved_at' => now(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Sistema',
                    'person_id' => null,
                    'person_name' => 'Público General',
                    'responsible_id' => $user?->id,
                    'responsible_name' => $user?->name ?? 'Sistema',
                    'comment' => 'Pedido desde punto de venta',
                    'status' => 'P',
                    'movement_type_id' => $movementType->id,
                    'document_type_id' => $documentType->id,
                    'branch_id' => $branchId,
                    'parent_movement_id' => null,
                ]);

                $orderMovement = OrderMovement::create([
                    'currency' => 'PEN',
                    'exchange_rate' => 1,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'people_count' => $peopleCount,
                    'finished_at' => null,
                    'table_id' => $tableId,
                    'area_id' => $areaId,
                    'delivery_amount' => $deliveryAmount,
                    'contact_phone' => $request->filled('contact_phone') ? $request->contact_phone : null,
                    'delivery_address' => $request->filled('delivery_address') ? $request->delivery_address : null,
                    'delivery_time' => $request->filled('delivery_time') ? $request->delivery_time : null,
                    'status' => 'PENDIENTE',
                    'movement_id' => $movement->id,
                    'branch_id' => $branchId,
                ]);
            }

            // Marcar la mesa como ocupada
            if ($tableId) {
                $table = Table::find($tableId);
                if ($table) {
                    $table->situation = 'ocupada';
                    $table->opened_at = $table->opened_at ?? now();
                    $table->save();
                }
            }

        foreach ($items as $rawItem) {
            $productId = $rawItem['product_id'] ?? $rawItem['pId'] ?? null;
            $product = $productId ? Product::find($productId) : null;

            $qty = (float) ($rawItem['quantity'] ?? $rawItem['qty'] ?? 1);
            $price = (float) ($rawItem['price'] ?? 0);
            $amount = $qty * $price;

            $unitId = $rawItem['unit_id'] ?? ($product?->unit_id ?? null);
            if (!$unitId) {
                $unitId = Unit::query()->value('id'); // unidad por defecto
            }

            $code = $rawItem['code'] ?? ($product?->code ?? (string) $productId);
            $description = $rawItem['description'] ?? ($product?->description ?? ($rawItem['name'] ?? 'Producto'));

            OrderMovementDetail::create([
                'order_movement_id' => $orderMovement->id,
                'product_id' => $productId,
                'code' => $code,
                'description' => $description,
                'product_snapshot' => $product ? $product->toArray() : null,
                'unit_id' => $unitId,
                'tax_rate_id' => $rawItem['tax_rate_id'] ?? null,
                'tax_rate_snapshot' => $rawItem['tax_rate_snapshot'] ?? null,
                'quantity' => $qty,
                'amount' => $amount,
                'branch_id' => $branchId,
                'comment' => $rawItem['note'] ?? null,
            ]);
        }

        DB::commit();

        return response()->json([
            'success' => true,
            'order_movement_id' => $orderMovement->id,
            'movement_id' => $movement->id,
        ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al procesar pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pedido',
            ], 500);
        }
    }

    public function processOrderPayment(Request $request)
    {
        $movementId = $request->input('movement_id');
        $tableId = $request->input('table_id');

        // Buscar el pedido por movement_id (envía la vista de cobro) o por table_id
        $orderMovement = null;
        if ($movementId) {
            $orderMovement = OrderMovement::where('movement_id', $movementId)->first();
        }
        if (!$orderMovement && $tableId) {
            $orderMovement = OrderMovement::where('table_id', $tableId)
                ->whereIn('status', ['PENDIENTE', 'P'])
                ->first();
        }

        if ($orderMovement) {
            $orderMovement->status = 'FINALIZADO';
            $orderMovement->finished_at = now();
            $orderMovement->save();

            // Al cobrar, liberar la mesa: situation = libre, opened_at = null
            $tableIdToFree = $tableId ?? $orderMovement->table_id;
            if ($tableIdToFree) {
                $table = Table::find($tableIdToFree);
                if ($table) {
                    $table->situation = 'libre';
                    $table->opened_at = null;
                    $table->save();
                }
            }
        }

        try {
            return response()->json([
                'success' => true,
                'order_movement_id' => $orderMovement?->id,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar el pago',
            ], 500);
        }
    }

    public function cancelOrder(Request $request)
    {
        $tableId = $request->input('table_id');
        if (!$tableId) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }
        $table = Table::find($tableId);
        if (!$table) {
            return response()->json([
                'success' => false,
                'message' => 'Mesa no encontrada',
            ], 404);
        }

        // Cancelar pedidos pendientes asociados a la mesa
        OrderMovement::where('table_id', $tableId)
            ->whereIn('status', ['PENDIENTE', 'P'])
            ->update([
                'status' => 'CANCELADO',
                'finished_at' => now(),
            ]);

        $table->situation = 'libre';
        $table->opened_at = null;
        $table->save();

        return response()->json([
            'success' => true,
            'message' => 'Mesa liberada correctamente',
        ]);
    }
}
