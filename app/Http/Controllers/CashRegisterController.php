<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;
use Illuminate\Validation\Rule;

class CashRegisterController extends Controller
{
    public function set(Request $request)
    {
        $branchId = session('branch_id');
        $request->validate([
            'cash_register_id' => [
                'required',
                Rule::exists('cash_registers', 'id')->where(function ($query) use ($branchId) {
                    return $query->where('branch_id', $branchId);
                }),
            ],
        ]);
        session(['cash_register_id' => $request->cash_register_id]);
        $caja = CashRegister::find($request->cash_register_id);
        
        return back()->with('success', "Caja cambiada a: {$caja->number}");
    }

    public function select()
    {
        return redirect('/')->with('warning', 'Por favor seleccione una caja en la barra superior.');
    }
}