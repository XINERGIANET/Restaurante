<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentConcept;

class PaymentConceptController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $paymentConcepts = PaymentConcept::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();

        return view('payment_concept.index', compact('paymentConcepts', 'search', 'perPage', 'allowedPerPage'));
    }

    public function create()
    {
        return view('payment_concept.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|string|max:1',
        ]);

        PaymentConcept::create($request->all());

        return redirect()->route('admin.payment_concepts.index')->with('status', 'Concepto de pago creado correctamente');
    }

    public function edit(PaymentConcept $paymentConcept)
    {
        return view('payment_concept.edit', compact('paymentConcept'));
    }

    public function update(Request $request, PaymentConcept $paymentConcept)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|string|max:1',
        ]);
        
        $paymentConcept->update($request->all());

        return redirect()->route('admin.payment_concepts.index',)->with('status', 'Concepto de pago actualizado correctamente');
    }

    public function destroy(PaymentConcept $paymentConcept)
    {
        $paymentConcept->delete();

        return redirect()->route('admin.payment_concepts.index')->with('status', 'Concepto de pago eliminado correctamente');
    }
}
