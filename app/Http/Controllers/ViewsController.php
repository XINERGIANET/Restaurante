<?php

namespace App\Http\Controllers;

use App\Models\View;
use App\Models\Operation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class ViewsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $views = View::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10); 

        return view('views.index', [
            'title' => 'Vistas',
            'views' => $views
        ]);
    }
    
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abbreviation' => 'nullable|string|max:255',
            'status' => 'required|in:0,1',
        ], [
            'name.required' => 'El nombre de la vista es obligatorio.',
            'name.string' => 'El nombre debe ser una cadena de texto.',
            'name.max' => 'El nombre no debe exceder los 255 caracteres.',
            'abbreviation.string' => 'La abreviatura debe ser una cadena de texto.',
            'abbreviation.max' => 'La abreviatura no debe exceder los 255 caracteres.',
            'status.required' => 'El estado es obligatorio.',
            'status.in' => 'El estado debe ser Activo o Inactivo.',
        ]);

        try {
            DB::transaction(function () use ($validated) {
                $view = View::create([
                    'name'      => $validated['name'],
                    'abbreviation' => $validated['abbreviation'] ?? null,
                    'status'    => (bool) $validated['status'],  
                ]);

                $base = $validated['abbreviation'] ?: $validated['name'];
                $actionBase = Str::slug($base, '.');

                $operations = [
                    [
                        'name' => 'Nuevo ' . $view->name,
                        'icon' => 'ri-add-line',
                        'action' => $actionBase . '.create',
                        'color' => '#12f00e',
                    ],
                    [
                        'name' => 'Editar ' . $view->name,
                        'icon' => 'ri-pencil-line',
                        'action' => $actionBase . '.edit',
                        'color' => '#FBBF24',
                    ],
                    [
                        'name' => 'Eliminar ' . $view->name,
                        'icon' => 'ri-delete-bin-line',
                        'action' => $actionBase . '.destroy',
                        'color' => '#EF4444',
                    ],
                ];

                foreach ($operations as $operation) {
                    Operation::create([
                        'name' => $operation['name'],
                        'icon' => $operation['icon'],
                        'action' => $operation['action'],
                        'view_id' => $view->id,
                        'color' => $operation['color'],
                        'status' => 1,
                    ]);
                }
            });

            return redirect()->route('admin.views.index')
                ->with('status', 'Vista creada correctamente');

        } catch (\Exception $e) {
            Log::error('Error al crear la vista: ' . $e->getMessage());

            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $view = View::findOrFail($id);

        return view('views.edit', [
            'view' => $view
        ]);
    }

    public function update(Request $request, $id)
    {
        $view = View::findOrFail($id);

        try {
            $view->update([
                'name'      => $request->input('name'),
                'abbreviation' => $request->input('abbreviation'),
                'status'    => $request->input('status'),
            ]);

            return redirect()->route('admin.views.index')
                ->with('status', 'Vista actualizada correctamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar la vista: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al actualizar: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $view = View::findOrFail($id);

        try {
            $view->update([
                'status' => 0
            ]);

            $view->delete();

            return redirect()->route('admin.views.index')
                ->with('status', 'Vista eliminada correctamente');

        } catch (\Exception $e) {
            Log::error('Error al eliminar la vista', ['error' => $e->getMessage()]);
            return back()->withErrors(['error' => 'Error al eliminar la vista: ' . $e->getMessage()]);
        }
    }
}
