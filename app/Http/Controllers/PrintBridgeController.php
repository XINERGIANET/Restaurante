<?php

namespace App\Http\Controllers;

use App\Services\PrintBridgeQueue;
use Illuminate\Http\Request;
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
}
