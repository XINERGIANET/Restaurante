<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-visit-control" content="reload">
    <meta name="qz-sign-url" content="{{ $qzSignUrl }}">
    <meta name="qz-certificate-url" content="{{ $qzCertUrl }}">
    <meta name="qz-signature-algorithm" content="{{ config('qz.signature_algorithm', 'SHA512') }}">
    <title>{{ $title }}</title>
    <script>
        window.__qzSecondaryFirstPrinterNames = @json(config('qz.secondary_first_printer_names', []));
    </script>
    @vite(['resources/js/qz-tray-init.js'])
</head>
<body style="margin:0;font-family:system-ui,sans-serif;background:#0f172a;color:#e2e8f0;">
    <div style="max-width:36rem;margin:0 auto;padding:1rem;">
        <h1 style="font-size:1.125rem;margin:0 0 .5rem;">{{ $title }}</h1>
        <p style="font-size:.875rem;color:#94a3b8;margin:0 0 1rem;line-height:1.45;">
            Deje esta pestaña <strong>abierta</strong> en la PC que tiene la ticketera USB (p. ej. BARRA2) y QZ Tray.
            Los pedidos desde el móvil en la misma red se imprimen aquí si el <code style="background:#1e293b;padding:0 .25rem;border-radius:.25rem;">branch_id</code>
            coincide con la sucursal del POS.
        </p>
        <p style="font-size:.75rem;color:#64748b;margin:0 0 1rem;">
            Sucursal (cola): <strong id="bridge-branch">{{ $branchId }}</strong>
            · Impresora: <strong>{{ $targetPrinter }}</strong>
        </p>
        <div id="bridge-status" style="border:1px solid #334155;border-radius:.5rem;padding:.75rem;font-family:ui-monospace,monospace;font-size:.8125rem;min-height:3rem;background:#1e293b;">
            Iniciando…
        </div>
    </div>
    <script>
        (function () {
            const targetPrinter = @json($targetPrinter);
            const branchId = @json($branchId);
            const kioskToken = @json($kioskToken);
            const pullPath = @json($pullKioskPath);
            const st = document.getElementById('bridge-status');

            function log(msg) {
                if (st) st.textContent = msg;
            }

            async function printJob(job) {
                const qzApi = window.qz;
                if (!qzApi) {
                    log('QZ Tray no está cargado');
                    return;
                }
                const name = String(job.printer_name || targetPrinter).trim() || 'BARRA2';
                if (typeof window.__qzApplyCertPairOverrideForPrinter === 'function') {
                    window.__qzApplyCertPairOverrideForPrinter(name);
                }
                if (typeof window.__qzConnectWithCertPairFallback === 'function') {
                    const ok = await window.__qzConnectWithCertPairFallback(qzApi, name);
                    if (!ok) {
                        log('No se pudo conectar QZ. Revise certificado (qz2) en esta PC.');
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
                    const u = new URL(pullPath, window.location.origin);
                    u.searchParams.set('token', kioskToken);
                    u.searchParams.set('branch_id', String(branchId));
                    u.searchParams.set('printer_name', targetPrinter);
                    const r = await fetch(u.toString(), { credentials: 'same-origin', cache: 'no-store', headers: { 'Accept': 'application/json' } });
                    const j = r.headers.get('content-type') && r.headers.get('content-type').includes('application/json')
                        ? await r.json() : null;
                    if (j && j.job && j.job.b64) {
                        log('Imprimiendo…');
                        await printJob(j.job);
                        log('Listo. Esperando cola…');
                    } else {
                        log('En espera (cola vacía)… ' + new Date().toLocaleTimeString());
                    }
                } catch (e) {
                    console.error(e);
                    log('Error: ' + (e && e.message ? e.message : e));
                } finally {
                    busy = false;
                }
            }

            setInterval(tick, 1500);
            tick();
        })();
    </script>
</body>
</html>
