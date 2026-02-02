<?php

namespace App\Http\Controllers;

use App\Models\View;
use App\Models\Operation;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index(Request $request, View $view) 
    {
        $operations = $view->operations()
            ->orderBy('id', 'asc')
            ->paginate(10);

        return view('views.operations.index', [
            'view' => $view,
            'operations' => $operations
        ]);
    }

    public function store(Request $request, View $view)
    {
        $data = $this->validateData($request);
        
        $view->operations()->create($data);

        return redirect()
            ->route('admin.views.operations.index', $view)
            ->with('status', 'Operación creada correctamente.');
    }

    public function edit(View $view, Operation $operation)
    {
        $operation = $this->resolveScope($view, $operation);

        return view('views.operations.edit', [
            'view' => $view,
            'operation' => $operation,
        ]);
    }

    public function update(Request $request, View $view, Operation $operation)
    {
        $operation = $this->resolveScope($view, $operation);
        $data = $this->validateData($request);

        $operation->update($data);

        return redirect()
            ->route('admin.views.operations.index', $view)
            ->with('status', 'Operación actualizada correctamente.');
    }

    public function destroy(View $view, Operation $operation)
    {
        $operation = $this->resolveScope($view, $operation);
        $operation->delete();

        return redirect()
            ->route('admin.views.operations.index', $view)
            ->with('status', 'Operación eliminada correctamente.');
    }

    // --- Validaciones y Helpers ---

    private function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'icon' => ['required', 'string', 'max:255'], // Asumo requerido para botones
            'action' => ['required', 'string', 'max:255'], // Ej: create, edit, delete, export
            'color' => ['required', 'string', 'max:50'],   // Nuevo campo Color
            'status' => ['required', 'boolean'],
            'type' => ['required', 'in:R,T'],
        ]);
    }

    private function resolveScope(View $view, Operation $operation): Operation
    {
        if ($operation->view_id !== $view->id) {
            abort(404);
        }

        return $operation;
    }
}
