<?php

namespace App\Http\Controllers;

use App\Models\Operation;
use Illuminate\Http\Request;

class OperationsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $operations = Operation::query()
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name', 'asc')
            ->paginate(10);
            
        return view('operations.index', compact('operations', 'search', 'perPage', 'allowedPerPage'));
    }

    public function create()
    {
        return view('operations.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'required|string|max:255',
            'action' => 'required|string|max:255',
            'view_id' => 'required|exists:views,id',
            'color' => 'required|string|max:255',
            'status' => 'required|integer',

        ]);

        Operation::create($request->all());
        return redirect()->route('admin.operations.index')->with('status', 'Operación creada correctamente');
    }
}