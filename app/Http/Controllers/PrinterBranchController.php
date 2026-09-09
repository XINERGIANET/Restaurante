<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\PrinterBranch;
use App\Models\Operation;
use App\Models\PrintStation;
use Illuminate\Validation\Rule;

class PrinterBranchController extends Controller
{
    public function index(Request $request){
        $branchId = $request->session()->get('branch_id');
        $profileId = $request->session()->get('profile_id') ?? $request->user()?->profile_id;
        $search = $request->input('search');
        $perPage = (int) $request->input('per_page', 10);
        $allowedPerPage = [10, 20, 50, 100];
        if (!in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }
        $viewId = $request->input('view_id');

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

        $printers = PrinterBranch::query()
            ->with(['branch', 'station'])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($search, function ($query, $search) {
                $query->where('name', 'like', '%' . $search . '%');
            })
            ->orderByDesc('id')
            ->paginate($perPage)
            ->withQueryString();

        $stations = $branchId
            ? PrintStation::query()->with(['printers' => fn ($q) => $q->orderBy('name')])
                ->where('branch_id', $branchId)->orderBy('name')->get()
            : collect();

        return view('printers_branch.index', [
            'printers' => $printers,
            'search' => $search,
            'perPage' => $perPage,
            'allowedPerPage' => $allowedPerPage,
            'operaciones' => $operaciones,
            'stations' => $stations,
        ]);
    }

    public function create(Request $request){
        $stations = PrintStation::query()->where('branch_id', $request->session()->get('branch_id'))->where('status', 'E')->orderBy('name')->get();
        return view('printers_branch.create', compact('stations'));
    }

    public function store(Request $request){
        $branchId = $request->session()->get('branch_id');
        if (!$branchId) {
            return redirect()->back()->with('error', 'No se detectó una sucursal activa en la sesión.');
        }

        $validated = $this->validatePrinter($request, (int) $branchId);
        /*$validated = $request->validate([
            'name' => 'required|string|max:255',
            'width' => 'nullable|string|max:50',
            'ip' => 'nullable|string|max:45',
            'status' => 'nullable|string|in:E,I',
        ]);*/

        $validated['branch_id'] = $branchId;
        $validated['status'] = $validated['status'] ?? 'E';

        PrinterBranch::create($validated);

        return redirect()
            ->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
            ->with('success', 'Ticketera creada correctamente');
    }

