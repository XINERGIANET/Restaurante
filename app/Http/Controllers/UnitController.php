<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use Illuminate\Http\Request;

class UnitController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $units = Unit::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%")
                    ->orWhere('abbreviation', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();
        return view('units.index', [
            'units' => $units,
            'search' => $search,
            'perPage' => $perPage,
        ]);
    }

    public function store(Request $request){
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'is_sunat' => ['nullable', 'boolean'],
        ]);

        $data['is_sunat'] = $request->has('is_sunat') ? (bool) $request->input('is_sunat') : false;

        Unit::create($data);
        return redirect()->route('admin.units.index')->with('status', 'Unidad creada correctamente.');
    }

    public function edit(Unit $unit){
        return view('units.edit', [
            'unit' => $unit,
        ]);
    }

    public function update(Request $request, Unit $unit){
        $data = $request->validate([
            'description' => ['required', 'string', 'max:255'],
            'abbreviation' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'is_sunat' => ['nullable', 'boolean'],
        ]);

        $data['is_sunat'] = $request->has('is_sunat') ? (bool) $request->input('is_sunat') : false;
        $unit->update($data);
        return redirect()->route('admin.units.index')->with('status', 'Unidad actualizada correctamente.');
    }

    public function destroy(Unit $unit){
        $unit->delete();
        return redirect()->route('admin.units.index')->with('status', 'Unidad eliminada correctamente.');
    }
}
