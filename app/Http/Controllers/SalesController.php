<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Branch;
use App\Models\Card;
use App\Models\CashMovements;
use App\Models\CashRegister;
use App\Models\CashShiftRelation;
use App\Models\Category;
use App\Models\DigitalWallet;
use App\Models\DocumentType;
use App\Models\Movement;
use App\Models\MovementType;
use App\Models\Operation;
use App\Models\PaymentConcept;
use App\Models\PaymentGateways;
use App\Models\PaymentMethod;
use App\Models\Person;
use App\Models\PrinterBranch;
use App\Models\Product;
use App\Models\ProductBranch;
use App\Models\ProductType;
use App\Models\SalesMovement;
use App\Models\SalesMovementDetail;
use App\Models\Shift;
use App\Models\TaxRate;
use App\Models\User;
use App\Services\ThermalNetworkPrintService;
use App\Support\InsensitiveSearch;
use App\Support\LocalNetworkClient;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\Process\Process;

class SalesController extends Controller
{
    public function index(Request $request)
    {
        $branchId = session('branch_id');
        $profileId = session('profile_id') ?? $request->user()?->profile_id;
        $viewId = $request->input('view_id');
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $personId = $request->input('person_id');
        $documentTypeId = $request->input('document_type_id');
        $paymentMethodId = $request->input('payment_method_id');
        $cashRegisterId = $request->input('cash_register_id');
        $cashShiftRelationId = $request->input('cash_shift_relation_id');
        $saleType = $request->input('sale_type');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

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

        $documentTypes = DocumentType::query()
            ->where('movement_type_id', 2)
            ->orderBy('name')
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::query()
            ->where('status', true)
            ->restrictedToBranch($branchId ? (int) $branchId : null)
            ->orderBy('order_num')
            ->get(['id', 'description']);
        $cashRegisters = $branchId
            ? CashRegister::query()->where('branch_id', $branchId)->orderBy('number')->get(['id', 'number'])
            : CashRegister::query()->whereRaw('1 = 0')->get(['id', 'number']);

        $effectiveCashRegisterId = $cashRegisterId ?: session('cash_register_id');

        // Sesiones (turnos) por caja para filtrar el listado y "limpiar" al abrir una nueva
        $cashShiftSessions = collect();
        if ($branchId && $effectiveCashRegisterId) {
            $cashShiftSessions = CashShiftRelation::query()
                ->where('branch_id', $branchId)
                ->whereHas('cashMovementStart', function ($q) use ($effectiveCashRegisterId) {
                    $q->where('cash_register_id', $effectiveCashRegisterId);
                })
                ->with(['cashMovementStart.shift', 'cashMovementEnd'])
                ->orderByDesc('id')
                ->limit(100)
                ->get();
        }

        // Por defecto: filtrar por el turno actual (abierto) de la caja seleccionada/en sesión
        if (($cashShiftRelationId === null || $cashShiftRelationId === '') && $branchId && $effectiveCashRegisterId) {
            $activeShift = CashShiftRelation::query()
                ->where('branch_id', $branchId)
                ->where('status', '1')
                ->whereNull('ended_at')
                ->whereNull('cash_movement_end_id')
                ->whereHas('cashMovementStart', function ($q) use ($effectiveCashRegisterId) {
                    $q->where('cash_register_id', $effectiveCashRegisterId);
                })
                ->latest('id')
                ->first();
            if ($activeShift) {
                $cashShiftRelationId = (string) $activeShift->id;
            }
        }

        $query = Movement::query()
            ->with(['branch', 'person', 'movementType', 'documentType', 'salesMovement'])
            ->where('movement_type_id', 2)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(! $branchId, fn ($q) => $q->whereRaw('1 = 0'))
            ->whereHas('salesMovement');

        if ($documentTypeId !== null && $documentTypeId !== '' && is_numeric($documentTypeId)) {
            $query->where('document_type_id', (int) $documentTypeId);
        }

        if ($search !== null && $search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('person_name', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        // Filtros Adicionales
        if ($personId !== null && $personId !== '') {
            $query->where('person_id', $personId);
        }
        if ($dateFrom !== null && $dateFrom !== '') {
            $query->where('moved_at', '>=', $dateFrom.' 00:00:00');
        }
        if ($dateTo !== null && $dateTo !== '') {
            $query->where('moved_at', '<=', $dateTo.' 23:59:59');
        }
        if ($paymentMethodId) {
            $query->whereExists(function ($sub) use ($paymentMethodId) {
                $sub->select(DB::raw(1))
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->join('cash_movement_details as cmd', 'cmd.cash_movement_id', '=', 'cm.id')
                    ->whereColumn('m.parent_movement_id', 'movements.id')
                    ->where('cmd.payment_method_id', $paymentMethodId)
                    ->whereNull('cm.deleted_at')
                    ->whereNull('cmd.deleted_at');
            });
        }
        if ($effectiveCashRegisterId) {
            $query->whereExists(function ($sub) use ($effectiveCashRegisterId) {
                $sub->select(DB::raw(1))
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'movements.id')
                    ->where('cm.cash_register_id', $effectiveCashRegisterId)
                    ->whereNull('cm.deleted_at');
            });
        }

        // Filtro por turno (CashShiftRelation): ventana temporal por started_at/ended_at
        if ($cashShiftRelationId !== null && $cashShiftRelationId !== '' && $branchId && $effectiveCashRegisterId) {
            $csrApplied = CashShiftRelation::query()
                ->with(['cashMovementStart', 'cashMovementEnd'])
                ->where('branch_id', $branchId)
                ->where('id', (int) $cashShiftRelationId)
                ->whereHas('cashMovementStart', function ($q) use ($effectiveCashRegisterId) {
                    $q->where('cash_register_id', $effectiveCashRegisterId);
                })
                ->first();

            if ($csrApplied && $csrApplied->started_at) {
                $from = \Illuminate\Support\Carbon::parse($csrApplied->started_at)->startOfSecond();
                $to = $csrApplied->ended_at
                    ? \Illuminate\Support\Carbon::parse($csrApplied->ended_at)->endOfSecond()
                    : now()->endOfSecond();
                $query->whereBetween('moved_at', [$from, $to]);
            } else {
                $query->whereRaw('1 = 0');
            }
        }
        if ($saleType !== null && $saleType !== '') {
            $query->whereHas('salesMovement', function ($sub) use ($saleType) {
                $sub->where('detail_type', $saleType);
            });
        }

        $sales = $query->orderBy('moved_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'sales' => $sales,
                'pagination' => [
                    'current_page' => $sales->currentPage(),
                    'last_page' => $sales->lastPage(),
                    'per_page' => $sales->perPage(),
                    'total' => $sales->total(),
                ],
            ]);
        }

        $viewData = [
            'sales' => $sales,
            'search' => $search,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
            'operaciones' => $operaciones,
            'viewId' => $viewId,
            'branchId' => $branchId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'documentTypeId' => $documentTypeId,
            'documentTypes' => $documentTypes,
            'paymentMethodId' => $paymentMethodId,
            'paymentMethods' => $paymentMethods,
            'cashRegisterId' => $effectiveCashRegisterId,
            'cashRegisters' => $cashRegisters,
            'personId' => $personId,
            'saleType' => $saleType,
            'cashShiftRelationId' => $cashShiftRelationId,
            'cashShiftSessions' => $cashShiftSessions,
        ];

