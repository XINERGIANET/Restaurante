<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuOption;
use App\Models\CashRegister;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        require_once app_path('helpers.php');

        // Compartir quickOptions con el header y el sidebar
        View::composer(['layouts.app-header', 'layouts.sidebar'], function ($view) {
            $quickOptions = MenuOption::where('status', 1)
                ->where('quick_access', 1)
                ->orderBy('id', 'asc')
                ->get();
            
            $view->with('quickOptions', $quickOptions);
        });

        View::composer('*', function ($view) {
            if (Auth::check()) {
                $query = CashRegister::where('status', '1')->orderBy('number', 'asc');
                $branchId = session('branch_id');
                if ($branchId) {
                    $query->where('branch_id', $branchId);
                }
                $cajas = $query->get();
                $currentCajaId = session('cash_register_id');
                if ($cajas->isNotEmpty()) {
                    if (!$currentCajaId || ($branchId && !$cajas->contains('id', $currentCajaId))) {
                        session(['cash_register_id' => $cajas->first()->id]);
                    }
                }
                $view->with('cashRegisters', $cajas);
            }
        });
    }
}
