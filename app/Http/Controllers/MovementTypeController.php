<?php

namespace App\Http\Controllers;

use App\Models\MovementType;
use Illuminate\Http\Request;

class MovementTypeController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $movementTypes = MovementType::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('movement_types.index', [
            'movementTypes' => $movementTypes,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
        ]);

        MovementType::create($data);

        return redirect()
            ->route('admin.movement-types.index')
            ->with('status', 'Tipo de movimiento creado correctamente.');
    }

    public function edit(MovementType $movementType)
    {
        return view('movement_types.edit', [
            'movementType' => $movementType,
        ]);
    }

    public function update(Request $request, MovementType $movementType)
    {
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
        ]);

        $movementType->update($data);

        return redirect()
            ->route('admin.movement-types.index')
            ->with('status', 'Tipo de movimiento actualizado correctamente.');
    }

    public function destroy(MovementType $movementType)
    {
        $movementType->delete();

        return redirect()
            ->route('admin.movement-types.index')
            ->with('status', 'Tipo de movimiento eliminado correctamente.');
    }
}
