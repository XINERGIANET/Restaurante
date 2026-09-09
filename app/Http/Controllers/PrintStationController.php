<?php

namespace App\Http\Controllers;

use App\Models\PrintStation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PrintStationController extends Controller
{
    public function store(Request $request)
    {
        $branchId = $this->branchId($request);
        $data = $this->validateStation($request, $branchId);

        DB::transaction(function () use ($request, $branchId, $data): void {
            $station = new PrintStation($data);
            $station->branch_id = $branchId;
            $this->applyCredentials($request, $station, true);
            $station->save();
        });

        return back()->with('success', 'Estación de impresión registrada correctamente.');
    }

    public function update(Request $request, PrintStation $printStation)
    {
        $branchId = $this->branchId($request);
        $this->assertBranch($printStation, $branchId);
        $data = $this->validateStation($request, $branchId, $printStation->id);

        DB::transaction(function () use ($request, $printStation, $data): void {
            $printStation->fill($data);
            $this->applyCredentials($request, $printStation, false);
            $printStation->save();
        });

        return back()->with('success', 'Estación de impresión actualizada.');
    }

    public function destroy(Request $request, PrintStation $printStation)
    {
        $this->assertBranch($printStation, $this->branchId($request));
        if ($printStation->printers()->exists()) {
            return back()->with('error', 'Reasigna primero las ticketeras conectadas a esta estación.');
        }
        $printStation->delete();

        return back()->with('success', 'Estación eliminada.');
    }

    public function heartbeat(Request $request)
    {
        $request->validate(['station_uuid' => ['required', 'uuid']]);
        $station = PrintStation::query()
            ->where('branch_id', $this->branchId($request))
            ->where('uuid', $request->string('station_uuid'))
            ->where('status', 'E')
            ->firstOrFail();

        $station->forceFill([
            'last_seen_at' => now(),
            'ip_address' => $request->ip() ?: $station->ip_address,
        ])->save();

        return response()->json([
            'success' => true,
            'station' => ['uuid' => $station->uuid, 'name' => $station->name],
        ]);
    }

    private function validateStation(Request $request, int $branchId, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('print_stations')->where('branch_id', $branchId)->ignore($ignoreId)],
            'hostname' => ['nullable', 'string', 'max:120'],
            'ip_address' => ['required', 'ip'],
            'location' => ['nullable', 'string', 'max:160'],
            'status' => ['nullable', Rule::in(['E', 'I'])],
            'qz_certificate' => ['nullable', 'file', 'max:128', 'mimes:txt,pem,crt,cer'],
            'qz_private_key' => ['nullable', 'file', 'max:64', 'mimes:txt,pem,key'],
        ]);
    }

    private function applyCredentials(Request $request, PrintStation $station, bool $required): void
    {
        $hasCertificate = $request->hasFile('qz_certificate');
        $hasKey = $request->hasFile('qz_private_key');
        if ($hasCertificate xor $hasKey) {
            throw ValidationException::withMessages([
                'qz_certificate' => 'Debes subir el certificado y la clave privada juntos.',
            ]);
        }
        if (! $hasCertificate) {
            if ($required) {
                throw ValidationException::withMessages([
                    'qz_certificate' => 'El certificado QZ y la clave privada son obligatorios al registrar la PC.',
                ]);
            }
            return;
        }

        $certificate = trim((string) $request->file('qz_certificate')->get());
        $privateKey = trim((string) $request->file('qz_private_key')->get());
        if ($certificate === '' || $privateKey === '' || openssl_pkey_get_private($privateKey) === false) {
            throw ValidationException::withMessages([
                'qz_private_key' => 'La clave privada no es un archivo PEM válido.',
            ]);
        }
        $probe = '';
        if (! openssl_sign('restaurant-qz-station-check', $probe, $privateKey, OPENSSL_ALGO_SHA512)) {
            throw ValidationException::withMessages(['qz_private_key' => 'No se pudo validar la clave privada.']);
        }

        $station->qz_certificate = $certificate;
        $station->qz_private_key = $privateKey;
        $station->certificate_fingerprint = hash('sha256', $certificate);
        $station->certificate_uploaded_at = now();
    }

    private function branchId(Request $request): int
    {
        $branchId = (int) $request->session()->get('branch_id');
        abort_if($branchId < 1, 422, 'No se detectó una sucursal activa.');

        return $branchId;
    }

    private function assertBranch(PrintStation $station, int $branchId): void
    {
        abort_unless((int) $station->branch_id === $branchId, 403);
    }
}
