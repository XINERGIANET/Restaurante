<?php

namespace App\Http\Controllers;

use App\Services\ShiftCashClosePdfService;
use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private function dashboardMovementNumber(?\App\Models\Movement $movement, string $fallbackPrefix = 'Movimiento'): string
    {
        $number = trim((string) ($movement?->number ?? ''));
        if ($number === '') {
            return $fallbackPrefix;
        }

        return $fallbackPrefix.' #'.$number;
    }

    private function mapDashboardSalesItems(\App\Models\SalesMovement $salesMovement): array
    {
        $movement = $salesMovement->movement;
        $documentNumber = trim((string) ($movement?->number ?? ''));
        $series = trim((string) ($salesMovement->series ?? ''));
        $label = trim(($series !== '' ? $series.'-' : '').($documentNumber !== '' ? $documentNumber : (string) $salesMovement->id), '-');
        $customer = trim((string) ($movement?->person_name ?? '')) ?: 'Público General';
        $seller = trim((string) ($movement?->responsible_name ?? $movement?->user_name ?? '')) ?: 'Sistema';
        $movedAt = $movement?->moved_at ?? $salesMovement->created_at;

        $lines = ($salesMovement->details ?? collect())
            ->filter(fn($detail) => ($detail->status ?? 'A') !== 'C')
            ->map(function (\App\Models\SalesMovementDetail $detail) {
                $qty = (float) ($detail->quantity ?? 0);
                $courtesyQty = max(0, (float) ($detail->courtesy_quantity ?? 0));
                $lineTotal = round((float) ($detail->amount ?? 0), 2);
                $billableQty = max(0, $qty - $courtesyQty);
                $unitAmount = $billableQty > 0
                    ? round($lineTotal / $billableQty, 2)
                    : ($qty > 0 ? round($lineTotal / $qty, 2) : 0.0);

                return [
                    'name' => trim((string) ($detail->description ?? 'Producto')) ?: 'Producto',
                    'qty' => round($qty, 6),
                    'courtesy_qty' => round($courtesyQty, 6),
                    'unit_amount' => $unitAmount,
                    'line_total' => $lineTotal,
                    'comment' => trim((string) ($detail->comment ?? '')),
                    'complements' => collect($detail->complements ?? [])
                        ->map(fn($value) => trim((string) $value))
                        ->filter()
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $linesTotal = round(array_sum(array_map(fn($line) => (float) ($line['line_total'] ?? 0), $lines)), 2);

        return [
            'id' => 'sales-'.$salesMovement->id,
            'label' => $label !== '' ? $label : 'Venta',
            'number' => $this->dashboardMovementNumber($movement, 'Venta'),
            'date' => $movedAt ? $movedAt->format('d/m/Y H:i') : '-',
            'customer' => $customer,
            'seller' => $seller,
            'detail_count' => count($lines),
            'subtotal' => round((float) ($salesMovement->subtotal ?? 0), 2),
            'tax' => round((float) ($salesMovement->tax ?? 0), 2),
            'total' => round((float) ($salesMovement->total ?? 0), 2),
            'lines_total' => $linesTotal,
            'difference' => round((float) ($salesMovement->total ?? 0) - $linesTotal, 2),
            'lines' => $lines,
        ];
    }

    private function mapDashboardPurchaseItems(\App\Models\PurchaseMovement $purchaseMovement): array
    {
        $movement = $purchaseMovement->movement;
        $documentNumber = trim((string) ($movement?->number ?? ''));
        $series = trim((string) ($purchaseMovement->serie ?? ''));
        $label = trim(($series !== '' ? $series.'-' : '').($documentNumber !== '' ? $documentNumber : (string) $purchaseMovement->id), '-');
        $supplier = trim((string) ($movement?->person_name ?? '')) ?: 'Proveedor';
        $seller = trim((string) ($movement?->responsible_name ?? $movement?->user_name ?? '')) ?: 'Sistema';
        $movedAt = $movement?->moved_at ?? $purchaseMovement->created_at;

        $lines = ($purchaseMovement->details ?? collect())
            ->filter(fn($detail) => ($detail->situacion ?? 'A') !== 'C')
            ->map(function (\App\Models\PurchaseMovementDetail $detail) {
                $qty = (float) ($detail->cantidad ?? 0);
                $lineTotal = round((float) ($detail->monto ?? 0), 2);
                $unitAmount = $qty > 0 ? round($lineTotal / $qty, 2) : 0.0;

                return [
                    'name' => trim((string) ($detail->descripcion ?? 'Producto')) ?: 'Producto',
                    'qty' => round($qty, 6),
                    'unit_amount' => $unitAmount,
                    'line_total' => $lineTotal,
                    'comment' => trim((string) ($detail->comentario ?? '')),
                ];
            })
            ->values()
            ->all();

        $linesTotal = round(array_sum(array_map(fn($line) => (float) ($line['line_total'] ?? 0), $lines)), 2);

        return [
            'id' => 'purchases-'.$purchaseMovement->id,
            'label' => $label !== '' ? $label : 'Compra',
            'number' => $this->dashboardMovementNumber($movement, 'Compra'),
            'date' => $movedAt ? $movedAt->format('d/m/Y H:i') : '-',
            'supplier' => $supplier,
            'seller' => $seller,
            'detail_count' => count($lines),
            'subtotal' => round((float) ($purchaseMovement->subtotal ?? 0), 2),
            'tax' => round((float) ($purchaseMovement->igv ?? 0), 2),
            'total' => round((float) ($purchaseMovement->total ?? 0), 2),
            'lines_total' => $linesTotal,
            'difference' => round((float) ($purchaseMovement->total ?? 0) - $linesTotal, 2),
            'lines' => $lines,
        ];
    }

    private function mapDashboardCashItems(\App\Models\CashMovements $cashMovement): array
    {
        $movement = $cashMovement->movement;
        $concept = trim((string) ($cashMovement->paymentConcept?->description ?? '')) ?: 'Movimiento de caja';
        $movementType = strtoupper(trim((string) ($cashMovement->paymentConcept?->type ?? '')));
        $kind = $movementType === 'I' ? 'Ingreso' : ($movementType === 'E' ? 'Salida' : 'Movimiento');
        $movedAt = $movement?->moved_at ?? $cashMovement->created_at;

        $lines = ($cashMovement->details ?? collect())
            ->filter(fn($detail) => ($detail->status ?? 'A') !== 'C')
            ->map(function (\App\Models\CashMovementDetail $detail) {
                $parts = array_filter([
                    trim((string) ($detail->payment_method ?? '')),
                    trim((string) ($detail->card ?? '')),
                    trim((string) ($detail->bank ?? '')),
                    trim((string) ($detail->digital_wallet ?? '')),
                    trim((string) ($detail->payment_gateway ?? '')),
                    trim((string) ($detail->number ?? '')),
                ], fn($value) => $value !== '');

                return [
                    'name' => ! empty($parts) ? implode(' · ', $parts) : 'Detalle',
                    'amount' => round((float) ($detail->amount ?? 0), 2),
                    'comment' => trim((string) ($detail->comment ?? '')),
                    'method' => trim((string) ($detail->payment_method ?? '')) ?: 'Otro',
                ];
            })
            ->values()
            ->all();

        $linesTotal = round(array_sum(array_map(fn($line) => (float) ($line['amount'] ?? 0), $lines)), 2);

        return [
            'id' => 'cash-'.$cashMovement->id,
            'label' => $this->dashboardMovementNumber($movement, $kind),
            'number' => $this->dashboardMovementNumber($movement, $kind),
            'kind' => $kind,
            'concept' => $concept,
            'date' => $movedAt ? $movedAt->format('d/m/Y H:i') : '-',
            'cash_register' => trim((string) ($cashMovement->cash_register ?? $cashMovement->cashRegister?->number ?? '')) ?: '-',
            'shift' => trim((string) ($cashMovement->shift?->name ?? '')) ?: '-',
            'detail_count' => count($lines),
            'total' => round((float) ($cashMovement->total ?? 0), 2),
            'lines_total' => $linesTotal,
            'difference' => round((float) ($cashMovement->total ?? 0) - $linesTotal, 2),
            'lines' => $lines,
        ];
    }

    public function index(Request $request)
    {
        if (current_user_is_mozo()) {
            return $this->waiterDashboard($request);
        }

        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfDay();
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        // Filtros del reporte diario por vendedor (params separados para no pisar el filtro global)
        $reportStart = $request->input('report_start')
            ? \Carbon\Carbon::parse($request->input('report_start'))->startOfDay()
            : now()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
        $reportEnd = $request->input('report_end')
            ? \Carbon\Carbon::parse($request->input('report_end'))->endOfDay()
            : now()->endOfDay();
        $reportType = $request->input('report_type', 'ventas'); // ventas | compras | ambos

        $branchId = session('branch_id');
        $cashRegisterId = session('cash_register_id');

        // 1. Totales de tarjetas métricas en el rango de fechas seleccionado

        // Ventas: el cash_movement está en un movimiento HIJO (parent_movement_id = sales_movements.movement_id)
        $totalVentas = \App\Models\SalesMovement::whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'sales_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->sum('total');

        // Compras: mismo patrón padre-hijo
        $totalCompras = \App\Models\PurchaseMovement::whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'purchase_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->sum('total');

        // Entradas: ingresos manuales de la caja activa (concepto tipo 'I', no restringido)
        $totalEntradas = \App\Models\CashMovements::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'I')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->sum('total');

        // Salidas: egresos manuales de la caja activa (concepto tipo 'E', no restringido)
        $totalSalidas = \App\Models\CashMovements::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'E')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->sum('total');

        $salesBreakdown = \App\Models\SalesMovement::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'sales_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->with([
                'movement',
                'details' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'C');
                    })->orderBy('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        $purchaseBreakdown = \App\Models\PurchaseMovement::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'purchase_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->with([
                'movement',
                'details' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('situacion')->orWhere('situacion', '!=', 'C');
                    })->orderBy('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        $cashBreakdown = \App\Models\CashMovements::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn($q) => $q->whereIn('type', ['I', 'E'])->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->with([
                'movement',
                'paymentConcept',
                'cashRegister',
                'shift',
                'details' => function ($query) {
                    $query->where(function ($q) {
                        $q->whereNull('status')->orWhere('status', '!=', 'C');
                    })->orderBy('id');
                },
            ])
            ->orderByDesc('created_at')
            ->get();

        // 2. Monthly Sales & Purchases (Current Year or Selected Range)
        $salesByMonth = \App\Models\SalesMovement::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'sales_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $purchasesByMonth = \App\Models\PurchaseMovement::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'purchase_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $expensesByMonth = \App\Models\CashMovements::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'E')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $limitMonth = ($startDate->year == now()->year) ? now()->month : 12;
        $monthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        $monthlySalesData = [];
        $monthlyPurchasesData = [];
        $monthlyProfitData = [];
        $monthlyLabels = [];
        for ($i = 1; $i <= $limitMonth; $i++) {
            $sales = (float) ($salesByMonth[$i] ?? 0);
            $purchases = (float) ($purchasesByMonth[$i] ?? 0);
            $otherExpenses = (float) ($expensesByMonth[$i] ?? 0);

            $monthlySalesData[] = $sales;
            $monthlyPurchasesData[] = $purchases;
            $monthlyProfitData[] = $sales - ($purchases + $otherExpenses);
            $monthlyLabels[] = $monthNames[$i - 1];
        }

        // 3. Top Products (rango filtrado)
        $topProductsRaw = \App\Models\OrderMovementDetail::select(
            'product_id',
            \DB::raw('count(*) as cnt'),
            \DB::raw('SUM(amount) as total_sales')
        )
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('product')
            ->get();

        $topProducts = $topProductsRaw->map(fn($item) => [
            'name' => $item->product->description ?? 'Producto',
            'count' => (int) $item->cnt,
            'total_sales' => (float) $item->total_sales,
        ]);

        // 4. Financial Balance (Income vs Expenses)
        $incomeByMonth = \App\Models\CashMovements::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'I')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $incomeTrend = [];
        $expenseTrend = [];
        for ($i = 1; $i <= $limitMonth; $i++) {
            $incomeTrend[] = (float) ($incomeByMonth[$i] ?? 0);
            $expenseTrend[] = (float) ($expensesByMonth[$i] ?? 0);
        }

        // 5. Daily Trend (Tendencia Diaria)
        $period = \Carbon\CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
        $dateRange = collect($period)->map(fn($d) => $d->format('Y-m-d'))->values()->toArray();

        $dailySalesRaw = \App\Models\SalesMovement::selectRaw("DATE(created_at) as day, SUM(total) as total")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'sales_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)->whereNull('cm.deleted_at');
            }))
            ->groupBy('day')->pluck('total', 'day')->toArray();

        $dailyPurchasesRaw = \App\Models\PurchaseMovement::selectRaw("DATE(created_at) as day, SUM(total) as total")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'purchase_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)->whereNull('cm.deleted_at');
            }))
            ->groupBy('day')->pluck('total', 'day')->toArray();

        $dailyEntradasRaw = \App\Models\CashMovements::selectRaw("DATE(created_at) as day, SUM(total) as total")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'I')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('day')->pluck('total', 'day')->toArray();

        $dailySalidasRaw = \App\Models\CashMovements::selectRaw("DATE(created_at) as day, SUM(total) as total")
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'E')->where('restricted', false))
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('day')->pluck('total', 'day')->toArray();

        $dailySales = array_map(fn($d) => (float) ($dailySalesRaw[$d] ?? 0), $dateRange);
        $dailyPurchases = array_map(fn($d) => (float) ($dailyPurchasesRaw[$d] ?? 0), $dateRange);
        $dailyEntradas = array_map(fn($d) => (float) ($dailyEntradasRaw[$d] ?? 0), $dateRange);
        $dailySalidas = array_map(fn($d) => (float) ($dailySalidasRaw[$d] ?? 0), $dateRange);

        // 6. Sales by Seller (monthly breakdown for current year)
        $sellerSalesRaw = \App\Models\SalesMovement::selectRaw(
            'movements.user_name as seller,
                 EXTRACT(MONTH FROM sales_movements.created_at)::int as month,
                 SUM(sales_movements.total) as total'
        )
            ->join('movements', 'movements.id', '=', 'sales_movements.movement_id')
            ->whereYear('sales_movements.created_at', $startDate->year)
            ->when($branchId, fn($q) => $q->where('sales_movements.branch_id', $branchId))
            ->groupBy('movements.user_name', 'month')
            ->get();

        // Pivotar: { seller => { month => total } }
        $sellerMap = [];
        foreach ($sellerSalesRaw as $row) {
            $s = $row->seller ?? 'Sin usuario';
            $sellerMap[$s][$row->month] = (float) $row->total;
        }

        $sellerReport = [];
        foreach ($sellerMap as $seller => $months) {
            $monthTotals = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthTotals[] = $months[$i] ?? 0;
            }
            $sellerReport[] = [
                'seller' => $seller,
                'total' => array_sum($monthTotals),
                'months' => $monthTotals,
            ];
        }
        usort($sellerReport, fn($a, $b) => $b['total'] <=> $a['total']);

        $totalVentasAnual = array_sum(array_column($sellerReport, 'total'));
        $mesesConVentas = $sellerSalesRaw->pluck('month')->unique()->count();
        $ventasPromedio = $mesesConVentas > 0 ? $totalVentasAnual / $mesesConVentas : 0;

        // 7. Reporte diario por vendedor (ventas o compras)
        $reportPeriod = \Carbon\CarbonPeriod::create($reportStart->copy(), $reportEnd->copy()->startOfDay());
        $reportDates = collect($reportPeriod)->map(fn($d) => $d->format('Y-m-d'))->values()->toArray();

        // Etiquetas de meses para el reporte (año del reportStart)
        $reportMonthNames = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        $reportYear = $reportStart->year;

        // Query ventas mensual por vendedor
        $reportRawVentas = \App\Models\SalesMovement::selectRaw(
            'movements.user_name as seller,
                 EXTRACT(MONTH FROM sales_movements.created_at)::int as month,
                 SUM(sales_movements.total) as total'
        )
            ->join('movements', 'movements.id', '=', 'sales_movements.movement_id')
            ->whereYear('sales_movements.created_at', $reportYear)
            ->when($branchId, fn($q) => $q->where('sales_movements.branch_id', $branchId))
            ->groupBy('movements.user_name', 'month')
            ->get();

        // Query compras mensual por vendedor
        $reportRawCompras = \App\Models\PurchaseMovement::selectRaw(
            'movements.user_name as seller,
                 EXTRACT(MONTH FROM purchase_movements.created_at)::int as month,
                 SUM(purchase_movements.total) as total'
        )
            ->join('movements', 'movements.id', '=', 'purchase_movements.movement_id')
            ->whereYear('purchase_movements.created_at', $reportYear)
            ->when($branchId, fn($q) => $q->where('purchase_movements.branch_id', $branchId))
            ->groupBy('movements.user_name', 'month')
            ->get();

        // Seleccionar cuál usar para la tabla
        $reportRaw = match ($reportType) {
            'compras' => $reportRawCompras,
            default => $reportRawVentas,
        };

        // Pivotar: seller -> { month(1-12) -> total }
        $reportSellerMap = [];
        foreach ($reportRaw as $row) {
            $s = $row->seller ?? 'Sin usuario';
            $reportSellerMap[$s][(int) $row->month] = (float) $row->total;
        }

        $reportRows = [];
        foreach ($reportSellerMap as $seller => $months) {
            $monthTotals = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthTotals[] = $months[$i] ?? 0;
            }
            $reportRows[] = [
                'seller' => $seller,
                'total_dif' => array_sum($monthTotals),
                'months' => $monthTotals,
            ];
        }
        usort($reportRows, fn($a, $b) => $b['total_dif'] <=> $a['total_dif']);

        // Productos vendidos en el rango del filtro (mismos criterios que total ventas)
        $productsSoldMovements = $this->salesMovementsForProductsSold($startDate, $endDate, $branchId, $cashRegisterId);
        $productsSoldInFilter = app(ShiftCashClosePdfService::class)->consolidateProductsSold($productsSoldMovements);
        usort($productsSoldInFilter, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // Totales para tarjetas
        $reportTotalVentas = $reportRawVentas->sum('total');
        $reportTotalCompras = $reportRawCompras->sum('total');
        $reportNroDias = $reportRawVentas->pluck('month')->unique()->count();
        $reportPromedio = $reportNroDias > 0 ? $reportTotalVentas / $reportNroDias : 0;

        // Datos para gráfico mensual
        $reportChartData = array_map(fn($i) => (float) $reportRawVentas->where('month', $i)->sum('total'), range(1, 12));
        $reportChartDataCompras = array_map(fn($i) => (float) $reportRawCompras->where('month', $i)->sum('total'), range(1, 12));


        $dashboardData = [
            'accounts' => [
                'Ventas' => ['total' => $totalVentas, 'diff' => 0, 'transactions' => $salesBreakdown->count()],
                'Compras' => ['total' => $totalCompras, 'diff' => 0, 'transactions' => $purchaseBreakdown->count()],
                'Entradas' => [
                    'total' => $totalEntradas,
                    'diff' => 0,
                    'transactions' => $cashBreakdown->filter(fn($item) => strtoupper((string) ($item->paymentConcept?->type ?? '')) === 'I')->count(),
                ],
                'Salidas' => [
                    'total' => $totalSalidas,
                    'diff' => 0,
                    'transactions' => $cashBreakdown->filter(fn($item) => strtoupper((string) ($item->paymentConcept?->type ?? '')) === 'E')->count(),
                ],
            ],
            'accountBreakdowns' => [
                'sales' => [
                    'title' => 'Ventas',
                    'subtitle' => 'Cada venta y sus líneas de producto que alimentan la tarjeta.',
                    'formula' => 'Total de ventas = suma de cada comprobante y sus líneas facturadas.',
                    'color' => '#2979ff',
                    'total' => round($totalVentas, 2),
                    'transactions' => $salesBreakdown->count(),
                    'items' => $salesBreakdown->map(fn(SalesMovement $sale) => $this->mapDashboardSalesItems($sale))->values()->all(),
                ],
                'purchases' => [
                    'title' => 'Compras',
                    'subtitle' => 'Cada compra y el detalle de productos que compone el monto.',
                    'formula' => 'Total de compras = suma de cada documento de compra.',
                    'color' => '#FE0000',
                    'total' => round($totalCompras, 2),
                    'transactions' => $purchaseBreakdown->count(),
                    'items' => $purchaseBreakdown->map(fn(PurchaseMovement $purchase) => $this->mapDashboardPurchaseItems($purchase))->values()->all(),
                ],
                'entries' => [
                    'title' => 'Entradas',
                    'subtitle' => 'Ingresos manuales registrados en caja y su desglose por método.',
                    'formula' => 'Total de entradas = suma de ingresos de caja.',
                    'color' => '#03B430',
                    'total' => round($totalEntradas, 2),
                    'transactions' => $cashBreakdown->filter(fn($movement) => strtoupper((string) ($movement->paymentConcept?->type ?? '')) === 'I')->count(),
                    'items' => $cashBreakdown
                        ->filter(fn($movement) => strtoupper((string) ($movement->paymentConcept?->type ?? '')) === 'I')
                        ->map(fn(CashMovements $movement) => $this->mapDashboardCashItems($movement))
                        ->values()
                        ->all(),
                ],
                'expenses' => [
                    'title' => 'Salidas',
                    'subtitle' => 'Egresos manuales registrados en caja y su desglose por método.',
                    'formula' => 'Total de salidas = suma de egresos de caja.',
                    'color' => '#FFA500',
                    'total' => round($totalSalidas, 2),
                    'transactions' => $cashBreakdown->filter(fn($movement) => strtoupper((string) ($movement->paymentConcept?->type ?? '')) === 'E')->count(),
                    'items' => $cashBreakdown
                        ->filter(fn($movement) => strtoupper((string) ($movement->paymentConcept?->type ?? '')) === 'E')
                        ->map(fn(CashMovements $movement) => $this->mapDashboardCashItems($movement))
                        ->values()
                        ->all(),
                ],
            ],
            'monthlySales' => $monthlySalesData,
            'monthlyPurchases' => $monthlyPurchasesData,
            'monthlyProfit' => $monthlyProfitData,
            'monthlyLabels' => $monthlyLabels,
            'topProducts' => $topProducts,
            'incomeTrend' => $incomeTrend,
            'expenseTrend' => $expenseTrend,
            'userName' => auth()->user()->id_persona ? ((\App\Models\Person::find(auth()->user()->id_persona)->full_name) ?? 'Administrador') : 'Administrador',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'dateRange' => $dateRange,
            'dailySales' => $dailySales,
            'dailyPurchases' => $dailyPurchases,
            'dailyEntradas' => $dailyEntradas,
            'dailySalidas' => $dailySalidas,
            // Reporte diario por vendedor
            'reportRows' => $reportRows,
            'reportChartData' => $reportChartData,
            'reportChartDataCompras' => $reportChartDataCompras,
            'reportMonthNames' => $reportMonthNames,
            'reportYear' => $reportYear,
            'reportTotalVentas' => $reportTotalVentas,
            'reportTotalCompras' => $reportTotalCompras,
            'reportNroDias' => $reportNroDias,
            'reportPromedio' => $reportPromedio,
            'reportType' => $reportType,
            'sellerReport' => $sellerReport,
            'totalVentasAnual' => $totalVentasAnual,
            'mesesConVentas' => $mesesConVentas,
            'ventasPromedio' => $ventasPromedio,
            'productsSoldInFilter' => $productsSoldInFilter,
        ];

        return view('pages.dashboard.ecommerce', compact('dashboardData'));
    }

    private function waiterDashboard(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        $branchId = session('branch_id');

        $orders = \App\Models\OrderMovement::query()
            ->with([
                'table.area',
                'area',
                'movement',
                'details' => function ($query) {
                    $query
                        ->where(function ($q) {
                            $q->whereNull('status')->orWhere('status', '!=', 'C');
                        })
                        ->with(['product', 'unit'])
                        ->orderBy('created_at')
                        ->orderBy('id');
                },
            ])
            ->whereHas('movement', function ($query) use ($startDate, $endDate) {
                $query->whereBetween('moved_at', [$startDate, $endDate]);
            })
            ->when($branchId, fn($query) => $query->where('branch_id', $branchId))
            ->orderByDesc('updated_at')
            ->get();

        $totalItems = $orders->sum(function ($order) {
            return $order->details->sum(fn($detail) => (float) ($detail->quantity ?? 0));
        });

        $finishedOrders = $orders->filter(fn($order) => in_array($order->status, ['FINALIZADO', 'F'], true))->count();
        $pendingOrders = $orders->filter(fn($order) => in_array($order->status, ['PENDIENTE', 'P'], true))->count();
        $waiterNames = $orders->map(function ($order) {
            $name = trim((string) ($order->movement?->responsible_name ?? ''));
            return $name !== '' ? $name : 'Sin mozo';
        })->unique()->values();

        $dashboardData = [
            'orders' => $orders,
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
            'waiterName' => 'Todos los mozos',
            'summary' => [
                'tables' => $orders->pluck('table_id')->filter()->unique()->count(),
                'orders' => $orders->count(),
                'items' => $totalItems,
                'finished' => $finishedOrders,
                'pending' => $pendingOrders,
                'waiters' => $waiterNames->count(),
            ],
            'waiterNames' => $waiterNames,
        ];

        return view('pages.dashboard.waiter', compact('dashboardData'));
    }

    public function productsSoldPdf(Request $request)
    {
        $startDate = $request->input('start_date') ? Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfDay();
        $endDate = $request->input('end_date') ? Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        $branchId = session('branch_id');
        $cashRegisterId = session('cash_register_id');

        $movements = $this->salesMovementsForProductsSold($startDate, $endDate, $branchId, $cashRegisterId);
        $rows = app(ShiftCashClosePdfService::class)->consolidateProductsSold($movements);
        usort($rows, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $totalQty = array_sum(array_column($rows, 'qty'));
        $totalAmount = array_sum(array_column($rows, 'amount'));

        $fileName = 'productos-vendidos-' . $startDate->format('Y-m-d') . '-' . $endDate->format('Y-m-d') . '.pdf';
        $fileName = preg_replace('/[^\p{L}\p{N}_\-.]+/u', '-', (string) $fileName);

        try {
            $pdf = PDF::loadView('pages.dashboard.products-sold-pdf', [
                'rows' => $rows,
                'startDate' => $startDate,
                'endDate' => $endDate,
                'totalQty' => $totalQty,
                'totalAmount' => $totalAmount,
            ]);

            $pdf->setPaper('a4')
                ->setOption('margin-bottom', 10)
                ->setOption('encoding', 'utf-8')
                ->setOption('enable-local-file-access', true);

            return $pdf->download($fileName);
        } catch (\Throwable $e) {
            Log::warning('PDF productos vendidos dashboard (Snappy): ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            return response()
                ->view('pages.dashboard.products-sold-pdf', [
                    'rows' => $rows,
                    'startDate' => $startDate,
                    'endDate' => $endDate,
                    'totalQty' => $totalQty,
                    'totalAmount' => $totalAmount,
                    'pdfGenerationFailed' => true,
                ], 200)
                ->header('X-Pdf-Error', '1');
        }
    }

    /**
     * @return Collection<int, \App\Models\SalesMovement>
     */
    private function salesMovementsForProductsSold(Carbon $startDate, Carbon $endDate, $branchId, $cashRegisterId): Collection
    {
        return \App\Models\SalesMovement::query()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
                $sub->selectRaw('1')
                    ->from('movements as m')
                    ->join('cash_movements as cm', 'cm.movement_id', '=', 'm.id')
                    ->whereColumn('m.parent_movement_id', 'sales_movements.movement_id')
                    ->where('cm.cash_register_id', $cashRegisterId)
                    ->whereNull('cm.deleted_at');
            }))
            ->with([
                'details' => function ($q) {
                    $q->where('status', '!=', 'C');
                }
            ])
            ->get();
    }
}
