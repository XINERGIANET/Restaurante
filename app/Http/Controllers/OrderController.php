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
                'area_id' => $table->area_id,
                'situation' => $table->situation ?? 'libre',
                'diners' => $table->capacity ?? 0,
                'waiter' => '-',
                'client' => '-',
                'total' => 0,
                'elapsed' => $elapsed,
            ];
        })->values();

        return view('orders.index', [
            'areas' => $areas,
            'tables' => $tablesPayload,
        ]);
    }
}
