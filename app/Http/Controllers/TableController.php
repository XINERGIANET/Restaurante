<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Table;
use Illuminate\Http\Request;

class TableController extends Controller
{
    public function index(Area $area)
    {
        $tables = Table::where('area_id', $area->id)
            ->where('deleted', false)
            ->get();
                
        return view('restaurant.areas.tables.index', [
            'tables' => $tables,
            'area' => $area,
        ]);
    }

    public function store(Area $area, Request $request)
    {   
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Table::create([
            'name' => $request->name,
            'area_id' => $area->id,
            'branch_id' => $area->branch_id,
            'deleted' => false,
        ]);

        return redirect()->route('admin.areas.tables.index', $area)
            ->with('success', 'Mesa creada correctamente');
    }
}
