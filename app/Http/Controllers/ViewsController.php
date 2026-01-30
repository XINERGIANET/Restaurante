<?php

namespace App\Http\Controllers;

use App\Models\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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
        try {
            View::create([
                'name'      => $request->name,
                'status'    => $request->status,  
            ]);

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
