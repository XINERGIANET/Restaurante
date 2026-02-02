<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Branch;

class AreaController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');
        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
        return view('areas.index', compact('areas'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
        ]);
        
        try {
            Area::create([
                'name' => $validated['name'],
                'branch_id' => session('branch_id'),
            ]);
            
            return redirect()->route('areas.index')
                ->with('success', 'Área creada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('areas.index')
                ->withErrors(['error' => 'Error al crear el área: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function edit(Area $area)
    {
        $branchId = session('branch_id');
        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->get();
        return view('areas.index', compact('areas', 'area'));
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
        ]);
        
        try {
            $area->update([
                'name' => $validated['name'],
                'branch_id' => session('branch_id'),
            ]);
            
            return redirect()->route('areas.index')
                ->with('success', 'Área actualizada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar el área: ' . $e->getMessage());
            return redirect()->route('areas.index')
                ->withErrors(['error' => 'Error al actualizar el área: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Area $area)
    {
        try {
            $area->delete();
            
            return redirect()->route('areas.index')
                ->with('success', 'Área eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('areas.index')
                ->withErrors(['error' => 'Error al eliminar el área: ' . $e->getMessage()]);
        }
    }
}
