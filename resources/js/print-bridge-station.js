import './qz-tray-init.js';

/**
 * PC estación (BARRA2 USB + QZ/qz2): escucha la cola del servidor (móvil → Laravel → caché).
 * Activar una vez: localStorage xinergia_print_bridge_station = '1' (p. ej. desde /print-bridge/worker).
 */
export function startPrintBridgeStationPoll() {
    if (window.__xinergiaPrintBridgePollStarted) {
        return;
    }
    const pullBase = typeof window.__printBridgePullUrl === 'string' ? window.__printBridgePullUrl.trim() : '';
    if (!pullBase) {
        return;
    }
    window.__xinergiaPrintBridgePollStarted = true;
    let busy = false;

    const getStationUuid = () => {
        try {
            return localStorage.getItem('restaurant_print_station_uuid') || '';
        } catch (e) {
            return '';
        }
    };

    const tick = async () => {
        if (busy) {
            return;
        }
        busy = true;
        let currentJob = null;
        let stationUuid = '';
        try {
            stationUuid = getStationUuid();
            if (!stationUuid) return;
            const u = new URL(pullBase, window.location.origin);
            u.searchParams.set('station_uuid', stationUuid);
            const r = await fetch(u.toString(), {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { Accept: 'application/json' },
            });
            if (r.status === 401 || r.status === 419) {
                return;
            }
            const ct = r.headers.get('content-type') || '';
            if (!ct.includes('application/json')) {
                return;
            }
            const j = await r.json();
            if (!j || !j.job || !j.job.b64) {
                return;
            }
            currentJob = j.job;
            const qzApi = window.qz;
            if (!qzApi) {
                return;
            }
            const driver = String(j.job.printer_name || '').trim();
            const configName = String(j.job.configured_printer_name || driver).trim();
            if (!driver && !configName) return;

            if (typeof window.__qzConnectWithCertPairFallback === 'function') {
                const ok = await window.__qzConnectWithCertPairFallback(qzApi, driver || configName);
                if (!ok) {
                    return;
                }
            }

            let targetPrinter = driver || configName;
            try {
                targetPrinter = await qzApi.printers.find(driver);
            } catch (e1) {
                try {
                    targetPrinter = await qzApi.printers.find(configName);
                } catch (e2) {
                    try {
                        const allPrinters = await qzApi.printers.find();
                        const matched = allPrinters.find(p => {
                            const pl = String(p).toLowerCase();
                            return pl.includes(driver.toLowerCase()) || pl.includes(configName.toLowerCase());
                        });
                        if (matched) {
                            targetPrinter = matched;
                        }
                    } catch (e3) {
                        // ignore and use fallback
                    }
                }
            }

            const config = qzApi.configs.create(targetPrinter, {
                units: 'mm',
                size: { width: 80, height: 200 },
                margins: 0,
            });
            const data = atob(String(j.job.b64));
            await qzApi.print(config, [{
                type: 'raw',
                format: 'command',
                flavor: 'plain',
                data,
            }]);

            if (j.job.id) {
                const ackUrl = pullBase.replace('/pull', '/ack');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                const fd = new FormData();
                fd.append('printer_name', String(j.job.configured_printer_name || driver));
                fd.append('job_id', j.job.id);
                fd.append('station_uuid', stationUuid);
                fd.append('status', 'printed');
                
                await fetch(ackUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: fd
                });
            }
        } catch (e) {
            console.warn('[print-bridge-station]', e);
            if (currentJob && currentJob.id) {
                try {
                    const ackUrl = pullBase.replace('/pull', '/ack');
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
                    const fd = new FormData();
                    fd.append('printer_name', String(currentJob.configured_printer_name || currentJob.printer_name || ''));
                    fd.append('job_id', currentJob.id);
                    fd.append('station_uuid', stationUuid);
                    fd.append('status', 'error');
                    fd.append('error_message', e?.message || 'Error en QZ Tray');
                    
                    await fetch(ackUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: fd
                    });
                } catch (reportErr) {}
            }
        } finally {
            busy = false;
        }
    };

    window.__xinergiaPrintBridgeInterval = setInterval(tick, 1600);
    tick();
}
