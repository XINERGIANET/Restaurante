<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfDay();
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();

        // Filtros del reporte diario por vendedor (params separados para no pisar el filtro global)
        $reportStart = $request->input('report_start')
            ? \Carbon\Carbon::parse($request->input('report_start'))->startOfDay()
            : now()->startOfWeek(\Carbon\Carbon::MONDAY)->startOfDay();
        $reportEnd = $request->input('report_end')
            ? \Carbon\Carbon::parse($request->input('report_end'))->endOfDay()
            : now()->endOfDay();
        $reportType   = $request->input('report_type', 'ventas'); // ventas | compras | ambos

        $branchId       = session('branch_id');
        $cashRegisterId = session('cash_register_id');

        // 1. Totales de tarjetas métricas en el rango de fechas seleccionado

        // Ventas: el cash_movement está en un movimiento HIJO (parent_movement_id = sales_movements.movement_id)
        $totalVentas = \App\Models\SalesMovement::whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
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
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
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
            ->whereHas('paymentConcept', fn ($q) => $q->where('type', 'I')->where('restricted', false))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->where('cash_register_id', $cashRegisterId))
            ->sum('total');

        // Salidas: egresos manuales de la caja activa (concepto tipo 'E', no restringido)
        $totalSalidas = \App\Models\CashMovements::whereBetween('created_at', [$startDate, $endDate])
            ->whereHas('paymentConcept', fn ($q) => $q->where('type', 'E')->where('restricted', false))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->where('cash_register_id', $cashRegisterId))
            ->sum('total');

        // 2. Monthly Sales & Purchases (Current Year or Selected Range)
        $salesByMonth = \App\Models\SalesMovement::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
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
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->whereExists(function ($sub) use ($cashRegisterId) {
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
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $limitMonth = ($startDate->year == now()->year) ? now()->month : 12;
        $monthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];

        $monthlySalesData     = [];
        $monthlyPurchasesData = [];
        $monthlyProfitData    = [];
        $monthlyLabels        = [];
        for ($i = 1; $i <= $limitMonth; $i++) {
            $sales         = (float) ($salesByMonth[$i]     ?? 0);
            $purchases     = (float) ($purchasesByMonth[$i] ?? 0);
            $otherExpenses = (float) ($expensesByMonth[$i]  ?? 0);

            $monthlySalesData[]     = $sales;
            $monthlyPurchasesData[] = $purchases;
            $monthlyProfitData[]    = $sales - ($purchases + $otherExpenses);
            $monthlyLabels[]        = $monthNames[$i - 1];
        }

        // 3. Top Products (mes actual completo, independiente del filtro de día)
        $topProductsRaw = \App\Models\OrderMovementDetail::select(
                'product_id',
                \DB::raw('count(*) as cnt'),
                \DB::raw('SUM(amount) as total_sales')
            )
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->groupBy('product_id')
            ->orderByDesc('total_sales')
            ->limit(10)
            ->with('product')
            ->get();

        $topProducts = $topProductsRaw->map(fn($item) => [
            'name'        => $item->product->description ?? 'Producto',
            'count'       => (int) $item->cnt,
            'total_sales' => (float) $item->total_sales,
        ]);

        // 4. Financial Balance (Income vs Expenses)
        $incomeByMonth = \App\Models\CashMovements::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'I')->where('restricted', false))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($cashRegisterId, fn ($q) => $q->where('cash_register_id', $cashRegisterId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $incomeTrend  = [];
        $expenseTrend = [];
        for ($i = 1; $i <= $limitMonth; $i++) {
            $incomeTrend[]  = (float) ($incomeByMonth[$i]  ?? 0);
            $expenseTrend[] = (float) ($expensesByMonth[$i] ?? 0);
        }

        // 5. Daily Trend (Tendencia Diaria)
        $period    = \Carbon\CarbonPeriod::create($startDate->copy()->startOfDay(), $endDate->copy()->startOfDay());
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

        $dailySales     = array_map(fn($d) => (float)($dailySalesRaw[$d]     ?? 0), $dateRange);
        $dailyPurchases = array_map(fn($d) => (float)($dailyPurchasesRaw[$d] ?? 0), $dateRange);
        $dailyEntradas  = array_map(fn($d) => (float)($dailyEntradasRaw[$d]  ?? 0), $dateRange);
        $dailySalidas   = array_map(fn($d) => (float)($dailySalidasRaw[$d]   ?? 0), $dateRange);

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
                'total'  => array_sum($monthTotals),
                'months' => $monthTotals,
            ];
        }
        usort($sellerReport, fn($a, $b) => $b['total'] <=> $a['total']);

        $totalVentasAnual   = array_sum(array_column($sellerReport, 'total'));
        $mesesConVentas     = $sellerSalesRaw->pluck('month')->unique()->count();
        $ventasPromedio     = $mesesConVentas > 0 ? $totalVentasAnual / $mesesConVentas : 0;

        // 7. Reporte diario por vendedor (ventas o compras)
        $reportPeriod = \Carbon\CarbonPeriod::create($reportStart->copy(), $reportEnd->copy()->startOfDay());
        $reportDates  = collect($reportPeriod)->map(fn($d) => $d->format('Y-m-d'))->values()->toArray();

        // Etiquetas de meses para el reporte (año del reportStart)
        $reportMonthNames = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
        $reportYear       = $reportStart->year;

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
        $reportRaw = match($reportType) {
            'compras' => $reportRawCompras,
            default   => $reportRawVentas,
        };

        // Pivotar: seller -> { month(1-12) -> total }
        $reportSellerMap = [];
        foreach ($reportRaw as $row) {
            $s = $row->seller ?? 'Sin usuario';
            $reportSellerMap[$s][(int)$row->month] = (float) $row->total;
        }

        $reportRows = [];
        foreach ($reportSellerMap as $seller => $months) {
            $monthTotals = [];
            for ($i = 1; $i <= 12; $i++) {
                $monthTotals[] = $months[$i] ?? 0;
            }
            $reportRows[] = [
                'seller'    => $seller,
                'total_dif' => array_sum($monthTotals),
                'months'    => $monthTotals,
            ];
        }
        usort($reportRows, fn($a, $b) => $b['total_dif'] <=> $a['total_dif']);

        // Totales para tarjetas
        $reportTotalVentas  = $reportRawVentas->sum('total');
        $reportTotalCompras = $reportRawCompras->sum('total');
        $reportNroDias      = $reportRawVentas->pluck('month')->unique()->count();
        $reportPromedio     = $reportNroDias > 0 ? $reportTotalVentas / $reportNroDias : 0;

        // Datos para gráfico mensual
        $reportChartData        = array_map(fn($i) => (float) $reportRawVentas->where('month', $i)->sum('total'), range(1, 12));
        $reportChartDataCompras = array_map(fn($i) => (float) $reportRawCompras->where('month', $i)->sum('total'), range(1, 12));


        $dashboardData = [
            'accounts' => [
                'Ventas'   => ['total' => $totalVentas,   'diff' => 0, 'transactions' => 0],
                'Compras'  => ['total' => $totalCompras,  'diff' => 0, 'transactions' => 0],
                'Entradas' => ['total' => $totalEntradas, 'diff' => 0, 'transactions' => 0],
                'Salidas'  => ['total' => $totalSalidas,  'diff' => 0, 'transactions' => 0],
            ],
            'monthlySales'     => $monthlySalesData,
            'monthlyPurchases' => $monthlyPurchasesData,
            'monthlyProfit'    => $monthlyProfitData,
            'monthlyLabels'    => $monthlyLabels,
            'topProducts' => $topProducts,
            'incomeTrend' => $incomeTrend,
            'expenseTrend' => $expenseTrend,
            'userName' => auth()->user()->id_persona ? ((\App\Models\Person::find(auth()->user()->id_persona)->full_name) ?? 'Administrador') : 'Administrador',
            'startDate'      => $startDate->format('Y-m-d'),
            'endDate'        => $endDate->format('Y-m-d'),
            'dateRange'      => $dateRange,
            'dailySales'     => $dailySales,
            'dailyPurchases' => $dailyPurchases,
            'dailyEntradas'  => $dailyEntradas,
            'dailySalidas'      => $dailySalidas,
            // Reporte diario por vendedor
            'reportRows'             => $reportRows,
            'reportChartData'        => $reportChartData,
            'reportChartDataCompras' => $reportChartDataCompras,
            'reportMonthNames'       => $reportMonthNames,
            'reportYear'             => $reportYear,
            'reportTotalVentas'      => $reportTotalVentas,
            'reportTotalCompras'     => $reportTotalCompras,
            'reportNroDias'          => $reportNroDias,
            'reportPromedio'         => $reportPromedio,
            'reportType'             => $reportType,
            'sellerReport'     => $sellerReport,
            'totalVentasAnual' => $totalVentasAnual,
            'mesesConVentas'   => $mesesConVentas,
            'ventasPromedio'   => $ventasPromedio,
        ];

        return view('pages.dashboard.ecommerce', compact('dashboardData'));
    }
}
