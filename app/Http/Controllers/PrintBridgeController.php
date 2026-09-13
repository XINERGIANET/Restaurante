<?php

namespace App\Http\Controllers;

use App\Models\ThermalPrintJob;
use App\Models\PrintStation;
use App\Models\PrinterBranch;
use App\Services\PrintBridgeQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PrintBridgeController extends Controller
{
    public function worker(Request $request): View
    {
        if (! config('qz.enabled', true)) {
            abort(404, 'QZ Tray está desactivado en configuración.');
        }
        $printer = trim((string) $request->query('printer', 'BARRA2')) ?: 'BARRA2';
        if (! app(PrintBridgeQueue::class)->isStationPrinterName($printer)) {
            abort(400, 'Impresora no permitida para el puente.');
        }

        return view('print-bridge.worker', [
            'title' => 'Puente BARRA2 (QZ)',
            'targetPrinter' => $printer,
        ]);
    }

    public function pull(Request $request, PrintBridgeQueue $queue): JsonResponse
    {
        if (! config('qz.enabled', true)) {
            return response()->json(['job' => null, 'message' => 'deshabilitado'], 200);
        }
        $request->validate([
            'printer_name' => 'nullable|string|max:120',
            'station_uuid' => 'nullable|uuid',
        ]);
        $name = trim((string) $request->input('printer_name', 'BARRA2')) ?: 'BARRA2';
        if (! $request->filled('station_uuid') && ! $queue->isStationPrinterName($name)) {
            return response()->json(['message' => 'impresora no permitida'], 422);
        }
        $branchId = (int) session('branch_id');
        if (! $branchId) {
            return response()->json(['job' => null, 'message' => 'sin sucursal en sesión'], 200);
        }
        if ($request->filled('station_uuid')) {
            $station = PrintStation::query()
                ->where('uuid', $request->string('station_uuid'))
                ->where('branch_id', $branchId)
                ->where('status', 'E')
                ->first();

            if ($station) {
                if (! $station->last_seen_at || $station->last_seen_at->lt(now()->subSeconds(10))) {
                    $station->forceFill(['last_seen_at' => now()])->save();
                }

                // 1. Primero revisar impresoras asignadas a esta estación
                $assignedPrinters = $station->printers()->where('status', 'E')->orderBy('id')->get();

                foreach ($assignedPrinters as $assignedPrinter) {
                    $stationJob = $this->claimPendingThermalPrintJob($branchId, (string) $assignedPrinter->name)
                        ?: $this->nextLegacyQueuedJob($queue, $branchId, (string) $assignedPrinter->name);
                    if ($stationJob) {
                        $stationJob['printer_name'] = filled($assignedPrinter->driver_name) ? $assignedPrinter->driver_name : $assignedPrinter->name;
                        $stationJob['configured_printer_name'] = $assignedPrinter->name;
                        $stationJob['printer_ip'] = $assignedPrinter->ip;
                        $stationJob['printer_port'] = (int) ($assignedPrinter->port ?: 9100);
                        return response()->json(['job' => $stationJob, 'station' => $station->name]);
                    }
                }

                // Una sola consulta para LAN/no asignadas. Nunca toma la USB de otra PC.
                $unmatchedJob = $this->claimAnyPendingThermalPrintJobForBranch($branchId, (int) $station->id);
                if ($unmatchedJob) {
                    return response()->json(['job' => $unmatchedJob, 'station' => $station->name]);
                }

                return response()->json(['job' => null, 'station' => $station->name]);
            }
        }

        $job = $this->claimPendingThermalPrintJob($branchId, $name);
        if (! $job) {
            $job = $this->nextLegacyQueuedJob($queue, $branchId, $name);
        }
        if (! $job) {
            $allBranchPrinters = PrinterBranch::query()->where('branch_id', $branchId)->where('status', 'E')->orderBy('id')->get();
            foreach ($allBranchPrinters as $p) {
                $job = $this->claimPendingThermalPrintJob($branchId, (string) $p->name);
                if ($job) {
                    $job['printer_name'] = filled($p->driver_name) ? $p->driver_name : $p->name;
                    $job['configured_printer_name'] = $p->name;
                    break;
                }
            }
        }
        if (! $job) {
            $job = $this->claimAnyPendingThermalPrintJobForBranch($branchId);
        }
        if ($job && empty($job['printer_name'])) {
            $job['printer_name'] = $name;
        }

        return response()->json(['job' => $job]);
    }

    public function ack(Request $request, PrintBridgeQueue $queue): JsonResponse
    {
        if (! config('qz.enabled', true)) {
            return response()->json(['success' => false, 'message' => 'deshabilitado'], 200);
        }
        $request->validate([
            'printer_name' => 'nullable|string|max:120',
            'job_id' => 'required|string|max:120',
            'station_uuid' => 'nullable|uuid',
        ]);
        $name = trim((string) $request->input('printer_name', 'BARRA2')) ?: 'BARRA2';
        $branchId = (int) session('branch_id');
        if (! $branchId) {
            return response()->json(['success' => false, 'message' => 'sin sucursal en sesión'], 200);
        }
        if ($request->filled('station_uuid')) {
            $station = PrintStation::query()->where('uuid', $request->string('station_uuid'))->where('branch_id', $branchId)->where('status', 'E')->first();
            if ($station) {
                $station->forceFill(['last_seen_at' => now()])->save();
            }
        }
        $jobId = trim((string) $request->input('job_id'));
        if ($jobId === '') {
            return response()->json(['success' => false, 'message' => 'job_id inválido'], 422);
        }

        // La pantalla de comandas clasifica un fallo como pending + last_error.
        // Mantenerlo pendiente permite reintentar y evita que desaparezca de "Con error".
        $isError = $request->input('status') === 'error';
        $targetStatus = $isError ? 'pending' : 'printed';
        $errorMessage = $isError ? Str::limit((string) $request->input('error_message', 'Error en QZ Tray'), 500, '') : null;

        $thermalJobFromDirectId = $this->thermalPrintJobIdFromBridgeJobId($jobId);
        if ($thermalJobFromDirectId > 0) {
            ThermalPrintJob::query()
                ->whereKey($thermalJobFromDirectId)
                ->where('branch_id', $branchId)
                ->where('source', 'kitchen_order')
                ->whereIn('status', ['pending', 'printing'])
                ->update([
                    'status' => $targetStatus,
                    'printed_at' => $targetStatus === 'printed' ? now() : null,
                    'printed_by' => $request->user()?->id,
                    'last_error' => $errorMessage,
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true]);
        }

        if (! $request->filled('station_uuid') && ! $queue->isStationPrinterName($name)) {
            return response()->json(['success' => false, 'message' => 'impresora no permitida'], 422);
        }

        $job = $queue->ack($branchId, $name, $jobId);
        $thermalPrintJobId = (int) ($job['thermal_print_job_id'] ?? 0);
        if ($thermalPrintJobId > 0) {
            ThermalPrintJob::query()
                ->whereKey($thermalPrintJobId)
                ->where('branch_id', $branchId)
                ->where('source', 'kitchen_order')
                ->whereIn('status', ['pending', 'printing'])
                ->update([
                    'status' => $targetStatus,
                    'printed_at' => $targetStatus === 'printed' ? now() : null,
                    'printed_by' => $request->user()?->id,
                    'last_error' => $errorMessage,
                    'updated_at' => now(),
                ]);
        }
        // Idempotente: devolver éxito incluso si el trabajo ya no existe.
        return response()->json(['success' => true]);
    }

    private function claimPendingThermalPrintJob(int $branchId, string $printerName): ?array
    {
        if (
            ! Schema::hasTable('thermal_print_jobs')
            || ! Schema::hasColumn('thermal_print_jobs', 'ticket_text')
        ) {
            return null;
        }

        $leaseSeconds = max(15, (int) config('print_bridge.lease_seconds', 45));
        $leaseExpiredAt = now()->subSeconds($leaseSeconds);
        $normalizedPrinterName = mb_strtolower(trim($printerName));
        if ($branchId <= 0 || $normalizedPrinterName === '') {
            return null;
        }

        return DB::transaction(function () use ($branchId, $normalizedPrinterName, $leaseExpiredAt) {
            $job = ThermalPrintJob::query()
                ->where('branch_id', $branchId)
                ->where('source', 'kitchen_order')
                ->where('created_at', '>=', now()->subHours(48))
                ->whereRaw('LOWER(TRIM(printer_name)) = ?', [$normalizedPrinterName])
                ->where(function ($query) use ($leaseExpiredAt) {
                    $query->where(function ($pendingQuery) use ($leaseExpiredAt) {
                        $pendingQuery->where('status', 'pending')
                            ->where(function ($retryQuery) use ($leaseExpiredAt) {
                                $retryQuery->whereNull('last_error')
                                    ->orWhereNull('last_attempt_at')
                                    ->orWhere('last_attempt_at', '<', $leaseExpiredAt);
                            });
                    })
                        ->orWhere(function ($subQuery) use ($leaseExpiredAt) {
                            $subQuery->where('status', 'printing')
                                ->where(function ($expiredQuery) use ($leaseExpiredAt) {
                                    $expiredQuery->whereNull('last_attempt_at')
                                        ->orWhere('last_attempt_at', '<', $leaseExpiredAt);
                                });
                        });
                })
                ->whereNotNull('ticket_text')
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->forceFill([
                'status' => 'printing',
                'attempts' => (int) $job->attempts + 1,
                'last_attempt_at' => now(),
                'last_error' => null,
            ])->save();

            $pname = trim((string) ($job->printer_name ?: 'BARRA'));
            $driver = $pname;
            $printerModel = PrinterBranch::query()
                ->where('branch_id', $branchId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($pname)])
                ->first();
            if ($printerModel && filled($printerModel->driver_name)) {
                $driver = $printerModel->driver_name;
            }

            return [
                'id' => 'thermal:' . $job->id,
                'thermal_print_job_id' => (int) $job->id,
                'b64' => base64_encode($this->buildKitchenEscPosPayload((string) $job->ticket_text)),
                'at' => time(),
                'printer_name' => $driver,
                'configured_printer_name' => $pname,
                'printer_ip' => $printerModel?->ip,
                'printer_port' => (int) ($printerModel?->port ?: 9100),
            ];
        }, 3);
    }

    private function nextLegacyQueuedJob(PrintBridgeQueue $queue, int $branchId, string $printerName): ?array
    {
        for ($i = 0; $i < 5; $i++) {
            $job = $queue->peek($branchId, $printerName);
            if (! $job) {
                return null;
            }

            $thermalPrintJobId = (int) ($job['thermal_print_job_id'] ?? 0);
            if ($thermalPrintJobId <= 0 || ! Schema::hasTable('thermal_print_jobs')) {
                return $job;
            }

            $status = ThermalPrintJob::query()
                ->whereKey($thermalPrintJobId)
                ->where('branch_id', $branchId)
                ->value('status');

            if (in_array($status, ['pending', 'printing'], true)) {
                $queue->ack($branchId, $printerName, (string) ($job['id'] ?? ''));

                continue;
            }

            $queue->ack($branchId, $printerName, (string) ($job['id'] ?? ''));
        }

        return null;
    }

    private function thermalPrintJobIdFromBridgeJobId(string $jobId): int
    {
        $jobId = trim($jobId);
        if (preg_match('/^thermal:(\d+)$/', $jobId, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    private function buildKitchenEscPosPayload(string $plainText): string
    {
        $normalized = $this->normalizeKitchenAscii($plainText);

        return
            "\x1B\x40" .
            "\x1B\x74\x02" .
            $normalized .
            "\n\n" .
            "\x1D\x56\x42\x10";
    }

    private function normalizeKitchenAscii(string $text): string
    {
        $value = str_replace(
            ['á', 'Á', 'é', 'É', 'í', 'Í', 'ó', 'Ó', 'ú', 'Ú', 'ü', 'Ü', 'ñ', 'Ñ', '¿', '¡'],
            ['a', 'A', 'e', 'E', 'i', 'I', 'o', 'O', 'u', 'U', 'u', 'U', 'n', 'N', '?', '!'],
            $text
        );

        return str_replace("\r\n", "\n", (string) $value);
    }

    private function claimAnyPendingThermalPrintJobForBranch(int $branchId, ?int $stationId = null): ?array
    {
        if (
            ! Schema::hasTable('thermal_print_jobs')
            || ! Schema::hasColumn('thermal_print_jobs', 'ticket_text')
        ) {
            return null;
        }

        $leaseSeconds = max(15, (int) config('print_bridge.lease_seconds', 45));
        $leaseExpiredAt = now()->subSeconds($leaseSeconds);
        if ($branchId <= 0) {
            return null;
        }

        return DB::transaction(function () use ($branchId, $leaseExpiredAt, $stationId) {
            $job = ThermalPrintJob::query()
                ->where('branch_id', $branchId)
                ->where('source', 'kitchen_order')
                ->where(function ($query) use ($leaseExpiredAt) {
                    $query->where(function ($pendingQuery) use ($leaseExpiredAt) {
                        $pendingQuery->where('status', 'pending')
                            ->where(function ($retryQuery) use ($leaseExpiredAt) {
                                $retryQuery->whereNull('last_error')
                                    ->orWhereNull('last_attempt_at')
                                    ->orWhere('last_attempt_at', '<', $leaseExpiredAt);
                            });
                    })
                        ->orWhere(function ($subQuery) use ($leaseExpiredAt) {
                            $subQuery->where('status', 'printing')
                                ->where(function ($expiredQuery) use ($leaseExpiredAt) {
                                    $expiredQuery->whereNull('last_attempt_at')
                                        ->orWhere('last_attempt_at', '<', $leaseExpiredAt);
                                });
                        });
                })
                ->whereNotNull('ticket_text')
                ->when($stationId, function ($query) use ($stationId) {
                    $query->where(function ($printerQuery) use ($stationId) {
                        $printerQuery->whereNull('printer_branch_id')
                            ->orWhereHas('printerBranch', function ($branchQuery) use ($stationId) {
                                $branchQuery->whereNull('print_station_id')
                                    ->orWhere('print_station_id', $stationId);
                            });
                    });
                })
                ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
                ->orderBy('created_at')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if (! $job) {
                return null;
            }

            $job->forceFill([
                'status' => 'printing',
                'attempts' => (int) $job->attempts + 1,
                'last_attempt_at' => now(),
                'last_error' => null,
            ])->save();

            $pname = trim((string) ($job->printer_name ?: 'BARRA'));
            $driver = $pname;
            $printerModel = PrinterBranch::query()
                ->where('branch_id', $branchId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($pname)])
                ->first();
            if ($printerModel && filled($printerModel->driver_name)) {
                $driver = $printerModel->driver_name;
            }

            return [
                'id' => 'thermal:' . $job->id,
                'thermal_print_job_id' => (int) $job->id,
                'b64' => base64_encode($this->buildKitchenEscPosPayload((string) $job->ticket_text)),
                'at' => time(),
                'printer_name' => $driver,
                'configured_printer_name' => $pname,
                'printer_ip' => $printerModel?->ip,
                'printer_port' => (int) ($printerModel?->port ?: 9100),
            ];
        }, 3);
    }
}
