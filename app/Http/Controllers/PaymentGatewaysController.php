<?php

namespace App\Http\Controllers;

use App\Models\PaymentGateways;
use Illuminate\Http\Request;

class PaymentGatewaysController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $paymentGateways = PaymentGateways::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();

        return view('payment_gateways.index', compact('paymentGateways', 'search', 'perPage', 'allowedPerPage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            PaymentGateways::create($request->all());
            return redirect()->route('admin.payment_gateways.index')->with('status', 'Medio de pago creado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.payment_gateways.index')->withErrors(['error' => 'Error al crear el medio de pago: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, PaymentGateways $paymentGateway)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            $paymentGateway->update($request->all());
            return redirect()->route('admin.payment_gateways.index')->with('status', 'Medio de pago actualizado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.payment_gateways.index')->withErrors(['error' => 'Error al actualizar el medio de pago: ' . $e->getMessage()]);
        }
    }

    public function destroy(PaymentGateways $paymentGateway)
    {
        try {
            $paymentGateway->update(['status' => 0]);
            $paymentGateway->delete();
            return redirect()->route('admin.payment_gateways.index')->with('status', 'Medio de pago eliminado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.payment_gateways.index')->withErrors(['error' => 'Error al eliminar el medio de pago: ' . $e->getMessage()]);
        }
    }
}
