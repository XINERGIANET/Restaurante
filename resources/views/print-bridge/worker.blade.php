@extends('layouts.app')

@push('head')
    <meta name="qz-sign-url" content="{{ route('qz.sign') }}">
    <meta name="qz-certificate-url" content="{{ route('qz.certificate') }}">
    <meta name="qz-signature-algorithm" content="{{ config('qz.signature_algorithm', 'SHA512') }}">
    <script>
        window.__qzSecondaryFirstPrinterNames = @json(config('qz.secondary_first_printer_names', []));
    </script>
    @vite(['resources/js/qz-tray-init.js'])
    <meta name="turbo-visit-control" content="no-cache">
@endpush

@section('content')
    <div class="p-4 max-w-xl mx-auto" data-turbo="false">
        <h1 class="text-lg font-bold text-slate-800 dark:text-white mb-2">Puente de impresión (BARRA2 / QZ)</h1>
        <p class="text-sm text-slate-600 dark:text-slate-300 mb-4">
            Deje esta ventana <strong>abierta</strong> en la PC que tiene BARRA2 por USB y QZ Tray. Los pedidos
            hechos desde el celular en la misma red y sucursal se imprimen aquí.
        </p>
        <p class="text-xs text-slate-500 mb-2">Sucursal en sesión: <strong id="bridge-branch">—</strong> ·
            Impresora: <strong>{{ $targetPrinter }}</strong></p>
        <div id="bridge-status" class="rounded-lg border border-slate-200 dark:border-slate-600 bg-slate-50 dark:bg-slate-800/80 p-3 text-sm font-mono min-h-[3rem]">
            Iniciando…
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const targetPrinter = @json($targetPrinter);
            const pullBase = @json(route('print-bridge.pull'));
            const st = document.getElementById('bridge-status');
            const bEl = document.getElementById('bridge-branch');
            if (bEl) bEl.textContent = @json((string) (session('branch_id') ?? ''));

            function log(msg) {
                if (st) st.textContent = msg;
            }

            async function printJob(job) {
                const qzApi = window.qz;
                if (!qzApi) {
                    log('QZ Tray no está cargado en la página');
                    return;
                }
                const name = String(job.printer_name || targetPrinter).trim() || 'BARRA2';
                if (typeof window.__qzApplyCertPairOverrideForPrinter === 'function') {
                    window.__qzApplyCertPairOverrideForPrinter(name);
                }
                if (typeof window.__qzConnectWithCertPairFallback === 'function') {
                    const ok = await window.__qzConnectWithCertPairFallback(qzApi, name);
                    if (!ok) {
                        log('No se pudo conectar QZ. Revise el certificado (qz2) en esta PC.');
                        return;
                    }
                }
                const paperMm = 80;
                const config = qzApi.configs.create(name, {
                    units: 'mm',
                    size: { width: paperMm, height: 200 },
                    margins: 0,
                });
                const data = atob(String(job.b64 || ''));
                await qzApi.print(config, [{
                    type: 'raw',
                    format: 'command',
                    flavor: 'plain',
                    data: data
                }]);
            }

            let busy = false;
            async function tick() {
                if (busy) return;
                busy = true;
                try {
                    const u = new URL(pullBase, window.location.origin);
                    u.searchParams.set('printer_name', targetPrinter);
                    const r = await fetch(u.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
                    if (r.status === 401 || r.status === 419) {
                        log('Sesión caducada. Vuelva a iniciar sesión y abra esta página de nuevo.');
                        return;
                    }
                    const j = r.headers.get('content-type') && r.headers.get('content-type').includes('application/json')
                        ? await r.json() : null;
                    if (j && j.job && j.job.b64) {
                        log('Imprimiendo comanda / precuenta…');
                        await printJob(j.job);
                        log('Listo. Esperando cola…');
                    } else {
                        log('En espera (cola vacía)… ' + new Date().toLocaleTimeString());
                    }
                } catch (e) {
                    console.error(e);
                    log('Error de red: ' + (e && e.message ? e.message : e));
                } finally {
                    busy = false;
                }
            }

            setInterval(tick, 1500);
            tick();
        })();
    </script>
@endpush
