<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use Illuminate\Http\Request;

class ModulesController extends Controller
{
    public function index(){
        $modules = Modules::all();
        return view('modules.index', ['title' => 'Modulos']);
    }
    
    public function store(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'menu_id' => 'required|integer',
        ]);
        try {
            Modules::create([
                'name' => $request->name,
                'icon' => $request->icon,
                'order_num' => $request->order_num,
                'menu_id' => $request->menu_id,
            ]);
            return redirect()->route('modules.index')->with('success', 'Modulo creado correctamente');
        } catch (\Throwable $th) {
            return redirect()->route('modules.index')->with('error', 'Error al crear el modulo');
        }
    }
}
