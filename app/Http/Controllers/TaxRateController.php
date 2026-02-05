<?php

namespace App\Http\Controllers;

use App\Models\TaxRate;
use Illuminate\Http\Request;

class TaxRateController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $taxRates = TaxRate::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();
        return view('tax_rates.index', compact('taxRates', 'search', 'perPage', 'allowedPerPage'));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'tax_rate' => 'required|numeric',
            'order_num' => 'required|integer',
            'status' => ['nullable', 'boolean'],
        ]);

        $data['status'] = $request->has('status') ? (bool) $request->input('status') : false;

        TaxRate::create($data);
        return redirect()->route('admin.tax_rates.index')->with('status', 'Tasa de impuesto creada correctamente.');
    }

    public function edit(TaxRate $taxRate)
    {
        return view('tax_rates.edit', compact('taxRate'));
    }


    public function update(Request $request, TaxRate $taxRate)
    {
        $data = $request->validate([
            'code' => 'required|string|max:255',
            'description' => 'required|string|max:255',
            'tax_rate' => 'required|numeric',
            'order_num' => 'required|integer',
            'status' => ['nullable', 'boolean'],
        ]);

        $data['status'] = $request->has('status') ? (bool) $request->input('status') : false;

        $taxRate->update($data);
        return redirect()->route('admin.tax_rates.index')->with('status', 'Tasa de impuesto actualizada correctamente.');
    }

    public function destroy(TaxRate $taxRate)
    {
        $taxRate->delete();
        return redirect()->route('admin.tax_rates.index')->with('status', 'Tasa de impuesto eliminada correctamente.');
    }
}
