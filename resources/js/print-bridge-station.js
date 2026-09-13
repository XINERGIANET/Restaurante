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
    let drainImmediately = false;

    // Conectar QZ antes de que llegue la primera comanda elimina la espera de
    // certificado/websocket en el momento crítico de impresión.
    const warmUpQz = async () => {
        try {
            const qzApi = window.qz;
            if (!qzApi || typeof window.__qzConnectWithCertPairFallback !== 'function') return;
            const printerHint = String(
                localStorage.getItem('xinergia_local_printer_name') ||
                localStorage.getItem('xinergia_print_bridge_printer') ||
                window.__qzConfig?.defaultPrinterName || ''
            ).trim();
            await window.__qzConnectWithCertPairFallback(qzApi, printerHint);
        } catch (error) {
            console.warn('[print-bridge-station] precarga QZ:', error);
        }
    };

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
                throw new Error('QZ Tray no está disponible en esta estación.');
            }
            const driver = String(j.job.printer_name || '').trim();
            const configName = String(j.job.configured_printer_name || driver).trim();
            const printerIp = String(j.job.printer_ip || '').trim();
            const printerPort = parseInt(j.job.printer_port, 10) || 9100;
            if (!driver && !configName) return;

            if (typeof window.__qzConnectWithCertPairFallback === 'function') {
                const ok = await window.__qzConnectWithCertPairFallback(qzApi, driver || configName);
                if (!ok) {
                    throw new Error('No se pudo conectar con QZ Tray en esta estación.');
                }
            }

            let targetPrinter = printerIp ? { host: printerIp, port: printerPort } : (driver || configName);
            if (!printerIp) {
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
                            if (matched) targetPrinter = matched;
                        } catch (e3) {
                            // ignore and use fallback
                        }
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
                
                const ackResponse = await fetch(ackUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || ''
                    },
                    body: fd
                });
                if (!ackResponse.ok) {
                    throw new Error('La impresión salió, pero el servidor no pudo confirmarla.');
                }
                drainImmediately = true;
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
                    
                    const reportResponse = await fetch(ackUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || ''
                        },
                        body: fd
                    });
                    if (!reportResponse.ok) {
                        console.warn('[print-bridge-station] No se pudo registrar el error:', reportResponse.status);
                    }
                } catch (reportErr) {}
            }
        } finally {
            busy = false;
            if (drainImmediately) {
                drainImmediately = false;
                queueMicrotask(tick);
            }
        }
    };

    // Despertar la cola inmediatamente al comandar desde esta PC u otra pestaña.
    window.__xinergiaPrintBridgeTick = tick;
    window.addEventListener('xinergia:print-bridge:wake', tick);
    window.addEventListener('storage', (event) => {
        if (event.key === 'xinergia_print_bridge_wake') tick();
    });
    if ('BroadcastChannel' in window) {
        const channel = new BroadcastChannel('xinergia-print-bridge');
        channel.addEventListener('message', (event) => {
            if (event.data === 'wake') tick();
        });
        window.__xinergiaPrintBridgeChannel = channel;
    }

    warmUpQz();
    window.__xinergiaPrintBridgeInterval = setInterval(tick, 250);
    tick();
}
