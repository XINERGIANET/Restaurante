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
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;

class ShiftCashController extends Controller
{
    public function redirectBase(Request $request)
    {
        $branchId = \effective_branch_id();
        $query = CashRegister::where('status', '1');
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }
        $firstBox = $query->first();
        if ($firstBox) {
            $params = ['cash_register_id' => $firstBox->id];
            if ($request->filled('view_id')) {
                $params['view_id'] = $request->input('view_id');
            }
            return redirect()->route('shift-cash.index', $params);
        }
        abort(404, 'No hay cajas registradas');
    }

    public function index(Request $request, $cash_register_id = null)
    {
        $branchId = \effective_branch_id();
        $cashRegistersQuery = CashRegister::where('status', '1');
        if ($branchId) {
            $cashRegistersQuery->where('branch_id', $branchId);
        }
        $cashRegisters = $cashRegistersQuery->orderBy('number', 'asc')->get();

        if (empty($cash_register_id)) {
            if ($cashRegisters->isNotEmpty()) {
                $defaultId = $cashRegisters->first()->id;                
                $params = ['cash_register_id' => $defaultId];
                if ($request->filled('view_id')) {
                    $params['view_id'] = $request->input('view_id');
                }
                
                return redirect()->route('shift-cash.index', $params);
            } else {
                abort(404, 'No hay cajas registradas');
            }
        }

        $selectedBoxId = $cash_register_id;

        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        $viewId = $request->input('view_id');
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

        $selectedBoxId = $request->input('cash_register_id') ?? $cash_register_id;
        if (empty($selectedBoxId) && $cashRegisters->isNotEmpty()) {
            $selectedBoxId = $cashRegisters->first()->id;
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
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->whereHas('cashMovementStart', function($q) use ($selectedBoxId) {
                $q->where('cash_register_id', $selectedBoxId);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q2) use ($search) {
                    $q2->whereHas('cashMovementStart.movement', function ($q) use ($search) {
                        $q->where('number', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('cashMovementEnd.movement', function ($q) use ($search) {
                        $q->where('number', 'ILIKE', "%{$search}%");
                    });
                });
            })
            ->orderBy('started_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();

        
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
            'selectedBoxId'   => $selectedBoxId,
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

    public function print(Request $request, CashShiftRelation $shiftCash)
    {
        $branchId = \effective_branch_id();

        $shift = CashShiftRelation::query()
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
            ->where('id', $shiftCash->id)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->firstOrFail();

        $printedAt = now();
        $viewData = [
            'shift' => $shift,
            'printedAt' => $printedAt,
            'autoPrint' => false,
        ];

        $html = view('shift_cash.print', $viewData)->render();
        $pdfBinary = $this->renderPdfWithWkhtmltopdf($html, 'A4');

        if ($pdfBinary === null) {
            // Fallback: mostrar HTML con autoPrint para al menos poder imprimir
            $viewData['autoPrint'] = true;
            return view('shift_cash.print', $viewData);
        }

        $docName = 'cierre-caja-' . ($shift->cashMovementEnd?->movement?->number ?? $shift->id);
        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $docName . '.pdf"',
        ]);
    }

    private function resolveWkhtmltopdfBinary(): ?string
    {
        $candidates = array_filter([
            env('SNAPPY_PDF_BINARY'),
        ]);

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && file_exists($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function renderPdfWithWkhtmltopdf(string $html, ?string $pageSize = 'A4', array $extraArgs = []): ?string
    {
        $binary = $this->resolveWkhtmltopdfBinary();
        if (!$binary) {
            return null;
        }

        $tmpDir = storage_path('app/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }

        $htmlFile = tempnam($tmpDir, 'shift_html_');
        $pdfFile = tempnam($tmpDir, 'shift_pdf_');

        if ($htmlFile === false || $pdfFile === false) {
            return null;
        }

        $htmlPath = $htmlFile . '.html';
        $pdfPath = $pdfFile . '.pdf';
        @rename($htmlFile, $htmlPath);
        @rename($pdfFile, $pdfPath);

        file_put_contents($htmlPath, $html);

        $args = array_merge([
            $binary,
            '--enable-local-file-access',
            '--disable-javascript',
            '--load-error-handling', 'ignore',
            '--load-media-error-handling', 'ignore',
            '--encoding', 'utf-8',
            '--margin-top', '10',
            '--margin-right', '10',
            '--margin-bottom', '10',
            '--margin-left', '10',
        ], $extraArgs);

        if (!empty($pageSize)) {
            $args[] = '--page-size';
            $args[] = $pageSize;
        }

        $args = array_merge($args, [
            $htmlPath,
            $pdfPath,
        ]);

        $process = new Process($args);

        try {
            $process->setTimeout(120);
            $process->run();
            $pdfExists = file_exists($pdfPath) && filesize($pdfPath) > 0;
            if (!$pdfExists) {
                Log::warning('wkhtmltopdf fallo al generar PDF de cierre de caja', [
                    'error' => $process->getErrorOutput(),
                    'output' => $process->getOutput(),
                ]);
                return null;
            }

            $content = file_get_contents($pdfPath);
            return $content === false ? null : $content;
        } catch (\Throwable $e) {
            Log::warning('Error ejecutando wkhtmltopdf para cierre de caja: ' . $e->getMessage());
            return null;
        } finally {
            @unlink($htmlPath);
            @unlink($pdfPath);
        }
    }
}