import * as qz from 'qz-tray';

if (!window.qz) {
    window.qz = qz;
}
if (window.qz?.api?.showDebug) {
    window.qz.api.showDebug(false);
}

function getQz() {
    return window.qz || null;
}

function readMeta(name) {
    const el = document.querySelector(`meta[name="${name}"]`);
    return el?.getAttribute('content')?.trim() || '';
}

function qzCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function appendPairQuery(url, pair) {
    const u = new URL(url, window.location.href);
    if (pair === 'primary' || pair === 'secondary') {
        u.searchParams.set('pair', pair);
    } else {
        u.searchParams.delete('pair');
    }
    return u.toString();
}

export function resetQzTraySecurityState() {
    window.__qzTraySecurityConfigured = false;
    window.__qzActiveCertPair = null;
}

function resolveCertPairTryOrder() {
    const cfg = window.__qzConfig || {};
    let raw = [];
    if (Array.isArray(cfg.certPairTryOrder)) {
        raw = cfg.certPairTryOrder;
    } else {
        raw = String(cfg.certPairTryOrder || 'primary,secondary')
            .split(',')
            .map((s) => s.trim().toLowerCase())
            .filter(Boolean);
    }
    const allowed = ['primary', 'secondary'];
    const seen = new Set();
    const order = [];
    try {
        const pref = (window.localStorage?.getItem('qzCertPair') || '').trim().toLowerCase();
        if (pref === 'primary' || pref === 'secondary') {
            order.push(pref);
            seen.add(pref);
        }
    } catch (e) {
        // ignore
    }
    for (const item of raw) {
        const p = String(item || '').trim().toLowerCase();
        if (allowed.includes(p) && !seen.has(p)) {
            order.push(p);
            seen.add(p);
        }
    }
    if (order.length === 0) {
        return ['primary', 'secondary'];
    }
    return order;
}

/**
 * Configura certificado y firma para un par explícito (primary = app/qz, secondary = app/qz2).
 * @param {string} pair 'primary' | 'secondary'
 */
export function configureQzSecurityForPair(pair) {
    const qzLib = getQz();
    if (!qzLib) {
        return false;
    }

    const cfg = window.__qzConfig || {};
    const signBase = readMeta('qz-sign-url') || cfg.signUrl || '';
    const certBase = readMeta('qz-certificate-url') || cfg.certificateUrl || '';
    const algo = (readMeta('qz-signature-algorithm') || cfg.signatureAlgorithm || 'SHA512').toUpperCase();
    if (!signBase || !certBase) {
        return false;
    }

    const signUrl = appendPairQuery(signBase, pair);
    const certUrl = appendPairQuery(certBase, pair);

    resetQzTraySecurityState();

    qzLib.security.setCertificatePromise((resolve, reject) => {
        fetch(certUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'text/plain' },
        })
            .then((r) => (r.ok ? r.text() : Promise.reject(new Error(r.statusText))))
            .then(resolve)
            .catch(reject);
    });

    qzLib.security.setSignatureAlgorithm(algo);
    qzLib.security.setSignaturePromise((toSign) => {
        return (resolve, reject) => {
            const body = { request: toSign };
            fetch(signUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': qzCsrfToken(),
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(body),
            })
                .then(async (r) => {
                    const raw = await r.text();
                    if (!r.ok) {
                        throw new Error(raw || r.statusText);
                    }
                    let sig = '';
                    try {
                        const payload = raw ? JSON.parse(raw) : {};
                        sig = payload?.signature ? String(payload.signature) : '';
                    } catch (e) {
                        sig = String(raw || '').trim();
                    }
                    if (!sig) throw new Error('Firma QZ inválida');
                    return sig;
                })
                .then((sig) => resolve(sig))
                .catch(reject);
        };
    });

    window.__qzTraySecurityConfigured = true;
    window.__qzActiveCertPair = pair;
    return true;
}

/**
 * Intenta conectar a QZ Tray probando cada par de certificado en orden (p. ej. primary → secondary).
 */
export async function connectQzWithCertPairFallback(qzApi) {
    if (!qzApi) {
        return false;
    }
    const order = resolveCertPairTryOrder();
    let lastErr = null;
    for (let i = 0; i < order.length; i++) {
        const pair = order[i];
        try {
            configureQzSecurityForPair(pair);
            if (qzApi.websocket.isActive()) {
                try {
                    await qzApi.websocket.disconnect();
                } catch (e) {
                    console.warn('[QZ Xinergia] disconnect antes de reintentar:', e);
                }
            }
            await qzApi.websocket.connect();
            console.info('[QZ Xinergia] Conectado con par de certificado:', pair, '| orden probado:', order.join(' → '));
            window.__qzSelectedCertPair = pair;
            return true;
        } catch (e) {
            lastErr = e;
            console.warn('[QZ Xinergia] Falló conexión con par', pair, e);
            try {
                if (qzApi.websocket.isActive()) {
                    await qzApi.websocket.disconnect();
                }
            } catch (e2) {
                // ignore
            }
        }
    }
    console.error('[QZ Xinergia] No se pudo conectar con ningún par.', order.join(' → '), lastErr);
    return false;
}

/**
 * Compatibilidad: configura el primer par del orden (sin conectar).
 */
export function configureQzSecurity() {
    const first = resolveCertPairTryOrder()[0] || 'primary';
    return configureQzSecurityForPair(first);
}

function initQzIfMetaPresent() {
    const cfg = window.__qzConfig || {};
    const signUrl = readMeta('qz-sign-url') || cfg.signUrl || '';
    const certUrl = readMeta('qz-certificate-url') || cfg.certificateUrl || '';
    if (!signUrl || !certUrl) {
        return;
    }
    configureQzSecurity();
}

window.__qzConnectWithCertPairFallback = connectQzWithCertPairFallback;
window.__qzConfigureQzSecurityForPair = configureQzSecurityForPair;
window.__qzResolveCertPairTryOrder = resolveCertPairTryOrder;

initQzIfMetaPresent();

document.addEventListener('turbo:load', initQzIfMetaPresent);
