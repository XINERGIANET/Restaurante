<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $startDate = $request->input('start_date') ? \Carbon\Carbon::parse($request->input('start_date'))->startOfDay() : now()->startOfYear();
        $endDate = $request->input('end_date') ? \Carbon\Carbon::parse($request->input('end_date'))->endOfDay() : now()->endOfDay();
        
        $branchId = session('branch_id');

        // 1. Account Balances (Filtered by date if provided)
        $cajaPrincipal = \App\Models\CashMovementDetail::where('payment_method_id', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');
        
        $bancoBCP = \App\Models\CashMovementDetail::where('bank_id', 1)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $interbank = \App\Models\CashMovementDetail::where('bank_id', 3)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        $walletDigital = \App\Models\CashMovementDetail::whereNotNull('digital_wallet_id')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->sum('amount');

        // 2. Monthly Sales & Purchases (Current Year or Selected Range)
        $salesByMonth = \App\Models\SalesMovement::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $purchasesByMonth = \App\Models\PurchaseMovement::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('month')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $expensesByMonth = \App\Models\CashMovements::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'E'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $monthlySalesData = [];
        $monthlyProfitData = [];
        for ($i = 1; $i <= 12; $i++) {
            $sales = (float) ($salesByMonth[$i] ?? 0);
            $purchases = (float) ($purchasesByMonth[$i] ?? 0);
            $otherExpenses = (float) ($expensesByMonth[$i] ?? 0);
            
            $monthlySalesData[] = $sales;
            $monthlyProfitData[] = $sales - ($purchases + $otherExpenses);
        }

        // 3. Top Products
        $topProducts = \App\Models\OrderMovementDetail::select('product_id', \DB::raw('count(*) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('product_id')
            ->orderByDesc('total')
            ->limit(2)
            ->with('product')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->product->description ?? 'Producto',
                    'count' => $item->total,
                ];
            });

        // 4. Financial Balance (Income vs Expenses)
        $incomeByMonth = \App\Models\CashMovements::selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(total) as total')
            ->whereYear('created_at', $startDate->year)
            ->whereHas('paymentConcept', fn($q) => $q->where('type', 'I'))
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->groupBy('month')
            ->get()->pluck('total', 'month')->toArray();

        $incomeTrend = [];
        $expenseTrend = [];
        $limitMonth = ($startDate->year == now()->year) ? now()->month : 12;
        for ($i = 1; $i <= $limitMonth; $i++) {
            $incomeTrend[] = (float) ($incomeByMonth[$i] ?? 0);
            $expenseTrend[] = (float) ($expensesByMonth[$i] ?? 0);
        }

        // 5. Recent Products
        $recentProducts = \App\Models\OrderMovementDetail::with(['product.category', 'orderMovement'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($detail) {
                $price = (float) $detail->amount;
                $profitPercent = match(rand(1, 4)) { 1 => 35, 2 => 60, 3 => 75, default => 100 };
                return [
                    'name' => $detail->description ?: ($detail->product->description ?? 'Producto'),
                    'sales' => 'S/' . number_format($price, 2),
                    'profit_percent' => $profitPercent,
                    'image' => ($detail->product && $detail->product->image) ? asset('storage/' . $detail->product->image) : null,
                ];
            });

        $dashboardData = [
            'accounts' => [
                'caja' => ['total' => $cajaPrincipal, 'diff' => 12.5, 'transactions' => rand(100, 200)],
                'bcp' => ['total' => $bancoBCP, 'diff' => 8.2, 'transactions' => rand(50, 100)],
                'interbank' => ['total' => $interbank, 'diff' => -2.1, 'transactions' => rand(40, 80)],
                'wallet' => ['total' => $walletDigital, 'diff' => 15.8, 'transactions' => rand(150, 250)],
            ],
            'monthlySales' => $monthlySalesData,
            'monthlyProfit' => $monthlyProfitData,
            'topProducts' => $topProducts,
            'incomeTrend' => $incomeTrend,
            'expenseTrend' => $expenseTrend,
            'recentProducts' => $recentProducts,
            'userName' => auth()->user()->id_persona ? ((\App\Models\Person::find(auth()->user()->id_persona)->full_name) ?? 'Administrador') : 'Administrador',
            'startDate' => $startDate->format('Y-m-d'),
            'endDate' => $endDate->format('Y-m-d'),
        ];

        return view('pages.dashboard.ecommerce', compact('dashboardData'));
    }
}
