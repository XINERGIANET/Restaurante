<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use App\Models\Branch; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ShiftController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $shifts = Shift::query()
            ->with('branch') 
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('abbreviation', 'like', "%{$search}%");
            })
            ->orderBy('id', 'desc')
            ->paginate(10); 

        $branches = Branch::all();

        return view('shifts.index', [
            'title' => 'Gestión de Turnos',
            'shifts' => $shifts,
            'branches' => $branches,
        ]);
    }
    
    public function store(Request $request)
    {
        $branchId = session('branch_id');

        $request->validate([
            'name' => 'required|string|max:255',
            'start_time' => 'required',
            'end_time' => 'required',
        ]);

        if (!$branchId) {
            return back()->withErrors(['error' => 'No se pudo identificar tu sucursal.']);
        }

        try {
            Shift::create([
                'name'         => $request->name,
                'abbreviation' => $request->abbreviation,     
                'start_time'   => $request->start_time,
                'end_time'     => $request->end_time,
                'branch_id'    => $branchId,
            ]);

            return redirect()->route('admin.shifts.index')
                ->with('status', 'Turno creado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al crear el turno: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        $branches = Branch::all(); 

        return view('shifts.edit', [
            'shift' => $shift,
            'branches' => $branches
        ]);
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);

        $branchId = session('branch_id');

        if (!$branchId) {
            return back()->withErrors(['error' => 'No se pudo identificar tu sucursal.']);
        }

        try {
            $shift->update([
                'name'         => $request->input('name'),
                'abbreviation' => $request->input('abbreviation'), 
                'start_time'   => $request->input('start_time'),
                'end_time'     => $request->input('end_time'),
                'branch_id'    => $branchId,
            ]);

            return redirect()->route('admin.shifts.index')
                ->with('status', 'Turno actualizado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar el turno: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $shift = Shift::findOrFail($id);

        try {
            $shift->delete();

            return redirect()->route('admin.shifts.index')
                ->with('status', 'Turno eliminado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar el turno', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al eliminar: ' . $e->getMessage()]);
        }
    }
}