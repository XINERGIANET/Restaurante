<?php

namespace App\Http\Controllers;

use App\Services\PrintBridgeQueue;
use App\Services\QzTraySigningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class PrintBridgeController extends Controller
{
    public function worker(Request $request): View
    {
        if (! config('print_bridge.enabled', true)) {
            abort(404, 'Puente de impresión desactivado (PRINT_BRIDGE_ENABLED).');
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
        if (! config('print_bridge.enabled', true)) {
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
        $job = $queue->pop($branchId, $name);
        if ($job) {
            $job['printer_name'] = $name;
        }

        return response()->json(['job' => $job]);
    }

    public function kiosk(Request $request): View
    {
        if (! config('print_bridge.enabled', true)) {
            abort(404, 'Puente de impresión desactivado (PRINT_BRIDGE_ENABLED).');
        }
        $expected = trim((string) config('print_bridge.kiosk_token', ''));
        if ($expected === '') {
            abort(503, 'Defina PRINT_BRIDGE_KIOSK_TOKEN en .env (cadena larga y secreta) y reinicie la aplicación.');
        }
        $this->assertValidKioskToken($request);
        $request->validate([
            'branch_id' => 'required|integer|min:1',
            'printer' => 'nullable|string|max:120',
        ]);
        $branchId = (int) $request->query('branch_id');
        $printer = trim((string) $request->query('printer', 'BARRA2')) ?: 'BARRA2';
        if (! app(PrintBridgeQueue::class)->isStationPrinterName($printer)) {
            abort(400, 'Impresora no permitida para el puente.');
        }
        $token = trim((string) $request->query('token', ''));

        return view('print-bridge.kiosk', [
            'title' => 'Kiosco impresión estación (QZ)',
            'targetPrinter' => $printer,
            'branchId' => $branchId,
            'kioskToken' => $token,
            'qzCertUrl' => route('print-bridge.qz.certificate', ['token' => $token], false),
            'qzSignUrl' => route('print-bridge.qz.sign', ['token' => $token], false),
            'pullKioskPath' => route('print-bridge.pull-kiosk', [], false),
        ]);
    }

    public function pullKiosk(Request $request, PrintBridgeQueue $queue): JsonResponse
    {
        if (! config('print_bridge.enabled', true)) {
            return response()->json(['job' => null, 'message' => 'deshabilitado'], 200);
        }
        $this->assertValidKioskToken($request);
        $request->validate([
            'branch_id' => 'required|integer|min:1',
            'printer_name' => 'nullable|string|max:120',
        ]);
        $branchId = (int) $request->input('branch_id');
        $name = trim((string) $request->input('printer_name', 'BARRA2')) ?: 'BARRA2';
        if (! $queue->isStationPrinterName($name)) {
            return response()->json(['message' => 'impresora no permitida'], 422);
        }
        $job = $queue->pop($branchId, $name);
        if ($job) {
            $job['printer_name'] = $name;
        }

        return response()->json(['job' => $job]);
    }

    public function kioskCertificate(Request $request, QzTraySigningService $signing)
    {
        $this->assertValidKioskToken($request);

        return app(QzTrayController::class)->certificate($request, $signing);
    }

    public function kioskSign(Request $request, QzTraySigningService $signing)
    {
        if (! config('qz.enabled', true)) {
            abort(503, 'QZ Tray deshabilitado.');
        }
        $this->assertValidKioskToken($request);

        try {
            $request->validate(['request' => 'required|string']);
            $payload = (string) $request->input('request');
            $pair = strtolower(trim((string) ($request->query('pair') ?? $request->input('pair') ?? '')));
            if ($pair !== '' && ! in_array($pair, ['primary', 'secondary'], true)) {
                abort(400, 'Par de certificado QZ inválido.');
            }

            if ($pair === '') {
                $signature = $signing->sign($payload);
            } else {
                $signature = $signing->signForPair($pair, $payload);
            }

            Log::info('QZ Tray (kiosco): firma de solicitud', [
                'pair' => $pair === '' ? 'auto' : $pair,
                'ip' => $request->ip(),
            ]);

            if ($signature === null || trim($signature) === '') {
                abort(503, 'No se pudo firmar la solicitud de QZ.');
            }

            if ($request->isMethod('POST') || $request->expectsJson() || $request->isJson()) {
                return response()->json(['signature' => $signature]);
            }

            return response($signature, 200, [
                'Content-Type' => 'text/plain; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ]);
        } catch (\Throwable $e) {
            Log::error('QZ sign (kiosco) error: ' . $e->getMessage());
            abort(500, 'No se pudo firmar la solicitud de QZ.');
        }
    }

    private function assertValidKioskToken(Request $request): void
    {
        $expected = trim((string) config('print_bridge.kiosk_token', ''));
        if ($expected === '') {
            abort(403, 'Kiosco no configurado (PRINT_BRIDGE_KIOSK_TOKEN).');
        }
        $got = trim((string) $request->query('token', ''));
        if ($got === '' || ! hash_equals($expected, $got)) {
            abort(403, 'Token inválido.');
        }
    }
}
