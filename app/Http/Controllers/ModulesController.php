<?php

namespace App\Http\Controllers;

use App\Models\Modules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ModulesController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $modules = Modules::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('order_num', 'asc')
            ->paginate(10); 

        return view('modules.index', [
            'title' => 'Módulos',
            'modules' => $modules
        ]);
    }
    
    public function store(Request $request)
    {
        try {
            Modules::create([
                'name'      => $request->name,
                'icon'      => $request->icon,     
                'order_num' => $request->order_num,
                'status'    => $request->status,  
            ]);

            return redirect()->route('admin.modules.index')
                ->with('status', 'Módulo creado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al crear el modulo: ' . $e->getMessage());

            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $module = Modules::findOrFail($id);

        return view('modules.edit', [
            'module' => $module
        ]);
    }

    public function update(Request $request, $id)
    {
        $module = Modules::findOrFail($id);

        try {
            $module->update([
                'name'      => $request->input('name'),
                'icon'      => $request->input('icon'), 
                'order_num' => $request->input('order_num'),
                'status'    => $request->input('status'),
            ]);

            return redirect()->route('admin.modules.index')
                ->with('status', 'Módulo actualizado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar el modulo: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $module = Modules::findOrFail($id);

        try {
            $module->update([
                'status' => 0
            ]);

            $module->delete();

            return redirect()->route('admin.modules.index')
                ->with('status', 'Módulo eliminado correctamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar el módulo', ['error' => $e->getMessage()]);

            return back()->withErrors(['error' => 'Error al eliminar el módulo: ' . $e->getMessage()]);
        }
    }
}
