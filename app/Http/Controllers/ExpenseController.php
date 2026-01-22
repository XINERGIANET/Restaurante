<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $expenses = Expense::where('deleted', false)
            ->orderBy('date', 'desc')
            ->paginate(10);
        
        return view('expenses.index', compact('expenses'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $expenses = Expense::where('deleted', false)
            ->orderBy('date', 'desc')
            ->paginate(10);
        
        return view('expenses.create', compact('expenses'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $request->validate([
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'description' => 'required|string|max:255',
        ]);

        Expense::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'description' => $request->description,
            'date' => now(),
            'deleted' => false,
        ]);

        return redirect()->route('expenses.create')
            ->with('success', 'Egreso registrado exitosamente.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $expense = Expense::where('id', $id)
            ->where('deleted', false)
            ->firstOrFail();
        
        return view('expenses.show', compact('expense'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $expense = Expense::where('id', $id)
            ->where('deleted', false)
            ->firstOrFail();
        
        return view('expenses.edit', compact('expense'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $expense = Expense::where('id', $id)
            ->where('deleted', false)
            ->firstOrFail();

        $request->validate([
            'amount' => 'required|numeric|regex:/^\d+(\.\d{1,2})?$/',
            'description' => 'required|string|max:255',
        ]);

        $expense->update([
            'amount' => $request->amount,
            'description' => $request->description,
        ]);

        return redirect()->route('expenses.create')
            ->with('success', 'Egreso actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage (soft delete).
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $expense = Expense::where('id', $id)
            ->where('deleted', false)
            ->firstOrFail();

        $expense->update(['deleted' => true]);

        return redirect()->route('expenses.create')
            ->with('success', 'Egreso eliminado exitosamente.');
    }
}