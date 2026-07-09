<?php

namespace App\Http\Controllers;

use App\Models\ThermalPrintJob;
use App\Services\PrintBridgeQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        ]);
        $name = trim((string) $request->input('printer_name', 'BARRA2')) ?: 'BARRA2';
        if (! $queue->isStationPrinterName($name)) {
            return response()->json(['message' => 'impresora no permitida'], 422);
        }
        $branchId = (int) session('branch_id');
        if (! $branchId) {
            return response()->json(['job' => null, 'message' => 'sin sucursal en sesión'], 200);
        }
        $job = $this->claimPendingThermalPrintJob($branchId, $name);
        if (! $job) {
            $job = $this->nextLegacyQueuedJob($queue, $branchId, $name);
        }
        if ($job) {
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
        ]);
        $name = trim((string) $request->input('printer_name', 'BARRA2')) ?: 'BARRA2';
        if (! $queue->isStationPrinterName($name)) {
            return response()->json(['success' => false, 'message' => 'impresora no permitida'], 422);
        }
        $branchId = (int) session('branch_id');
        if (! $branchId) {
            return response()->json(['success' => false, 'message' => 'sin sucursal en sesión'], 200);
        }
        $jobId = trim((string) $request->input('job_id'));
        if ($jobId === '') {
            return response()->json(['success' => false, 'message' => 'job_id inválido'], 422);
        }

        $thermalJobFromDirectId = $this->thermalPrintJobIdFromBridgeJobId($jobId);
        if ($thermalJobFromDirectId > 0) {
            ThermalPrintJob::query()
                ->whereKey($thermalJobFromDirectId)
                ->where('branch_id', $branchId)
                ->where('source', 'kitchen_order')
                ->whereIn('status', ['pending', 'printing'])
                ->update([
                    'status' => 'printed',
                    'printed_at' => now(),
                    'printed_by' => $request->user()?->id,
                    'last_error' => null,
                    'updated_at' => now(),
                ]);

            return response()->json(['success' => true]);
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
                    'status' => 'printed',
                    'printed_at' => now(),
                    'printed_by' => $request->user()?->id,
                    'last_error' => null,
                    'updated_at' => now(),
                ]);
        }
        // Idempotente: devolver éxito incluso si el trabajo ya no existe.
        // El objetivo se logró: el trabajo no está en la cola.

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
                ->whereRaw('LOWER(TRIM(printer_name)) = ?', [$normalizedPrinterName])
                ->where(function ($query) use ($leaseExpiredAt) {
                    $query->where('status', 'pending')
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

            return [
                'id' => 'thermal:' . $job->id,
                'thermal_print_job_id' => (int) $job->id,
                'b64' => base64_encode($this->buildKitchenEscPosPayload((string) $job->ticket_text)),
                'at' => time(),
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
}
