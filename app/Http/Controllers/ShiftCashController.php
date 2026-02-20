<?php

namespace App\Http\Controllers;

use App\Models\CashShiftRelation;
use App\Models\Operation;
use App\Models\CashRegister;
use App\Models\DocumentType;
use App\Models\PaymentConcept;
use App\Models\Shift;
use App\Models\CashMovementDetail;
use App\Models\CashMovements;
use App\Models\Bank;
use App\Models\PaymentMethod;
use App\Models\Card;
use App\Models\PaymentGateways;
use App\Models\DigitalWallet;
use Illuminate\Http\Request;

class ShiftCashController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $viewId = $request->input('view_id');
        $branchId = $request->session()->get('branch_id');
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $operaciones = collect();

        if ($viewId && $branchId && $profileId) {
            $operaciones = Operation::query()
                ->select('operations.*')
                ->join('branch_operation', function ($join) use ($branchId) {
                    $join->on('branch_operation.operation_id', '=', 'operations.id')
                        ->where('branch_operation.branch_id', $branchId)
                        ->where('branch_operation.status', 1)
                        ->whereNull('branch_operation.deleted_at');
                })
                ->join('operation_profile_branch', function ($join) use ($branchId, $profileId) {
                    $join->on('operation_profile_branch.operation_id', '=', 'operations.id')
                        ->where('operation_profile_branch.branch_id', $branchId)
                        ->where('operation_profile_branch.profile_id', $profileId)
                        ->where('operation_profile_branch.status', 1)
                        ->whereNull('operation_profile_branch.deleted_at');
                })
                ->where('operations.status', 1)
                ->where('operations.view_id', $viewId)
                ->whereNull('operations.deleted_at')
                ->orderBy('operations.id')
                ->distinct()
                ->get();
        }

        $shift_cash = CashShiftRelation::query()
            ->with([
                'cashMovementStart.movement.documentType',
                'cashMovementStart.movement.movementType',
                'cashMovementEnd.movement.documentType',
                'cashMovementEnd.movement.movementType',
                'branch',                
                'movements.paymentConcept',
                'movements.details.paymentMethod',
                'movements.movement.salesMovement',
                'movements.movement.warehouseMovement',
                'movements.movement.orderMovement' => function ($query) {
                    $query->where('status', 'FINALIZADO');
                }
            ])
            ->when($search, function ($query, $search) {
                $query->whereHas('cashMovementStart.movement', function ($q) use ($search) {
                    $q->where('number', 'ILIKE', "%{$search}%");
                })
                ->orWhereHas('cashMovementEnd.movement', function ($q) use ($search) {
                    $q->where('number', 'ILIKE', "%{$search}%");
                });
            })
            ->orderBy('started_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        $cashRegisters = CashRegister::where('status', '1')->orderBy('number', 'asc')->get();
        
        $documentTypes = DocumentType::where('movement_type_id', 4)->get();
        $docIngreso = $documentTypes->firstWhere('name', 'Ingreso');
        $ingresoDocId = $docIngreso ? $docIngreso->id : '';
        $docEgreso = $documentTypes->firstWhere('name', 'Egreso');
        $egresoDocId = $docEgreso ? $docEgreso->id : '';

        $conceptsIngreso = PaymentConcept::where('type', 'I')
            ->where(function ($query) {
                $query->where('restricted', false)
                    ->orWhere('description', 'like', '%Apertura%');
            })
            ->get();

        $conceptsEgreso = PaymentConcept::where('type', 'E')
            ->where(function ($query) {
                $query->where('restricted', false)
                    ->orWhere('description', 'like', '%Cierre%');
            })
            ->get();

        $shifts = Shift::where('branch_id', session('branch_id'))->get();
        $paymentMethods = PaymentMethod::where('status', true)->orderBy('order_num', 'asc')->get();
        $banks = Bank::where('status', true)->orderBy('order_num', 'asc')->get();
        $paymentGateways = PaymentGateways::where('status', true)->orderBy('order_num', 'asc')->get();
        $digitalWallets = DigitalWallet::where('status', true)->orderBy('order_num', 'asc')->get();
        $cards = Card::where('status', true)->orderBy('order_num', 'asc')->get();

        return view('shift_cash.index', [
            'title'           => 'Gestión de Turnos',
            'shift_cash'      => $shift_cash,
            'search'          => $search,
            'perPage'         => $perPage,
            'operaciones'     => $operaciones,
            'viewId'          => $viewId,
            'documentTypes'   => $documentTypes,
            'ingresoDocId'    => $ingresoDocId,
            'egresoDocId'     => $egresoDocId,
            'cashRegisters'   => $cashRegisters,
            'conceptsIngreso' => $conceptsIngreso,
            'conceptsEgreso'  => $conceptsEgreso,
            'shifts'          => $shifts,
            'paymentMethods'  => $paymentMethods,
            'paymentGateways' => $paymentGateways,
            'banks'           => $banks,
            'digitalWallets'  => $digitalWallets,
            'cards'           => $cards,
        ]);
    }
}