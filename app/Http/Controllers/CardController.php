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
}
