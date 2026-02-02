<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $paymentMethods = PaymentMethod::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();

        return view('payment_method.index', compact('paymentMethods', 'search', 'perPage', 'allowedPerPage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            PaymentMethod::create($request->all());
            return redirect()->route('admin.payment_methods.index')->with('status', 'Medio de pago creado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.payment_methods.index')->withErrors(['error' => 'Error al crear el medio de pago: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, PaymentMethod $paymentMethod)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'nullable|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            $paymentMethod->update($request->all());
            return redirect()->route('admin.payment_methods.index')->with('status', 'Medio de pago actualizado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.payment_methods.index')->withErrors(['error' => 'Error al actualizar el medio de pago: ' . $e->getMessage()]);
        }
    }

    public function destroy(PaymentMethod $paymentMethod)
    {
        $paymentMethod->update(['status' => 0]);
        $paymentMethod->delete();
        return redirect()->route('admin.payment_methods.index')->with('status', 'Medio de pago eliminado correctamente');
    }
}
