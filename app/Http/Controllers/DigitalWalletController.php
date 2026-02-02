<?php

namespace App\Http\Controllers;

use App\Models\DigitalWallet;
use Illuminate\Http\Request;

class DigitalWalletController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $digitalWallets = DigitalWallet::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();

        return view('digital_wallets.index', compact('digitalWallets', 'search', 'perPage', 'allowedPerPage'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            DigitalWallet::create([
                'description' => $request->description,
                'order_num' => $request->order_num,
                'status' => $request->status ?? true,
            ]);

            return redirect()->route('admin.digital_wallets.index')
                ->with('status', 'Billetera digital creada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.digital_wallets.index')
                ->withErrors(['error' => 'Error al crear la billetera digital: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function update(Request $request, DigitalWallet $digitalWallet)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'order_num' => 'required|integer',
            'status' => 'nullable|boolean',
        ]);

        try {
            $digitalWallet->update([
                'description' => $request->description,
                'order_num' => $request->order_num,
                'status' => $request->status ?? true,
            ]);

            return redirect()->route('admin.digital_wallets.index')
                ->with('status', 'Billetera digital actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.digital_wallets.index')
                ->withErrors(['error' => 'Error al actualizar la billetera digital: ' . $e->getMessage()])
                ->withInput();
        }
    }

    public function destroy(DigitalWallet $digitalWallet)
    {
        try {
            $digitalWallet->delete();
            return redirect()->route('admin.digital_wallets.index')
                ->with('status', 'Billetera digital eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.digital_wallets.index')
                ->withErrors(['error' => 'Error al eliminar la billetera digital: ' . $e->getMessage()]);
        }
    }
}   
