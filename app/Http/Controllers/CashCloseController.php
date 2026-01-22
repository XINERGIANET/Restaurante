<?php

namespace App\Http\Controllers;

use App\Models\CashBox;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\CashClose;
use App\Models\Sale;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CashCloseController extends Controller
{


    public function index(Request $request)
    {
        // 
    }


    public function create(Request $request)
    {
        $date = $request->date ? $request->date : now()->toDateString();
        $shift = auth()->user()->shift;

        $date_record = now()->format('Y-m-d');

        $cash_box_id = CashBox::where('deleted', false)
            ->whereDate('date', $date_record)
            ->value('id');

        $total_egresos = Expense::where('deleted', false)
            ->whereDate('date', $date_record)
            ->sum('amount');

        $total_egresos = number_format($total_egresos, 2, '.', '');

        $caja_chica = CashBox::where('deleted', false)
            ->whereDate('date', $date_record)
            ->value('opening_amount');

        $caja_chica = number_format($caja_chica, 2, '.', '');

        $pos_tips = Sale::where('deleted', false)
            ->whereDate('date', $date_record)
            ->sum('tip');

        $pos_tips = number_format($pos_tips, 2, '.', '');

        // Formatear el saldo con 2 decimales, igual que caja chica y egresos
        $saldo = number_format(($caja_chica - $total_egresos), 2, '.', '');

        // Monto de apertura de caja para la fecha/turno/sede
        $monto = CashClose::where('deleted', 0)
            ->where('date', $date)
            ->where('shift', $shift)
            ->value('amount');


        $ventas_payment_methods = PaymentMethod::select('id', 'name')
            ->where('deleted', 0)
            ->get()
            ->map(function ($method) use ($date, $shift) {
                $total = Payment::where('deleted', 0)
                    ->where('payment_method_id', $method->id)
                    ->where('date', $date)
                    ->whereHas('sale', function ($q) {
                        $q->where('deleted', 0);
                    })
                    ->sum('subtotal');

                $method->total = $total;
                return $method;
            });

        $total_ventas = $ventas_payment_methods->sum('total');
        //Contar comprobantes de venta
        $ticket_count = Sale::where('deleted', 0)   
            ->whereDate('date', $date )
            ->where('voucher_type', 'Ticket')
            ->count();
        $factura_count = Sale::where('deleted', 0)
            ->whereDate('date', $date )
            ->where('voucher_type', 'Factura')
            ->count();
        $boleta_count = Sale::where('deleted', 0)
            ->whereDate('date', $date )
            ->where('voucher_type', 'Boleta')
            ->count();
        //Contar pagos

        $efectivo = Payment::where('deleted', 0)
            ->where('date', $date)
            ->where('user_id', auth()->id())
            ->where('shift', $shift)
            ->whereHas('payment_method', function ($q) {
                $q->whereRaw('UPPER(name) = "EFECTIVO"');
            })
            ->whereHas('sale', function ($q) {
                $q->where('deleted', 0);
            })
            ->sum('subtotal');
        return view('cash_close.create', compact(
            'efectivo',
            'ventas_payment_methods',
            'total_ventas',
            'date',
            'monto',
            'shift',
            'ticket_count',
            'factura_count',
            'boleta_count',
            'cash_box_id',
            'total_egresos',
            'caja_chica',
            'saldo',
            'pos_tips'
        ));
    }


    public function store(Request $request)
    {
        try {

            $date = $request->date;
            $amount = $request->amount;
            $shift = auth()->user()->shift;
            $user_id = auth()->user()->id;

            $cierre = CashClose::updateOrCreate(
                [
                    'date' => $date,
                    'shift' => $shift,
                    'user_id' => $user_id,
                    'deleted' => 0,
                ],
                [
                    'amount' => $amount,
                ]
            );

            return response()->json([
                'status' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'error' => 'Error al guardar cierre: ' . $e->getMessage(),
            ], 500);
        }
    }
    public function show($id) {}

    public function edit($id) {}

    public function update(Request $request, $id) {}

    public function destroy($id) {}



    // public function pdf(Request $request)
    // {
    //     try {
    //         Log::info('cashClosePDF recibe:', $request->all());
    //         $request->validate([
    //             'user_id' => 'nullable|exists:users,id',
    //             'turno' => 'nullable|numeric|in:0,1',
    //             'headquarter_id' => 'nullable|exists:headquarters,id',
    //             'tabla' => 'required',
    //             'date' => 'required|date',
    //             'monto' => 'nullable|numeric'
    //         ]);

    //         $user_id = $request->user_id ?? auth()->user()->id;
    //         $user = Usuario::find($user_id)->nombre;
    //         $turn = $request->turno ?? auth()->user()->turno;
    //         if ($turn === 0) {
    //             $turno = 'mañana';
    //         } else {
    //             $turno = 'tarde';
    //         }
    //         $headquarter_id = $request->headquarter_id ?? auth()->user()->sede_id;
    //         $headquarter = Headquarters::find($headquarter_id)->nombre;
    //         $tabla = $request->tabla;
    //         $fecha = $request->date;
    //         $monto = $request->monto ?? "No registrado";

    //         $pdf = Pdf::loadView('cashClose.pdf', compact('user', 'turno', 'headquarter', 'tabla', 'fecha', 'monto'));

    //         // Descargar el archivo PDF
    //         return $pdf->download('Cierre.pdf');
    //     } catch (\Throwable $e) {
    //         Log::error('Error generando PDF: ' . $e->getMessage());
    //         return response('Error generando PDF: ' . $e->getMessage(), 500);
    //     }
    // }





}
