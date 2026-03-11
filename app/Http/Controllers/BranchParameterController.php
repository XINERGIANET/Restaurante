<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\BranchParameter;
use App\Models\ParameterCategories;
use App\Models\Operation;
use App\Models\DocumentType;
use App\Models\TaxRate;
use App\Models\PaymentMethod;

class BranchParameterController extends Controller
{
    public function index(Request $request)
    {
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

        // ==========================================
        // TODAS las categorías con TODOS los parámetros activos.
        // LEFT JOIN branch_parameters: si no existe para esta sucursal, se muestra con valor por defecto.
        // ==========================================
        $categories = collect();

        if ($branchId) {
            $categories = ParameterCategories::whereHas('parameters', function ($query) {
                $query->where('parameters.status', 1)->whereNull('parameters.deleted_at');
            })
                ->with(['parameters' => function ($query) use ($branchId) {
                    $query->where('parameters.status', 1)
                        ->whereNull('parameters.deleted_at')
                        ->leftJoin('branch_parameters', function ($join) use ($branchId) {
                            $join->on('parameters.id', '=', 'branch_parameters.parameter_id')
                                ->where('branch_parameters.branch_id', $branchId)
                                ->whereNull('branch_parameters.deleted_at');
                        })
                        ->select(
                            'parameters.id',
                            'parameters.description',
                            'parameters.value',
                            'parameters.parameter_category_id',
                            'parameters.status',
                            'parameters.created_at',
                            'parameters.updated_at',
                            'parameters.deleted_at',
                            DB::raw('COALESCE(branch_parameters.value, parameters.value) as branch_value'),
                            'branch_parameters.id as branch_parameter_id'
                        )
                        ->orderBy('parameters.id');
                }])
                ->orderBy('id')
                ->get();
        }

        $tiposVenta = DocumentType::where('movement_type_id', 2)->get();

        $igv = TaxRate::where('status', true)->get();

        $paymentMethods = PaymentMethod::where('status', true)->get();

        return view('branch_parameters.index', [
            'title' => 'Parámetros de Sucursal',
            'categories' => $categories,
            'operaciones' => $operaciones,
            'tiposVenta' => $tiposVenta,
            'igv' => $igv,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    public function store(Request $request)
    {
        $branchId = $request->session()->get('branch_id');

        if (!$branchId) {
            return redirect()->back()->with('error', 'No se detectó una sucursal activa en la sesión.');
        }

        $parameters = $request->input('parameters');

        if (is_array($parameters)) {
            DB::beginTransaction();
            try {
                foreach ($parameters as $paramKey => $value) {
                    $valorSeguro = $value ?? '';

                    // Clave puede ser branch_parameter_id (numérico) o "p{parameter_id}" para nuevos
                    if (is_numeric($paramKey)) {
                        $branchParam = BranchParameter::where('id', $paramKey)
                            ->where('branch_id', $branchId)
                            ->first();
                        if ($branchParam) {
                            $branchParam->update(['value' => $valorSeguro]);
                        }
                    } elseif (str_starts_with((string) $paramKey, 'p') && is_numeric(substr($paramKey, 1))) {
                        $parameterId = (int) substr($paramKey, 1);
                        $branchParam = BranchParameter::where('parameter_id', $parameterId)
                            ->where('branch_id', $branchId)
                            ->whereNull('deleted_at')
                            ->first();
                        if ($branchParam) {
                            $branchParam->update(['value' => $valorSeguro]);
                        } else {
                            BranchParameter::create([
                                'parameter_id' => $parameterId,
                                'branch_id' => $branchId,
                                'value' => $valorSeguro,
                            ]);
                        }
                    }
                }
                DB::commit();

                return redirect()->back()->with('success', 'Parámetros actualizados correctamente.');
            } catch (\Exception $e) {
                DB::rollBack();
                return redirect()->back()->with('error', 'Ocurrió un error al actualizar los parámetros: ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('warning', 'No se enviaron datos para actualizar.');
    }
}
