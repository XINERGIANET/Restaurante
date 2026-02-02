<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Card;
class CardController extends Controller
{
    public function index(Request $request){
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $cards = Card::query()
            ->when($search, function ($query) use ($search) {
                $query->where('description', 'like', "%{$search}%");
            })
            ->orderBy('order_num')
            ->paginate($perPage)
            ->withQueryString();

        return view('cards.index', compact('cards', 'search', 'perPage', 'allowedPerPage'));
    }

    public function store( Request $request ){
        $request->validate([
            'description' => 'required|string|max:255',
            'type' => 'required|string|max:1',
            'order_num' => 'required|integer',
            'icon' => 'nullable|string|max:255',
        ]);
        try {
            Card::create($request->all());
            return redirect()->route('admin.cards.index')->with('status', 'Tarjeta creada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.cards.index')->withErrors(['error' => 'Error al crear la tarjeta: ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        $card = Card::findOrFail($id);
        return view('cards.edit', compact('card'));
    }

    public function update(Request $request, $id)
    {
        $card = Card::findOrFail($id);
        try {
            $card->update([
                'description' => $request->description,
                'type' => $request->type,
                'order_num' => $request->order_num,
                'icon' => $request->icon,
                'status' => $request->status,
            ]);
            return redirect()->route('admin.cards.index')->with('status', 'Tarjeta actualizada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.cards.index')->withErrors(['error' => 'Error al actualizar la tarjeta: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $card = Card::findOrFail($id);
        try {
            $card->update([
                'status' => 0
            ]);
            $card->delete();
            return redirect()->route('admin.cards.index')->with('status', 'Tarjeta eliminada correctamente');
        } catch (\Exception $e) {
            return redirect()->route('admin.cards.index')->withErrors(['error' => 'Error al eliminar la tarjeta: ' . $e->getMessage()]);
        }
    }
}
