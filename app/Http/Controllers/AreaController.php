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
        $request->validate([
            'name' => 'required|string|max:255',
            'branch_id' => 'required|exists:branches,id',
        ]);
        
        try {
            Area::create([
                'name' => $request->name,
                'branch_id' => $request->branch_id,
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
}
