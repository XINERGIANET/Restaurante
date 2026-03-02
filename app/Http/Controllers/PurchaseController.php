<?php

namespace App\Http\Controllers;

use App\Models\PurchaseMovement;
use App\Models\Movement;
use App\Models\PurchaseMovementDetail;
use App\Models\Person;
use App\Models\Branch;
use App\Models\ProductBranch;
use App\Models\DocumentType;
use App\Models\Unit;
use App\Models\Product;
use App\Models\WarehouseMovement;
use App\Models\WarehouseMovementDetail;
use App\Models\CashMovements;
use App\Models\CashMovementDetail;
use App\Models\CashShiftRelation;
use App\Models\PaymentConcept;
use App\Models\Shift;
use App\Models\CashRegister;
use App\Models\Card;
use App\Models\Bank;
use App\Models\DigitalWallet;
use App\Models\PaymentGateways;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = PurchaseMovement::query();

        if ($search) {
            $query->whereHas('movement', function($q) use ($search) {
                $q->where('number', 'ILIKE', "%{$search}%")
                  ->orWhere('person_name', 'ILIKE', "%{$search}%");
            });
        }

        $purchases = $query->with('movement')
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        $startOfMonth = Carbon::now()->startOfMonth();
        
        $metrics = [
            'total_month' => PurchaseMovement::where('created_at', '>=', $startOfMonth)->sum('total'),
            'igv_month'   => PurchaseMovement::where('created_at', '>=', $startOfMonth)->sum('igv'),
            'count_invoices' => PurchaseMovement::where('serie', 'LIKE', 'F%')->count(),
            'count_tickets'  => PurchaseMovement::where('serie', 'LIKE', 'B%')->count(),
            'total_docs'     => PurchaseMovement::count(),
        ];

        return view('purchases.index', compact('purchases', 'search', 'metrics'));
    }

    public function create(Request $request)
    {
        $branchId = $request->session()->get('branch_id');
        $branch = Branch::find($branchId);
        $companyId = $branch?->company_id;

        $branches = Branch::where('company_id', $companyId)->get();
        
        $people = Person::whereHas('roles', function ($query) use ($branchId) {
            $query->where('roles.id', 4) 
                  ->where('role_person.branch_id', $branchId); 
        })
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        $documentTypes = DocumentType::where('movement_type_id', 1)->get();
        $units = Unit::all();
        $defaultTaxRate = 18.00;
        $purchase = null; 

        $products = ProductBranch::where('branch_id', $branchId)
            ->with(['product', 'product.baseUnit'])
            ->where('status', 'E')
            ->get()
            ->map(function ($pb) {
                return (object) [
                    'id' => $pb->product_id,
                    'code' => $pb->product->code ?? '',
                    'description' => $pb->product->description ?? '',
                    'unit_sale' => $pb->product->base_unit_id ?? 0,
                    'unit_name' => $pb->product->baseUnit->description ?? $pb->product->baseUnit->name ?? '',
                    'price' => $pb->price ?? 0,
                ];
            });

        // Cargamos los datos de pago para los selectores del formulario
        $cards = Card::all(); 
        $paymentGateways = PaymentGateways::all(); 
        $digitalWallets = DigitalWallet::all(); 
        $banks = Bank::all(); 

        return view('purchases._form', compact(
            'people', 'documentTypes', 'units', 'products', 'defaultTaxRate', 
            'purchase', 'branches', 'branchId', 'cards', 'paymentGateways', 'digitalWallets', 'banks'
        ));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'moved_at' => ['required', 'date'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'series' => ['required', 'string', 'max:50'],
            'includes_tax' => ['required', 'string', 'in:S,N'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_type' => ['required', 'string', 'in:CONTADO,CREDITO'],
            'currency' => ['required', 'string'],
            'exchange_rate' => ['required', 'numeric', 'min:0.001'],
            'affects_cash' => ['required', 'string', 'in:S,N'],
            'affects_kardex' => ['required', 'string', 'in:S,N'],
            'comment' => ['nullable', 'string'],
            
            // Validar items
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.comment' => ['nullable', 'string'],

            // Validar pagos
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required_with:payments', 'integer'],
            'payments.*.payment_method' => ['nullable', 'string'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.number' => ['nullable', 'string'],
            'payments.*.card_id' => ['nullable', 'integer'],
            'payments.*.bank_id' => ['nullable', 'integer'],
            'payments.*.digital_wallet_id' => ['nullable', 'integer'],
            'payments.*.payment_gateway_id' => ['nullable', 'integer'],
            
            'payment_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,pdf', 'max:2048'],
        ]);

        $cashRegisterId = session('cash_register_id');
        $activeShiftRelation = null;

        if ($data['payment_type'] === 'CONTADO' && $data['affects_cash'] === 'S') {
            if (!$cashRegisterId) {
                return back()->withErrors(['error' => 'Por favor, seleccione una caja en la barra superior antes de registrar una compra que afecta caja.'])->withInput();
            }

            // Buscamos el turno activo de la caja seleccionada
            $activeShiftRelation = CashShiftRelation::with('cashMovementStart')
                ->where('branch_id', session('branch_id'))
                ->where('status', '1')
                ->whereHas('cashMovementStart', function ($q) use ($cashRegisterId) {
                    $q->where('cash_register_id', $cashRegisterId);
                })
                ->latest('id')
                ->first();

            if (!$activeShiftRelation) {
                return back()->withErrors(['error' => 'No hay un turno de caja activo para la caja seleccionada. Realice una Apertura de Caja primero.'])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $request, $activeShiftRelation, $cashRegisterId) {
                $branchId = $data['branch_id']; 
                $person = Person::findOrFail($data['person_id']);
                
                // Recalcular totales
                $lineTotal = collect($data['items'])->reduce(function ($carry, $item) {
                    return $carry + ($item['quantity'] * $item['amount']);
                }, 0);

                $taxRate = $data['tax_rate_percent'] / 100;
                
                if ($data['includes_tax'] === 'S') {
                    $subtotal = $taxRate > 0 ? ($lineTotal / (1 + $taxRate)) : $lineTotal;
                    $tax = $lineTotal - $subtotal;
                    $total = $lineTotal;
                } else {
                    $subtotal = $lineTotal;
                    $tax = $subtotal * $taxRate;
                    $total = $subtotal + $tax;
                }

                // Crear Movement Genérico
                $movement = Movement::create([
                    'number' => $data['series'] . '-' . Carbon::parse($data['moved_at'])->format('Y'),
                    'moved_at' => Carbon::parse($data['moved_at']),
                    'user_id' => $request->user()?->id,
                    'user_name' => $request->user()?->name ?? '',
                    'responsible_id' => $request->user()?->id ?? session('user_id'),
                    'responsible_name' => $request->user()?->name ?? session('user_name') ?? '',
                    'person_id' => $person->id, 
                    'person_name' => trim($person->first_name . ' ' . $person->last_name),
                    'comment' => $data['comment'] ?? '',
                    'status' => 'E',
                    'movement_type_id' => 1, 
                    'document_type_id' => $data['document_type_id'],
                    'branch_id' => $branchId,
                ]);

                // Crear PurchaseMovement
                $purchase = PurchaseMovement::create([
                    'movement_id' => $movement->id,
                    'branch_id' => $branchId,
                    'json_persona' => $person->toJson(),
                    'serie' => $data['series'], 
                    'anio' => Carbon::parse($data['moved_at'])->format('Y'),
                    'tipo_detalle' => 'DETALLADO',
                    'incluye_igv' => $data['includes_tax'],
                    'tipo_pago' => $data['payment_type'],
                    'afecta_caja' => $data['affects_cash'],
                    'moneda' => $data['currency'],
                    'tipocambio' => $data['exchange_rate'],
                    'subtotal' => round($subtotal, 2),
                    'igv' => round($tax, 2),
                    'total' => round($total, 2),
                    'afecta_kardex' => $data['affects_kardex'],
                ]);

                if ($request->hasFile('payment_image')) {
                    $path = $request->file('payment_image')->store('payment_images', 'public');
                    $purchase->update(['payment_image' => $path]);
                }

                // ==========================================
                // LOGICA PARA KARDEX: CABECERA DE ALMACEN
                // ==========================================
                $warehouseMovementObj = null;

                if ($data['affects_kardex'] === 'S') {
                    // Buscamos un tipo de documento que el Kardex identifique como Entrada (Ej: "Nota de Entrada")
                    $docEntrada = DocumentType::where('name', 'ILIKE', '%entrada%')->first();
                    $docEntradaId = $docEntrada ? $docEntrada->id : $data['document_type_id'];

                    // Creamos un Movement gemelo para el Kardex, usando el prefijo 'E-' para que sea tomado como Ingreso
                    $whCoreMovement = Movement::create([
                        'number' => 'E-COMPRA-' . $data['series'] . '-' . Carbon::parse($data['moved_at'])->format('His'),
                        'moved_at' => Carbon::parse($data['moved_at']),
                        'user_id' => $request->user()?->id,
                        'user_name' => $request->user()?->name ?? '',
                        'responsible_id' => $request->user()?->id ?? session('user_id'),
                        'responsible_name' => $request->user()?->name ?? session('user_name') ?? '',
                        'person_id' => $person->id,
                        'person_name' => trim($person->first_name . ' ' . $person->last_name),
                        'comment' => 'Ingreso automático por compra: ' . ($data['comment'] ?? $data['series']),
                        'status' => 'E',
                        'movement_type_id' => 1, 
                        'document_type_id' => $docEntradaId,
                        'branch_id' => $branchId,
                    ]);

                    $warehouseMovementObj = WarehouseMovement::create([
                        'movement_id' => $whCoreMovement->id,
                        'branch_id' => $branchId,
                    ]);
                }

                // Crear los Detalles de la compra y Almacén
                foreach ($data['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $unit = Unit::find($item['unit_id']);

                    PurchaseMovementDetail::create([
                        'purchase_movement_id' => $purchase->id,
                        'branch_id' => $branchId,
                        'tipo_detalle' => 'DETALLADO',
                        'producto_id' => $item['product_id'],
                        'codigo' => $product ? $product->code : null,
                        'descripcion' => $item['description'],
                        'json_producto' => $product ? $product->toJson() : null,
                        'unidad_id' => $item['unit_id'],
                        'json_unidad' => $unit ? $unit->toJson() : null,
                        'cantidad' => $item['quantity'],
                        'monto' => $item['amount'],
                        'comentario' => $item['comment'] ?? '',
                        'situacion' => 'E',
                    ]);

                    // ==========================================
                    // LOGICA PARA KARDEX: DETALLE Y STOCK
                    // ==========================================
                    if ($data['affects_kardex'] === 'S' && $warehouseMovementObj) {
                        
                        // 1. Guardar detalle de almacén
                        WarehouseMovementDetail::create([
                            'warehouse_movement_id' => $warehouseMovementObj->id,
                            'branch_id' => $branchId,
                            'product_id' => $item['product_id'],
                            'unit_id' => $item['unit_id'],
                            'quantity' => $item['quantity'],
                            'product_snapshot' => $product ? $product->toJson() : '{}',
                            'comment' => $item['comment'] ?? '',
                        ]);
                        $productBranch = ProductBranch::firstOrCreate(
                            ['product_id' => $item['product_id'], 'branch_id' => $branchId],
                            ['stock' => 0]
                        );
                        
                        $productBranch->increment('stock', $item['quantity']);
                    }
                }

                // REGISTRAR LOS PAGOS EN LA CAJA (SOLO SI AFECTA CAJA)
                if ($data['payment_type'] === 'CONTADO' && $data['affects_cash'] === 'S' && !empty($data['payments'])) {
                    
                    $paymentConcept = PaymentConcept::where('description', 'ILIKE', '%compra%')->first();
                    $conceptId = $paymentConcept ? $paymentConcept->id : 1; 

                    $shiftId = null;
                    $shiftSnapshotJson = null;
                    $box = CashRegister::find($cashRegisterId);
                    $cashRegisterName = $box ? $box->number : 'Caja Desconocida';

                    if ($activeShiftRelation) {
                        $shift = Shift::find($activeShiftRelation->cashMovementStart->shift_id ?? null);
                        if ($shift) {
                            $shiftId = $shift->id;
                            $shiftSnapshotJson = json_encode([
                                'name'       => $shift->name,
                                'start_time' => $shift->start_time,
                                'end_time'   => $shift->end_time
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    $totalAmountPaid = collect($data['payments'])->sum('amount');

                    $cashMovement = CashMovements::create([
                        'payment_concept_id' => $conceptId,
                        'currency'           => $data['currency'],
                        'exchange_rate'      => $data['exchange_rate'],
                        'total'              => $totalAmountPaid,
                        'cash_register_id'   => $cashRegisterId, 
                        'cash_register'      => $cashRegisterName,
                        'shift_id'           => $shiftId,
                        'shift_snapshot'     => $shiftSnapshotJson,
                        'movement_id'        => $movement->id,
                        'branch_id'          => $branchId,
                    ]);

                    foreach ($data['payments'] as $paymentData) {
                        $cardName = !empty($paymentData['card_id']) ? Card::find($paymentData['card_id'])?->description : null;
                        $bankName = !empty($paymentData['bank_id']) ? Bank::find($paymentData['bank_id'])?->description : null;
                        $walletName = !empty($paymentData['digital_wallet_id']) ? DigitalWallet::find($paymentData['digital_wallet_id'])?->description : null;
                        $gatewayName = !empty($paymentData['payment_gateway_id']) ? PaymentGateways::find($paymentData['payment_gateway_id'])?->description : null;

                        $individualComment = ($paymentData['payment_method_id'] != 1 && !empty($paymentData['number']))
                            ? 'Ref: ' . $paymentData['number']
                            : ($data['comment'] ?? 'Pago de compra');

                        CashMovementDetail::create([
                            'cash_movement_id'   => $cashMovement->id,
                            'branch_id'          => $branchId,
                            'type'               => 'EGRESO', 
                            'status'             => 'A',
                            'paid_at'            => now(),
                            'amount'             => $paymentData['amount'],
                            'payment_method_id'  => $paymentData['payment_method_id'],
                            'payment_method'     => $paymentData['payment_method'] ?? 'Desconocido',
                            'comment'            => $individualComment,
                            'number'             => $paymentData['number'] ?? null,
                            'card_id'            => $paymentData['card_id'] ?? null,
                            'card'               => $cardName,
                            'bank_id'            => $paymentData['bank_id'] ?? null,
                            'bank'               => $bankName,
                            'digital_wallet_id'  => $paymentData['digital_wallet_id'] ?? null,
                            'digital_wallet'     => $walletName,
                            'payment_gateway_id' => $paymentData['payment_gateway_id'] ?? null,
                            'payment_gateway'    => $gatewayName,
                        ]);
                    }
                }
            });

            return redirect()->route('purchase.index')->with('status', 'Compra registrada correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al guardar la compra: ' . $e->getMessage()])->withInput();
        }
    }

    public function update(Request $request, PurchaseMovement $purchaseMovement)
    {
        $data = $request->validate([
            'moved_at' => ['required', 'date'],
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'person_id' => ['required', 'integer', 'exists:people,id'],
            'document_type_id' => ['required', 'integer', 'exists:document_types,id'],
            'series' => ['required', 'string', 'max:50'],
            'includes_tax' => ['required', 'string', 'in:S,N'],
            'tax_rate_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'payment_type' => ['required', 'string', 'in:CONTADO,CREDITO'],
            'currency' => ['required', 'string'],
            'exchange_rate' => ['required', 'numeric', 'min:0.001'],
            'affects_cash' => ['required', 'string', 'in:S,N'],
            'affects_kardex' => ['required', 'string', 'in:S,N'],
            'comment' => ['nullable', 'string'],
            
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.unit_id' => ['required', 'integer'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'items.*.amount' => ['required', 'numeric', 'min:0'],
            'items.*.comment' => ['nullable', 'string'],

            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required_with:payments', 'integer'],
            'payments.*.payment_method' => ['nullable', 'string'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
            'payments.*.number' => ['nullable', 'string'],
            'payments.*.card_id' => ['nullable', 'integer'],
            'payments.*.bank_id' => ['nullable', 'integer'],
            'payments.*.digital_wallet_id' => ['nullable', 'integer'],
            'payments.*.payment_gateway_id' => ['nullable', 'integer'],
        ]);

        $cashRegisterId = session('cash_register_id');
        $activeShiftRelation = null;

        if ($data['payment_type'] === 'CONTADO' && $data['affects_cash'] === 'S') {
            if (!$cashRegisterId) {
                return back()->withErrors(['error' => 'Por favor, seleccione una caja en la barra superior.'])->withInput();
            }

            $activeShiftRelation = CashShiftRelation::with('cashMovementStart')
                ->where('branch_id', session('branch_id'))
                ->where('status', '1')
                ->whereHas('cashMovementStart', function ($q) use ($cashRegisterId) {
                    $q->where('cash_register_id', $cashRegisterId);
                })
                ->latest('id')
                ->first();

            if (!$activeShiftRelation) {
                return back()->withErrors(['error' => 'No hay un turno de caja activo para la caja seleccionada.'])->withInput();
            }
        }

        try {
            DB::transaction(function () use ($data, $purchaseMovement, $request, $activeShiftRelation, $cashRegisterId) {
                $branchId = $data['branch_id'];
                $person = Person::findOrFail($data['person_id']);
                
                $lineTotal = collect($data['items'])->reduce(function ($carry, $item) {
                    return $carry + ($item['quantity'] * $item['amount']);
                }, 0);

                $taxRate = $data['tax_rate_percent'] / 100;
                
                if ($data['includes_tax'] === 'S') {
                    $subtotal = $taxRate > 0 ? ($lineTotal / (1 + $taxRate)) : $lineTotal;
                    $tax = $lineTotal - $subtotal;
                    $total = $lineTotal;
                } else {
                    $subtotal = $lineTotal;
                    $tax = $subtotal * $taxRate;
                    $total = $subtotal + $tax;
                }

                if ($purchaseMovement->movement) {
                    $purchaseMovement->movement->update([
                        'number' => $data['series'] . '-' . Carbon::parse($data['moved_at'])->format('Y'),
                        'moved_at' => Carbon::parse($data['moved_at']),
                        'person_id' => $person->id,
                        'person_name' => trim($person->first_name . ' ' . $person->last_name),
                        'document_type_id' => $data['document_type_id'],
                        'comment' => $data['comment'] ?? '',
                        'branch_id' => $branchId,
                    ]);
                }

                $purchaseMovement->update([
                    'branch_id' => $branchId,
                    'json_persona' => $person->toJson(),
                    'serie' => $data['series'],
                    'anio' => Carbon::parse($data['moved_at'])->format('Y'),
                    'incluye_igv' => $data['includes_tax'],
                    'tipo_pago' => $data['payment_type'],
                    'moneda' => $data['currency'],
                    'tipocambio' => $data['exchange_rate'],
                    'afecta_caja' => $data['affects_cash'],
                    'afecta_kardex' => $data['affects_kardex'],
                    'subtotal' => round($subtotal, 2),
                    'igv' => round($tax, 2),
                    'total' => round($total, 2),
                ]);

                if ($request->hasFile('payment_image')) {
                    $path = $request->file('payment_image')->store('payment_images', 'public');
                    $purchaseMovement->update(['payment_image' => $path]);
                }

                $purchaseMovement->details()->delete();

                foreach ($data['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    $unit = Unit::find($item['unit_id']);

                    PurchaseMovementDetail::create([
                        'purchase_movement_id' => $purchaseMovement->id,
                        'branch_id' => $branchId,
                        'tipo_detalle' => 'DETALLADO',
                        'producto_id' => $item['product_id'],
                        'codigo' => $product ? $product->code : null,
                        'descripcion' => $item['description'],
                        'json_producto' => $product ? $product->toJson() : null,
                        'unidad_id' => $item['unit_id'],
                        'json_unidad' => $unit ? $unit->toJson() : null,
                        'cantidad' => $item['quantity'],
                        'monto' => $item['amount'],
                        'comentario' => $item['comment'] ?? '',
                        'situacion' => 'E',
                    ]);
                }

                // Actualizar caja: Borramos el registro viejo
                if ($purchaseMovement->movement) {
                    $oldCashMovement = CashMovements::where('movement_id', $purchaseMovement->movement->id)->first();
                    if ($oldCashMovement) {
                        CashMovementDetail::where('cash_movement_id', $oldCashMovement->id)->delete();
                        $oldCashMovement->delete();
                    }
                }

                // Y creamos el nuevo si corresponde
                if ($data['payment_type'] === 'CONTADO' && $data['affects_cash'] === 'S' && !empty($data['payments'])) {
                    $paymentConcept = PaymentConcept::where('description', 'ILIKE', '%compra%')->first();
                    $conceptId = $paymentConcept ? $paymentConcept->id : 1; 

                    $shiftId = null;
                    $shiftSnapshotJson = null;
                    $box = CashRegister::find($cashRegisterId);
                    $cashRegisterName = $box ? $box->number : 'Caja Desconocida';

                    if ($activeShiftRelation) {
                        $shift = Shift::find($activeShiftRelation->cashMovementStart->shift_id ?? null);
                        if ($shift) {
                            $shiftId = $shift->id;
                            $shiftSnapshotJson = json_encode([
                                'name'       => $shift->name,
                                'start_time' => $shift->start_time,
                                'end_time'   => $shift->end_time
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                        }
                    }

                    $cashMovement = CashMovements::create([
                        'payment_concept_id' => $conceptId,
                        'currency'           => $data['currency'],
                        'exchange_rate'      => $data['exchange_rate'],
                        'total'              => collect($data['payments'])->sum('amount'),
                        'cash_register_id'   => $cashRegisterId,
                        'cash_register'      => $cashRegisterName,
                        'shift_id'           => $shiftId,
                        'shift_snapshot'     => $shiftSnapshotJson,
                        'movement_id'        => $purchaseMovement->movement->id,
                        'branch_id'          => $branchId,
                    ]);

                    foreach ($data['payments'] as $paymentData) {
                        $cardName = !empty($paymentData['card_id']) ? Card::find($paymentData['card_id'])?->description : null;
                        $bankName = !empty($paymentData['bank_id']) ? Bank::find($paymentData['bank_id'])?->description : null;
                        $walletName = !empty($paymentData['digital_wallet_id']) ? DigitalWallet::find($paymentData['digital_wallet_id'])?->description : null;
                        $gatewayName = !empty($paymentData['payment_gateway_id']) ? PaymentGateways::find($paymentData['payment_gateway_id'])?->description : null;

                        CashMovementDetail::create([
                            'cash_movement_id'   => $cashMovement->id,
                            'branch_id'          => $branchId,
                            'type'               => 'EGRESO',
                            'status'             => 'A',
                            'paid_at'            => now(),
                            'amount'             => $paymentData['amount'],
                            'payment_method_id'  => $paymentData['payment_method_id'],
                            'payment_method'     => $paymentData['payment_method'] ?? 'Desconocido',
                            'comment'            => !empty($paymentData['number']) ? 'Ref: ' . $paymentData['number'] : 'Pago de compra',
                            'number'             => $paymentData['number'] ?? null,
                            'card_id'            => $paymentData['card_id'] ?? null,
                            'card'               => $cardName,
                            'bank_id'            => $paymentData['bank_id'] ?? null,
                            'bank'               => $bankName,
                            'digital_wallet_id'  => $paymentData['digital_wallet_id'] ?? null,
                            'digital_wallet'     => $walletName,
                            'payment_gateway_id' => $paymentData['payment_gateway_id'] ?? null,
                            'payment_gateway'    => $gatewayName,
                        ]);
                    }
                }
            });

            return redirect()->route('purchase.index')->with('status', 'Compra actualizada correctamente.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al actualizar la compra: ' . $e->getMessage()])->withInput();
        }
    }

    public function edit(Request $request, PurchaseMovement $purchaseMovement)
    {
        $branchId = $request->session()->get('branch_id');
        $branch = Branch::find($branchId);
        $companyId = $branch?->company_id;

        $branches = Branch::where('company_id', $companyId)->get();

        $people = Person::whereIn('person_type', ['SUPPLIER', 'PROVIDER'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $documentTypes = DocumentType::where('movement_type_id', 1)->get();
        $units = Unit::all();
        $defaultTaxRate = 18.00;
        
        $purchaseMovement->load('movement', 'details');
        
        $purchase = $purchaseMovement->movement; 
        
        $products = ProductBranch::where('branch_id', $branchId)
            ->with(['product', 'product.baseUnit'])
            ->where('status', 'E')
            ->get()
            ->map(function ($pb) {
                return (object) [
                    'id' => $pb->product_id,
                    'code' => $pb->product->code ?? '',
                    'description' => $pb->product->description ?? '',
                    'unit_sale' => $pb->product->base_unit_id ?? 0,
                    'unit_name' => $pb->product->baseUnit->description ?? $pb->product->baseUnit->name ?? '',
                    'price' => $pb->price ?? 0,
                ];
            });

        // Mandar los modelos de pago
        $cards = Card::all(); 
        $paymentGateways = PaymentGateways::all(); 
        $digitalWallets = DigitalWallet::all(); 
        $banks = Bank::all(); 

        return view('purchases._form', compact(
            'purchaseMovement', 'purchase', 'people', 'documentTypes', 'units', 
            'products', 'defaultTaxRate', 'branches', 'branchId',
            'cards', 'paymentGateways', 'digitalWallets', 'banks'
        ));
    }

    public function destroy(PurchaseMovement $purchaseMovement)
    {
        $purchaseMovement->delete();
        return redirect()->route('purchase.index')
            ->with('status', 'Compra eliminada correctamente.');
    }
}