        return view('sales.index', $viewData);
    }

    /**
     * Página de reportes de ventas (mismo listado con filtros y opción de exportar PDF).
     */
    public function reportSales(Request $request)
    {
        return $this->index($request);
    }

    // Obtener caja desde sesión
    public function getSessionCashRegister(Request $request)
    {
        $cashRegisterId = session('cash_register_id');

        return response()->json([
            'success' => true,
            'cash_register_id' => $cashRegisterId,
        ]);
    }

    public function create(Request $request)
    {
        $branchId = (int) (session('branch_id') ?? 0) ?: null;

        $defaultImage = asset('images/logo/Xinergia-icon.png');

        $userId = session('user_id');
        $user = User::find($userId);

        $personId = session('person_id');
        $person = Person::find($personId);

        // Categorías asignadas a esta sucursal (category_branch)
        $categories = Category::query()
            ->when($branchId, fn ($q) => $q->forBranchMenu($branchId, 'VENTAS_PEDIDOS'), function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->orderBy('description')
            ->get()
            ->map(function ($cat) use ($defaultImage) {
                $img = $defaultImage;
                if ($cat->image && Storage::disk('public')->exists($cat->image)) {
                    $img = asset('storage/'.$cat->image);
                }

                return [
                    'id' => $cat->id,
                    'name' => $cat->description,
                    'img' => $img,
                ];
            })->values();

        // Productos vendibles y ambos (sin ingredientes/insumos SUPPLY): product_branch y categoría en la sucursal
        $products = Product::query()
            ->where('type', 'PRODUCT')
            ->where(function ($q) {
                $q->whereNull('product_type_id')
                    ->orWhereHas('productType', fn ($q2) => $q2->whereIn('behavior', [
                        ProductType::BEHAVIOR_SELLABLE,
                        ProductType::BEHAVIOR_BOTH,
                    ]));
            })
            ->when($branchId, function ($query) use ($branchId) {
                $query->whereHas('productBranches', fn ($q) => $q->where('branch_id', $branchId))
                    ->whereExists(function ($sub) use ($branchId) {
                        $sub->select(DB::raw(1))
                            ->from('category_branch')
                            ->whereColumn('category_branch.category_id', 'products.category_id')
                            ->where('category_branch.branch_id', $branchId)
                            ->whereIn('category_branch.menu_type', ['VENTAS_PEDIDOS', 'GENERAL'])
                            ->whereNull('category_branch.deleted_at');
                    });
            }, function ($query) {
                $query->whereRaw('1 = 0');
            })
            ->with('category')
            ->orderBy('description')
            ->get()
            ->map(function (Product $product) use ($defaultImage) {
                $imageUrl = $defaultImage;
                if (! empty($product->image)) {
                    $path = ltrim($product->image, '/');
                    if (Storage::disk('public')->exists($path)) {
                        $imageUrl = asset('storage/'.$path);
                    }
                }

                return [
                    'id' => (int) $product->id,
                    'name' => $product->description,
                    'img' => $imageUrl,
                    'note' => $product->note ?? null,
                    'category' => $product->category ? $product->category->description : 'Sin categoria',
                    'category_id' => $product->category_id,
                ];
            })
            ->values();

        $productBranches = $branchId
            ? ProductBranch::query()
                ->where('branch_id', $branchId)
                ->with(['product.productType', 'taxRate'])
                ->get()
                ->filter(function ($productBranch) {
                    if ($productBranch->product === null) {
                        return false;
                    }
                    $pt = $productBranch->product->productType;

                    return $pt === null || $pt->isSellable();
                })
                ->map(function ($productBranch) {
                    $taxRate = $productBranch->taxRate;
                    $taxRatePct = $taxRate ? (float) $taxRate->tax_rate : null;

                    return [
                        'id' => (int) $productBranch->id,
                        'product_id' => (int) $productBranch->product_id,
                        'price' => (float) $productBranch->price,
                        'tax_rate' => $taxRatePct,
                        'stock' => (float) ($productBranch->stock ?? 0),
                        'favorite' => ($productBranch->favorite ?? 'N'),
                    ];
                })
                ->values()
            : collect();

        $saleType = 'IN_SITU';

        $people = $this->branchClientsForBranch($branchId);

        $documentTypes = DocumentType::query()
            ->orderBy('name')
            ->where('movement_type_id', 2)
            ->get(['id', 'name']);

        $paymentMethods = PaymentMethod::query()
            ->where('status', true)
            ->restrictedToBranch($branchId)
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

        $banks = Bank::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'order_num']);

        $cashRegisters = CashRegister::query()
            ->orderByRaw("CASE WHEN status = 'A' THEN 0 ELSE 1 END")
            ->orderBy('number')
            ->get(['id', 'number', 'status']);

        $branch = $branchId ? Branch::find($branchId) : null;
        $allowZeroStockSales = (bool) ($branch?->allow_zero_stock_sales ?? true);

        $thermalPrinters = $branchId
            ? PrinterBranch::query()
                ->where('branch_id', $branchId)
                ->where('status', 'E')
                ->whereNotNull('ip')
                ->where('ip', '!=', '')
                ->orderBy('id')
                ->get(['id', 'name', 'ip', 'width'])
            : collect();

        return view('sales.create', [
            'products' => $products,
            'productBranches' => $productBranches,
            'productsBranches' => $productBranches,
            'user' => $user,
            'person' => $person,
            'categories' => $categories,
            'people' => $people,
            'documentTypes' => $documentTypes,
            'paymentMethods' => $paymentMethods,
            'paymentGateways' => $paymentGateways,
            'cards' => $cards,
            'digitalWallets' => $digitalWallets,
            'banks' => $banks,
            'cashRegisters' => $cashRegisters,
            'saleType' => $saleType,
            'branch' => $branch,
            'allowZeroStockSales' => $allowZeroStockSales,
            'clientOnLocalNetwork' => LocalNetworkClient::isOnLocalNetwork($request),
            'thermalPrinters' => $thermalPrinters,
        ]);
    }

    // POS: vista de cobro
    public function charge(Request $request)
    {
        $personId = session('person_id');
        $person = Person::find($personId);

        $documentTypes = DocumentType::query()
            ->orderBy('name')
            ->where('movement_type_id', 2)
            ->get(['id', 'name']);

        $branchId = session('branch_id');

        $paymentMethods = PaymentMethod::query()
            ->where('status', true)
            ->restrictedToBranch($branchId ? (int) $branchId : null)
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

        $banks = Bank::query()
            ->where('status', true)
            ->orderBy('order_num')
            ->get(['id', 'description', 'order_num']);

        $cashRegisters = CashRegister::query()
            ->orderByRaw("CASE WHEN status = 'A' THEN 0 ELSE 1 END")
            ->orderBy('number')
            ->get(['id', 'number', 'status']);
        $people = $this->branchClientsForBranch($branchId);

        $defaultClientId = Person::query()
            ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
            ->whereRaw('UPPER(first_name) = ?', ['CLIENTES'])
            ->whereRaw('UPPER(last_name) = ?', ['VARIOS'])
            ->value('id');

        if (! $defaultClientId) {
            $defaultClientId = 4;
        }

        // Si se pasa un movement_id, cargar la venta pendiente o con pago parcial
        $draftSale = null;
        $pendingAmount = 0;
        if ($request->has('movement_id')) {
            $movement = Movement::with(['salesMovement.details.product', 'cashMovement'])
                ->where('id', $request->movement_id)
                ->whereIn('status', ['P', 'A']) // Cargar si está pendiente o activo (puede tener deuda)
                ->first();

            if ($movement && $movement->salesMovement) {
                // Calcular el monto pendiente si hay una deuda
                $relatedCashMovement = $movement->cashMovement ?: $this->resolveCashMovementBySaleMovement($movement->id);
                if ($relatedCashMovement) {
                    $debt = DB::table('cash_movement_details')
                        ->where('cash_movement_id', $relatedCashMovement->id)
                        ->where('type', 'DEUDA')
                        ->where('status', 'A')
                        ->sum('amount');
                    $pendingAmount = $debt ?? 0;
                }

                $defaultTaxRate = TaxRate::where('status', true)->orderBy('order_num')->first();
                $defaultTaxPct = $defaultTaxRate ? (float) $defaultTaxRate->tax_rate : 18;
                $draftSale = [
                    'id' => $movement->id,
                    'number' => $movement->number,
                    'clientId' => $movement->person_id,
                    'items' => $movement->salesMovement->details->map(function ($detail) use ($defaultTaxPct) {
                        $taxRatePct = $defaultTaxPct;
                        if ($detail->tax_rate_snapshot && isset($detail->tax_rate_snapshot['tax_rate'])) {
                            $taxRatePct = (float) $detail->tax_rate_snapshot['tax_rate'];
                        }
                        $amountWithTax = (float) $detail->amount;
                        $quantity = (float) $detail->quantity ?: 1;
                        $priceWithTax = $quantity > 0 ? $amountWithTax / $quantity : 0;

                        return [
                            'pId' => $detail->product_id,
                            'name' => $detail->product->description ?? 'Producto #'.$detail->product_id,
                            'qty' => $quantity,
                            'price' => $priceWithTax,
                            'tax_rate' => $taxRatePct,
                            'note' => $detail->comment ?? '',
                            'product_note' => $detail->product->note ?? null,
                        ];
                    })->toArray(),
                    'clientName' => $movement->person_name ?? 'Público General',
                    'notes' => $movement->comment ?? '',
                    'pendingAmount' => $pendingAmount,
                    'product_notes' => $movement->salesMovement->details->pluck('product.note')->toArray(),
                ];
            }
        }

        // Obtener todos los productos para poder mostrar sus nombres cuando se carga desde localStorage
        $products = Product::pluck('description', 'id')->toArray();

        $productBranches = ProductBranch::query()
            ->where('branch_id', $branchId ?? 0)
            ->with(['product.productType', 'taxRate'])
            ->get()
            ->filter(function ($pb) {
                $pt = $pb->product?->productType;

                return $pt === null || $pt->isSellable();
            })
            ->map(fn ($pb) => [
                'product_id' => (int) $pb->product_id,
                'price' => (float) $pb->price,
                'tax_rate' => $pb->taxRate ? (float) $pb->taxRate->tax_rate : null,
            ])
            ->values();

        $branch = $branchId ? Branch::find($branchId) : null;
        $company = $branch ? \App\Models\Company::find($branch->company_id) : null;

        return view('sales.charge', [
            'documentTypes' => $documentTypes,
            'paymentMethods' => $paymentMethods,
            'paymentGateways' => $paymentGateways,
            'cards' => $cards,
            'digitalWallets' => $digitalWallets,
            'banks' => $banks,
            'cashRegisters' => $cashRegisters,
            'people' => $people,
            'defaultClientId' => $defaultClientId,
            'draftSale' => $draftSale,
            'pendingAmount' => $pendingAmount,
            'products' => $products,
            'productBranches' => $productBranches,
            'person' => $person,
            'branch' => $branch,
            'company' => $company,
        ]);
    }

    // POS: procesar venta
    public function processSale(Request $request)
    {
        try {
            $branchId = session('branch_id');
            $restrictedPmIds = PaymentMethod::paymentMethodIdsForBranchOrNull($branchId ? (int) $branchId : null);
            $paymentMethodIdRules = [
                'required',
                'integer',
                Rule::exists('payment_methods', 'id')->where('status', true),
            ];
            if ($restrictedPmIds !== null) {
                $paymentMethodIdRules[] = Rule::in($restrictedPmIds);
            }

            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.pId' => 'required|integer|exists:products,id',
                'items.*.qty' => 'required|numeric|min:0.000001',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.courtesyQty' => 'nullable|numeric|min:0',
                'items.*.note' => 'nullable|string|max:65535',
                // Compatibilidad: algunos flujos pueden enviar `comment` en lugar de `note`
                'items.*.comment' => 'nullable|string|max:65535',
                'document_type_id' => 'required|integer|exists:document_types,id',
                'cash_register_id' => 'nullable|integer|exists:cash_registers,id',
                'person_id' => 'nullable|integer|exists:people,id',
                'payment_methods' => 'required|array|min:1',
                'payment_methods.*.payment_method_id' => $paymentMethodIdRules,
                'payment_methods.*.amount' => 'required|numeric|min:0.01',
                'payment_methods.*.payment_gateway_id' => 'nullable|integer|exists:payment_gateways,id',
                'payment_methods.*.card_id' => 'nullable|integer|exists:cards,id',
                'payment_methods.*.digital_wallet_id' => 'nullable|integer|exists:digital_wallets,id',
                'notes' => 'nullable|string',
                'movement_id' => 'nullable|integer|exists:movements,id', // ID del borrador a completar
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        // ── Validar caja ANTES de iniciar la transacción ──────────────────────
        $branchIdForCheck = (int) session('branch_id');
        $cashRegisterId = $request->input('cash_register_id') ?: session('cash_register_id');

        if (! $cashRegisterId) {
            return response()->json(['success' => false, 'message' => 'Selecciona una caja antes de registrar la venta.'], 422);
        }

        $cashRegisterCheck = CashRegister::where('id', $cashRegisterId)->where('status', true)->first();
        if (! $cashRegisterCheck) {
            return response()->json(['success' => false, 'message' => 'La caja seleccionada no está habilitada.'], 422);
        }

        $activeShiftCheck = CashShiftRelation::query()
            ->where('branch_id', $branchIdForCheck)
            ->where('status', '1')
            ->whereNull('ended_at')
            ->whereNull('cash_movement_end_id')
            ->whereHas('cashMovementStart', function ($q) use ($cashRegisterId) {
                $q->where('cash_register_id', $cashRegisterId);
            })
            ->latest('id')
            ->first();

        if (! $activeShiftCheck) {
            return response()->json([
                'success' => false,
                'message' => 'La caja "'.$cashRegisterCheck->number.'" no tiene un turno abierto. Realice una Apertura de Caja primero.',
            ], 422);
        }
        // ──────────────────────────────────────────────────────────────────────

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = session('branch_id');
            $branch = Branch::findOrFail($branchId);

            // Obtener turno de la sucursal
            $shift = Shift::where('branch_id', $branchId)->first();

            // Si no hay turno de la sucursal, usar el primero disponible
            if (! $shift) {
                $shift = Shift::first();
            }

            if (! $shift) {
                throw new \Exception('No hay turno disponible. Por favor, crea un turno primero.');
            }

            // Obtener tipos de movimiento y documento para ventas
            $movementType = MovementType::where('description', 'like', '%venta%')
                ->orWhere('description', 'like', '%sale%')
                ->orWhere('description', 'like', '%Venta%')
                ->first();

            if (! $movementType) {
                $movementType = MovementType::first();
            }

            if (! $movementType) {
                throw new \Exception('No se encontró un tipo de movimiento válido. Por favor, crea un tipo de movimiento primero.');
            }

            $documentType = DocumentType::findOrFail($request->document_type_id);

            $selectedPerson = null;
            if (! empty($validated['person_id'])) {
                $selectedPerson = Person::query()
                    ->where('id', $validated['person_id'])
                    ->where('branch_id', $branchId)
                    ->firstOrFail();
            }

            // Obtener concepto de pago para ventas (Pago de cliente - ID 5)
            $paymentConcept = PaymentConcept::find(3); // Pago de cliente

            // Si no existe el ID 5, buscar por descripción
            if (! $paymentConcept) {
                $paymentConcept = PaymentConcept::where('description', 'like', '%pago de cliente%')
                    ->orWhere('description', 'like', '%Pago de cliente%')
                    ->first();
            }

            // Si aún no se encuentra, buscar cualquier concepto de ingreso relacionado con venta
            if (! $paymentConcept) {
                $paymentConcept = PaymentConcept::where('description', 'like', '%venta%')
                    ->orWhere('description', 'like', '%cliente%')
                    ->where('type', 'I')
                    ->first();
            }

            // Si aún no se encuentra, buscar cualquier concepto de ingreso
            if (! $paymentConcept) {
                $paymentConcept = PaymentConcept::where('type', 'I')->first();
            }

            if (! $paymentConcept) {
                throw new \Exception('No se encontró un concepto de pago válido. Por favor, crea un concepto de pago primero.');
            }

            // Los precios del front ya incluyen IGV. Calcular subtotal e IGV por producto según su tasa.
            $calculated = $this->calculateSubtotalAndTaxFromItems($request->items, $branchId);
            $subtotal = $calculated['subtotal'];
            $tax = $calculated['tax'];
            $total = $calculated['total'];

            // Caja obtenida desde la sesión
            $cashRegister = CashRegister::findOrFail($cashRegisterId);

            // Validar que la suma de los métodos de pago sea igual al total
            $totalPaymentMethods = array_sum(array_column($request->payment_methods, 'amount'));
            if (abs($totalPaymentMethods - $total) > 0.01) {
                throw new \Exception("La suma de los métodos de pago ({$totalPaymentMethods}) debe ser igual al total ({$total})");
            }

            // Recalcular con la misma regla (precio final con IGV incluido)
            $calculated = $this->calculateSubtotalAndTaxFromItems($request->items, $branchId);
            $subtotal = $calculated['subtotal'];
            $tax = $calculated['tax'];
            $total = $calculated['total'];

            // Calcular el total recibido de todos los métodos de pago
            $amountReceived = $totalPaymentMethods;

            // Verificar si es un borrador a completar
            $isDraft = $request->has('movement_id') && $request->movement_id;
            $movement = null;
            $number = null;

            if ($isDraft) {
                // Cargar el movimiento existente (borrador)
                $movement = Movement::where('id', $request->movement_id)
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $movement) {
                    throw new \Exception('No se encontró el movimiento de venta');
                }

                $number = $movement->number;

                // Actualizar el movimiento - siempre se completa el pago completo
                $movement->update([
                    'comment' => $request->notes ?? 'Venta completada desde punto de venta',
                    'status' => 'A', // Siempre Activo (pago completo)
                    'document_type_id' => $documentType->id,
                    'person_id' => $selectedPerson?->id,
                    'person_name' => $selectedPerson
                        ? trim(($selectedPerson->first_name ?? '').' '.($selectedPerson->last_name ?? ''))
                        : 'Publico General',
                ]);

                // Eliminar los detalles anteriores para recrearlos
                if ($movement->salesMovement) {
                    SalesMovementDetail::where('sales_movement_id', $movement->salesMovement->id)->delete();
                }
            } else {
                // Crear nuevo Movement
                $number = $this->generateSaleNumber(
                    (int) $documentType->id,
                    (int) $cashRegister->id,
                    true
                );

                $movement = Movement::create([
                    'number' => $number,
                    'moved_at' => now(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Sistema',
                    'person_id' => $selectedPerson?->id,
                    'person_name' => $selectedPerson
                        ? trim(($selectedPerson->first_name ?? '').' '.($selectedPerson->last_name ?? ''))
                        : 'Publico General',
                    'responsible_id' => $user?->id,
                    'responsible_name' => $user?->person ? trim(($user->person->first_name ?? '').' '.($user->person->last_name ?? '')) : ($user?->name ?? 'Sistema'),
                    'comment' => $request->notes ?? '',
                    'status' => 'A', // Siempre Activo (pago completo)
                    'movement_type_id' => $movementType->id,
                    'document_type_id' => $documentType->id,
                    'branch_id' => $branchId,
                    'parent_movement_id' => null,
                    'shift_id' => $shift->id,
                    'shift_snapshot' => [
                        'name' => $shift->name,
                        'start_time' => $shift->start_time,
                        'end_time' => $shift->end_time,
                    ],
                ]);
            }

            // Crear o actualizar SalesMovement
            if ($isDraft && $movement->salesMovement) {
                // Actualizar el SalesMovement existente
                $salesMovement = $movement->salesMovement;
                $salesMovement->update([
                    'payment_type' => 'CASH', // Siempre CASH (pago completo)
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);
            } else {
                // Crear nuevo SalesMovement
                $salesMovement = SalesMovement::create([
                    'branch_snapshot' => [
                        'id' => $branch->id,
                        'legal_name' => $branch->legal_name,
                    ],
                    'series' => '001',
                    'year' => Carbon::now()->year,
                    'detail_type' => 'DETALLADO',
                    'consumption' => 'N',
                    'payment_type' => 'CONTADO', // Siempre CASH (pago completo)
                    'status' => 'N',
                    'sale_type' => 'MINORISTA',
                    'currency' => 'PEN',
                    'exchange_rate' => 3.5,
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                    'movement_id' => $movement->id,
                    'branch_id' => $branchId,
                ]);
            }

            // Crear SalesMovementDetails y actualizar stock (nota por producto en comment)
            foreach ($validated['items'] as $item) {
                $product = Product::with('baseUnit')->findOrFail($item['pId']);

                // Bloquear el registro para evitar condiciones de carrera
                $productBranch = ProductBranch::with('taxRate')
                    ->where('product_id', $item['pId'])
                    ->where('branch_id', $branchId)
                    ->lockForUpdate()
                    ->first();

                if (! $productBranch) {
                    throw new \Exception("Producto {$product->description} no disponible en esta sucursal");
                }

                // Validar stock disponible
                $quantityToSell = (int) $item['qty'];
                $currentStock = (int) ($productBranch->stock ?? 0);

                // if ($currentStock < $quantityToSell) {
                //     throw new \Exception(
                //         "Stock insuficiente para el producto {$product->description}. " .
                //         "Stock disponible: {$currentStock}, Cantidad solicitada: {$quantityToSell}"
                //     );
                // }

                $unit = $product->baseUnit;
                if (! $unit) {
                    throw new \Exception("El producto {$product->description} no tiene una unidad base configurada");
                }

                $taxRate = $productBranch->taxRate;
                $taxRateValue = $taxRate ? ($taxRate->tax_rate / 100) : $this->getDefaultTaxRateValue();

                $qty = (float) ($item['qty'] ?? 0);
                $courtesyQty = (float) ($item['courtesyQty'] ?? $item['courtesy_quantity'] ?? 0);
                $courtesyQty = max(0, min($courtesyQty, $qty));
                $paidQty = $qty - $courtesyQty;
                // Precio de venta incluye impuesto; el monto es solo por unidades pagadas (sin cortesía).
                $itemTotal = $paidQty * (float) ($item['price'] ?? 0);
                $itemSubtotal = $taxRateValue > 0 ? ($itemTotal / (1 + $taxRateValue)) : $itemTotal;
                $itemTax = $itemTotal - $itemSubtotal;

                // Nota por producto (compatibilidad note/comment) y normalización
                $detailNoteRaw = data_get($item, 'note', data_get($item, 'comment'));
                $detailNote = $detailNoteRaw === null ? null : trim((string) $detailNoteRaw);
                $detailNote = ($detailNote !== '') ? $detailNote : null;
                SalesMovementDetail::create([
                    'detail_type' => 'DETAILED',
                    'sales_movement_id' => $salesMovement->id,
                    'code' => $product->code,
                    'description' => $product->description,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'description' => $product->description,
                    ],
                    'unit_id' => $unit->id,
                    'tax_rate_id' => $taxRate?->id,
                    'tax_rate_snapshot' => $taxRate ? [
                        'id' => $taxRate->id,
                        'description' => $taxRate->description,
                        'tax_rate' => $taxRate->tax_rate,
                    ] : null,
                    'quantity' => $item['qty'],
                    'courtesy_quantity' => $courtesyQty,
                    'amount' => $itemTotal,
                    'discount_percentage' => 0.000000,
                    'original_amount' => $itemSubtotal,
                    'comment' => $detailNote,
                    'parent_detail_id' => null,
                    'complements' => [],
                    'status' => 'A',
                    'branch_id' => $branchId,
                ]);

                // Restar el stock del producto en la sucursal
                $newStock = $currentStock - $quantityToSell;
                $productBranch->update([
                    'stock' => max(0, $newStock), // Asegurar que no sea negativo
                ]);
            }

            // Crear/actualizar movimiento de caja separado del movimiento de venta
            $cashEntryMovement = $this->resolveCashEntryMovementBySaleMovement($movement->id);

            if (! $cashEntryMovement) {
                $cashEntryMovement = Movement::create([
                    'number' => $this->generateCashMovementNumber(
                        (int) $branchId,
                        (int) $cashRegister->id,
                        (int) $paymentConcept->id
                    ),
                    'moved_at' => now(),
                    'user_id' => $user?->id,
                    'user_name' => $user?->name ?? 'Sistema',
                    'person_id' => $selectedPerson?->id,
                    'person_name' => $selectedPerson
                        ? trim(($selectedPerson->first_name ?? '').' '.($selectedPerson->last_name ?? ''))
                        : 'Publico General',
                    'responsible_id' => $user?->id,
                    'responsible_name' => $user?->person ? trim(($user->person->first_name ?? '').' '.($user->person->last_name ?? '')) : ($user?->name ?? 'Sistema'),
                    'comment' => 'Cobro de venta '.$movement->number,
                    'status' => '1',
                    'movement_type_id' => 4,
                    'document_type_id' => 9,
                    'branch_id' => $branchId,
                    'parent_movement_id' => $movement->id,
                ]);
            } else {
                $cashEntryMovement->update([
                    'moved_at' => now(),
                    'person_id' => $selectedPerson?->id,
                    'person_name' => $selectedPerson
                        ? trim(($selectedPerson->first_name ?? '').' '.($selectedPerson->last_name ?? ''))
                        : 'Publico General',
                    'comment' => 'Cobro de venta '.$movement->number,
                    'status' => '1',
                    'movement_type_id' => 4,
                    'document_type_id' => 9,
                ]);
            }

            // Crear o actualizar CashMovement (entrada de dinero)
            $cashMovement = CashMovements::where('movement_id', $cashEntryMovement->id)->first();

            if ($cashMovement) {
                $cashMovement->update([
                    'payment_concept_id' => $paymentConcept->id,
                    'currency' => 'PEN',
                    'exchange_rate' => 1.000,
                    'total' => $total,
                    'cash_register_id' => $cashRegister->id,
                    'cash_register' => $cashRegister->number ?? 'Caja Principal',
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
                    'cash_register_id' => $cashRegister->id,
                    'cash_register' => $cashRegister->number ?? 'Caja Principal',
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

            // Crear CashMovementDetail para cada método de pago
            foreach ($request->payment_methods as $paymentMethodData) {
                $paymentMethod = PaymentMethod::findOrFail($paymentMethodData['payment_method_id']);
                $paymentGateway = null;
                $card = null;
                $digitalWallet = null;

                if (! empty($paymentMethodData['payment_gateway_id'])) {
                    $paymentGateway = PaymentGateways::find($paymentMethodData['payment_gateway_id']);
                }
                if (! empty($paymentMethodData['card_id'])) {
                    $card = Card::find($paymentMethodData['card_id']);
                }
                if (! empty($paymentMethodData['digital_wallet_id'])) {
                    $digitalWallet = DigitalWallet::find($paymentMethodData['digital_wallet_id']);
                }
                $bank = ! empty($paymentMethodData['bank_id'])
                    ? Bank::find($paymentMethodData['bank_id'])
                    : null;

                DB::table('cash_movement_details')->insert([
                    'cash_movement_id' => $cashMovement->id,
                    'type' => 'PAGADO',
                    'paid_at' => now(),
                    'payment_method_id' => $paymentMethod->id,
                    'payment_method' => $paymentMethod->description ?? '',
                    'number' => $cashEntryMovement->number,
                    'card_id' => $card?->id,
                    'card' => $card?->description ?? '',
                    'bank_id' => $bank?->id,
                    'bank' => $bank?->description ?? '',
                    'digital_wallet_id' => $digitalWallet?->id,
                    'digital_wallet' => $digitalWallet?->description ?? '',
                    'payment_gateway_id' => $paymentGateway?->id,
                    'payment_gateway' => $paymentGateway?->description ?? '',
                    'amount' => $paymentMethodData['amount'],
                    'comment' => $request->notes ?? 'Venta desde punto de venta - '.$documentType->name,
                    'status' => 'A',
                    'branch_id' => $branchId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();

            $thermalPrinterAvailable = PrinterBranch::query()
                ->where('branch_id', $branchId)
                ->where('status', 'E')
                ->whereNotNull('ip')
                ->where('ip', '!=', '')
                ->exists();

            return response()->json([
                'success' => true,
                'message' => 'Venta procesada correctamente',
                'data' => [
                    'movement_id' => $movement->id,
                    'cash_movement_id' => $cashEntryMovement->id,
                    'number' => $number,
                    'total' => $total,
                ],
                'client_on_local_network' => LocalNetworkClient::isOnLocalNetwork($request),
                'thermal_printer_available' => $thermalPrinterAvailable,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar la venta: '.$e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            $message = config('app.debug')
                ? $e->getMessage()
                : 'Error al procesar la venta';

            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ] : null,
            ], 500);
        }
    }

    // Guardar venta como borrador/pendiente (sin pago)
    public function saveDraft(Request $request)
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1',
                'items.*.pId' => 'required|integer|exists:products,id',
                'items.*.qty' => 'required|numeric|min:0.000001',
                'items.*.price' => 'required|numeric|min:0',
                'items.*.courtesyQty' => 'nullable|numeric|min:0',
                'items.*.note' => 'nullable|string|max:65535',
                // Compatibilidad: algunos flujos pueden enviar `comment` en lugar de `note`
                'items.*.comment' => 'nullable|string|max:65535',
                'document_type_id' => 'nullable|integer|exists:document_types,id',
                'notes' => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $request->user();
            $branchId = session('branch_id');
            $branch = Branch::findOrFail($branchId);

            // Obtener turno de la sucursal
            $shift = Shift::where('branch_id', $branchId)->first();
            if (! $shift) {
                $shift = Shift::first();
            }
            if (! $shift) {
                throw new \Exception('No hay turno disponible. Por favor, crea un turno primero.');
            }

            // Obtener tipos de movimiento y documento para ventas
            $movementType = MovementType::where('description', 'like', '%venta%')
                ->orWhere('description', 'like', '%sale%')
                ->orWhere('description', 'like', '%Venta%')
                ->first();

            if (! $movementType) {
                $movementType = MovementType::first();
            }

            if (! $movementType) {
                throw new \Exception('No se encontró un tipo de movimiento válido.');
            }

            // Obtener documento por defecto si no se especifica
            $documentType = null;
            if ($request->document_type_id) {
                $documentType = DocumentType::find($request->document_type_id);
            }

            if (! $documentType) {
                $documentType = DocumentType::where('movement_type_id', $movementType->id)->first();
            }

            if (! $documentType) {
                $documentType = DocumentType::first();
            }

            if (! $documentType) {
                throw new \Exception('No se encontró un tipo de documento válido.');
            }

            // Los precios del front ya incluyen IGV. Calcular subtotal e IGV por producto según su tasa.
            $calculated = $this->calculateSubtotalAndTaxFromItems($request->items, $branchId);
            $subtotal = $calculated['subtotal'];
            $tax = $calculated['tax'];
            $total = $calculated['total'];

            // Generar numero de movimiento con serie/correlativo por documento y caja activa
            $activeCashRegisterId = $this->resolveActiveCashRegisterId((int) $branchId);
            $number = $this->generateSaleNumber(
                (int) $documentType->id,
                $activeCashRegisterId,
                true
            );

            // Crear Movement con status 'P' (Pendiente) o 'I' (Inactivo)
            $movement = Movement::create([
                'number' => $number,
                'moved_at' => now(),
                'user_id' => $user?->id,
                'user_name' => $user?->name ?? 'Sistema',
                'person_id' => null,
                'person_name' => 'Público General',
                'responsible_id' => $user?->id,
                'responsible_name' => $user?->name ?? 'Sistema',
                'comment' => ($request->notes ?? 'Venta pendiente de pago').' [BORRADOR]',
                'status' => 'P', // P = Pendiente
                'movement_type_id' => $movementType->id,
                'document_type_id' => $documentType->id,
                'branch_id' => $branchId,
                'parent_movement_id' => null,
                'shift_id' => $shift->id,
                'shift_snapshot' => [
                    'name' => $shift->name,
                    'start_time' => $shift->start_time,
                    'end_time' => $shift->end_time,
                ],
            ]);

            // Crear SalesMovement con payment_type 'CREDIT' (pendiente de pago)
            $salesMovement = SalesMovement::create([
                'branch_snapshot' => [
                    'id' => $branch->id,
                    'legal_name' => $branch->legal_name,
                ],
                'series' => '001',
                'year' => Carbon::now()->year,
                'detail_type' => 'DETALLADO',
                'consumption' => 'N',
                'payment_type' => 'CONTADO',
                'status' => 'N',
                'sale_type' => 'MINORISTA',
                'currency' => 'PEN',
                'exchange_rate' => 1.000,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'movement_id' => $movement->id,
                'branch_id' => $branchId,
            ]);

            // Crear SalesMovementDetails (sin restar stock porque es borrador; nota por producto en comment)
            foreach ($validated['items'] as $item) {
                $product = Product::with('baseUnit')->findOrFail($item['pId']);
                $productBranch = ProductBranch::with('taxRate')
                    ->where('product_id', $item['pId'])
                    ->where('branch_id', $branchId)
                    ->first();

                if (! $productBranch) {
                    throw new \Exception("Producto {$product->description} no disponible en esta sucursal");
                }

                $unit = $product->baseUnit;
                if (! $unit) {
                    throw new \Exception("El producto {$product->description} no tiene una unidad base configurada");
                }

                $taxRate = $productBranch->taxRate;
                $taxRateValue = $taxRate ? ($taxRate->tax_rate / 100) : $this->getDefaultTaxRateValue();

                $qty = (float) ($item['qty'] ?? 0);
                $courtesyQty = (float) ($item['courtesyQty'] ?? $item['courtesy_quantity'] ?? 0);
                $courtesyQty = max(0, min($courtesyQty, $qty));
                $paidQty = $qty - $courtesyQty;
                // Precio de venta incluye impuesto; el monto es solo por unidades pagadas (sin cortesía).
                $itemTotal = $paidQty * (float) ($item['price'] ?? 0);
                $itemSubtotal = $taxRateValue > 0 ? ($itemTotal / (1 + $taxRateValue)) : $itemTotal;
                $itemTax = $itemTotal - $itemSubtotal;

                // Nota por producto (compatibilidad note/comment) y normalización
                $detailNoteRaw = data_get($item, 'note', data_get($item, 'comment'));
                $detailNote = $detailNoteRaw === null ? null : trim((string) $detailNoteRaw);
                $detailNote = ($detailNote !== '') ? $detailNote : null;

                SalesMovementDetail::create([
                    'detail_type' => 'DETAILED',
                    'sales_movement_id' => $salesMovement->id,
                    'code' => $product->code,
                    'description' => $product->description,
                    'product_id' => $product->id,
                    'product_snapshot' => [
                        'id' => $product->id,
                        'code' => $product->code,
                        'description' => $product->description,
                    ],
                    'unit_id' => $unit->id,
                    'tax_rate_id' => $taxRate?->id,
                    'tax_rate_snapshot' => $taxRate ? [
                        'id' => $taxRate->id,
                        'description' => $taxRate->description,
                        'tax_rate' => $taxRate->tax_rate,
                    ] : null,
                    'quantity' => $item['qty'],
                    'courtesy_quantity' => $courtesyQty,
                    'amount' => $itemTotal,
                    'discount_percentage' => 0.000000,
                    'original_amount' => $itemSubtotal,
                    'comment' => $detailNote,
                    'parent_detail_id' => null,
                    'complements' => [],
                    'status' => 'A',
                    'branch_id' => $branchId,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Venta guardada como borrador correctamente',
                'data' => [
                    'movement_id' => $movement->id,
                    'number' => $number,
                    'total' => $total,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al guardar borrador de venta: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error al guardar borrador: '.$e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $data = $this->validateSale($request);

        $user = $request->user();
        $personName = null;
        if (! empty($data['person_id'])) {
            $person = Person::find($data['person_id']);
            $personName = $person ? ($person->first_name.' '.$person->last_name) : null;
        }

        Movement::create([
            'number' => $data['number'],
            'moved_at' => $data['moved_at'],
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? '',
            'person_id' => $data['person_id'] ?? null,
            'person_name' => $personName ?? '',
            'responsible_id' => $user?->id,
            'responsible_name' => $user?->name ?? '',
            'comment' => $data['comment'] ?? '',
            'status' => $data['status'],
            'movement_type_id' => $data['movement_type_id'],
            'document_type_id' => $data['document_type_id'],
            'branch_id' => $data['branch_id'],
            'parent_movement_id' => $data['parent_movement_id'] ?? null,
        ]);

        return redirect()
            ->route('sales.index', $request->filled('view_id') ? ['view_id' => $request->input('view_id')] : [])
            ->with('status', 'Venta creada correctamente.');
    }

    public function edit(Movement $sale)
    {
        return view('sales.edit', [
            'sale' => $sale,
        ] + $this->getFormData($sale));
    }

    public function update(Request $request, Movement $sale)
    {
        $data = $this->validateSale($request);

        $personName = null;
        if (! empty($data['person_id'])) {
            $person = Person::find($data['person_id']);
            $personName = $person ? ($person->first_name.' '.$person->last_name) : null;
        }

        $sale->update([
            'number' => $data['number'],
            'moved_at' => $data['moved_at'],
            'person_id' => $data['person_id'] ?? null,
            'person_name' => $personName ?? '',
            'comment' => $data['comment'] ?? '',
            'status' => $data['status'],
            'movement_type_id' => $data['movement_type_id'],
            'document_type_id' => $data['document_type_id'],
            'branch_id' => $data['branch_id'],
            'parent_movement_id' => $data['parent_movement_id'] ?? null,
        ]);

        return redirect()
            ->route('sales.index', $request->filled('view_id') ? ['view_id' => $request->input('view_id')] : [])
            ->with('status', 'Venta actualizada correctamente.');
    }

    public function destroy(Movement $sale)
    {
        $sale->delete();

        return redirect()
            ->route('sales.index', request()->filled('view_id') ? ['view_id' => request()->input('view_id')] : [])
            ->with('status', 'Venta eliminada correctamente.');
    }

    private function validateSale(Request $request): array
    {
        return $request->validate([
            'number' => ['required', 'string', 'max:255'],
            'moved_at' => ['required', 'date'],
            'person_id' => ['nullable', 'integer', 'exists:people,id'],
            'comment' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:1'],
            'movement_type_id' => ['required', 'integer', 'exists:movement_types,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'parent_movement_id' => ['nullable', 'integer', 'exists:movements,id'],
        ]);
    }

    private function getFormData(?Movement $sale = null): array
    {
        $branches = Branch::query()->orderBy('legal_name')->get(['id', 'legal_name']);
        $people = Person::query()->orderBy('first_name')->get(['id', 'first_name', 'last_name']);
        $movementTypes = MovementType::query()->orderBy('description')->get(['id', 'description']);
        $documentTypes = DocumentType::query()->orderBy('name')->get(['id', 'name']);

        return [
            'branches' => $branches,
            'people' => $people,
            'movementTypes' => $movementTypes,
            'documentTypes' => $documentTypes,
        ];
    }

    /** Obtiene la tasa de impuesto por defecto del sistema (valor 0-1, ej: 0.18 para 18%). */
    private function getDefaultTaxRateValue(): float
    {
        $taxRate = TaxRate::where('status', true)->orderBy('order_num')->first();

        return $taxRate ? ((float) $taxRate->tax_rate) / 100 : 0.18;
    }

    private function resolveSalePaymentLabel(Movement $sale): string
    {
        if (($sale->salesMovement?->payment_type ?? null) === 'CREDIT') {
            return 'Credito';
        }

        $cashMovement = $sale->cashMovement ?: $this->resolveCashMovementBySaleMovement($sale->id);
        if (! $cashMovement) {
            return 'No especificado';
        }

        $methodName = DB::table('cash_movement_details as cmd')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'cmd.payment_method_id')
            ->where('cmd.cash_movement_id', $cashMovement->id)
            ->where('cmd.status', 'A')
            ->where('cmd.type', '!=', 'DEUDA')
            ->orderBy('cmd.id')
            ->value('pm.description');

        return $methodName ?: 'Mixto';
    }

    public function printTicket(Request $request, Movement $sale)
    {
        $sale = $this->resolvePrintableForTicket($sale);
        $printData = $this->buildSalePrintData($sale, $request);
        if ($request->boolean('direct_print')) {
            $printData['autoPrint'] = true;

            return view('sales.print.ticket', $printData);
        }

        $printData['autoPrint'] = false;

        $html = view('sales.print.ticket', $printData)->render();
        $pdfBinary = $this->renderPdfWithWkhtmltopdf($html, null, [
            '--page-width',
            '80mm',
            '--page-height',
            '140mm',
            '--margin-top',
            '0',
            '--margin-right',
            '0',
            '--margin-bottom',
            '0',
            '--margin-left',
            '0',
            '--print-media-type',
            '--disable-smart-shrinking',
            '--dpi',
            '203',
        ]);

        if ($pdfBinary === null) {
            $printData['autoPrint'] = true;

            return view('sales.print.ticket', $printData);
        }

        $docName = strtoupper(substr($sale->documentType?->name ?? 'T', 0, 1)).$this->ticketSeriesForMovement($sale).'-'.$sale->number;

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$docName.'-ticket.pdf"',
        ]);
    }

    /**
     * Envía ticket en texto plano a ticketera en red (puerto típico 9100).
     * Solo permite clientes cuya IP esté en LAN privada (WiFi del local).
     */
    public function printTicketThermalNetwork(Request $request)
    {
        if (! config('local_network.thermal_print_enabled', true)) {
            abort(404);
        }

        // Modo QZ: el cliente (QZ Tray) imprime directamente; el servidor solo devuelve el payload.
        // No requiere red local ni IP en la impresora (admite USB).
        $qzMode = $request->input('mode') === 'qz' || $request->boolean('qz_mode');

        if (! $qzMode && ! LocalNetworkClient::isOnLocalNetwork($request)) {
            return response()->json([
                'success' => false,
                'message' => 'La impresión por red desde el servidor solo está permitida dentro de la red del local. Si el sistema está en un VPS, usa QZ Tray en el equipo del establecimiento o conecta el VPS a la LAN/VPN del local.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'movement_id' => ['required', 'integer', 'exists:movements,id'],
                'printer_id' => ['nullable', 'integer', 'exists:printers_branch,id'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos no válidos',
                'errors' => $e->errors(),
            ], 422);
        }

        $branchId = (int) session('branch_id');
        if (! $branchId) {
            return response()->json(['success' => false, 'message' => 'Sin sucursal en sesión.'], 422);
        }

        $movement = Movement::query()
            ->where('id', $validated['movement_id'])
            ->where('branch_id', $branchId)
            ->first();

        if (! $movement) {
            return response()->json(['success' => false, 'message' => 'Venta no encontrada en esta sucursal.'], 404);
        }

        $movement = $this->resolvePrintableForTicket($movement);

        $printerQuery = PrinterBranch::query()
            ->where('branch_id', $branchId)
            ->where('status', 'E');

        // En modo TCP requerimos IP; en modo QZ no (puede ser USB identificado por nombre)
        if (! $qzMode) {
            $printerQuery->whereNotNull('ip')->where('ip', '!=', '');
        }

        if (! empty($validated['printer_id'])) {
            $printer = (clone $printerQuery)->where('id', $validated['printer_id'])->first();
        } else {
            $printer = $printerQuery->orderBy('id')->first();
        }

        $plain = $this->buildThermalTicketPlainText($movement, $request);
        $payload = $this->wrapEscPosPlainPayload($plain);

        // Modo QZ: devolver payload en base64 para que QZ Tray lo envíe (USB o red)
        if ($qzMode) {
            return response()->json([
                'success' => true,
                'payload_b64' => base64_encode($payload),
                'printer_name' => $printer?->name ?? null,
                'paper_width' => (int) ($printer?->width ?? 58),
            ]);
        }

        if (! $printer) {
            return response()->json([
                'success' => false,
                'message' => 'No hay ticketera con IP configurada para esta sucursal.',
            ], 422);
        }

        try {
            app(ThermalNetworkPrintService::class)->sendRaw(
                (string) $printer->ip,
                (int) config('local_network.thermal_port', 9100),
                $payload,
                (int) config('local_network.thermal_timeout_seconds', 4)
            );
        } catch (\Throwable $e) {
            Log::warning('Impresión térmica red: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => config('app.debug')
                    ? $e->getMessage()
                    : 'No se pudo enviar el ticket a la ticketera. Comprueba IP, cable/red y que el servidor alcance la impresora.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Ticket enviado a la ticketera.']);
    }

    private function resolveWkhtmltopdfBinary(): ?string
    {
        $candidates = array_filter([
            env('SNAPPY_PDF_BINARY'),
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function renderPdfWithWkhtmltopdf(string $html, ?string $pageSize = 'A4', array $extraArgs = []): ?string
    {
        $binary = $this->resolveWkhtmltopdfBinary();
        if (! $binary) {
            return null;
        }

        $tmpDir = storage_path('app/tmp');
        if (! is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $htmlFile = tempnam($tmpDir, 'sale_html_');
        $pdfFile = tempnam($tmpDir, 'sale_pdf_');

        if ($htmlFile === false || $pdfFile === false) {
            return null;
        }

        $htmlPath = $htmlFile.'.html';
        $pdfPath = $pdfFile.'.pdf';
        @rename($htmlFile, $htmlPath);
        @rename($pdfFile, $pdfPath);

        file_put_contents($htmlPath, $html);

        $args = array_merge([
            $binary,
            '--enable-local-file-access',
            '--disable-javascript',
            '--load-error-handling',
            'ignore',
            '--load-media-error-handling',
            'ignore',
            '--encoding',
            'utf-8',
            '--margin-top',
            '10',
            '--margin-right',
            '10',
            '--margin-bottom',
            '10',
            '--margin-left',
            '10',
        ], $extraArgs);

        if (! empty($pageSize)) {
            $args[] = '--page-size';
            $args[] = $pageSize;
        }

        $args = array_merge($args, [
            $htmlPath,
            $pdfPath,
        ]);

        $process = new Process($args);

        try {
            $process->setTimeout(120);
            $process->run();
            $pdfExists = file_exists($pdfPath) && filesize($pdfPath) > 0;
            if (! $pdfExists) {
                Log::warning('wkhtmltopdf fallo al generar PDF', [
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput(),
                ]);

                return null;
            }

            $content = file_get_contents($pdfPath);

            return $content === false ? null : $content;
        } catch (\Throwable $e) {
            Log::warning('Error ejecutando wkhtmltopdf: '.$e->getMessage());

            return null;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
        }
    }

    private function buildSalePrintData(Movement $sale, Request $request): array
    {
        $sale->loadMissing([
            'documentType',
            'person',
            'branch',
            'salesMovement.details.unit',
            'salesMovement.details.product',
            'salesMovement.details.taxRate',
            'orderMovement.details.unit',
            'orderMovement.details.product',
        ]);

        $sessionBranchId = (int) session('branch_id');
        $sessionBranch = $sessionBranchId ? Branch::find($sessionBranchId) : null;
        $branchForLogo = $sessionBranch ?: $sale->branch;

        $logoUrl = null;
        $logoFileUrl = null;
        if ($branchForLogo?->logo) {
            $logoUrl = str_starts_with($branchForLogo->logo, 'http')
                ? $branchForLogo->logo
                : asset('storage/'.ltrim((string) $branchForLogo->logo, '/'));

            if (! str_starts_with((string) $branchForLogo->logo, 'http')) {
                $localLogoPath = storage_path('app/public/'.ltrim((string) $branchForLogo->logo, '/'));
                if (file_exists($localLogoPath)) {
                    $normalized = str_replace('\\', '/', $localLogoPath);
                    $logoFileUrl = 'file:///'.ltrim($normalized, '/');
                }
            }
        }

        $details = $this->ticketDetailsForMovement($sale);
        if ($details->isEmpty()) {
            abort(404, 'Comprobante sin detalle.');
        }

        return [
            'sale' => $sale,
            'details' => $details,
            'branchForLogo' => $branchForLogo,
            'logoUrl' => $logoUrl,
            'logoFileUrl' => $logoFileUrl,
            'printedAt' => now(),
            'paymentLabel' => $this->resolveSalePaymentLabel($sale),
            'viewId' => $request->input('view_id'),
        ];
    }

    /**
     * Venta POS (salesMovement) o pedido cobrado (orderMovement).
     */
    private function resolvePrintableForTicket(Movement $sale): Movement
    {
        $sale->loadMissing([
            'documentType',
            'person',
            'branch',
            'salesMovement.details.unit',
            'salesMovement.details.product',
            'salesMovement.details.taxRate',
            'orderMovement.details.unit',
            'orderMovement.details.product',
        ]);

        if (! $sale->salesMovement && ! $sale->orderMovement) {
            abort(404, 'Comprobante no encontrado.');
        }

        $branchId = (int) session('branch_id');
        if ($branchId && (int) $sale->branch_id !== $branchId) {
            abort(403);
        }

        return $sale;
    }

    private function ticketSeriesForMovement(Movement $sale): string
    {
        return $sale->salesMovement?->series ?? '001';
    }

    /**
     * @return \Illuminate\Support\Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    private function ticketDetailsForMovement(Movement $sale)
    {
        if ($sale->salesMovement) {
            return $sale->salesMovement->details->sortBy('id')->values();
        }

        if ($sale->orderMovement) {
            return $sale->orderMovement->details()
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'C');
                })
                ->orderBy('id')
                ->get();
        }

        return collect();
    }

    public function printPdf(Request $request, Movement $sale)
    {
        $sale = $this->resolvePrintableForTicket($sale);
        $printData = $this->buildSalePrintData($sale, $request);
        $printData['autoPrint'] = false;

        $html = view('sales.print.pdf', $printData)->render();
        $pdfBinary = $this->renderPdfWithWkhtmltopdf($html, 'A4');

        if ($pdfBinary === null) {
            return view('sales.print.pdf', $printData);
        }

        $docName = strtoupper(substr($sale->documentType?->name ?? 'C', 0, 1)).$this->ticketSeriesForMovement($sale).'-'.$sale->number;

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$docName.'.pdf"',
        ]);
    }

    /**
     * Calcula subtotal, IGV y total desde los ítems usando la tasa de impuesto de cada producto.
     * Usa la tasa configurada en ProductBranch->TaxRate; si no tiene, usa la tasa por defecto del sistema.
     */
    private function calculateSubtotalAndTaxFromItems(array $items, int $branchId): array
    {
        $defaultTaxPct = $this->getDefaultTaxRateValue();
        $subtotal = 0.0;
        $tax = 0.0;
        $total = 0.0;

        foreach ($items as $item) {
            $productBranch = ProductBranch::with('taxRate')
                ->where('product_id', $item['pId'])
                ->where('branch_id', $branchId)
                ->first();

            $taxRate = $productBranch?->taxRate;
            $taxRateValue = $taxRate ? ($taxRate->tax_rate / 100) : $defaultTaxPct;

            $qty = (float) ($item['qty'] ?? 0);
            $courtesyQty = (float) ($item['courtesyQty'] ?? $item['courtesy_quantity'] ?? 0);
            $courtesyQty = max(0, min($courtesyQty, $qty));
            $paidQty = $qty - $courtesyQty;
            $itemTotal = $paidQty * (float) ($item['price'] ?? 0);
            $itemSubtotal = $taxRateValue > 0 ? ($itemTotal / (1 + $taxRateValue)) : $itemTotal;
            $itemTax = $itemTotal - $itemSubtotal;

            $subtotal += $itemSubtotal;
            $tax += $itemTax;
            $total += $itemTotal;
        }

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
        ];
    }

    public function exportPdf(Request $request)
    {
        $branchId = session('branch_id');
        $search = $request->input('search');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $documentTypeId = $request->input('document_type_id');
        $paymentMethodId = $request->input('payment_method_id');
        $cashRegisterId = $request->input('cash_register_id');
        $personId = $request->input('person_id');
        $saleType = $request->input('sale_type');

        $branch = $branchId ? Branch::with('company')->find($branchId) : null;
        $companyName = $branch?->company?->legal_name;
        $branchName = $branch?->legal_name;

        $query = Movement::query()
            ->with(['branch', 'person', 'movementType', 'documentType', 'salesMovement'])
            ->where('movement_type_id', 2)
            ->where('branch_id', $branchId)
            ->whereHas('salesMovement');

        // Aplicación de filtros
        if ($documentTypeId && is_numeric($documentTypeId)) {
            $query->where('document_type_id', (int) $documentTypeId);
        }
        if ($search) {
            $query->where(function ($inner) use ($search) {
                $inner->where('number', 'like', "%{$search}%")
                    ->orWhere('person_name', 'like', "%{$search}%")
                    ->orWhere('user_name', 'like', "%{$search}%");
            });
        }
        if ($dateFrom) {
            $query->where('moved_at', '>=', $dateFrom.' 00:00:00');
        }
        if ($dateTo) {
            $query->where('moved_at', '<=', $dateTo.' 23:59:59');
        }
        if ($paymentMethodId) {
            $query->whereExists(function ($sub) use ($paymentMethodId) {
                $sub->select(DB::raw(1))
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->join('cash_movement_details as cmd', 'cmd.cash_movement_id', '=', 'cm.id')
                    ->whereColumn('m.parent_movement_id', 'movements.id')
                    ->where('cmd.payment_method_id', $paymentMethodId)
                    ->whereNull('cm.deleted_at')
                    ->whereNull('cmd.deleted_at');
            });
        }
        if ($cashRegisterId) {
            $query->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->select(DB::raw(1))
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'movements.id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            });
        }
        if ($saleType) {
            $query->whereHas('salesMovement', function ($sub) use ($saleType) {
                $sub->where('detail_type', $saleType);
            });
        }

        $sales = $query->orderBy('moved_at', 'desc')->get();

        $filters = [];
        $filters['Desde'] = $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : null;
        $filters['Hasta'] = $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : null;
        if ($search) {
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
        if ($saleType) {
            $filters['Tipo de venta'] = $saleType;
        }

        try {
            $pdf = PDF::loadView('sales.pdfs.pdf_report', compact(
                'sales',
                'dateFrom',
                'dateTo',
                'filters',
                'companyName',
                'branchName'
            ));

            $pdf->setPaper('a4')
                ->setOption('margin-bottom', 10)
                ->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true);

            // Obtenemos el contenido binario del PDF
            $output = $pdf->output();

            // Devolvemos la respuesta forzando los headers
            return response($output, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Reporte_Ventas.pdf"',
                'Content-Length' => strlen($output),
            ]);
        } catch (\Exception $e) {
            Log::error('Error al exportar PDF de ventas', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->with('error', 'No se pudo generar el PDF de ventas.');
        }
    }

    /**
     * Genera numero de venta en formato correlativo simple: 00000127.
     * Mantiene compatibilidad leyendo tambien numeros historicos con formato antiguo.
     */
    private function generateSaleNumber(int $documentTypeId, int $cashRegisterId, bool $reserve = true): string
    {
        $documentType = DocumentType::find($documentTypeId);
        if (! $documentType) {
            throw new \Exception('Tipo de documento no encontrado.');
        }

        $cashRegister = CashRegister::find($cashRegisterId);
        if (! $cashRegister) {
            throw new \Exception('Caja no encontrada.');
        }

        $branchId = (int) session('branch_id');
        if (! $branchId) {
            throw new \Exception('No se encontro sucursal en sesion.');
        }

        $year = (int) now()->year;

        $query = Movement::query()
            ->where('branch_id', $branchId)
            ->where('document_type_id', $documentTypeId)
            ->whereYear('moved_at', $year);

        if ($reserve) {
            $query->lockForUpdate();
        }

        $lastCorrelative = 0;
        $numbers = $query->pluck('number');

        foreach ($numbers as $number) {
            $raw = trim((string) $number);
            if ($raw === '') {
                continue;
            }

            if (preg_match('/^\d+$/', $raw) === 1) {
                $value = (int) $raw;
                if ($value > $lastCorrelative) {
                    $lastCorrelative = $value;
                }

                continue;
            }

            if (preg_match('/(\d+)-\d{4}$/', $raw, $matches) === 1) {
                $value = (int) $matches[1];
                if ($value > $lastCorrelative) {
                    $lastCorrelative = $value;
                }
            }
        }

        $nextCorrelative = $lastCorrelative + 1;

        return str_pad((string) $nextCorrelative, 8, '0', STR_PAD_LEFT);
    }

    private function resolveDocumentAbbreviation(string $documentName): string
    {
        $name = strtolower(trim($documentName));

        if (str_contains($name, 'boleta')) {
            return 'B';
        }
        if (str_contains($name, 'factura')) {
            return 'F';
        }
        if (str_contains($name, 'ticket')) {
            return 'T';
        }
        if (str_contains($name, 'nota')) {
            return 'N';
        }

        $firstLetter = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $documentName) ?: 'X', 0, 1));

        return $firstLetter !== '' ? $firstLetter : 'X';
    }

    private function resolveActiveCashRegisterId(int $branchId): int
    {
        $cashRegisterId = CashRegister::query()
            ->where('branch_id', $branchId)
            ->where('status', 'A')
            ->orderBy('id')
            ->value('id');

        if (! $cashRegisterId) {
            $cashRegisterId = CashRegister::query()
                ->where('branch_id', $branchId)
                ->orderBy('id')
                ->value('id');
        }

        if (! $cashRegisterId) {
            throw new \Exception('No hay caja activa/disponible para generar el numero de venta.');
        }

        return (int) $cashRegisterId;
    }

    private function resolveCashMovementBySaleMovement(int $saleMovementId): ?CashMovements
    {
        $cashMovement = CashMovements::where('movement_id', $saleMovementId)->first();
        if ($cashMovement) {
            return $cashMovement;
        }

        $cashEntryMovementId = Movement::query()
            ->where('parent_movement_id', $saleMovementId)
            ->whereHas('cashMovement')
            ->orderByDesc('id')
            ->value('id');

        return $cashEntryMovementId ? CashMovements::where('movement_id', $cashEntryMovementId)->first() : null;
    }

    private function resolveCashEntryMovementBySaleMovement(int $saleMovementId): ?Movement
    {
        return Movement::query()
            ->where('parent_movement_id', $saleMovementId)
            ->whereHas('cashMovement')
            ->orderByDesc('id')
            ->first();
    }

    private function resolveCashMovementTypeId(): int
    {
        $movementTypeId = MovementType::query()
            ->where(function ($query) {
                InsensitiveSearch::whereInsensitiveLikePattern($query, 'description', '%caja%');
                InsensitiveSearch::whereInsensitiveLikePattern($query, 'description', '%cash%', 'or');
            })
            ->orderBy('id')
            ->value('id');

        if (! $movementTypeId) {
            $movementTypeId = MovementType::find(4)?->id;
        }

        if (! $movementTypeId) {
            $movementTypeId = MovementType::query()->orderBy('id')->value('id');
        }

        if (! $movementTypeId) {
            throw new \Exception('No se encontro tipo de movimiento para caja.');
        }

        return (int) $movementTypeId;
    }

    private function buildThermalTicketPlainText(Movement $sale, Request $request): string
    {
        $printData = $this->buildSalePrintData($sale, $request);
        $sale = $printData['sale'];
        $details = $printData['details'];
        $branch = $printData['branchForLogo'];
        $paymentLabel = $printData['paymentLabel'];

        $w = 32;
        $sep = str_repeat('=', $w);
        $lines = [];

        $lines[] = $this->thermalPadCenter(strtoupper(Str::ascii($branch->legal_name ?? 'SUCURSAL')), $w);
        $lines[] = $this->thermalPadCenter('RUC: '.Str::ascii($branch->ruc ?? '-'), $w);
        $lines[] = $this->thermalPadCenter(strtoupper(Str::ascii($sale->documentType?->name ?? 'TICKET')), $w);
        $docCode = strtoupper(substr($sale->documentType?->name ?? 'T', 0, 1))
            .$this->ticketSeriesForMovement($sale).'-'.$sale->number;
        $lines[] = $this->thermalPadCenter(Str::ascii($docCode), $w);
        $lines[] = $sep;
        $lines[] = 'Fecha: '.optional($sale->moved_at)->format('d/m/Y H:i');
        $lines[] = 'Cliente: '.Str::ascii($sale->person_name ?? 'CLIENTES VARIOS');
        $lines[] = 'Forma pago: '.Str::ascii($paymentLabel);
        $lines[] = $sep;

        foreach ($details as $detail) {
            $qty = (float) $detail->quantity;
            $lineTotal = (float) $detail->amount;
            $unitPrice = $qty > 0 ? ($lineTotal / $qty) : 0.0;
            $desc = Str::ascii(Str::limit($detail->description ?? $detail->product?->description ?? '-', 30));
            $lines[] = $desc;
            $lines[] = '  '
                .$this->thermalPadStart(number_format($qty, 2, '.', ''), 6)
                .' x '
                .$this->thermalPadStart(number_format($unitPrice, 2, '.', ''), 7)
                .' '
                .$this->thermalPadStart(number_format($lineTotal, 2, '.', ''), 8);
        }

        $docSubtotal = (float) ($sale->salesMovement?->subtotal ?? $sale->orderMovement?->subtotal ?? 0);
        $docTax = (float) ($sale->salesMovement?->tax ?? $sale->orderMovement?->tax ?? 0);
        $docTotal = (float) ($sale->salesMovement?->total ?? $sale->orderMovement?->total ?? 0);

        $lines[] = $sep;
        $lines[] = $this->thermalPadEnd('Op. gravada:', 20)
            .$this->thermalPadStart('S/'.number_format($docSubtotal, 2, '.', ''), 12);
        $lines[] = $this->thermalPadEnd('IGV:', 20)
            .$this->thermalPadStart('S/'.number_format($docTax, 2, '.', ''), 12);
        $lines[] = $this->thermalPadEnd('TOTAL:', 20)
            .$this->thermalPadStart('S/'.number_format($docTotal, 2, '.', ''), 12);

        if ($sale->comment) {
            $lines[] = $sep;
            $lines[] = 'Notas: '.Str::ascii(Str::limit((string) $sale->comment, 120));
        }

        $lines[] = $sep;
        $lines[] = $this->thermalPadCenter('Gracias por su preferencia', $w);
        $lines[] = 'Impreso: '.now()->format('d/m/Y H:i:s');

        return implode("\n", $lines);
    }

    private function wrapEscPosPlainPayload(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        return "\x1B\x40".$text."\n\n\n\x1D\x56\x00";
    }

    private function thermalPadCenter(string $s, int $len): string
    {
        $s = Str::ascii($s);
        $sl = strlen($s);
        if ($sl >= $len) {
            return substr($s, 0, $len);
        }
        $pad = $len - $sl;
        $l = intdiv($pad, 2);
        $r = $pad - $l;

        return str_repeat(' ', $l).$s.str_repeat(' ', $r);
    }

    private function thermalPadEnd(string $s, int $len): string
    {
        $s = Str::ascii($s);
        $sl = strlen($s);

        return $sl >= $len ? substr($s, 0, $len) : $s.str_repeat(' ', $len - $sl);
    }

    private function thermalPadStart(string $s, int $len): string
    {
        $s = Str::ascii($s);
        $sl = strlen($s);

        return $sl >= $len ? substr($s, -$len) : str_repeat(' ', $len - $sl).$s;
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

    /**
     * Clientes de la sucursal (misma lógica que OrderController::resolveClientPeople).
     */
    protected function branchClientsForBranch(?int $branchId): \Illuminate\Support\Collection
    {
        $q = Person::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->whereHas('roles', function ($qq) use ($branchId) {
                $qq->whereRaw("LOWER(TRIM(roles.name)) = 'cliente'")
                    ->whereNull('role_person.deleted_at');
                if ($branchId) {
                    $qq->where('role_person.branch_id', $branchId);
                }
            });

        return $q->get(['id', 'first_name', 'last_name', 'document_number']);
    }
}
