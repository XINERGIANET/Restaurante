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

/**
 * Configura certificado y firma RSA-SHA512 antes de conectar a QZ Tray.
 * Requiere meta: qz-sign-url, qz-certificate-url (rutas nombradas qz.sign, qz.certificate).
 */
export function configureQzSecurity() {
    const qz = getQz();
    if (!qz) {
        return false;
    }

    const cfg = window.__qzConfig || {};
    const signUrl = readMeta('qz-sign-url') || cfg.signUrl || '';
    const certUrl = readMeta('qz-certificate-url') || cfg.certificateUrl || '';
    const algo = (readMeta('qz-signature-algorithm') || cfg.signatureAlgorithm || 'SHA512').toUpperCase();
    if (!signUrl || !certUrl) {
        return false;
    }

    if (window.__qzTraySecurityConfigured) {
        return true;
    }

    qz.security.setCertificatePromise((resolve, reject) => {
        fetch(certUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { Accept: 'text/plain' },
        })
            .then((r) => (r.ok ? r.text() : Promise.reject(new Error(r.statusText))))
            .then(resolve)
            .catch(reject);
    });

    qz.security.setSignatureAlgorithm(algo);
    qz.security.setSignaturePromise((toSign) => {
        return (resolve, reject) => {
            // Usamos POST JSON para compatibilidad con integraciones tipo "qz.sign" legacy.
            fetch(signUrl, {
                method: 'POST',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': qzCsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({ request: toSign }),
            })
                .then((r) => (r.ok ? r.json() : Promise.reject(new Error(r.statusText))))
                .then((payload) => {
                    const sig = payload?.signature ? String(payload.signature) : '';
                    if (!sig) throw new Error('Firma QZ inválida');
                    resolve(sig);
                })
                .catch(reject);
        };
    });

    window.__qzTraySecurityConfigured = true;
    return true;
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

initQzIfMetaPresent();

document.addEventListener('turbo:load', initQzIfMetaPresent);
