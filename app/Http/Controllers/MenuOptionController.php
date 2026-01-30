<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\MenuOption;
use App\Models\View; // <--- 1. IMPORTANTE: Importar el modelo View
use Illuminate\Http\Request;

class MenuOptionController extends Controller
{
    public function index(Request $request, Module $module)
    {
        $search = $request->input('search');

        // 2. Obtener las vistas activas para el select
        $views = View::where('status', 1)->orderBy('name', 'asc')->get();

        $menuOptions = $module->menuOptions()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'ilike', "%{$search}%")
                        ->orWhere('action', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('menu_options.index', [
            'module' => $module,      
            'menuOptions' => $menuOptions,
            'search' => $search,
            'views' => $views,
        ]);
    }

    public function create(Module $module)
    {
        $views = View::where('status', 1)->orderBy('name', 'asc')->get();

        return view('menu_options.create', [
            'module' => $module,
            'views' => $views,
        ]);
    }

    public function store(Request $request, Module $module)
    {
        $data = $this->validateData($request);
        
        $module->menuOptions()->create($data);

        return redirect()
            ->route('admin.modules.menu-options.index', $module)
            ->with('status', 'Opción de menú creada correctamente.');
    }

    public function show(Module $module, MenuOption $menuOption)
    {
        $menuOption = $this->resolveScope($module, $menuOption);

        return view('menu_options.show', [
            'module' => $module,
            'menuOption' => $menuOption,
        ]);
    }

    public function edit(Module $module, MenuOption $menuOption)
    {
        $menuOption = $this->resolveScope($module, $menuOption);
        $views = View::where('status', 1)->orderBy('name', 'asc')->get(); // Obtener vistas para edit

        return view('menu_options.edit', [
            'module' => $module,
            'menuOption' => $menuOption,
            'views' => $views,
        ]);
    }

    public function update(Request $request, Module $module, MenuOption $menuOption)
    {
        $menuOption = $this->resolveScope($module, $menuOption);
        $data = $this->validateData($request);

        $menuOption->update($data);

        return redirect()
            ->route('admin.modules.menu-options.index', $module)
            ->with('status', 'Opción de menú actualizada correctamente.');
    }

    public function destroy(Module $module, MenuOption $menuOption)
    {
        $menuOption = $this->resolveScope($module, $menuOption);
        $menuOption->delete();

        return redirect()
            ->route('admin.modules.menu-options.index', $module)
            ->with('status', 'Opción de menú eliminada correctamente.');
    }

    // --- Validaciones y Helpers ---

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:255'],
            'action' => ['required', 'string', 'max:255'],
            'view_id' => ['required', 'integer', 'exists:views,id'], // <--- VALIDACIÓN AGREGADA
            'status' => ['required', 'boolean'],
            'quick_access' => ['required', 'boolean'],
        ]);
    }

    private function resolveScope(Module $module, MenuOption $menuOption): MenuOption
    {
        if ($menuOption->module_id !== $module->id) {
            abort(404);
        }

        return $menuOption;
    }
}