<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Bank;

class BankController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $banks = Bank::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();
        return view('banks.index', compact('banks', 'search', 'perPage', 'allowedPerPage'));
    }
    public function store(Request $request){
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'required|boolean',
        ]);
        Bank::create($request->all());
        return redirect()->route('admin.banks.index')->with('status', 'Banco creado correctamente');
    }
    public function edit(Bank $bank){
        return view('banks.edit', compact('bank'));
    }
    public function update(Request $request, Bank $bank){
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'required|boolean',
        ]);
        $bank->update($request->all());
        return redirect()->route('admin.banks.index')->with('status', 'Banco actualizado correctamente');
    }
    public function destroy(Bank $bank){
        $bank->delete();
        return redirect()->route('admin.banks.index')->with('status', 'Banco eliminado correctamente');
    }
}
