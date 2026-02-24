<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Branch;
use App\Models\Card;
use App\Models\CashMovements;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\DigitalWallet;
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
use Barryvdh\Snappy\Facades\SnappyPdf;
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
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $branchId = $request->session()->get('branch_id');
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $documentTypeId = $request->input('document_type_id');
        $paymentMethodId = $request->input('payment_method_id');
        $cashRegisterId = $request->input('cash_register_id');
        $documentTypes = DocumentType::query()
            ->orderBy('name')
            ->where('name', 'ILIKE', '%venta%')
            ->get(['id', 'name']);
        $paymentMethods = PaymentMethod::query()->where('status', true)->orderBy('order_num')->get(['id', 'description']);
        $cashRegisters = CashRegister::query()->orderBy('number')->get(['id', 'number']);
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
            ->when($dateFrom, function ($query) use ($dateFrom) {
                $query->whereHas('movement', function ($movementQuery) use ($dateFrom) {
                    $movementQuery->where('moved_at', '>=', $dateFrom . ' 00:00:00');
                });
            })
            ->when($dateTo, function ($query) use ($dateTo) {
                $query->whereHas('movement', function ($movementQuery) use ($dateTo) {
                    $movementQuery->where('moved_at', '<=', $dateTo . ' 23:59:59');
                });
            })

            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('movement', function ($movementQuery) use ($search) {
                        $movementQuery->where(function ($movementInner) use ($search) {
                            $movementInner->where('number', 'ILIKE', "%{$search}%")
                                ->orWhere('person_name', 'ILIKE', "%{$search}%")
                                ->orWhere('user_name', 'ILIKE', "%{$search}%");
                        });
                    })
                        ->orWhere('status', 'ILIKE', "%{$search}%");
                });
            })
            ->when($documentTypeId, function ($query) use ($documentTypeId) {
                $query->whereHas('movement', function ($movementQuery) use ($documentTypeId) {
                    $movementQuery->where('document_type_id', $documentTypeId);
                });
            })
            ->when($cashRegisterId, function ($query) use ($cashRegisterId) {
                $query->whereExists(function ($sub) use ($cashRegisterId) {
                    $sub->select(DB::raw(1))
                        ->from('movements as m')
                        ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                        ->whereColumn('m.parent_movement_id', 'order_movements.movement_id')
                        ->where('cm.cash_register_id', $cashRegisterId)
                        ->whereNull('cm.deleted_at');
                });
            })
            ->when($paymentMethodId, function ($query) use ($paymentMethodId) {
                $query->whereExists(function ($sub) use ($paymentMethodId) {
                    $sub->select(DB::raw(1))
                        ->from('movements as m')
                        ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                        ->join('cash_movement_details as cmd', 'cmd.cash_movement_id', '=', 'cm.id')
                        ->whereColumn('m.parent_movement_id', 'order_movements.movement_id')
                        ->where('cmd.payment_method_id', $paymentMethodId)
                        ->whereNull('cm.deleted_at')
                        ->whereNull('cmd.deleted_at');
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
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'documentTypes' => $documentTypes,
            'paymentMethods' => $paymentMethods,
            'cashRegisters' => $cashRegisters,
            'documentTypeId' => $request->input('document_type_id'),
            'paymentMethodId' => $request->input('payment_method_id'),
            'cashRegisterId' => $request->input('cash_register_id'),
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
                'movement_id' => $orderMovement?->movement_id ?? null,
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

        $response = response()->view('orders.index', [
            'areas' => $areasArray,
            'tables' => $tablesPayload,
            'user' => $request->user(),
            'turboCacheControl' => 'no-cache',
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }

    public function pdfReport(Request $request)
    {
        $dateFrom = $request->input('date_from') ?? now()->startOfMonth()->format('Y-m-d');
        $dateTo = $request->input('date_to') ?? now()->format('Y-m-d');
        $search = $request->input('search');
        $documentTypeId = $request->input('document_type_id');
        $paymentMethodId = $request->input('payment_method_id');
        $cashRegisterId = $request->input('cash_register_id');
        $branchId = $request->session()->get('branch_id');

        $orders = OrderMovement::query()
            ->with(['movement.documentType', 'movement.movementType', 'table', 'area'])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($dateFrom, fn($q) => $q->whereHas('movement', fn($m) => $m->where('moved_at', '>=', $dateFrom . ' 00:00:00')))
            ->when($dateTo, fn($q) => $q->whereHas('movement', fn($m) => $m->where('moved_at', '<=', $dateTo . ' 23:59:59')))
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->whereHas('movement', function ($movementQuery) use ($search) {
                        $movementQuery->where(function ($movementInner) use ($search) {
                            $movementInner->where('number', 'ILIKE', "%{$search}%")
                                ->orWhere('person_name', 'ILIKE', "%{$search}%")
                                ->orWhere('user_name', 'ILIKE', "%{$search}%");
                        });
                    })
                        ->orWhere('status', 'ILIKE', "%{$search}%");
                });
            })
            ->when($documentTypeId, function ($query) use ($documentTypeId) {
                $query->whereHas('movement', fn($m) => $m->where('document_type_id', $documentTypeId));
            })
            ->when($cashRegisterId, function ($query) use ($cashRegisterId) {
                $query->whereExists(function ($sub) use ($cashRegisterId) {
                    $sub->select(DB::raw(1))
                        ->from('movements as m')
                        ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                        ->whereColumn('m.parent_movement_id', 'order_movements.movement_id')
                        ->where('cm.cash_register_id', $cashRegisterId)
                        ->whereNull('cm.deleted_at');
                });
            })
            ->when($paymentMethodId, function ($query) use ($paymentMethodId) {
                $query->whereExists(function ($sub) use ($paymentMethodId) {
                    $sub->select(DB::raw(1))
                        ->from('movements as m')
                        ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                        ->join('cash_movement_details as cmd', 'cmd.cash_movement_id', '=', 'cm.id')
                        ->whereColumn('m.parent_movement_id', 'order_movements.movement_id')
                        ->where('cmd.payment_method_id', $paymentMethodId)
                        ->whereNull('cm.deleted_at')
                        ->whereNull('cmd.deleted_at');
                });
            })
            ->orderByDesc('id')
            ->get();

        $filters = [];
        $filters['Desde'] = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : null;
        $filters['Hasta'] = $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : null;
        if ($search !== null && $search !== '') {
            $filters['Búsqueda'] = $search;
        }
        if ($documentTypeId) {
            $dt = DocumentType::find($documentTypeId);
            $filters['Tipo de documento'] = $dt ? $dt->name : "ID {$documentTypeId}";
        }
        if ($paymentMethodId) {
            $pm = PaymentMethod::find($paymentMethodId);
            $filters['Método de pago'] = $pm ? ($pm->description ?? $pm->id) : "ID {$paymentMethodId}";
        }
        if ($cashRegisterId) {
            $cr = CashRegister::find($cashRegisterId);
            $filters['Caja'] = $cr ? ($cr->number ?? $cr->id) : "ID {$cashRegisterId}";
        }

        $pdf = SnappyPdf::loadView('orders.pdfs.pdf_report', [
            'orders' => $orders,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'filters' => $filters,
        ]);
        return $pdf->download('reporte_pedidos.pdf');
    }

    public function tablesData(Request $request)
    {
        $branchId = session('branch_id');
        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id')
            ->get(['id', 'name']);
        $tables = Table::query()
            ->when($areas->isNotEmpty(), fn($q) => $q->whereIn('area_id', $areas->pluck('id')))
            ->when($branchId && $areas->isEmpty(), fn($q) => $q->whereRaw('1 = 0'))
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
                'movement_id' => $orderMovement?->movement_id ?? null,
                'elapsed' => $elapsed,
            ];
        })->values();
        $areasArray = $areas->map(fn($area) => ['id' => (int) $area->id, 'name' => $area->name])->values();
        return response()
            ->json(['tables' => $tablesPayload, 'areas' => $areasArray])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
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
        $table = Table::with('area')->find($tableId);

        if (!$table) {
            abort(404, 'Mesa no encontrada');
        }

        $area = $table->area;
        if (!$area && $request->has('area_id')) {
            $area = Area::find($request->query('area_id'));
        }

        $products = Product::where('type', 'PRODUCT')
            ->with('category')
            ->get()
            ->map(function ($product) use ($table, $tableId, $branchId) {
                $imageUrl = ($product->image && !empty($product->image))
                    ? asset('storage/' . $product->image)
                    : null;
                return [
                    'id' => $product->id,
                    'name' => $product->description,
                    'img' => $imageUrl,
                    'category' => $product->category ? $product->category->description : 'Sin categoría',
                    'category_id' => $product->category_id,
                    'table_id' => $tableId,
                    'branch_id' => $branchId
                ];
            });

        $productBranches = ProductBranch::where('branch_id', $branchId)
            ->with(['product', 'taxRate'])
            ->get()
            ->map(function ($productBranch) {
                $taxRatePct = $productBranch->taxRate ? (float) $productBranch->taxRate->tax_rate : null;
                return [
                    'id' => $productBranch->id,
                    'product_id' => $productBranch->product_id,
                    'price' => (float) $productBranch->price,
                    'stock' => (float) ($productBranch->stock ?? 0),
                    'tax_rate' => $taxRatePct,
                ];
            });
        $categories = Category::orderBy('description')->get();
        $units = Unit::orderBy('description')->get();

        // Pedido pendiente activo para esta mesa (si existe, lo cargamos para no duplicar)
        $pendingOrder = OrderMovement::with('movement')
            ->where('table_id', $table->id)
            ->whereIn('status', ['PENDIENTE', 'P'])
            ->orderByDesc('id')
            ->first();
        $startFresh = !$pendingOrder;

        $response = response()->view('orders.create', [
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
            'startFresh' => $startFresh,
            'pendingOrderMovementId' => $pendingOrder?->id,
            'pendingMovementId' => $pendingOrder?->movement_id,
            'turboCacheControl' => 'no-cache',
        ]);
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        return $response;
    }

    public function charge(Request $request)
    {

        $saleOrOrderTypeIds = MovementType::query()
            ->where(function ($q) {
                $q->where('description', 'like', '%venta%')
                    ->orWhere('description', 'like', '%sale%')
                    ->orWhere('description', 'like', '%pedido%')
                    ->orWhere('description', 'like', '%orden%');
            })
            ->pluck('id')
            ->unique()
            ->values()
            ->all();
        $documentTypes = DocumentType::query()
            ->orderBy('name')
            ->whereIn('movement_type_id', !empty($saleOrOrderTypeIds) ? $saleOrOrderTypeIds : [2])
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

        $digitalWallets = DigitalWallet::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'order_num']);

        // Si se pasa un movement_id, cargar la orden pendiente (pedido o venta)
        $draftOrder = null;
        $pendingAmount = 0;
        $movement = null;
        $table = null;

        if ($request->has('movement_id')) {
            $movement = Movement::with(['salesMovement.details.product', 'cashMovement', 'orderMovement.details', 'orderMovement.table'])
                ->where('id', $request->movement_id)
                ->whereIn('status', ['P', 'A'])
                ->first();

            if ($movement && $movement->orderMovement) {
                $table = $movement->orderMovement->table;
            }
            if (!$table && $request->filled('table_id')) {
                $table = Table::find($request->table_id);
            }

            // Pedido: OrderMovement + detalles (subtotal/tax/total del pedido para que la vista de cobro no recalcule y coincida con la tarjeta de la mesa)
            if ($movement && $movement->orderMovement) {
                $om = $movement->orderMovement;
                $details = $om->details ?? collect();
                $omSubtotal = (float) $om->subtotal;
                $omTax = (float) ($om->tax ?? 0);
                $omTotal = (float) ($om->total ?? ($omSubtotal + $omTax));
                $draftOrder = [
                    'id' => $movement->id,
                    'number' => $movement->number,
                    'items' => $details->map(function ($detail) {
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
                    'subtotal' => round($omSubtotal, 2),
                    'tax' => round($omTax, 2),
                    'total' => round($omTotal, 2),
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

        $viewId = $request->input('view_id');
        $fromList = $request->input('from') === 'list';
        $backUrl = ($fromList || $viewId)
            ? route('orders.list', $viewId ? ['view_id' => $viewId] : [])
            : route('orders.index', $viewId ? ['view_id' => $viewId] : []);

        return view('orders.charge', [
            'documentTypes' => $documentTypes,
            'paymentMethods' => $paymentMethods,
            'paymentGateways' => $paymentGateways,
            'cards' => $cards,
            'digitalWallets' => $digitalWallets,
            'draftOrder' => $draftOrder,
            'pendingAmount' => $pendingAmount,
            'products' => $products,
            'backUrl' => $backUrl,
            'movement' => $movement,
            'table' => $table,
            'turboCacheControl' => 'no-cache',
        ]);
    }

    public function moveTable(Request $request)
    {
        $sourceTableId  = $request->input('table_id');       // mesa origen (ocupada)
        $destTableId    = $request->input('new_table_id');   // mesa destino (libre)

        if (!$sourceTableId || !$destTableId) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una mesa destino.',
            ], 422);
        }

        if ((int) $sourceTableId === (int) $destTableId) {
            return response()->json([
                'success' => false,
                'message' => 'La mesa destino debe ser diferente a la mesa origen.',
            ], 422);
        }

        $sourceTable = Table::find($sourceTableId);
        $destTable   = Table::find($destTableId);

        if (!$sourceTable) {
            return response()->json(['success' => false, 'message' => 'Mesa origen no encontrada.'], 404);
        }
        if (!$destTable) {
            return response()->json(['success' => false, 'message' => 'Mesa destino no encontrada.'], 404);
        }

        $destSituation = strtolower(trim((string) ($destTable->situation ?? '')));
        if ($destSituation === 'ocupada') {
            return response()->json([
                'success' => false,
                'message' => 'La mesa destino ya está ocupada.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Transferir el pedido pendiente de la mesa origen a la mesa destino
            OrderMovement::where('table_id', $sourceTableId)
                ->whereIn('status', ['PENDIENTE', 'P'])
                ->update([
                    'table_id' => $destTableId,
                    'area_id'  => $destTable->area_id,
                ]);

            // Liberar mesa origen
            Table::where('id', $sourceTableId)->update([
                'situation' => 'libre',
                'opened_at' => null,
            ]);

            // Ocupar mesa destino (conservar opened_at si ya tenía)
            $destOpenedAt = $destTable->opened_at ?? now();
            Table::where('id', $destTableId)->update([
                'situation' => 'ocupada',
                'opened_at' => $destOpenedAt,
            ]);

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Pedido movido a la mesa ' . ($destTable->name ?? $destTableId),
                'new_table_id'  => $destTableId,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
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
            // Prioridad 1: si el front envía order_movement_id, actualizar ese pedido (evita crear duplicado al auto-guardar)
            $existingOrderMovement = null;
            if ($request->filled('order_movement_id')) {
                $byId = OrderMovement::where('id', (int) $request->order_movement_id)
                    ->whereIn('status', ['PENDIENTE', 'P'])
                    ->first();
                if ($byId && ($tableId === null || (int) $byId->table_id === (int) $tableId)) {
                    $existingOrderMovement = $byId;
                }
            }
            // Prioridad 2: si no, buscar por mesa
            if (!$existingOrderMovement && $tableId) {
                $existingOrderMovement = OrderMovement::where('table_id', $tableId)
                    ->whereIn('status', ['PENDIENTE', 'P'])
                    ->orderByDesc('id')
                    ->first();
            }

            // Si ya hay pedido pendiente en la mesa pero items vacío: no crear duplicado (p. ej. al guardar sin productos al ir a cobrar)
            if ($existingOrderMovement && empty($items)) {
                DB::commit();
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Pedido pendiente en la mesa.',
                        'movement_id' => $existingOrderMovement->movement_id,
                        'order_movement_id' => $existingOrderMovement->id,
                    ]);
                }
                return redirect()->route('orders.index')->with('status', 'Pedido pendiente en la mesa.');
            }

            // Mesa concreta pero sin items y sin pedido previo: no crear pedido vacío
            if ($tableId && empty($items)) {
                DB::rollBack();
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Agregue productos al pedido.',
                    ], 422);
                }
                return redirect()->back()->with('error', 'Agregue productos al pedido.');
            }

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

                $documentType = DocumentType::where('movement_type_id', $movementType->id)->first() ?? DocumentType::first();

                if (!$movementType || !$documentType) {
                    throw new \Exception('No hay tipo de movimiento o tipo de documento configurado para pedidos.');
                }

                $number = $this->generateOrderMovementNumber(
                    (int) $branchId,
                    (int) $movementType->id,
                    (int) $documentType->id
                );

                $movement = Movement::create([
                    'number' => $number,
                    'moved_at' => now(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Sistema',
                    'person_id' => null,
                    'person_name' => 'Público General',
                    'responsible_id' => $user?->person->id,
                    'responsible_name' => $user?->person->first_name . ' ' . $user?->person->last_name ?? '-',
                    'comment' => 'Pedido desde punto de venta',
                    'status' => 'A',
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
                $existingOpenedAt = Table::where('id', $tableId)->value('opened_at');
                Table::where('id', $tableId)->update([
                    'situation' => 'ocupada',
                    'opened_at' => $existingOpenedAt ?? now(),
                ]);
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

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Pedido guardado correctamente',
                    'movement_id' => $movement->id,
                    'order_movement_id' => $orderMovement->id,
                ]);
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al procesar pedido', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }

            return redirect()->route('orders.index')->with('error', 'Error al procesar el pedido');
        }
    }

    public function processOrderPayment(Request $request)
    {
        $movementId = $request->input('movement_id');
        $tableId = $request->input('table_id');
        $branchId = (int) session('branch_id');
        $user = $request->user();
        $paymentMethods = collect($request->input('payment_methods', []));

        $orderMovement = null;
        if ($movementId) {
            $orderMovement = OrderMovement::where('movement_id', $movementId)->first();
        }
        if (!$orderMovement && $tableId) {
            $orderMovement = OrderMovement::where('table_id', $tableId)
                ->whereIn('status', ['PENDIENTE', 'P'])
                ->first();
        }

        $cashEntryMovement = null;
        if ($orderMovement) {
            DB::beginTransaction();
            try {
                $orderMovement->status = 'FINALIZADO';
                $orderMovement->finished_at = now();
                $orderMovement->save();

                $orderBaseMovement = Movement::find($orderMovement->movement_id);
                if ($orderBaseMovement) {
                    $updateData = [
                        'status' => 'A',
                        'moved_at' => now(),
                    ];
                    $requestDocumentTypeId = $request->input('document_type_id');
                    if ($requestDocumentTypeId && DocumentType::where('id', $requestDocumentTypeId)->exists()) {
                        $updateData['document_type_id'] = (int) $requestDocumentTypeId;
                    }
                    $orderBaseMovement->update($updateData);
                }

                $paymentConcept = $this->resolveOrderPaymentConcept();
                $cashMovementTypeId = $this->resolveCashMovementTypeId();
                $cashDocumentTypeId = $this->resolveCashIncomeDocumentTypeId($cashMovementTypeId);
                $cashRegisterId = $this->resolveActiveCashRegisterId($branchId);
                $cashRegister = CashRegister::find($cashRegisterId);
                $shift = Shift::where('branch_id', $branchId)->first() ?? Shift::first();
                if (!$shift) {
                    throw new \Exception('No hay turno disponible para registrar el cobro.');
                }

                // Movimiento de caja hijo del movimiento de pedido
                $cashEntryMovement = $this->resolveCashEntryMovementByParentMovement((int) $orderMovement->movement_id);
                if (!$cashEntryMovement) {
                    $cashEntryMovement = Movement::create([
                        'number' => $this->generateCashMovementNumber($branchId, (int) $cashRegisterId, (int) $paymentConcept->id),
                        'moved_at' => now(),
                        'user_id' => $user?->id,
                        'user_name' => $user?->name ?? 'Sistema',
                        'person_id' => $orderBaseMovement?->person_id,
                        'person_name' => $orderBaseMovement?->person_name ?? 'Publico General',
                        'responsible_id' => $user?->person->id,
                        'responsible_name' => $user?->person->first_name . ' ' . $user?->person->last_name ?? '-',
                        'comment' => 'Cobro de pedido ' . ($orderBaseMovement?->number ?? ''),
                        'status' => '1',
                        'movement_type_id' => $cashMovementTypeId,
                        'document_type_id' => $cashDocumentTypeId,
                        'branch_id' => $branchId,
                        'parent_movement_id' => $orderMovement->movement_id,
                    ]);
                } else {
                    $cashEntryMovement->update([
                        'moved_at' => now(),
                        'comment' => 'Cobro de pedido ' . ($orderBaseMovement?->number ?? ''),
                        'status' => '1',
                    ]);
                }

                $cashMovement = CashMovements::where('movement_id', $cashEntryMovement->id)->first();
                $total = (float) ($orderMovement->total ?? 0);
                if ($cashMovement) {
                    $cashMovement->update([
                        'payment_concept_id' => $paymentConcept->id,
                        'currency' => 'PEN',
                        'exchange_rate' => 1.000,
                        'total' => $total,
                        'cash_register_id' => $cashRegisterId,
                        'cash_register' => $cashRegister?->number ?? 'Caja Principal',
                        'shift_id' => $shift->id,
                        'shift_snapshot' => [
                            'name' => $shift->name,
                            'start_time' => $shift->start_time,
                            'end_time' => $shift->end_time,
                        ],
                        'branch_id' => $branchId,
                    ]);
                    DB::table('cash_movement_details')
                        ->where('cash_movement_id', $cashMovement->id)
                        ->delete();
                } else {
                    $cashMovement = CashMovements::create([
                        'payment_concept_id' => $paymentConcept->id,
                        'currency' => 'PEN',
                        'exchange_rate' => 1.000,
                        'total' => $total,
                        'cash_register_id' => $cashRegisterId,
                        'cash_register' => $cashRegister?->number ?? 'Caja Principal',
                        'shift_id' => $shift->id,
                        'shift_snapshot' => [
                            'name' => $shift->name,
                            'start_time' => $shift->start_time,
                            'end_time' => $shift->end_time,
                        ],
                        'movement_id' => $cashEntryMovement->id,
                        'branch_id' => $branchId,
                    ]);
                }

                if ($paymentMethods->isNotEmpty()) {
                    foreach ($paymentMethods as $paymentMethodData) {
                        $paymentMethod = PaymentMethod::findOrFail((int) ($paymentMethodData['payment_method_id'] ?? 0));
                        $paymentGateway = !empty($paymentMethodData['payment_gateway_id'])
                            ? PaymentGateways::find((int) $paymentMethodData['payment_gateway_id'])
                            : null;
                        $card = !empty($paymentMethodData['card_id'])
                            ? Card::find((int) $paymentMethodData['card_id'])
                            : null;
                        $digitalWallet = !empty($paymentMethodData['digital_wallet_id'])
                            ? DigitalWallet::find((int) $paymentMethodData['digital_wallet_id'])
                            : null;

                        DB::table('cash_movement_details')->insert([
                            'cash_movement_id' => $cashMovement->id,
                            'type' => 'PAGADO',
                            'paid_at' => now(),
                            'payment_method_id' => $paymentMethod->id,
                            'payment_method' => $paymentMethod->description ?? '',
                            'number' => $cashEntryMovement->number,
                            'card_id' => $card?->id,
                            'card' => $card?->description,
                            'bank_id' => null,
                            'bank' => null,
                            'digital_wallet_id' => $digitalWallet?->id,
                            'digital_wallet' => $digitalWallet?->description,
                            'payment_gateway_id' => $paymentGateway?->id,
                            'payment_gateway' => $paymentGateway?->description,
                            'amount' => (float) ($paymentMethodData['amount'] ?? 0),
                            'comment' => $request->input('notes') ?: 'Cobro de pedido ' . ($orderBaseMovement?->number ?? ''),
                            'status' => 'A',
                            'branch_id' => $branchId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Al cobrar, liberar la mesa (update directo para asegurar que se persiste)
                $tableIdToFree = $tableId ?? $orderMovement->table_id;
                if ($tableIdToFree) {
                    Table::where('id', $tableIdToFree)->update([
                        'situation' => 'libre',
                        'opened_at' => null,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Cobro de pedido procesado correctamente',
                    'movement_id' => $orderMovement?->movement_id,
                    'order_movement_id' => $orderMovement?->id,
                    'cash_movement_id' => $cashEntryMovement?->id,
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error al procesar cobro de pedido', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 500);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No se encontró un pedido pendiente para esta mesa. Guarda el pedido antes de cobrar.',
        ], 404);
    }

    private function generateOrderMovementNumber(int $branchId, int $movementTypeId, int $documentTypeId): string
    {
        if (DB::connection()->getDriverName() === 'pgsql') {
            // Serializar con advisory lock
            DB::statement('SELECT pg_advisory_xact_lock(?)', [7000001]);
            // Secuencia de 8 dígitos: 00000001, 00000002, ...
            DB::statement('CREATE SEQUENCE IF NOT EXISTS order_movement_number_seq START WITH 1');
            $next = DB::selectOne("SELECT nextval('order_movement_number_seq') AS v");
            $val = (int) $next->v;
            // Si la secuencia se pasó de 8 dígitos, reiniciar para mostrar 00000001, 00000002, ...
            if ($val > 99999999) {
                DB::statement("SELECT setval('order_movement_number_seq', 1)");
                $val = 1;
            }
            return str_pad((string) $val, 8, '0', STR_PAD_LEFT);
        }

        // Fallback para MySQL u otro: lock de fila + máximo
        $last = Movement::query()
            ->where('branch_id', $branchId)
            ->where('movement_type_id', $movementTypeId)
            ->where('document_type_id', $documentTypeId)
            ->orderByRaw("COALESCE(NULLIF(REGEXP_REPLACE(number, '[^0-9]', '', 'g'), '')::BIGINT, 0) DESC")
            ->lockForUpdate()
            ->first();

        $lastCorrelative = 0;
        if ($last) {
            $raw = trim((string) $last->number);
            if (preg_match('/^\d+$/', $raw) === 1) {
                $lastCorrelative = (int) $raw;
            } elseif (preg_match('/(\d+)-\d{4}$/', $raw, $matches) === 1) {
                $lastCorrelative = (int) $matches[1];
            }
        }

        return str_pad((string) ($lastCorrelative + 1), 8, '0', STR_PAD_LEFT);
    }

    private function resolveCashMovementTypeId(): int
    {
        $movementTypeId = MovementType::query()
            ->where(function ($query) {
                $query->where('description', 'ILIKE', '%caja%')
                    ->orWhere('description', 'ILIKE', '%cash%');
            })
            ->orderBy('id')
            ->value('id');

        if (!$movementTypeId) {
            $movementTypeId = MovementType::find(4)?->id;
        }
        if (!$movementTypeId) {
            $movementTypeId = MovementType::query()->orderBy('id')->value('id');
        }
        if (!$movementTypeId) {
            throw new \Exception('No se encontro tipo de movimiento para caja.');
        }

        return (int) $movementTypeId;
    }

    private function resolveCashIncomeDocumentTypeId(int $cashMovementTypeId): int
    {
        $documentTypeId = DocumentType::query()
            ->where('movement_type_id', $cashMovementTypeId)
            ->where('name', 'ILIKE', '%ingreso%')
            ->orderBy('id')
            ->value('id');

        if (!$documentTypeId) {
            $documentTypeId = DocumentType::query()
                ->where('movement_type_id', $cashMovementTypeId)
                ->orderBy('id')
                ->value('id');
        }

        if (!$documentTypeId) {
            throw new \Exception('No se encontro tipo de documento para movimiento de caja.');
        }

        return (int) $documentTypeId;
    }

    private function resolveActiveCashRegisterId(int $branchId): int
    {
        // cash_registers no tiene branch_id; branch_id viene de session para movimientos/shifts
        $cashRegisterId = CashRegister::query()
            ->where('status', 'A')
            ->orderBy('id')
            ->value('id');

        if (!$cashRegisterId) {
            $cashRegisterId = CashRegister::query()
                ->orderBy('id')
                ->value('id');
        }

        if (!$cashRegisterId) {
            throw new \Exception('No hay caja activa/disponible para registrar cobro.');
        }

        return (int) $cashRegisterId;
    }

    private function resolveOrderPaymentConcept(): PaymentConcept
    {
        $paymentConcept = PaymentConcept::query()
            ->where('type', 'I')
            ->where(function ($query) {
                $query->where('description', 'ILIKE', '%pago de cliente%')
                    ->orWhere('description', 'ILIKE', '%venta%')
                    ->orWhere('description', 'ILIKE', '%pedido%');
            })
            ->orderBy('id')
            ->first();

        if (!$paymentConcept) {
            $paymentConcept = PaymentConcept::query()
                ->where('type', 'I')
                ->orderBy('id')
                ->first();
        }

        if (!$paymentConcept) {
            throw new \Exception('No se encontro concepto de pago de ingreso para el cobro.');
        }

        return $paymentConcept;
    }

    private function generateCashMovementNumber(int $branchId, int $cashRegisterId, ?int $paymentConceptId = null): string
    {
        $lastRecord = Movement::query()
            ->select('movements.number')
            ->join('cash_movements', 'cash_movements.movement_id', '=', 'movements.id')
            ->where('movements.branch_id', $branchId)
            ->where('cash_movements.cash_register_id', $cashRegisterId)
            ->when($paymentConceptId !== null, function ($query) use ($paymentConceptId) {
                $query->where('cash_movements.payment_concept_id', $paymentConceptId);
            })
            ->lockForUpdate()
            ->orderByDesc('movements.number')
            ->first();

        $lastNumber = $lastRecord?->number;
        $nextSequence = $lastNumber ? ((int) $lastNumber + 1) : 1;

        return str_pad((string) $nextSequence, 8, '0', STR_PAD_LEFT);
    }

    private function resolveCashEntryMovementByParentMovement(int $parentMovementId): ?Movement
    {
        return Movement::query()
            ->where('parent_movement_id', $parentMovementId)
            ->whereHas('cashMovement')
            ->orderByDesc('id')
            ->first();
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

        Table::where('id', $table->id)->update([
            'situation' => 'libre',
            'opened_at' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mesa cerrada correctamente',
        ]);
    }

    public function openTable(Request $request)
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

        Table::where('id', $table->id)->update([
            'situation' => 'ocupada',
            'opened_at' => $table->opened_at ?? now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mesa abierta',
        ]);
    }
}
