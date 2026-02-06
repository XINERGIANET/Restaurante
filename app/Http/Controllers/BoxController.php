<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CashRegister;

class BoxController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $cash = CashRegister::query()
            ->when($search, function ($query, $search) {
                $query->where('number', 'like', "%{$search}%")
                      ->orWhere('series', 'like', "%{$search}%");
            })
            ->orderBy('number', 'asc')
            ->paginate(10); 

        return view('boxes.index', [
            'title' => 'Cajas Registradoras',
            'cash'  => $cash
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:20|unique:cash_registers,number',
            'series' => 'required|string|max:10',
            'status' => 'required|boolean',
        ], [
            'number.required' => 'El número de caja es obligatorio.',
            'number.unique'   => 'Este número de caja ya existe.',
            'series.required' => 'La serie es obligatoria.',
        ]);
        
        try {
            CashRegister::create([
                'number'    => $validated['number'],
                'series'    => $validated['series'],
                'status'    => $validated['status'],
            ]);
            
            return redirect()->route('boxes.index')
                ->with('success', 'Caja creada correctamente');

        } catch (\Exception $e) {
            return redirect()->route('boxes.index')
                ->withErrors(['error' => 'Error al crear la caja: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function edit(CashRegister $box)
    {
        $cash = CashRegister::paginate(10); 
        
        return view('boxes.edit', [
            'title' => 'Cajas',
            'cash'  => $cash,
            'box'   => $box
        ]);
    }

    public function update(Request $request, CashRegister $box)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:20|unique:cash_registers,number,' . $box->id,
            'series' => 'required|string|max:10',
            'status' => 'required|boolean',
        ]);
        
        try {
            $box->update([
                'number' => $validated['number'],
                'series' => $validated['series'],
                'status' => $validated['status'],
            ]);
            
            return redirect()->route('boxes.index')
                ->with('success', 'Caja actualizada correctamente');

        } catch (\Exception $e) {
            \Log::error('Error al actualizar la caja: ' . $e->getMessage());
            return redirect()->route('boxes.index')
                ->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()])
            ->withInput();
        }
    }

    public function destroy(CashRegister $box)
    {
        try {
            $box->delete();
            return redirect()->route('boxes.index')
                ->with('success', 'Caja eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('boxes.index')
                ->withErrors(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }
}