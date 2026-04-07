<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use App\Models\MenuOption;
use App\Models\CashRegister;
use App\Models\Profile;

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

        // Sidebar colapsado para Mozo: isMozo y hasMultipleModules
        View::composer('layouts.app', function ($view) {
            $isMozo = false;
            $hasMultipleModules = false;
            if (Auth::check()) {
                $profileId = Auth::user()->profile_id;
                $mozoId = Profile::query()
                    ->whereNull('deleted_at')
                    ->whereRaw('LOWER(TRIM(name)) = ?', ['mozo'])
                    ->value('id');
                $isMozo = $mozoId && $profileId && (int) $profileId === (int) $mozoId;
                $hasMultipleModules = MenuOption::where('status', 1)->pluck('module_id')->unique()->count() > 1;
            }
            $view->with(compact('isMozo', 'hasMultipleModules'));
        });

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
                $currentCajaId = effective_cash_register_id($branchId ? (int) $branchId : null);
                $currentCaja = $currentCajaId ? $cajas->firstWhere('id', $currentCajaId) : null;

                if (! $currentCaja && session()->has('cash_register_id')) {
                    session()->forget(['cash_register_id', 'cash_register_name', 'cash_register_number']);
                }

                $cashSelectionRequired = cash_register_selection_required($branchId ? (int) $branchId : null);
                $forceCashRegisterModal = $cashSelectionRequired || (bool) session('force_cash_register_modal', false);

                $view->with([
                    'cashRegisters' => $cajas,
                    'selectedCashRegister' => $currentCaja,
                    'cashSelectionRequired' => $cashSelectionRequired,
                    'forceCashRegisterModal' => $forceCashRegisterModal,
                ]);
            }
        });
    }
}