    public function edit(Request $request, PrinterBranch $printerBranch)
    {
        $branchId = $request->session()->get('branch_id');
        if (!$branchId) {
            return redirect()->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
                ->with('error', 'No se detectó una sucursal activa en la sesión.');
        }
        if ((int) $printerBranch->branch_id !== (int) $branchId) {
            return redirect()->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
                ->with('error', 'No autorizado para editar ticketeras de otra sucursal.');
        }

        return view('printers_branch.edit', [
            'printer' => $printerBranch,
            'viewId' => $request->input('view_id'),
            'stations' => PrintStation::query()->where('branch_id', $branchId)->where('status', 'E')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PrinterBranch $printerBranch)
    {
        $branchId = $request->session()->get('branch_id');
        if (!$branchId) {
            return redirect()->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
                ->with('error', 'No se detectó una sucursal activa en la sesión.');
        }
        if ((int) $printerBranch->branch_id !== (int) $branchId) {
            return redirect()->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
                ->with('error', 'No autorizado para editar ticketeras de otra sucursal.');
        }

        $validated = $this->validatePrinter($request, (int) $branchId);

        $printerBranch->fill($validated);
        $printerBranch->save();

        return redirect()
            ->route('printers_branch.edit', ['printerBranch' => $printerBranch->id] + ($request->input('view_id') ? ['view_id' => $request->input('view_id')] : []))
            ->with('success', 'Ticketera actualizada correctamente');
    }

    private function validatePrinter(Request $request, int $branchId): array
    {
        $connection = (string) $request->input('connection_type', 'network');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'width' => ['nullable', Rule::in(['58', '80'])],
            'connection_type' => ['required', Rule::in(['usb', 'network'])],
            'print_station_id' => [Rule::requiredIf($connection === 'usb'), 'nullable', 'integer', Rule::exists('print_stations', 'id')->where('branch_id', $branchId)],
            'ip' => [Rule::requiredIf($connection === 'network'), 'nullable', 'ip'],
            'port' => [Rule::requiredIf($connection === 'network'), 'nullable', 'integer', 'between:1,65535'],
            'driver_name' => ['nullable', 'string', 'max:180'],
            'location' => ['nullable', 'string', 'max:160'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'status' => ['nullable', Rule::in(['E', 'I'])],
        ]);

        if ($connection === 'usb') {
            $data['ip'] = null;
            $data['port'] = 9100;
            $data['driver_name'] = filled($data['driver_name'] ?? null) ? trim($data['driver_name']) : trim($data['name']);
        }
        $data['status'] ??= 'E';

        return $data;
    }

    public function destroy(Request $request, PrinterBranch $printerBranch)
    {
        $branchId = $request->session()->get('branch_id');
        if ($branchId && (int) $printerBranch->branch_id !== (int) $branchId) {
            abort(403, 'No autorizado para eliminar ticketeras de otra sucursal.');
        }

        $printerBranch->delete();

        return redirect()
            ->route('printers_branch.index', $request->input('view_id') ? ['view_id' => $request->input('view_id')] : [])
            ->with('success', 'Ticketera eliminada correctamente');
    }

    public function testPrint(Request $request, PrinterBranch $printerBranch, ThermalNetworkPrintService $thermalService)
    {
        $branchId = (int) $request->session()->get('branch_id');
        if ($branchId && (int) $printerBranch->branch_id !== $branchId) {
            return response()->json(['success' => false, 'message' => 'No autorizado para esta ticketera.'], 403);
        }

        $payload = "\x1B\x40"
            . "\x1B\x61\x01"
            . "\x1D\x21\x11" . "TEST DE IMPRESION\n"
            . "\x1D\x21\x00" . "XINERGIA RESTAURANTE\n"
            . "--------------------------------\n"
            . "\x1B\x61\x00"
            . "Ticketera: " . $printerBranch->name . "\n"
            . "Conexion: " . strtoupper($printerBranch->connection_type ?? 'network') . "\n"
            . ($printerBranch->connection_type === 'usb'
                ? "Estacion: " . ($printerBranch->station?->name ?? 'Sin asignar') . "\nDriver: " . ($printerBranch->driver_name ?: $printerBranch->name) . "\n"
                : "IP: " . $printerBranch->ip . ":" . ($printerBranch->port ?? 9100) . "\n")
            . "Fecha: " . date('d/m/Y H:i:s') . "\n"
            . "--------------------------------\n\n\n"
            . "\x1D\x56\x42\x10";

        if (($printerBranch->connection_type ?? 'network') === 'usb') {
            return response()->json([
                'success' => true,
                'is_usb' => true,
                'driver_name' => $printerBranch->driver_name ?: $printerBranch->name,
                'printer_name' => $printerBranch->name,
                'b64' => base64_encode($payload),
                'message' => 'Prueba enviada a QZ Tray para la impresora local ' . $printerBranch->name,
            ]);
        }

        if (blank($printerBranch->ip)) {
            return response()->json(['success' => false, 'message' => 'La ticketera de red no tiene IP configurada.'], 422);
        }

        try {
            $thermalService->sendRaw(
                (string) $printerBranch->ip,
                (int) ($printerBranch->port ?: 9100),
                $payload,
                3
            );

            return response()->json([
                'success' => true,
                'is_usb' => false,
                'message' => 'Ticket de prueba enviado correctamente a la IP ' . $printerBranch->ip . ':' . ($printerBranch->port ?: 9100),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al conectar con la ticketera LAN (' . $printerBranch->ip . '): ' . $e->getMessage(),
            ], 500);
        }
    }
}

