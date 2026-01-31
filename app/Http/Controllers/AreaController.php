<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Area;
use App\Models\Branch;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::all();
        $branches = Branch::all();
        return view('restaurant.areas.index', compact('areas', 'branches'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'branch_id.required' => 'Debe seleccionar una sucursal.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
        ]);
        
        try {
            Area::create([
                'name' => $validated['name'],
                'branch_id' => $validated['branch_id'],
                'deleted' => false,
            ]);
            
            return redirect()->route('admin.areas.index')
                ->with('success', 'Área creada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.areas.index')
                ->withErrors(['error' => 'Error al crear el área: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function edit(Area $area)
    {
        $areas = Area::all();
        $branches = Branch::all();
        return view('restaurant.areas.index', compact('areas', 'branches', 'area'));
    }

    public function update(Request $request, Area $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
        ], [
            'name.required' => 'El nombre del área es obligatorio.',
            'name.string' => 'El nombre debe ser texto.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'branch_id.required' => 'Debe seleccionar una sucursal.',
            'branch_id.exists' => 'La sucursal seleccionada no existe.',
        ]);
        
        try {
            $area->update([
                'name' => $validated['name'],
                'branch_id' => $validated['branch_id'],
            ]);
            
            return redirect()->route('admin.areas.index')
                ->with('success', 'Área actualizada correctamente');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar el área: ' . $e->getMessage());
            return redirect()->route('admin.areas.index')
                ->withErrors(['error' => 'Error al actualizar el área: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(Area $area)
    {
        try {
            $area->update(['deleted' => true]);
            
            return redirect()->route('admin.areas.index')
                ->with('success', 'Área eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.areas.index')
                ->withErrors(['error' => 'Error al eliminar el área: ' . $e->getMessage()]);
        }
    }
}
