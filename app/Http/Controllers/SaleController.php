<?php

namespace App\Http\Controllers;

use App\Exports\AnticipatedExport;
use App\Exports\DeliveryExport;
use App\Exports\SalesExport;
use App\Models\Area;
use App\Models\Category;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $pms = PaymentMethod::where('deleted', 0)->get();
        // Solo categorías que tengan productos pertenecientes a la sale_line "RESTAURANTE"
        $pc = Category::where('deleted', 0)->get();
        return view('sales.index');
    }

    public function restauranteAnt()
    {
        $mesas = Table::where('deleted', 0)->get();
        $products = Product::where('deleted', 0)->get();
        $pms = PaymentMethod::where('deleted', 0)->get();
        $employees = Employee::where('deleted', 0)->get();
        $areas = Area::where('deleted', 0)->get();
        $mesa_directa = Table::whereRaw('UPPER(name) = ?', ['DELIVERY'])
        ->where('deleted', 1)
        ->first();
        $pc = Category::where('deleted', 0)
            ->whereHas('products', function ($q) {
                $q->where('deleted', 0);
            })->get();
        return view('sales.restaurante', compact(
            'pms',
            'pc',
            'mesas',
            'products',
            'employees',
            'areas',
            'mesa_directa'
        ));
    }

    //Pago de la mesa de restaurante
    public function restaurantePago(Request $request,$mesaId)
    {
        $account_number = $request->account_number; //se pasa el ?account_number= para cobrar mesa segun cuenta, si no tiene cuenta que no filtre por cuenta

        $mesa = Table::find($mesaId);

        // Obtener o crear la orden de la mesa
        $response = $this->abrirMesa($mesaId);
        $responseData = $response->getData();
        $orderId = $responseData->order_id ?? null;

        $products = collect();
        $delivery = null; 

        if ($orderId) {
            $order = Order::with(['order_details.product'])->find($orderId);

            if ($order && $order->order_details) {
                // Mapear los productos
                $products = OrderDetail::where('order_id',$orderId)
                ->when($account_number, function($q) use ($account_number){
                    $q->where('account_number', $account_number);
                })
                ->with('product')    // asegurar que product esté disponible
                ->get()   
                ->map(function ($detail) {
                    return [
                        'id' => $detail->product_id,
                        'name' => $detail->product->name ?? 'Producto',
                        'description' => $detail->product->description ?? '',
                        'quantity' => $detail->quantity ?? 0,
                        'unit_price' => $detail->product_price ?? 0,
                        'discount' => $detail->discount_amount ?? 0,
                        'subtotal' => (($detail->quantity ?? 0) * ($detail->product_price ?? 0)) - ($detail->discount_amount ?? 0),
                    ];
                });
            }
            if ($order->delivery) {
                $delivery = is_array($order->delivery)
                    ? $order->delivery
                    : json_decode($order->delivery, true);
            }
        }

        $employeeId = request()->query('employeeId');
        $employeeName = request()->query('employeeName');

        $area_id = $mesa && $mesa->area ? $mesa->area->id : null;

        $categories = Category::where('deleted', 0)
            ->with(['products' => function ($q) {
                $q->where('deleted', 0);
            }])->get();

        $paymentMethods = PaymentMethod::where('deleted', 0)->get();

        $porcentaje = 0.18;
        $totalPagar = floatval($products->sum('subtotal'));
        $subtotal = round($totalPagar / (1 + $porcentaje), 2);
        $igv = round($totalPagar - $subtotal, 2);

        $pagos = [];

        return view('sales.restaurante_pago', compact(
            'totalPagar',
            'subtotal',
            'igv',
            'categories',
            'products',
            'area_id',
            'mesa',
            'orderId',
            'employeeId',
            'employeeName',
            'paymentMethods',
            'pagos',
            'delivery'
        ));
    }

    //Completar venta de orden
    public function completarVentaRestaurante(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'order_id' => 'required|exists:orders,id',
                'mesa_id' => 'required|exists:tables,id',
                'voucher_type' => 'required|in:Boleta,Factura,Ticket',
                'document' => 'nullable|string',
                'client' => 'nullable|string',
                'pagos' => 'required|array|min:1',
                'pagos.*.metodo_id' => 'required|exists:payment_methods,id',
                'pagos.*.monto' => 'required|numeric|min:0.01',
                'tip' => 'nullable|numeric|min:0.01',
                'account_number' => 'nullable|integer',
            ]);

            $order = Order::with('order_details.product')->findOrFail($validated['order_id']);
            $account_number = $request->account_number ?? 1;
            $details = OrderDetail::where('order_id',$validated['order_id'])
                ->where('account_number',$account_number)
                ->get();

            $porcentaje = 0.18;
            // Calcular total considerando descuentos por línea (discount_amount)
            $totalBruto = $details->sum(fn($d) => ($d->quantity ?? 0) * ($d->product_price ?? 0));
            $totalDescuentos = $details->sum(fn($d) => floatval($d->discount_amount ?? 0));
            $total = $totalBruto - $totalDescuentos;
            $subtotal = round($total / (1 + $porcentaje), 2);
            $igv = round($total - $subtotal, 2);

            $totalPagado = collect($validated['pagos'])->sum('monto');
            if ($totalPagado < $total) {
                return response()->json([
                    'success' => false,
                    'message' => 'El monto pagado no cubre el total ('.$total.') de la cuenta '.$account_number
                ], 422);
            }

            // Crear cliente si tiene documento
            $cliente_id = null;
            if (!empty($validated['document'])) {
                $cliente = Client::firstOrCreate(
                    ['document' => $validated['document']],
                    ['business_name' => $validated['client'] ?? 'Cliente']
                );
                $cliente_id = $cliente->id;
            }

            $deliveryData = is_array($order->delivery)
                ? $order->delivery
                : (json_decode($order->delivery, true) ?? []);

            // Crear la venta con datos del delivery incluidos
            $sale = Sale::create([
                'type_sale'     => 1, 
                'type_status'   => isset($deliveryData['is_delivery']) && $deliveryData['is_delivery'] ? 1 : 0,
                'user_id'       => auth()->id(),
                'employee_id'   => $order->employee_id ?? null,
                'voucher_type'  => $validated['voucher_type'],
                'total'         => $total,
                'date'          => now(),
                'number_persons'=> $order->number_persons == 0 ? 1 : $order->number_persons,
                'client_id'     => $cliente_id,
                'client_name'   => $validated['client'] ?? ($deliveryData['client_name'] ?? 'Varios'),
                'table_id'      => $validated['mesa_id'],
                'shift'         => auth()->user()->shift ?? 1,
                'tip'           => $request->tip ?? 0,
                'status'        => 0, 
                'deleted'       => 0,

                'phone'         => $deliveryData['phone'] ?? null,
                'address'       => $deliveryData['address'] ?? null,
                'reference'     => $deliveryData['reference'] ?? null,
                'observation'   => $deliveryData['observation'] ?? null,
                'delivery_date' => $deliveryData['delivery_date'] ?? null,
                'delivery_hour' => $deliveryData['delivery_hour'] ?? null,
                'foto'          => $deliveryData['photo_path'] ?? null,
            ]);

            // Crear los detalles de venta
            foreach ($details as $detail) {
                $lineDiscount = floatval($detail->discount_amount ?? 0);
                $lineSubtotal = ($detail->quantity * $detail->product_price) - $lineDiscount;
                SaleDetail::create([
                    'product_id' => $detail->product_id,
                    'sale_id'    => $sale->id,
                    'quantity'   => $detail->quantity,
                    'unit_price' => $detail->product_price,
                    'subtotal'   => $lineSubtotal,
                    'discount_amount' => $detail->discount_amount ?? 0,
                    'discount_reason' => $detail->discount_reason ?? null,
                    'estado'     => 0,
                ]);

                $this->reducirStockProducto($detail->product_id, $detail->quantity);
            }

            // Crear pagos
            foreach ($validated['pagos'] as $pago) {
                Payment::create([
                    'sale_id'            => $sale->id,
                    'payment_method_id'  => $pago['metodo_id'],
                    'user_id'            => auth()->id(),
                    'shift'              => auth()->user()->shift ?? 1,
                    'date'               => now(),
                    'subtotal'           => $pago['monto'],
                    'deleted'            => 0,
                ]);
            }

            // Generar comprobante (Boleta o Factura)
            $pdf_url = null;
            if (in_array($validated['voucher_type'], ['Boleta', 'Factura'])) {
                $sunatResponse = $this->sendInvoice($sale);
                if ($sunatResponse['status']) {
                    $pdf_url = $sunatResponse['pdf'];
                }
            } else {
                $sale->update(['number' => $this->generarNumeroTicket()]);
            }

            //borrar orderdetails procesados
            $processedIds = $details->pluck('id')->filter()->values()->all();
            if (!empty($processedIds)) {
                OrderDetail::whereIn('id', $processedIds)->delete();
            }
            $remaining = OrderDetail::where('order_id', $validated['order_id'])->count();
            
            //si no queda ninguno (se ha cobrado todo) cerrar la mesa
            if ($remaining === 0) {
                // Para mesa de delivery, asegurarse de cerrar completamente
                $mesa = Table::find($validated['mesa_id']);
                if ($mesa && strtoupper($mesa->name) === 'DELIVERY') {
                    // Forzar eliminación de la orden
                    Order::where('id', $validated['order_id'])->delete();
                    $mesa->update([
                        'status' => 'Libre',
                        'opened_at' => null,
                    ]);
                } else {
                    $this->cerrarMesa($validated['mesa_id']);
                }
            }

            DB::commit();

            return response()->json([
                'success'  => true,
                'message'  => 'Venta completada exitosamente',
                'sale_id'  => $sale->id,
                'pdf_url'  => $pdf_url,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al completar venta restaurante: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al completar la venta: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function create()
    {
        //
        $products = Product::where('deleted', 0)->get();
        $employees = Employee::where('deleted', 0)->get();
        $pms = PaymentMethod::where('deleted', 0)->get();
        // Solo categorías que tengan productos pertenecientes a la sale_line "RESTAURANTE"
        $pc = Category::where('deleted', 0)
            ->whereHas('sale_line', function ($q) {
                $q->where('deleted', 0)
                    ->whereRaw('LOWER(name) = ?', ['Ropa']);
            })
            ->whereHas('products', function ($q) {
                $q->where('deleted', 0);
            })->get();
        return view('sales.create', compact('pms', 'pc', 'products', 'employees'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validaciones básicas antes de la transacción
        $validator = Validator::make($request->all(), [
            'type_sale' => 'required|numeric',
            'type_status' => 'required|numeric',
            'voucher_type' => 'required|string|in:Boleta,Factura,Ticket',
            'document'     => 'nullable|numeric',
            'client'       => 'nullable|string',
            'telefono'     => 'nullable|string|max:15',
            'sede_recojo'  => 'nullable|integer|exists:headquarters,id',
            'total'        => 'required|numeric',
            'products'     => 'required',
            'monto'        => 'required|array',
            'fecha_entrega' => 'nullable|date',
            'direccion'    => 'nullable|string',
            'referencia'   => 'nullable|string',
            'observacion'  => 'nullable|string',
            'hora_entrega' => 'nullable|string',
            'employee_id' => 'nullable|int',
            'discount' => 'required|int',
            'discount_reason' => 'nullable|string',
            'table_id' => 'nullable|int',
            'status' => 'required|numeric',
        ]);


        // Validaciones condicionales
        $validator->sometimes('document', 'nullable|digits:8', function ($r) {
            return $r->voucher_type === 'Boleta';
        });
        $validator->sometimes('document', 'nullable|digits:11', function ($r) {
            return $r->voucher_type === 'Factura';
        });
        $validator->sometimes('client', 'required|string', function ($r) {
            return $r->voucher_type === 'Factura';
        });
        $validator->sometimes('direccion', 'nullable|string', function ($r) {
            return $r->voucher_type === 'Factura';
        });

        if ($validator->fails()) {
            // Solo log de error para validación fallida
            Log::error('Validación fallida en SaleController@store: ' . $validator->errors()->first());

            return response()->json([
                'status' => false,
                'errors'  => $validator->errors()->messages()
            ], 422);
        }

        try {
            $response = DB::transaction(function () use ($request) {

                $documento = $request->document ?? null;
                $cliente_id = null;
                $cliente_nombre = "varios";
                $foto = $request->file('foto');

                if ($documento) {
                    $clienteEncontrado = Client::where('document', $documento)->first();

                    if ($clienteEncontrado) {
                        $cliente_id = $clienteEncontrado->id;
                        $cliente_nombre = $clienteEncontrado->nombre;
                    } else {
                        $nuevoCliente = Client::create([
                            'document' => $documento,
                            'business_name' => $request->client,
                            'estado' => 0
                        ]);
                        $cliente_id = $nuevoCliente->id;
                        $cliente_nombre = $nuevoCliente->nombre;
                    }
                } else {
                    // Si no hay documento pero el usuario ingresó un nombre, usar ese nombre
                    if ($request->client && trim($request->client) !== '') {
                        $cliente_nombre = $request->client;
                    }
                }

                $type_sale = $request->type_sale ?? null;
                $type_status = $request->type_status ?? null;
                $user_id   = auth()->user()->id; // Usar el usuario autenticado
                $status = $request->status ?? null;
                $fecha_entrega = $request->fecha_entrega ?? null;
                $direccion = $request->direccion ?? null;
                $referencia = $request->referencia ?? null;
                $observacion = $request->observacion ?? null;
                $telefono = $request->telefono ?? null;
                $employee_id = $request->employee_id ?? null;
                $hora_entrega = $request->hora_entrega ?? null;
                $discount = $request->discount;
                $discount_reason = $request->discount_reason ?? null;
                $total = floatval($request->total);
                $fecha = now();
                $sede_id = auth()->user()->sede_id ?? null;
                $turno = auth()->user()->shift;
                // Normalizar products: aceptar JSON o inputs con keys tipo products[1][cantidad]
                $rawProducts = $request->input('products');
                if (is_string($rawProducts)) {
                    $products = json_decode($rawProducts, true) ?? [];
                } elseif (is_array($rawProducts)) {
                    // Reindex numeric keys (form inputs often come as associative with numeric keys)
                    $products = array_values($rawProducts);
                } else {
                    $products = [];
                }

                $table_id = $request->table_id;

                //venta directa con mesa null
                if ($table_id) {
                    $table = Table::find($table_id);

                    if ($table && strtoupper($table->name) === 'DIRECTA') {
                        $table_id = null;
                    }
                }


                // Sanear y unificar claves por cada producto
                $cleanProducts = [];
                foreach ($products as $p) {
                    if (is_array($p) || is_object($p)) {
                        $id = isset($p['id']) ? $p['id'] : (isset($p->id) ? $p->id : null);
                        $cantidad = isset($p['cantidad']) ? $p['cantidad'] : (isset($p->quantity) ? $p->quantity : (isset($p->cantidad) ? $p->cantidad : 0));
                        $precio = isset($p['precio']) ? $p['precio'] : (isset($p->price) ? $p->price : (isset($p->precio) ? $p->precio : 0));
                        if ($id) {
                            $cleanProducts[] = [
                                'id' => $id,
                                'cantidad' => $cantidad,
                                'precio' => $precio,
                            ];
                        }
                    }
                }
                $products = $cleanProducts;

                $venta = Sale::create([
                    'type_sale'      => $type_sale,
                    'type_status'    => $type_status,
                    'user_id'        => $user_id,
                    'voucher_type'   => $request->voucher_type,
                    'total'          => $total,
                    'date'           => $fecha,
                    'client_id'      => $cliente_id,
                    'client_name'    => $cliente_nombre,
                    'phone'          => $telefono,
                    'delivery_hour'  => $hora_entrega,
                    'delivery_date'  => $fecha_entrega,
                    'address'      => $direccion,
                    'reference'      => $referencia,
                    'observation'    => $observacion,
                    'employee_id'    => $employee_id,
                    'table_id'    => $table_id,
                    'shift'    => $turno,
                    'discount'    => $discount,
                    'discount_reason'    => $discount_reason,
                    'status'         => $status,
                    'deleted'        => 0,
                ]);

                $sale_id = $venta->id;

                if ($foto != null) {
                    $path = $this->guardarFoto($foto, $sale_id);
                }

                foreach ($request->monto as $metodo_id => $monto) {
                    if ($monto !== null && $monto !== '' && floatval($monto) != 0) {
                        Payment::create([
                            'sale_id'           => $venta->id,
                            'payment_method_id' => $metodo_id,
                            'user_id'           => auth()->user()->id,
                            'shift'             => auth()->user()->shift,
                            'date' => now(),
                            'subtotal'          => floatval($monto),
                            'deleted'           => 0,
                        ]);
                    }
                }
                // Guardar detalles de la venta (todos los productos como individuales)
                foreach ($products as $product) {
                    $id = $product['id'];
                    $cantidad = floatval($product['cantidad']);
                    $precio = floatval($product['precio']);
                    $subtotal = $cantidad * $precio;
                    SaleDetail::create([
                        'product_id' => $id,
                        'sale_id'    => $venta->id,
                        'quantity'   => $cantidad,
                        'unit_price' => $precio,
                        'subtotal'   => $subtotal,
                        'estado'     => 0,
                    ]);
                }

                // REDUCIR STOCK: Solo para ventas normales (type_status = 0), no para anticipadas
                if ($type_status == 0) {
                    foreach ($products as $product) {
                        $this->reducirStockProducto($product['id'], floatval($product['cantidad']), $sede_id);
                    }
                }

                // Si es Boleta o Factura, enviamos a SUNAT
                $pdf_url = null;
                $detraction_text = null;
                // En tu método store, después de crear la venta:
                if (in_array($request->voucher_type, ['Boleta', 'Factura'])) {
                    // $sunatResponse = $this->sendInvoice($venta);

                    // if (!$sunatResponse['status']) {
                    //     throw new \Exception('Error al enviar a SUNAT: ' . $sunatResponse['console']);
                    // }

                    // $pdf_url = $sunatResponse['pdf'];
                    // $detraction_text = $sunatResponse['detraction_text'];
                } elseif ($request->voucher_type === 'Ticket') {
                    // Generar número correlativo interno para Ticket
                    $numeroInterno = $this->generarNumeroTicket();
                    $venta->update(['number' => $numeroInterno]);

                    // No hay PDF ni texto de detracción para Ticket
                    $pdf_url = null;
                    $detraction_text = null;
                }

                // ...dentro del método store...
                $metodos_pago = [];
                foreach ($request->monto as $metodo_id => $monto) {
                    if ($monto !== null && $monto !== '' && floatval($monto) != 0) {
                        $metodo = PaymentMethod::find($metodo_id);
                        $nombreMetodo = $metodo ? $metodo->nombre : 'Método';
                        $metodos_pago[] = [
                            'nombre' => $nombreMetodo,
                            'monto'  => floatval($monto),
                        ];
                    }
                }

                // Cargar la relación del usuario para la respuesta
                $venta->load('usuario');

                // Respuesta exitosa
                return response()->json([
                    'status'  => true,
                    'message' => 'Venta registrada correctamente.',
                    'sale_id' => $venta->id,
                    'venta'   => [
                        'id'            => $venta->id,
                        'user_id'       => $venta->user_id,
                        'usuario'       => $venta->usuario, // Incluir toda la información del usuario
                        'number'        => $venta->number,
                        'cliente'       => $cliente_nombre,
                        'documento'     => $documento ?? '-',
                        'fecha'         => $fecha,
                        'fecha_entrega' => $fecha_entrega ?? '-',
                        'direccion'     => $direccion ?? '-',
                        'productos'     => $products,
                        'total'         => $total,
                        'metodos_pago'  => $metodos_pago, // <-- aquí el array correcto
                        'pagado'        => collect($request->monto)->sum(),
                    ],
                    'pdf'            => $pdf_url,
                    'detraction_text' => $detraction_text,
                ], 201);
            });

            return $response;
        } catch (\Throwable $e) {
            Log::error('❌ Error en store(): ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error'  => 'Error al registrar venta: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function generarNumeroTicket()
    {
        // Usa transacción para evitar conflictos en concurrencia
        return DB::transaction(function () {
            // Bloquea la fila para actualizar el número
            $registro = DB::table('correlativos')->where('tipo', 'Ticket')->lockForUpdate()->first();

            if (!$registro) {
                // Si no existe registro, crea uno
                DB::table('correlativos')->insert([
                    'tipo' => 'Ticket',
                    'numero' => 1
                ]);
                return 'TICKET-00000001';
            }

            $nuevoNumero = $registro->numero + 1;

            DB::table('correlativos')
                ->where('tipo', 'Ticket')
                ->update(['numero' => $nuevoNumero]);

            // Formatea el número con ceros a la izquierda y prefijo
            return 'TICKET-' . str_pad($nuevoNumero, 8, '0', STR_PAD_LEFT);
        });
    }


    public function sendInvoice(Sale $sale)
    {
        $url = config('apisunat.url') . '/personas/lastDocument';
        $personaId = config('apisunat.id');
        $personaToken = config('apisunat.token.prod');

        $catalog = [
            'Boleta' => [
                'InvoiceTypeCode' => '03',
                'PartyIdentification' => '1',
                'serie' => 'B001'
            ],
            'Factura' => [
                'InvoiceTypeCode' => '01',
                'PartyIdentification' => '6',
                'serie' => 'F001'
            ]
        ];

        if (!isset($catalog[$sale->voucher_type])) {
            return [
                'status' => false,
                'console' => 'Tipo de comprobante no soportado para envío a SUNAT.'
            ];
        }

        $cat = $catalog[$sale->voucher_type];

        // Datos del emisor (tu empresa)
        $ruc = config('ruc.number');
        $name = 'LA FINKA SAN IGNACIO E.I.R.L.';
        $address = 'CAL. LAS VIOLETAS NRO. 196  BANCARIOS CHICLAYO CHICLAYO LAMBAYEQUES';

        $client = optional($sale->client);

        $type = $cat['InvoiceTypeCode'];
        $serie = $cat['serie'];

        // Consultar último correlativo SUNAT
        $respUltimo = Http::post($url, [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'type' => $type,
            'serie' => $serie
        ]);

        if ($respUltimo->failed()) {
            return [
                'status' => false,
                'console' => 'Error al consultar último correlativo: ' . $respUltimo->body()
            ];
        }

        $responseObj = $respUltimo->object();
        $number = trim($responseObj->suggestedNumber ?? '');

        if (!$number || !is_numeric($number)) {
            return [
                'status' => false,
                'console' => 'No se recibió correlativo válido desde SUNAT.'
            ];
        }

        $number = str_pad($number, 8, "0", STR_PAD_LEFT);

        // Cálculo de montos
        $total = round(floatval($sale->total), 2);
        $subtotal = round($total / 1.18, 2); // IGV 18% en Perú
        $igv = round($total - $subtotal, 2);

        $data = [
            'personaId' => $personaId,
            'personaToken' => $personaToken,
            'fileName' => "{$ruc}-{$type}-{$serie}-{$number}",
            'documentBody' => [
                'cbc:UBLVersionID' => ['_text' => '2.1'],
                'cbc:CustomizationID' => ['_text' => '2.0'],
                'cbc:ID' => ['_text' => "{$serie}-{$number}"],
                'cbc:IssueDate' => [
                    '_text' => now()->format('Y-m-d')
                ],
                'cbc:IssueTime' => [
                    '_text' => now()->format('H:i:s')
                ],
                'cbc:InvoiceTypeCode' => [
                    '_attributes' => ['listID' => '0101'],
                    '_text' => $type
                ],
                'cbc:Note' => [],
                'cbc:DocumentCurrencyCode' => ['_text' => 'PEN'],
                'cac:AccountingSupplierParty' => [
                    'cac:Party' => [
                        'cac:PartyIdentification' => [
                            'cbc:ID' => [
                                '_attributes' => ['schemeID' => '6'],
                                '_text' => $ruc
                            ]
                        ],
                        'cac:PartyLegalEntity' => [
                            'cbc:RegistrationName' => ['_text' => $name],
                            'cac:RegistrationAddress' => [
                                'cbc:AddressTypeCode' => ['_text' => '0000'],
                                'cac:AddressLine' => ['cbc:Line' => ['_text' => $address]]
                            ]
                        ]
                    ]
                ],
                'cac:AccountingCustomerParty' => [
                    'cac:Party' => [
                        'cac:PartyIdentification' => [
                            'cbc:ID' => [
                                '_attributes' => ['schemeID' => $cat['PartyIdentification']],
                                '_text' => $client->document ?? '00000000'
                            ]
                        ],
                        'cac:PartyLegalEntity' => [
                            'cbc:RegistrationName' => ['_text' => $client->business_name ?? 'CLIENTE VARIOS']
                        ]
                    ]
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $igv
                    ],
                    'cac:TaxSubtotal' => [
                        'cbc:TaxableAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $subtotal
                        ],
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igv
                        ],
                        'cac:TaxCategory' => [
                            'cac:TaxScheme' => [
                                'cbc:ID' => ['_text' => '1000'],
                                'cbc:Name' => ['_text' => 'IGV'],
                                'cbc:TaxTypeCode' => ['_text' => 'VAT']
                            ]
                        ]
                    ]
                ],
                'cac:LegalMonetaryTotal' => [
                    'cbc:LineExtensionAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotal
                    ],
                    'cbc:TaxInclusiveAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $total
                    ],
                    'cbc:AllowanceTotalAmount' => [],
                    'cbc:PayableAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $total
                    ]
                ],
                'cac:InvoiceLine' => [],
            ]
        ];

        // Manejo de términos de pago para Facturas
        if ($sale->voucher_type == 'Factura') {
            // Siempre establecer como "Contado"
            $data['documentBody']['cac:PaymentTerms'] = [[
                "cbc:ID" => ["_text" => "FormaPago"],
                "cbc:PaymentMeansID" => ["_text" => "Contado"]
            ]];
        }

        // Detracción para factura > S/700
        $detraction_text = '';
        if ($sale->voucher_type == 'Factura' && $total >= 700) {
            $detraction = round($total * 0.12, 2);
            $detraction_text = "Detracción: Nro. Cta. Banco de la Nación: 00-250-053223, Porcentaje: 12.00, Monto: S/{$detraction}";

            $data['documentBody']['cbc:InvoiceTypeCode']['_attributes']['listID'] = '1001';
            $data['documentBody']['cbc:Note'][] = [
                '_text' => 'OPERACIÓN SUJETA A DETRACCIÓN',
                '_attributes' => ['languageLocaleID' => '2006']
            ];
            $data['documentBody']['cac:PaymentTerms'][] = [
                'cbc:ID' => ['_text' => 'Detraccion'],
                'cbc:PaymentMeansID' => ['_text' => '022'],
                'cbc:PaymentPercent' => ['_text' => '12'],
                'cbc:Amount' => [
                    '_attributes' => ['currencyID' => 'PEN'],
                    '_text' => $detraction
                ]
            ];
            $data['documentBody']['cac:PaymentMeans'][] = [
                'cbc:ID' => ['_text' => 'Detraccion'],
                'cbc:PaymentMeansCode' => ['_text' => '001'],
                'cac:PayeeFinancialAccount' => [
                    'cbc:ID' => ['_text' => '00250053223']
                ]
            ];
        }

        // Detalle de productos (InvoiceLine) - Adaptado a tu estructura
        $details = $sale->details()->where('unit_price', '>', 0)->get();

        if ($details->isEmpty()) {
            // Si no hay detalles específicos, crear una línea general
            $data['documentBody']['cac:InvoiceLine'][] = [
                'cbc:ID' => ['_text' => 1],
                'cbc:InvoicedQuantity' => [
                    '_attributes' => ['unitCode' => 'NIU'],
                    '_text' => 1
                ],
                'cbc:LineExtensionAmount' => [
                    '_attributes' => ['currencyID' => 'PEN'],
                    '_text' => $subtotal
                ],
                'cac:PricingReference' => [
                    'cac:AlternativeConditionPrice' => [
                        'cbc:PriceAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $total
                        ],
                        'cbc:PriceTypeCode' => ['_text' => '01']
                    ]
                ],
                'cac:TaxTotal' => [
                    'cbc:TaxAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $igv
                    ],
                    'cac:TaxSubtotal' => [
                        'cbc:TaxableAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $subtotal
                        ],
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igv
                        ],
                        'cac:TaxCategory' => [
                            'cbc:Percent' => ['_text' => 18],
                            'cbc:TaxExemptionReasonCode' => ['_text' => '10'],
                            'cac:TaxScheme' => [
                                'cbc:ID' => ['_text' => '1000'],
                                'cbc:Name' => ['_text' => 'IGV'],
                                'cbc:TaxTypeCode' => ['_text' => 'VAT']
                            ]
                        ]
                    ]
                ],
                'cac:Item' => [
                    'cbc:Description' => ['_text' => 'Venta general']
                ],
                'cac:Price' => [
                    'cbc:PriceAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotal
                    ]
                ]
            ];
        } else {
            // Usar los detalles específicos de la venta
            $i = 1;
            foreach ($details as $detail) {
                $price = round($detail->unit_price, 2);
                $cost = round($price / 1.18, 2); // Precio sin IGV
                $quantity = $detail->quantity;
                $totalLine = round($price * $quantity, 2);
                $subtotalLine = round($totalLine / 1.18, 2);
                $igvLine = round($totalLine - $subtotalLine, 2);

                $data['documentBody']['cac:InvoiceLine'][] = [
                    'cbc:ID' => ['_text' => $i],
                    'cbc:InvoicedQuantity' => [
                        '_attributes' => ['unitCode' => 'NIU'],
                        '_text' => $quantity
                    ],
                    'cbc:LineExtensionAmount' => [
                        '_attributes' => ['currencyID' => 'PEN'],
                        '_text' => $subtotalLine
                    ],
                    'cac:PricingReference' => [
                        'cac:AlternativeConditionPrice' => [
                            'cbc:PriceAmount' => [
                                '_attributes' => ['currencyID' => 'PEN'],
                                '_text' => $price
                            ],
                            'cbc:PriceTypeCode' => ['_text' => '01']
                        ]
                    ],
                    'cac:TaxTotal' => [
                        'cbc:TaxAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $igvLine
                        ],
                        'cac:TaxSubtotal' => [
                            [
                                'cbc:TaxableAmount' => [
                                    '_attributes' => ['currencyID' => 'PEN'],
                                    '_text' => $subtotalLine
                                ],
                                'cbc:TaxAmount' => [
                                    '_attributes' => ['currencyID' => 'PEN'],
                                    '_text' => $igvLine
                                ],
                                'cac:TaxCategory' => [
                                    'cbc:Percent' => ['_text' => 18],
                                    'cbc:TaxExemptionReasonCode' => ['_text' => '10'],
                                    'cac:TaxScheme' => [
                                        'cbc:ID' => ['_text' => '1000'],
                                        'cbc:Name' => ['_text' => 'IGV'],
                                        'cbc:TaxTypeCode' => ['_text' => 'VAT']
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'cac:Item' => [
                        'cbc:Description' => ['_text' => optional($detail->product)->name ?? 'Producto']
                    ],
                    'cac:Price' => [
                        'cbc:PriceAmount' => [
                            '_attributes' => ['currencyID' => 'PEN'],
                            '_text' => $cost
                        ]
                    ]
                ];

                $i++;
            }
        }

        // Enviar a SUNAT
        $urlSend = config('apisunat.url') . '/personas/v1/sendBill';
        $source = Http::post($urlSend, $data);
        $response = $source->object();

        if ($source->failed()) {
            return [
                'status' => false,
                'console' => $response->error->message ?? 'Error desconocido al enviar a SUNAT'
            ];
        }

        $documentId = $response->documentId;
        $filename = "{$ruc}-{$type}-{$serie}-{$number}";

        $url = config('apisunat.url') . "/documents/{$documentId}/getPDF/ticket80mm/{$filename}.pdf";

        // Actualizar la venta con los datos de SUNAT
        $sale->update([
            'voucher_id' => $documentId,
            'voucher_file' => $filename . '.pdf',
            'number' => "{$serie}-{$number}"
        ]);

        return [
            'status' => true,
            'pdf' => $url,
            'detraction_text' => $detraction_text
        ];
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function showRestauranteOrders($mesaId)
    {
        $mesa = Table::find($mesaId);

        $area_id = $mesa->area ? $mesa->area->id : null; 

        $products = Product::where('deleted', 0)->get();
        $categories = Category::where('deleted', 0)
            ->with(['products' => function ($q) {
                $q->where('deleted', 0);
            }])->get();

        $response = $this->abrirMesa($mesaId);
        $responseData = $response->getData();
        $orderId = $responseData->order_id ?? null;

        if ($orderId) {
            $order = Order::with(['employee'])->find($orderId);

            
            $employeeId = request()->query('employeeId');
            $employeeName = request()->query('employeeName');
            $cantidadPersonas = request()->query('cantidadPersonas');

            // Actualizar cantidad de personas y empleado solo si vienen en la URL
            $updated = false;
            if ($cantidadPersonas !== null && $cantidadPersonas !== '') {
                $order->number_persons = (int) $cantidadPersonas;
                $updated = true;
            }

            if ($employeeId !== null && $employeeId !== '') {
                // Si el parámetro viene como número, validar que existe
                if (is_numeric($employeeId)) {
                    $employee = Employee::find((int) $employeeId);
                    if ($employee) {
                        $order->employee_id = (int) $employeeId;
                        $updated = true;
                    }
                }
            }

            if ($updated) {
                $order->save();
            }
        }

        return view('sales.table', compact('categories', 'products', 'area_id', 'mesa', 'order'));
    }

    public function updateCantidadPersonas(Request $request)
    {
        $request->validate([
            'mesa_id' => 'required|exists:tables,id',
            'cantidad_personas' => 'required|integer|min:1',
        ]);

        $mesa = Table::find($request->mesa_id);
        if (!$mesa) {
            return response()->json(['success' => false, 'message' => 'Mesa no encontrada.'], 404);
        }

        $order = Order::where('table_id', $mesa->id)->first();
        if ($order) {
            $order->number_persons = $request->cantidad_personas;
            $order->save();
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada.'], 404);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function historic(Request $request)
    {
        $start_date = $request->input('start_date');
        $end_date = $request->input('end_date');
        $numero_comprobante = $request->input('number');
        $client_name = $request->input('client_name');
        $client_id = $request->input('client_id');
        $voucher_type = $request->input('voucher_type');
        $payment_method_id = $request->input('payment_method_id');
        $type_sale = $request->input('type_sale');

        $client = Client::find($client_id);
        if ($client) {
            // Agrega el nombre al request usando merge
            $request->merge(['client_name' => $client->business_name ? $client->business_name : $client->contact_name]);
        }


        $paymentMethod = PaymentMethod::where('deleted', 0)->get();

        $consulta = Sale::query()
            ->where('deleted', 0)
            ->when($start_date, fn($q) => $q->whereDate('date', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('date', '<=', $end_date))
            ->when($type_sale, fn($q) => $q->where('type_sale', $type_sale))
            ->when($numero_comprobante, fn($q) => $q->where('number', 'like', "%$numero_comprobante%"))
            ->when($client_id, fn($q) => $q->where('client_id', $client_id))
            ->when($client_name, fn($q) => $q->where('client_name', 'like', "%$client_name%"))
            ->when($voucher_type, fn($q) => $q->where('voucher_type', $voucher_type))
            ->when($payment_method_id, function ($q) use ($payment_method_id) {
                $q->whereHas('payments', fn($q2) => $q2->where('payment_method_id', $payment_method_id));
            })
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc');

        $total = $consulta->sum('total');

        $total_pagos = Payment::query()
            ->where('deleted', 0)
            ->when($start_date, fn($q) => $q->whereDate('date', '>=', $start_date))
            ->when($end_date, fn($q) => $q->whereDate('date', '<=', $end_date))
            ->when($payment_method_id, fn($q) => $q->where('payment_method_id', $payment_method_id))
            ->whereHas('sale', function ($q) use ($numero_comprobante, $client_id, $voucher_type, $type_sale) {
                $q->when($numero_comprobante, fn($q2) => $q2->where('number', 'like', "%$numero_comprobante%"))
                    ->when($client_id, fn($q2) => $q2->where('client_id', $client_id))
                    ->when($type_sale, fn($q) => $q->where('type_sale', $type_sale))
                    ->when($voucher_type, fn($q2) => $q2->where('voucher_type', $voucher_type));
            })
            ->sum('subtotal');

        $anticipadas = $consulta->paginate(15);
        $anticipadas->appends($request->all());

        return view('sales.historic', compact(
            'anticipadas',
            'start_date',
            'end_date',
            'paymentMethod',
            'client_name',
            'client_id',
            'voucher_type',
            'type_sale',
            'total',
            'total_pagos',
            'payment_method_id'
        ));
    }
    /**
     * Export sales to Excel
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function excel(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $number = $request->number;
        $client_id = $request->client_id;
        $client_name = $request->client_name;
        // Usar client_name si existe, sino usar client_id
        $client = $client_name ?: $client_id;
        $voucher_type = $request->voucher_type;
        $payment_method_id = $request->payment_method_id;

        // Crear nombre de archivo con fechas si están presentes
        $filename = 'ventas_historico';

        if ($start_date || $end_date) {
            $filename .= '_' . ($start_date ?: 'inicio') . '_a_' . ($end_date ?: 'fin');
        }
        $filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new SalesExport(
                $start_date,
                $end_date,
                $number,
                $client,
                $voucher_type,
                $payment_method_id,
            ),
            $filename
        );
    }

    public function pdf(Request $request)
    {
        try {
            $start_date = $request->start_date;
            $end_date = $request->end_date;
            $number = $request->number;
            $client_id = $request->client_id;
            $client_name = $request->client_name;
            $voucher_type = $request->voucher_type;
            $payment_method_id = $request->payment_method_id;

            $query = Sale::with('payments.payment_method', 'client')
                ->where('deleted', 0)
                ->when($start_date, fn($query) => $query->whereDate('date', '>=', $start_date))
                ->when($end_date, fn($query) => $query->whereDate('date', '<=', $end_date))
                ->when($number, fn($query) => $query->where('number', 'like', "%$number%"))
                ->when($client_id, fn($query) => $query->where('client_id', $client_id))
                ->when($client_name, fn($query) => $query->where('client_name', 'like', "%$client_name%"))
                ->when($voucher_type, fn($query) => $query->where('voucher_type', $voucher_type))
                ->when($payment_method_id, function ($query) use ($payment_method_id) {
                    $query->whereHas('payments', fn($q2) => $q2->where('payment_method_id', $payment_method_id));
                });
            $sales = $query->get();
            $client_name = '';
            $payment_method_name = '';
            if ($payment_method_id) {
                $paymentMethod = PaymentMethod::find($payment_method_id);
                if ($paymentMethod) {
                    $payment_method_name = $paymentMethod->name;
                }
            }

            if ($request->client_id) {
                $clientObj = Client::find($request->client_id);
                if ($clientObj) {
                    $client_name = $clientObj->business_name ?: $clientObj->contact_name;
                }
            }

            $data = [
                'sales' => $sales,
                'title' => 'REPORTE DE VENTAS',
                'subtitle' => 'LISTADO DE VENTAS REGISTRADAS',
                'filters' => [
                    'start_date' => $start_date,
                    'end_date' => $end_date,
                    'number' => $number,
                    'client_name' => $client_name,
                    'voucher_type' => $voucher_type,
                    'payment_method_name' => $payment_method_name,
                ]

            ];

            $pdf = Pdf::loadView('sales.pdf.pdf_historic_sales', $data);
            $pdf->getDomPDF()->setBasePath(public_path());
            $pdf->setPaper('A4', 'portrait');
            $filename = 'reporte_ventas' . '_' . date('Y-m-d_H-i-s') . '.pdf';
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar PDF de ventas: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'error' => 'Error al generar PDF: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function anticipatedExcel(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $number = $request->number;

        // Crear nombre de archivo con fechas si están presentes
        $filename = 'ventas_por_entregar';

        if ($start_date || $end_date) {
            $filename .= '_' . ($start_date ?: 'inicio') . '_a_' . ($end_date ?: 'fin');
        }
        $filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new AnticipatedExport(
                $start_date,
                $end_date,
                $number,
            ),
            $filename
        );
    }

    /**
     * Get products by category for AJAX requests
     *
     * @param  int  $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProductsByCategory(Request $request, $categoryId)
    {
        // Opcional: filtrar por sale_line (nombre). Ej: ?sale_line=RESTAURANTE o ?sale_line=ropa
        $saleLine = $request->query('sale_line');

        $query = Product::where('category_id', $categoryId)
            ->where('deleted', 0)
            ->select('id', 'name', 'unit_price', 'quantity');

        $products = $query->get();

        return response()->json($products);
    }

    /**
     * Get all products grouped by category for AJAX requests
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllProducts(Request $request)
    {
        // Opcional: filtrar por sale_line (nombre). Ej: ?sale_line=RESTAURANTE o ?sale_line=ropa
        $saleLine = $request->query('sale_line');

        $query = Product::with('category')
            ->where('deleted', 0)
            ->select('id', 'name', 'unit_price', 'quantity', 'category_id');

        if ($saleLine) {
            $saleLineLower = strtolower($saleLine);
            $query->whereHas('sale_line', function ($q) use ($saleLineLower) {
                $q->whereRaw('LOWER(name) = ?', [$saleLineLower])->where('deleted', 0);
            });
        }

        $products = $query->get()->groupBy('category_id');

        return response()->json($products);
    }

    /**
     * Guardar foto de la venta
     *
     * @param  \Illuminate\Http\UploadedFile  $foto
     * @param  int  $saleId
     * @return string
     */
    public function guardarFoto($foto, $sale_id)
    {
        $disk = \Storage::disk('public');
        $dir = $disk->path('fotos');
        foreach (glob($dir . "/{$sale_id}.*") as $file) {
            @unlink($file);
        }

        $extension = $foto->getClientOriginalExtension();
        $filename = $sale_id . '.' . $extension;
        $path = $foto->storeAs('fotos', $filename, 'public');
        Sale::where('id', $sale_id)->update(['foto' => $path]);
        return $path;
    }

    /**
     * Reducir stock de un producto
     *
     * @param  int  $productId
     * @param  float  $quantity
     * @param  int  $sedeId
     * @return void
     */
    private function reducirStockProducto($productId, $quantity, $sedeId = null)
    {
        try {
            $product = Product::find($productId);
            if ($product) {
                // Reducir el stock general del producto
                $newStock = $product->quantity - $quantity;
                $product->update(['quantity' => max(0, $newStock)]);

                Log::info("Stock reducido para producto ID {$productId}: -{$quantity}. Stock actual: {$newStock}");
            }
        } catch (\Exception $e) {
            Log::error("Error al reducir stock del producto {$productId}: " . $e->getMessage());
        }
    }

    public function consultarSunat(Request $request)
    {
        $doc = $request->query('doc');

        if (!$doc || (strlen($doc) !== 8 && strlen($doc) !== 11)) {
            return response()->json([
                'success' => false,
                'message' => 'Documento inválido'
            ], 422);
        }

        $urlBase = config('apisunat.url');
        $personaId = config('apisunat.id');
        $personaToken = config('apisunat.token.prod');

        try {
            if (strlen($doc) === 8) {
                $url = "$urlBase/personas/$personaId/getDNI?dni=$doc&personaToken=$personaToken";
            } else {
                $url = "$urlBase/personas/$personaId/getRUC?ruc=$doc&personaToken=$personaToken";
            }

            $response = Http::get($url);

            // ✅ LOG TEMPORAL
            \Log::info('Consulta a API Sunat/Reniec', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'data' => $response->json('data')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información de SUNAT/RENIEC'
                ], $response->status());
            }
        } catch (\Exception $e) {
            // ✅ LOG ERROR
            \Log::error('Error al consultar Sunat', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error interno: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmarPedido(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $order = Order::where('id', $order_id)
                ->firstOrFail();

            // Cargar detalles no confirmados con producto y su categoría
            $not_confirmed = $order->order_details()
                ->with('product.category')
                ->where('confirmed', 0) // Solo detalles no confirmados
                ->get();

            // Marcar como confirmados los detalles previamente no confirmados
            $order->order_details()
                ->where('confirmed', 0)
                ->update(['confirmed' => 1]);

            // Preparar trabajos de impresión por categoría -> printer
            $printJobs = [];

            foreach ($not_confirmed as $detail) {
                $product = $detail->product;
                $category = $product->category ?? null;

                // Si la categoría tiene configurada una impresora, agrupar
                if ($category && !empty($category->printer)) {
                    $printerName = $category->printer;
                    if (!isset($printJobs[$printerName])) {
                        $printJobs[$printerName] = [
                            'printer' => $printerName,
                            'table' => $order->table->name ?? null,
                            'order_id' => $order->id,
                            'lines' => []
                        ];
                    }

                    $printJobs[$printerName]['lines'][] = [
                        'product_id' => $product->id ?? null,
                        'product_name' => $product->name ?? ($detail->nombre ?? 'Producto'),
                        'quantity' => $detail->quantity,
                        'price' => $detail->product_price,
                        'notes' => $detail->notes ?? ''
                    ];
                }
            }

            // Reindexar printJobs como array simple para JSON
            $printJobs = array_values($printJobs);

            // Loggear jobs de impresión para depuración
            Log::info('print_jobs generados en confirmarPedido', [
                'order_id' => $order->id,
                'print_jobs' => $printJobs
            ]);

            return response()->json([
                'success' => true,
                'status' => true,
                'table' => $order->table->name,
                'order_id' => $order->id,
                'details' => $not_confirmed->count() > 0 ? $not_confirmed : null,
                'print_jobs' => count($printJobs) ? $printJobs : null,
            ]);
        } catch (\Exception $e) {
            Log::error('Error al cerrar mesa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al confirmar pedidos.']);
        }
    }

    public function precuenta(Request $request)
    {
        try {
            $order_id = $request->order_id;
            $order = Order::with(['employee','table'])->where('id', $order_id)
                ->firstOrFail();

            $details = $order->order_details()
                ->with('product')
                ->get();

            $subtotal = $details->sum(function ($d) {
                return $d->product_price * $d->quantity - $d->discount_amount;
            });

            return response()->json([
                'success' => true,
                'status' => true,
                'table' => $order->table->name,
                'order_id' => $order->id,
                'order' => $order,
                'subtotal' => $subtotal,
                'details' => $details->count() > 0 ? $details : null
            ]);
        } catch (\Exception $e) {
            Log::error('Error al generar precuenta: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al generar precuenta.']);
        }
    }

    public function abrirMesa($id)
    {
        $mesa = Table::with(['order.order_details.product'])->findOrFail($id);

        // Para mesa de DELIVERY, siempre limpiar órdenes anteriores completadas
        if (strtoupper($mesa->name) === 'DELIVERY') {
            // Si existe una orden anterior con todos sus items ya vendidos, limpiarla
            if ($mesa->order) {
                $orderDetails = OrderDetail::where('order_id', $mesa->order->id)->count();
                if ($orderDetails === 0) {
                    // Todos los items fueron vendidos, eliminar la orden vacía
                    $mesa->order->delete();
                    $mesa->update([
                        'status' => 'Libre',
                        'opened_at' => null,
                    ]);
                }
            }
        }

        if ($mesa->status === 'Libre') {
            $mesa->update([
                'status' => 'Ocupado',
                'opened_at' => now(),
            ]);

            $order = Order::create([
                'table_id' => $mesa->id,
                'status' => 'Abierto'
            ]);

            $productos = [];
        } else {
            // Para mesas ocupadas, buscar la orden activa
            $order = $mesa->order;

            // Si no existe orden, crear una nueva (caso edge)
            if (!$order) {
                $order = Order::create([
                    'table_id' => $mesa->id,
                    'status' => 'Abierto'
                ]);
                $productos = [];
            } else {
                // Cargar productos existentes si hay una orden
                $productos = [];
                if ($order->order_details && $order->order_details->count() > 0) {
                    Log::info('OrderDetails encontrados', [
                        'count' => $order->order_details->count(),
                        'detalles' => $order->order_details->toArray()
                    ]);

                    $productos = $order->order_details->map(function ($detalle) {
                        Log::info('Procesando detalle', [
                            'detalle_raw' => $detalle->toArray(),
                            'product' => $detalle->product ? $detalle->product->toArray() : null
                        ]);

                        // Usar nombres exactos de la base de datos
                        $nombre = ($detalle->product_id == 238)
                            ? 'Producto Personalizado'  // Para casos especiales, usar nombre genérico
                            : ($detalle->product ? $detalle->product->name : 'Producto');

                        $producto_mapeado = [
                            'id'         => $detalle->product_id,
                            'nombre'     => $nombre,
                            'cantidad'   => $detalle->quantity,        // Campo exacto de la DB
                            'precio'     => $detalle->product_price,   // Campo exacto de la DB
                            'confirmado' => $detalle->confirmed,       // Campo exacto de la DB
                            'stock'      => $detalle->product ? $detalle->product->quantity : 9999
                        ];

                        Log::info('Producto mapeado', $producto_mapeado);
                        return $producto_mapeado;
                    })->toArray();
                }
            }
        }

        Log::info('AbrirMesa - Respuesta', [
            'mesa_id' => $mesa->id,
            'order_id' => $order->id,
            'productos_count' => count($productos),
            'productos' => $productos
        ]);

        return response()->json([
            'success' => true,
            'mesa_id' => $mesa->id,
            'opened_at' => $mesa->opened_at,
            'order_id' => $order->id ?? null,
            'productos' => $productos,
            'mesa' => [
                'id' => $mesa->id,
                'name' => $mesa->name,
                'status' => $mesa->status
            ]
        ]);
    }

    public function verPedido($id)
    {
        $mesa = null;
        $order = Order::with(['order_details.product'])->where('table_id', $id)->first();

        if (!$order) {
            $this->abrirMesa($id);
            // return response()->json([
            //     'success' => false,
            //     'message' => 'No hay pedido abierto para esta mesa.'
            // ], 404);
            $mesa = Table::with(['order.order_details.product'])->find($id);
        } else {
            $mesa = Table::with(['order.order_details.product'])->find($id);
        }

        $productos = $mesa->order->order_details()->with('product')->get();

        // Log::info('Pedido cargado', [
        //     'mesa_id' => $id,
        //     'productos' => $productos
        // ]);

        return response()->json([
            'success' => true,
            'order_id' => $mesa->order->id,
            'number_persons' => $mesa->order->number_persons ?? 0,
            'productos' => $productos,
        ]);
    }

    public function updateQuantityAccount(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity_account' => 'required|integer|min:0',
        ]);

        // Buscar el detalle de pedido en base al order_id y product_id
        $detail = OrderDetail::where('order_id', $validated['order_id'])
            ->where('product_id', $validated['product_id'])
            ->first();

        // Si no existe ese detalle, devolvemos error
        if (!$detail) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el detalle de pedido para este producto y orden.'
            ], 404);
        }

        // Actualizar o insertar la cantidad separada
        $detail->quantity_account = $validated['quantity_account'];
        $detail->save();

        return response()->json(['success' => true]);
    }


    public function cerrarMesa($id)
    {
        try {
            $mesa = Table::with('order.order_details')->findOrFail($id);

            if ($mesa->order) {
                // Eliminar detalles
                $mesa->order->order_details()->delete();

                // Eliminar la orden
                $mesa->order()->delete();
            }

            // Liberar mesa
            $mesa->update([
                'status' => 'Libre',
                'opened_at' => null,
            ]);

            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            Log::error('Error al cerrar mesa: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al cerrar la mesa.']);
        }
    }

    public function addProductToOrder(Request $request, $orderId)
    {
        try {
            $validated = $request->validate([
                'product_id'      => 'required|integer|exists:products,id',
                'quantity'        => 'required|numeric|min:0',
                'product_price' => 'required|numeric|min:0',
                'notes' => 'nullable|string',
                'discount_amount' => 'nullable|numeric|min:0.1',
                'discount_reason' => 'nullable|string',
                'account_number' => 'nullable|integer',
            ]);

            $order = Order::findOrFail($orderId);
            // Usar key simple: order_id + product_id
            $key = [
                'order_id'   => $orderId,
                'product_id' => (int) $validated['product_id'],
            ];

            // Buscar detalle existente
            $detail = OrderDetail::where($key)->first();

            $cantidadNueva  = (float) $validated['quantity'];
            $precioUnitario = (float) $validated['product_price'];
            $discount_amount = (float) $validated['discount_amount'];
            $discount_reason = $validated['discount_reason'];
            $notes = $validated['notes'];
            $account_number = $validated['account_number'] ?? 1;

       
            $detail = OrderDetail::create([
                'order_id'        => $orderId,
                'product_id'      => (int) $validated['product_id'],
                'quantity'        => $cantidadNueva,
                'product_price' => $precioUnitario,
                'discount_amount' => $discount_amount,
                'discount_reason' => $discount_reason,
                'notes' => $notes,
                'account_number' => $account_number,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente',
                'data'    => $detail,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error al agregar producto al pedido: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    public function removeProduct(Request $request, $orderId)
    {
        try {
            $orderDetailId = $request->input('order_detail_id');
            $quantityToRemove = $request->input('quantity_to_remove', null);
            
            // Buscar el detalle de orden
            $orderDetail = OrderDetail::where('order_id', $orderId)
                ->where('id', $orderDetailId)
                ->first();
            
            if (!$orderDetail) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró el producto en la orden'
                ], 404);
            }
            
            // Si se especifica cantidad a eliminar y es menor que la cantidad total
            if ($quantityToRemove !== null && $quantityToRemove < $orderDetail->quantity) {
                // Reducir la cantidad
                $orderDetail->quantity -= $quantityToRemove;
                $orderDetail->save();
                
                return response()->json([
                    'success' => true,
                    'message' => "Se eliminaron {$quantityToRemove} unidad(es) del producto"
                ]);
            } else {
                // Eliminar completamente el detalle
                $orderDetail->delete();
                
                return response()->json([
                    'success' => true,
                    'message' => 'Producto eliminado completamente del carrito'
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar producto: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mover detalles seleccionados a una nueva cuenta dentro de la misma orden
     * POST /orders/{orderId}/split
     */
    public function splitOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'order_detail_moves' => 'required|array|min:1',
            'order_detail_moves.*.id' => 'required|integer',
            'order_detail_moves.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            $order = Order::findOrFail($orderId);

            $moves = $validated['order_detail_moves'];

            // Verificar que todos los ids pertenezcan a la orden y cantidades válidas
            $ids = array_map(fn($m) => $m['id'], $moves);
            $details = OrderDetail::where('order_id', $orderId)->whereIn('id', $ids)->get()->keyBy('id');

            if (count($details) !== count($ids)) {
                return response()->json(['success' => false, 'message' => 'Algunos productos no pertenecen a la orden'], 422);
            }

            // Comenzar transacción para evitar race conditions
            DB::beginTransaction();

            // Si el frontend envía target_account usarlo, si no, calcular el siguiente número de cuenta con lock
            $targetAccount = $request->input('target_account');
            if ($targetAccount && is_numeric($targetAccount) && intval($targetAccount) >= 1) {
                $next = intval($targetAccount);
            } else {
                // Calcular el siguiente número de cuenta (account_number) con lock
                $max = OrderDetail::where('order_id', $orderId)->lockForUpdate()->max('account_number');
                $next = $max ? intval($max) + 1 : 2;
            }

            foreach ($moves as $move) {
                $id = intval($move['id']);
                $moveQty = intval($move['quantity']);

                /** @var OrderDetail $detail */
                $detail = $details->get($id);
                if (!$detail) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => 'Detalle no encontrado: ' . $id], 404);
                }

                $origQty = intval($detail->quantity);
                $detailDiscount = floatval($detail->discount_amount ?? 0);
                if ($detailDiscount > 0) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "No se puede mover el detalle {$id} porque tiene descuento aplicado (S/ {$detailDiscount})"], 422);
                }
                if ($moveQty > $origQty) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Cantidad a mover ({$moveQty}) mayor a disponible ({$origQty}) para detalle {$id}"], 422);
                }

                if ($moveQty === $origQty) {
                    // Mover toda la línea
                    $detail->account_number = $next;
                    $detail->save();
                } else {
                    // Mover parte: restar en el detalle original y crear uno nuevo para la nueva cuenta
                    $detail->quantity = $origQty - $moveQty;

                    // Repartir proporcionalmente el discount_amount si existe
                    $origDiscount = floatval($detail->discount_amount ?? 0);
                    $movedDiscount = 0.0;
                    if ($origDiscount > 0) {
                        $movedDiscount = round(($origDiscount * $moveQty) / $origQty, 2);
                        $detail->discount_amount = round($origDiscount - $movedDiscount, 2);
                    }

                    $detail->save();

                    // Crear nuevo detalle con la cantidad movida
                    $newDetail = OrderDetail::create([
                        'order_id' => $orderId,
                        'product_id' => $detail->product_id,
                        'quantity' => $moveQty,
                        'product_price' => $detail->product_price,
                        'discount_amount' => $movedDiscount,
                        'discount_reason' => $detail->discount_reason,
                        'notes' => $detail->notes,
                        'account_number' => $next,
                        'confirmed' => $detail->confirmed ?? 0,
                        'deleted' => $detail->deleted ?? 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json(['success' => true, 'new_account' => $next, 'message' => 'Productos movidos a la cuenta ' . $next]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en splitOrder: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function getVoucherData(Request $request)
    {
        try {

            $voucher_id = $request->voucher_id;
            $type = $request->type;

            // cdr solo da en producción! en dev no
            if (!in_array($type, ['xml', 'cdr'])) { //si no es xml ni cdr que lance error
                return response()->json(['status' => false, 'message' => 'Type incorrecto']);
            }

            $response = $this->getInvoiceById($voucher_id);
            $data = $response->getData(true);

            // Manejo de error
            if (isset($data['status']) && $data['status'] === false) {
                return response()->json(['status' => false, 'error' => $data['error'] ?? 'Error desconocido']);
            }

            // Excepción para CDR no disponible
            if ($type === 'cdr' && (empty($data['data']['cdr']) || !filter_var($data['data']['cdr'], FILTER_VALIDATE_URL))) {
                return response()->json([
                    'status' => false,
                    'error' => 'El CDR solo estara disponible cuando el comprobante sea aceptado por SUNAT.'
                ])->header('Content-Type', 'application/json; charset=UTF-8');
            }


            return redirect()->away($data['data'][$type]);
        } catch (\Exception $e) {

            return response()->json([
                'status' => false,
                'error' => 'Error al obtener información del comprobante: ' . $e->getMessage(),
            ], 500);
        }
    }


    public function anular(Request $request)
    {
        try {
            $sale_id = $request->sale_id;

            // 1. Buscar la venta
            $venta = Sale::findOrFail($sale_id);

            if ($venta->deleted !== 0) {
                return response()->json([
                    'status' => false,
                    'error' => 'La venta ya fue anulada anteriormente.'
                ]);
            }

            DB::transaction(function () use ($venta) {
                // 2. Cambiar estado en tabla SALES
                $venta->deleted = 1;
                $venta->save();

                // 3. Cambiar estado en tabla PAYMENTS asociados a esa venta
                Payment::where('sale_id', $venta->id)
                    ->where('deleted', 0)
                    ->update(['deleted' => 1]);

                // 4. Obtener productos y restaurar stock
                $detalles = SaleDetail::where('sale_id', $venta->id)->get();

                foreach ($detalles as $detalle) {
                    $this->restaurarStockProducto(
                        $detalle->product_id,
                        $detalle->quantity
                    );
                }
            });

            return response()->json([
                'status' => true,
                'message' => 'Venta anulada, stock restaurado y pagos desactivados correctamente.'
            ]);
        } catch (\Throwable $e) {
            Log::error("❌ Error al anular venta: " . $e->getMessage());

            return response()->json([
                'status' => false,
                'error' => 'Error inesperado al anular la venta: ' . $e->getMessage()
            ]);
        }
    }

    public function details(Request $request)
    {
        try {
            $sale_id = $request->sale_id;

            // Obtener la venta con todas sus relaciones
            $sale = Sale::with([
                'client',
                'details.product',
                'payments.payment_method'
            ])->findOrFail($sale_id);

            // Mapear los productos
            $productos = $sale->details->map(function ($detail) {
                return [
                    'id' => $detail->product_id,
                    'nombre' => $detail->product->name ?? "Sin nombre",
                    'precio' => round($detail->unit_price, 2),
                    'cantidad' => round($detail->quantity, 2),
                    'subtotal' => round($detail->subtotal, 2),
                ];
            });

            // Mapear los pagos
            $pagos = $sale->payments->map(function ($payment) {
                return [
                    'metodo_pago' => $payment->payment_method->name ?? 'N/A',
                    'monto' => round($payment->subtotal, 2),
                    'fecha' => $payment->created_at->format('d/m/Y H:i'),
                ];
            });

            // Información de la venta
            $ventaInfo = [
                'id' => $sale->id,
                'fecha' => $sale->date ? $sale->date->format('d/m/Y H:i:s') : 'N/A',
                'cliente' => $sale->client->business_name ?? $sale->client_name ?? 'Varios',
                'fecha_entrega' => $sale->delivery_date ? $sale->delivery_date->format('Y-m-d') : 'N/A',
                'hora_entrega' => $sale->delivery_hour,
                'direccion' => $sale->address ?? "",
                'referencia' => $sale->reference ?? "",
                'observacion' => $sale->observation,
                'total' => round($sale->total, 2),
                'saldo' => round($sale->saldo(), 2),
                'telefono' => $sale->phone,
                'voucher_type' => $sale->voucher_type,
                'number' => $sale->number,
            ];

            // Retorna los detalles en formato JSON
            return response()->json([
                'status' => true,
                'productos' => $productos,
                'pagos' => $pagos,
                'venta' => $ventaInfo,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al obtener detalles de venta: ' . $e->getMessage(),
            ], 500);
        }
    }


    private function restaurarStockProducto($productId, $cantidadRestaurar)
    {
        $product = Product::find($productId);
        $product->quantity += $cantidadRestaurar;
        $product->save();
    }

    public function getInvoiceById($id)
    {
        $url = config('apisunat.url') . '/documents/' . $id . '/getById';

        Log::error('url: ' . $url);

        $response = Http::get($url);
        $data = $response->object();
        if ($response->failed()) {
            return response()->json(['status' => false, 'error' => $data->error->message]);
        }

        return response()->json(['status' => true, 'data' => $response->json()]);
    }

    public function anticipated(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $number = $request->number;
        $client = $request->client;


        // Consulta principal de ventas anticipadas - solo mostrar NO entregadas
        $consulta = Sale::with('client', 'details', 'payments')
            ->where('type_status', 1) //anticipada
            ->where('status', 0) //solo no entregadas (cuando se entregue, desaparece)
            ->where('deleted', 0) //excluir órdenes canceladas
            ->when($number, function ($query) use ($number) {
                $query->where('number', 'like', '%' . $number . '%');
            })
            ->when($start_date, function ($query) use ($start_date) {
                $query->whereDate('delivery_date', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                $query->whereDate('delivery_date', '<=', $end_date);
            })
            ->when($client, function ($query) use ($client) {
                $query->where('client_id', $client);
            })
            ->orderBy('delivery_date', 'desc');

        // Si se solicita JSON, devolver solo los datos
        if ($request->wantsJson() || $request->has('json')) {
            $anticipadas = $consulta->get();
            return response()->json([
                'success' => true,
                'anticipadas' => $anticipadas->map(function($venta) {
                    // Priorizar datos de la venta directamente (para delivery)
                    $clientName = $venta->client_name ?? ($venta->client ? ($venta->client->business_name ?? $venta->client->contact_name) : 'Cliente no especificado');
                    $clientAddress = $venta->address ?? ($venta->client && $venta->client->address ? $venta->client->address : 'Sin dirección');
                    
                    return [
                        'id' => $venta->id,
                        'number' => $venta->number,
                        'total' => $venta->total,
                        'saldo' => $venta->saldo(),
                        'status' => $venta->status,
                        'client_name' => $clientName,
                        'client_address' => $clientAddress,
                        'address' => $clientAddress,
                        'delivery_date' => $venta->delivery_date,
                        'delivery_hour' => $venta->delivery_hour,
                        'created_at' => $venta->created_at,
                        'details_count' => $venta->details ? $venta->details->count() : 0
                    ];
                })
            ]);
        }

        $anticipadas = $consulta->paginate(15);

        $paymentMethod = PaymentMethod::where('deleted', 0)->get();

        // Obtener productos activos para el modal de edición
        $products = Product::where('deleted', 0)
            ->orderBy('name')
            ->get();

        return view('sales.anticipated', compact('anticipadas', 'paymentMethod', 'products'));
    }

    //Consultas de delivery
    public function delivery(Request $request)
    {
        try {
            $order = Order::findOrFail($request->order_id);

            $request->validate([
                'document_type' => 'required|string',
                'document_number' => 'required|string',
                'client_name' => 'required|string',
                'phone' => 'required|string',
                'delivery_date' => 'required|date',
                'delivery_hour' => 'required',
                'address' => 'required|string',
                'reference' => 'nullable|string',
                'observation' => 'nullable|string',
            ]);

            $photoPath = null;
            if ($request->hasFile('foto')) {
                $photoPath = $request->file('foto')->store('deliveries', 'public');
            }

            $deliveryData = [
                'document_type'   => $request->document_type,
                'document_number' => $request->document_number,
                'client_name'     => $request->client_name,
                'phone'           => $request->phone,
                'delivery_date'   => $request->delivery_date,
                'delivery_hour'   => $request->delivery_hour,
                'address'         => $request->address,
                'reference'       => $request->reference,
                'observation'     => $request->observation,
                'photo_path'      => $photoPath,
                'is_delivery'     => true
            ];

            $order->delivery = json_encode($deliveryData);
            $order->status = 'delivery_confirmed';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Datos de delivery guardados correctamente',
                'data' => $deliveryData
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar datos de delivery: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deliveryExcel(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $number = $request->number;

        // Crear nombre de archivo con fechas si están presentes
        $filename = 'deliverys_por_entregar';

        if ($start_date || $end_date) {
            $filename .= '_' . ($start_date ?: 'inicio') . '_a_' . ($end_date ?: 'fin');
        }
        $filename .= '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new DeliveryExport(
                $start_date,
                $end_date,
                $number,
            ),
            $filename
        );
    }

    public function deliveryPdf(Request $request)
    {
        $start_date = $request->start_date;
        $end_date = $request->end_date;
        $number = $request->number;

        $query = Sale::with('client', 'details', 'payments')
            ->where('type_status', 2)
            ->where('status', 0)
            ->when($number, function ($query) use ($number) {
                $query->where('number', 'like', '%' . $number . '%');
            })
            ->when($start_date, function ($query) use ($start_date) {
                $query->whereDate('delivery_date', '>=', $start_date);
            })
            ->when($end_date, function ($query) use ($end_date) {
                $query->whereDate('delivery_date', '<=', $end_date);
            })
            ->orderBy('delivery_date', 'desc');

        $deliverys = $query->get();

        $data = [
            'deliverys' => $deliverys,
            'filters' => [
                'start_date' => $start_date,
                'end_date' => $end_date,
                'number' => $number,
            ],
            'title' => 'REPORTE DE DELIVERYS',
            'subtitle' => 'LISTADO DE DELIVERYS REGISTRADOS',
        ];

        $pdf = Pdf::loadView('sales.pdf.pdf_delivery_sales', $data);
        $filename = 'reporte_deliverys' . '_' . date('Y-m-d_H-i-s') . '.pdf';
        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    //Comprobantes
    public function generarComprobanteAnticipado(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'tipo_comprobante' => 'required',
            'document' => 'nullable|string|max:11',
            'client' => 'nullable|string|max:255',
            'observacion' => 'nullable|string|max:255'
        ]);

        $sale = Sale::with(['details.product', 'client'])->find($request->sale_id);

        if ($sale->saldo() > 0) {
            return response()->json(['status' => false, 'message' => 'La venta aún tiene saldo pendiente.']);
        }

        if ($sale->numero) {
            return response()->json(['status' => false, 'message' => 'Ya se generó el comprobante.']);
        }

        $client = null;
        if ($request->document) {
            $client = Client::where('document', $request->document)->first();

            if ($client) {
                if ($request->client && $client->business_name !== $request->client) {
                    $client->business_name = $request->client;
                    $client->save();

                    $sale->client_id = $client->id;
                    $sale->save();
                    $sale->load('client');
                }
            } else {
                // Si no existe, lo crea
                $client = Client::create([
                    'document' => $request->document,
                    'business_name'  => $request->client,
                    'deleted'  => 0
                ]);
            }
        }

        // Asocia el cliente encontrado o creado a la venta
        if ($client) {
            $sale->client_id = $client->id;
        } else if ($request->client) {
            // Si no hay documento pero sí nombre, podrías buscar por nombre exacto,
        }

        // Actualiza la observación si hay cambios
        if ($request->observacion) {
            $sale->observacion = $request->observacion;
        }

        try {
            if ($request->tipo_comprobante === 'ticket') {
                $sale->number = $this->generarNumeroTicket();
                $sale->voucher_type = 'Ticket';
                $sale->save();

                return response()->json([
                    'status' => true,
                    'url_pdf' => $respuesta['pdf'] ?? null,
                    'venta' => $sale,
                    'productos' => $sale->details->map(function ($item) {
                        return [
                            'nombre'   => $item->product->name ?? 'Sin nombre', // ✅ CAMBIO AQUÍ
                            'precio'   => (float) $item->unit_price,
                            'cantidad' => (float) $item->quantity,
                            'subtotal' => (float) $item->subtotal
                        ];
                    })->values(),
                    'tipo_comprobante' => strtolower($sale->voucher_type)
                ]);
            } else {
                $sale->voucher_type = ucfirst($request->tipo_comprobante);
                $sale->save();

                $sale->load('client');
                $respuesta = $this->sendInvoice($sale);


                return response()->json([
                    'status' => true,
                    'url_pdf' => $respuesta['pdf'] ?? null,
                    'venta' => $sale,
                    'productos' => $sale->details->map(function ($item) {
                        return [
                            'nombre'   => $item->product->name ?? 'Sin nombre', // ✅ CAMBIO AQUÍ
                            'precio'   => (float) $item->unit_price,
                            'cantidad' => (float) $item->quantity,
                            'subtotal' => (float) $item->subtotal
                        ];
                    })->values(),

                    'tipo_comprobante' => strtolower($sale->voucher_type)
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error al generar el comprobante: ' . $e->getMessage(),
            ]);
        }
    }

    public function anticipated_print(Request $request)
    {
        try {
            $sale_id = $request->sale_id;

            // Obtener la venta con todas sus relaciones
            $sale = Sale::with([
                'client',
                'user', // Agregar relación con usuario
                'details.product',
                'payments.payment_method'
            ])->findOrFail($sale_id);

            // Mapear los productos
            $productos = $sale->details->map(function ($detail) {
                return [
                    'nombre' => $detail->product->name,
                    'precio' => round($detail->unit_price, 2),
                    'cantidad' => round($detail->quantity, 2),
                    'subtotal' => round($detail->subtotal, 2),
                ];
            });

            // Mapear los pagos
            $pagos = $sale->payments->map(function ($payment) {
                return [
                    'metodo_pago' => $payment->payment_method->name ?? 'N/A',
                    'monto' => round($payment->subtotal, 2),
                    'fecha' => $payment->created_at->format('d/m/Y H:i'),
                ];
            });

            // $tipo = "";
            // $type_sale = $sale->type_sale;
            // $type_status = $sale->type_status;

            // if ($type_sale == 0) {
            //     $tipo = "Punto de venta";
            // } else if ($type_sale == 1) {
            //     $tipo = "Cafetería";
            // }

            // if ($type_status == 0) {
            //     $tp = "Directa";
            // } else if ($type_status == 1) {
            //     $tp = "Anticipada";
            // } else if ($type_status == 2) {
            //     $tp = "Delivery";
            // }

            // Información de la venta
            $ventaInfo = [
                'id' => $sale->id,
                'cliente' => $sale->client->name ?? $sale->client_name ?? 'Varios',
                'document' => $sale->client->document ?? '00000000',
                // 'tipo' => $tipo,
                'type_sale' => $sale->type_sale,
                // 'tp' => $tp,
                'fecha' => $sale->date->format('d/m/Y H:i:s'),
                'fecha_entrega' => $sale->delivery_date,
                'direccion' => $sale->address,
                'referencia' => $sale->reference,
                'observacion' => $sale->observation,
                'total' => round($sale->total, 2),
                'saldo' => round($sale->saldo(), 2),
                'telefono' => $sale->phone,
                'user_id' => $sale->user->email ?? 'No especificado', // Usar solo email del usuario
                'voucher_type' => $sale->voucher_type,
                'number' => $sale->number,
                'ticket_number' => $sale->ticket_number,
                'hora_entrega' => $sale->delivery_hour,
            ];

            return response()->json([
                'status' => true,
                'productos' => $productos,
                'pagos' => $pagos,
                'venta' => $ventaInfo,
                'now' => now()->format('d/m/Y H:i:s'),
                'user' => ['name' => Auth::user()->email ?? '-'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al obtener datos para impresión: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function subirFoto(Request $request)
    {
        try {
            $sale_id = $request->sale_id;
            $request->validate([
                'foto' => 'required|mimes:jpg,jpeg,png,webp|max:4096',
            ]);

            $foto = $request->file('foto');

            $path = $this->guardarFoto($foto, $sale_id);

            return response()->json(['path' => $path, 'success' => true]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al guardar foto: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function registrarPago(Request $request)
    {
        $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'monto' => 'required|numeric|min:0.01',
            'metodo' => 'required|exists:payment_methods,name',
        ]);

        try {
            DB::beginTransaction();

            $venta = Sale::findOrFail($request->sale_id);
            $montoPagado = Payment::where('sale_id', $venta->id)->sum('subtotal');
            $saldoPendiente = $venta->total - $montoPagado;

            if ($saldoPendiente <= 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Esta venta ya está completamente pagada.'
                ], 400);
            }

            $montoPago = floatval($request->monto);
            if ($montoPago > $saldoPendiente) {
                return response()->json([
                    'status' => false,
                    'message' => 'El monto ingresado excede el saldo pendiente.'
                ], 422);
            }

            $metodo = PaymentMethod::whereRaw('UPPER(name) = ?', [strtoupper($request->metodo)])->first();
            if (!$metodo) {
                return response()->json([
                    'status' => false,
                    'message' => 'Método de pago no válido.'
                ], 422);
            }

            Payment::create([
                'sale_id' => $venta->id,
                'payment_method_id' => $metodo->id,
                'subtotal' => $montoPago,
                'date' => now(),
                'deleted' => 0,
                'user_id' => auth()->user()->id,
                'shift' => auth()->user()->shift,
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Pago registrado correctamente.',
                'nuevo_saldo' => $venta->total - ($montoPagado + $montoPago)
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Error en registrarPago(): ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => 'Error al registrar el pago.'
            ], 500);
        }
    }

    public function updateDetails(Request $request)
    {
        try {
            // Si productos viene como JSON string, decodificarlo
            $productos = $request->productos;
            if (is_string($productos)) {
                $productos = json_decode($productos, true);
            }

            // Debug: Log de los datos recibidos
            Log::info('Datos recibidos en updateDetails:', [
                'productos' => $productos,
                'sale_id' => $request->sale_id,
                'telefono' => $request->telefono,
                'fecha_entrega' => $request->fecha_entrega,
                'hora_entrega' => $request->hora_entrega,
                'total' => $request->total,
                'total_type' => gettype($request->total),
                'total_empty' => empty($request->total),
                'has_foto' => $request->hasFile('foto'),
                'all_request' => $request->all()
            ]);

            // Crear una nueva instancia de request con productos decodificados
            $requestData = $request->all();
            $requestData['productos'] = $productos;
            $request->merge($requestData);

            DB::beginTransaction();

            $sale = Sale::findOrFail($request->sale_id);

            // Manejar la foto si se proporciona
            if ($request->hasFile('foto')) {
                $foto = $request->file('foto');
                $this->guardarFoto($foto, $sale->id);
            }

            // Actualizar los campos de la venta
            $sale->update([
                'phone' => $request->telefono,
                'delivery_date' => $request->fecha_entrega,
                'delivery_hour' => $request->hora_entrega,
                'address' => $request->direccion,
                'reference' => $request->referencia,
                'observation' => $request->observacion,
                'total' => (float) $request->total
            ]);

            // Eliminar detalles existentes
            $sale->details()->delete();

            // Crear nuevos detalles
            foreach ($productos as $producto) {
                $sale->details()->create([
                    'product_id' => $producto['id'],
                    'unit_price' => $producto['precio'],
                    'quantity' => $producto['cantidad'],
                    'subtotal' => $producto['precio'] * $producto['cantidad']
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Detalles actualizados correctamente',
                'sale' => $sale
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Datos inválidos',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar los detalles: ' . $e->getMessage()
            ], 500);
        }
    }

    public function confirmarEntrega(Request $request){
        try {
            $sale = Sale::find($request->id);
            if (!$sale) {
                return response()->json(['success' => false, 'message' => 'Venta no encontrada']);
            }
        $sale->status = 1;
        $sale->save();
        return response()->json(['success' => true, 'message' => 'Entrega confirmada']);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar entrega: ' . $e->getMessage()
            ], 500);
        }
    }


    public function nuevo_pdv()
    {
        //
        $areas = Area::where('deleted', 0)
            ->with(['tables' => function ($q) {
                $q->where('deleted', 0);
            }])
            ->get();
        $products = Product::where('deleted', 0)->get();
        $employees = Employee::where('deleted', 0)->get();
        $categories = Category::where('deleted', 0)
            ->with(['products' => function ($q) {
                $q->where('deleted', 0);
            }])->get();

        $pms = PaymentMethod::where('deleted', 0)->get();
        return view('sales.nuevo_pdv', compact('areas', 'categories', 'products', 'employees', 'pms'));
    }

    public function addOrders(Request $request, $mesaId)
    {
        $detalles = $request->input('detalles', []);
        $errores = [];
        $resultados = [];

        // Buscar la mesa por nombre (puede ser ID o nombre como "DELIVERY")
        $mesa = Table::where('name', $mesaId)
            ->orWhere('id', $mesaId)
            ->first();

        if (!$mesa) {
            return response()->json([
                'success' => false,
                'errors' => ['Mesa no encontrada.'],
                'results' => []
            ], 404);
        }

        // Buscar la orden abierta para la mesa usando el ID de la mesa
        $order = Order::where('table_id', $mesa->id)->first();

        // Si no existe orden, crear una nueva
        if (!$order) {
            $order = Order::create([
                'table_id' => $mesa->id,
                'status' => 'Abierto'
            ]);
        }

        $orderId = $order->id;

        foreach ($detalles as $detalle) {
            try {
                $productId = $detalle['product_id'] ?? null;
                $quantity = $detalle['quantity'] ?? null;
                $productPrice = $detalle['product_price'] ?? null;
                $isCortesia = $detalle['is_cortesia'] ?? 0;

                if (!$productId || !$quantity) {
                    $errores[] = [
                        'detalle' => $detalle,
                        'error' => 'Faltan datos requeridos'
                    ];
                    continue;
                }

                // Si es cortesía, siempre crear un registro nuevo (no acumular)
                if ($isCortesia) {
                    // Limpiar el product_id para cortesías (quitar el sufijo -cortesia-timestamp)
                    $cleanProductId = $productId;
                    if (strpos($productId, '-cortesia-') !== false) {
                        $cleanProductId = explode('-cortesia-', $productId)[0];
                    }

                    // Crear nuevo detalle de cortesía
                    $nuevo = OrderDetail::create([
                        'order_id' => $orderId,
                        'product_id' => $cleanProductId,
                        'quantity' => $quantity,
                        'product_price' => 0, // Las cortesías siempre van con precio 0
                        'is_cortesia' => true,
                    ]);
                    $resultados[] = $nuevo;
                } else {
                    // Para productos normales, buscar si ya existe y acumular
                    $orderDetail = OrderDetail::where([
                        'order_id' => $orderId,
                        'product_id' => $productId,
                        'is_cortesia' => false
                    ])->first();

                    if ($orderDetail) {
                        // Sumar cantidad si ya existe
                        $orderDetail->quantity += $quantity;
                        $orderDetail->product_price = $productPrice; // Actualiza precio si es necesario
                        $orderDetail->save();
                        $resultados[] = $orderDetail;
                    } else {
                        // Crear nuevo detalle
                        $nuevo = OrderDetail::create([
                            'order_id' => $orderId,
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'product_price' => $productPrice,
                            'is_cortesia' => false,
                        ]);
                        $resultados[] = $nuevo;
                    }
                }
            } catch (\Exception $e) {
                $errores[] = [
                    'detalle' => $detalle,
                    'error' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => empty($errores),
            'errors' => $errores,
            'results' => $resultados
        ]);
    }

    public function getOrdersByTable(Request $request, $mesaId)
    {
        try {
            $mesa = Table::where('name', $mesaId)
                ->orWhere('id', $mesaId)
                ->first();

            if (!$mesa) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesa no encontrada.',
                    'orders' => []
                ], 404);
            }

            // Buscar la orden abierta para la mesa
            $order = Order::where('table_id', $mesa->id)->first();

            if (!$order) {
                return response()->json([
                    'success' => true,
                    'message' => 'No hay pedidos para esta mesa.',
                    'orders' => []
                ]);
            }

            // Obtener los detalles de la orden con información del producto
            $orderDetails = OrderDetail::where('order_id', $order->id)
                ->with('product')
                ->get();

            // Mapear los detalles para la respuesta
            $orders = $orderDetails->map(function ($detail) {
                return [
                    'id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product ? $detail->product->name : 'Producto',
                    'quantity' => $detail->quantity,
                    'product_price' => $detail->product_price,
                    'discount' => $detail->discount_amount,
                    'confirmed' => $detail->confirmed ?? 0,
                    'notes' => $detail->notes ?? '',
                    'is_cortesia' => $detail->is_cortesia ?? 0,
                    'account_number' => $detail->account_number,
                ];
            });

            $delivery = null;
            if ($order->delivery) {
                $delivery = is_array($order->delivery)
                    ? $order->delivery
                    : json_decode($order->delivery, true);
            }

            return response()->json([
                'success' => true,
                'orders' => $orders,
                'order_id' => $order->id,
                'delivery' => $delivery, 
            ]);
        } catch (\Exception $e) {
            Log::error('Error en getOrdersByTable: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los pedidos: ' . $e->getMessage(),
                'orders' => []
            ], 500);
        }
    }

    public function separateOrder(Request $request, $orderId)
    {
        // items: array of { order_detail_id, quantity, account_number }
        $itemsRaw = $request->input('items');
        $items = is_string($itemsRaw) ? json_decode($itemsRaw, true) : $itemsRaw;

        if (!is_array($items) || count($items) === 0) {
            return response()->json(['success' => false, 'message' => 'No se recibieron items válidos'], 400);
        }

        // validar order existe
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Orden no encontrada'], 404);
        }

        // validar estructura básica de items
        foreach ($items as $i => $it) {
            if (!isset($it['order_detail_id']) || !isset($it['quantity']) || !isset($it['account_number'])) {
                return response()->json(['success' => false, 'message' => "Item inválido en índice {$i}. Se requiere order_detail_id, quantity y account_number"], 400);
            }
            if (!is_numeric($it['order_detail_id']) || !is_numeric($it['quantity']) || (int)$it['quantity'] <= 0 || !is_numeric($it['account_number'])) {
                return response()->json(['success' => false, 'message' => "Datos inválidos en índice {$i}"], 400);
            }
        }

        DB::beginTransaction();
        try {
            foreach ($items as $it) {
                $detailId = (int)$it['order_detail_id'];
                $moveQty = (int)$it['quantity'];
                $targetAccount = (int)$it['account_number'];

                $detail = OrderDetail::find($detailId);
                if (!$detail) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Detalle de orden {$detailId} no encontrado"], 404);
                }

                if ($detail->order_id != $order->id) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "El detalle {$detailId} no pertenece a la orden proporcionada"], 400);
                }

                $currentQty = (int)$detail->quantity;
                if ($moveQty > $currentQty) {
                    DB::rollBack();
                    return response()->json(['success' => false, 'message' => "Cantidad a mover mayor a la disponible para detalle {$detailId}"], 400);
                }

                // Si la cuenta objetivo es la misma que la actual, saltar
                $currentAccount = isset($detail->account_number) ? (int)$detail->account_number : 1;
                if ($moveQty === $currentQty) {
                    // mover todo el detalle: solo cambiar account_number
                    if ($currentAccount !== $targetAccount) {
                        $detail->account_number = $targetAccount;
                        $detail->save();
                    }
                } else {
                    // dividir: reducir cantidad en el detalle original y crear nuevo detalle con account_number objetivo
                    $detail->quantity = $currentQty - $moveQty;
                    $detail->save();

                    OrderDetail::create([
                        'order_id'        => $order->id,
                        'product_id'      => $detail->product_id,
                        'quantity'        => $moveQty,
                        'product_price'   => $detail->product_price,
                        'discount_amount' => $detail->discount_amount,
                        'discount_reason' => $detail->discount_reason,
                        'notes'           => $detail->notes,
                        'confirmed'       => $detail->confirmed ?? 0,
                        'is_cortesia'     => $detail->is_cortesia ?? 0,
                        'account_number'  => $targetAccount,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Cuentas actualizadas correctamente'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error separando cuentas (account_number): ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al separar cuentas', 'error' => $e->getMessage()], 500);
        }
    }
}