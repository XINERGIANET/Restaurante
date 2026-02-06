<?php

namespace App\Http\Controllers;
use App\Models\Area;
use App\Models\Table;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index()
    {
        $branchId = session('branch_id');

        $areas = Area::query()
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->orderBy('id')
            ->get(['id', 'name']);

        $tables = Table::query()
            ->when($areas->isNotEmpty(), fn($q) => $q->whereIn('area_id', $areas->pluck('id')))
            ->orderBy('name')
            ->get(['id', 'name', 'area_id', 'capacity', 'situation', 'opened_at']);

        $tablesPayload = $tables->map(function (Table $table) {
            $elapsed = '--:--';
            if ($table->opened_at instanceof \DateTimeInterface) {
                $elapsed = $table->opened_at->format('H:i');
            } elseif (!empty($table->opened_at)) {
                $elapsed = (string) $table->opened_at;
            }

            return [
                'id' => $table->id,
                'name' => $table->name,
                'area_id' => (int) $table->area_id,
                'situation' => $table->situation ?? 'libre',
                'diners' => (int) ($table->capacity ?? 0),
                'waiter' => '-',
                'client' => '-',
                'total' => 0,
                'elapsed' => $elapsed,
            ];
        })->values();

        // Convertir áreas a array para asegurar compatibilidad con Alpine.js
        $areasArray = $areas->map(function ($area) {
            return [
                'id' => (int) $area->id,
                'name' => $area->name,
            ];
        })->values();

        return view('orders.index', [
            'areas' => $areasArray,
            'tables' => $tablesPayload,
        ]);
    }

    public function create(Request $request)
    {
        $tableId = $request->query('table_id');
        $branchId = session('branch_id');

        $table = Table::query()
            ->when($branchId, function ($query) use ($branchId) {
                $query->where('branch_id', $branchId);
            })
            ->with('area:id,name')
            ->findOrFail($tableId);

        return view('orders.create', [
            'table' => $table,
        ]);
    }
}
