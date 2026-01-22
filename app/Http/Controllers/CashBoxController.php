<?php

namespace App\Http\Controllers;

use App\Models\CashBox;
use Illuminate\Http\Request;

class CashBoxController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
        $request->validate([
            'opening_amount' => 'required|numeric|min:0.01|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        $openingAmount = $request->input('opening_amount');
        // Usar solo la fecha (un único registro por día por location)
        $date = now()->format('Y-m-d H:i:s');

        $date_record = now()->format('Y-m-d');

        try {

            // Buscar si ya existe una apertura de caja para la fecha de hoy
            $record_cash_close = CashBox::whereDate('date', $date_record)
                ->first();

            if ($record_cash_close) {
                return response()->json([
                    'status' => false,
                    'message' => 'Ya existe un cierre de caja para la fecha especificada.'
                ], 422);
            }

            // Logic to store cash close data goes here
            $cash_close = CashBox::create([
                'opening_amount' => $openingAmount,
                'date' => $date,
                'deleted' => false,
            ]);


            return response()->json([
                'status' => true,
                'message' => 'Apertura de Caja registrada exitosamente.',
                'orders' => $cash_close
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
        $request->validate([
            'closing_amount' => 'required|numeric|min:0.01|regex:/^\d+(\.\d{1,2})?$/',
        ]);

        $cash_close = CashBox::find($id);;

        try {
            if (!$cash_close) {
                return response()->json(['status' => false, 'message' => 'Registro de cierre no encontrado'], 404);
            }

            if ($cash_close->closing_amount) {
                return response()->json(['status' => false, 'message' => 'Ya se registró un cierre de caja el día de hoy.'], 422);
            }

            $cash_close->closing_amount = $request->input('closing_amount');
            $cash_close->save();

            return response()->json(['status' => true, 'message' => 'Cierre de caja actualizado correctamente', 'cash_close' => $cash_close]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
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
}
