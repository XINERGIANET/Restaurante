import '@hotwired/turbo';
import './bootstrap';
import Alpine from 'alpinejs';
import ApexCharts from 'apexcharts';

// flatpickr
import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
// FullCalendar
import { Calendar } from '@fullcalendar/core';

window.Alpine = Alpine;
window.ApexCharts = ApexCharts;
window.flatpickr = flatpickr;
window.FullCalendar = Calendar;

const loadingOverlay = (() => {
    let overlayEl = null;

    const getEl = () => {
        if (!overlayEl) {
            overlayEl = document.querySelector('[data-loading-overlay]');
        }
        return overlayEl;
    };

    const show = () => {
        const el = getEl();
        if (!el) return;
        el.classList.remove('hidden');
        el.setAttribute('aria-hidden', 'false');
    };

    const hide = () => {
        const el = getEl();
        if (!el) return;
        el.classList.add('hidden');
        el.setAttribute('aria-hidden', 'true');
    };

    return { show, hide };
})();

window.showLoadingModal = loadingOverlay.show;
window.hideLoadingModal = loadingOverlay.hide;

const shouldIgnoreLink = (link, event) => {
    if (!link) return true;
    if (link.closest('[data-no-loading]')) return true;
    if (link.hasAttribute('download')) return true;
    const href = link.getAttribute('href');
    if (!href) return true;
    if (href.startsWith('#')) return true;
    if (href.startsWith('javascript:')) return true;
    if (href.startsWith('mailto:') || href.startsWith('tel:')) return true;
    const target = link.getAttribute('target');
    if (target && target !== '_self') return true;
    if (event) {
        if (event.defaultPrevented) return true;
        if (event.button !== 0) return true;
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return true;
    }
    return false;
};

const shouldIgnoreForm = (form, event) => {
    if (!form) return true;
    if (form.closest('[data-no-loading]')) return true;
    if (form.classList.contains('js-swal-delete')) return true;
    const target = form.getAttribute('target');
    if (target && target !== '_self') return true;
    if (event && event.defaultPrevented) return true;
    return false;
};

const bindLoadingOverlay = () => {
    if (window.__loadingOverlayBound) return;
    window.__loadingOverlayBound = true;

    document.addEventListener('click', (event) => {
        const link = event.target.closest('a');
        if (shouldIgnoreLink(link, event)) return;
        loadingOverlay.show();
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (shouldIgnoreForm(form, event)) return;
        loadingOverlay.show();
    });

    document.addEventListener('pageshow', () => loadingOverlay.hide());

    if (window.Turbo) {
        document.addEventListener('turbo:visit', () => loadingOverlay.show());
        document.addEventListener('turbo:submit-start', () => loadingOverlay.show());
        document.addEventListener('turbo:submit-end', () => loadingOverlay.hide());
        document.addEventListener('turbo:render', () => loadingOverlay.hide());
        document.addEventListener('turbo:load', () => loadingOverlay.hide());
        document.addEventListener('turbo:before-cache', () => loadingOverlay.hide());
        document.addEventListener('turbo:frame-load', () => loadingOverlay.hide());
    } else {
        document.addEventListener('DOMContentLoaded', () => loadingOverlay.hide(), { once: true });
    }
};

bindLoadingOverlay();

const syncSelectAllState = (scope) => {
    if (!scope) return;
    const selectAll = scope.querySelector('input[data-select-all]');
    if (!selectAll) return;
    const items = Array.from(scope.querySelectorAll('input[data-select-item]'))
        .filter((input) => !input.disabled);
    if (items.length === 0) {
        selectAll.checked = false;
        return;
    }
    selectAll.checked = items.every((input) => input.checked);
};

const syncAllSelectAllStates = () => {
    document.querySelectorAll('[data-select-scope]').forEach(syncSelectAllState);
};

const bindSelectAllCheckboxes = () => {
    if (window.__selectAllBound) return;
    window.__selectAllBound = true;

    document.addEventListener('change', (event) => {
        const selectAll = event.target.closest('input[data-select-all]');
        if (selectAll) {
            const scope = selectAll.closest('[data-select-scope]') || selectAll.closest('form') || document;
            scope.querySelectorAll('input[data-select-item]').forEach((input) => {
                if (input.disabled) return;
                input.checked = selectAll.checked;
            });
            return;
        }

        const item = event.target.closest('input[data-select-item]');
        if (!item) return;
        const scope = item.closest('[data-select-scope]') || item.closest('form') || document;
        syncSelectAllState(scope);
    });

    document.addEventListener('turbo:load', syncAllSelectAllStates);
    document.addEventListener('turbo:render', syncAllSelectAllStates);
    document.addEventListener('DOMContentLoaded', syncAllSelectAllStates, { once: true });
};

bindSelectAllCheckboxes();

Alpine.data('crudModal', (el) => {
    const parseJson = (value, fallback) => {
        if (value === undefined || value === null || value === '') {
            return fallback;
        }
        try {
            return JSON.parse(value);
        } catch (error) {
            return fallback;
        }
    };

    const initialForm = parseJson(el?.dataset?.form, {
        id: null,
        tax_id: '',
        legal_name: '',
        address: '',
    });

    return {
        open: parseJson(el?.dataset?.open, false),
        mode: parseJson(el?.dataset?.mode, 'create'),
        form: initialForm,
        createUrl: parseJson(el?.dataset?.createUrl, ''),
        updateBaseUrl: parseJson(el?.dataset?.updateBaseUrl, ''),
        get formAction() {
            return this.mode === 'create' ? this.createUrl : `${this.updateBaseUrl}/${this.form.id}`;
        },
        openCreate() {
            this.mode = 'create';
            this.form = { id: null, tax_id: '', legal_name: '', address: '' };
            this.open = true;
        },
        openEdit(company) {
            this.mode = 'edit';
            this.form = {
                id: company.id,
                tax_id: company.tax_id || '',
                legal_name: company.legal_name || '',
                address: company.address || '',
            };
            this.open = true;
        },
    };
});

if (window.Turbo) {
    window.Turbo.session.drive = true;
}

const bindSwalDelete = () => {
    document.querySelectorAll('.js-swal-delete').forEach((form) => {
        if (form.dataset.swalBound === 'true') return;
        form.dataset.swalBound = 'true';
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            if (!window.Swal) {
                form.submit();
                return;
            }
            const title = form.dataset.swalTitle || '¿Eliminar registro?';
            const text = form.dataset.swalText || 'Esta acción no se puede deshacer.';
            const icon = form.dataset.swalIcon || 'warning';
            const confirmText = form.dataset.swalConfirm || 'Sí, eliminar';
            const cancelText = form.dataset.swalCancel || 'Cancelar';
            const confirmColor = form.dataset.swalConfirmColor || '#ef4444';
            const cancelColor = form.dataset.swalCancelColor || '#6b7280';

            Swal.fire({
                title,
                text,
                icon,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                confirmButtonColor: confirmColor,
                cancelButtonColor: cancelColor,
                reverseButtons: true,
                allowOutsideClick: false,
            }).then((result) => {
                if (result.isConfirmed) {
                    if (window.showLoadingModal) {
                        window.showLoadingModal();
                    }
                    form.submit();
                }
            });
        });
    });
};

const initPage = () => {
    // Map imports
    if (document.querySelector('#mapOne')) {
        import('./components/map').then(module => module.initMap());
    }

    // Chart imports
    if (document.querySelector('#chartOne')) {
        import('./components/chart/chart-1').then(module => module.initChartOne());
    }
    if (document.querySelector('#chartTwo')) {
        import('./components/chart/chart-2').then(module => module.initChartTwo());
    }
    if (document.querySelector('#chartThree')) {
        import('./components/chart/chart-3').then(module => module.initChartThree());
    }
    if (document.querySelector('#chartSix')) {
        import('./components/chart/chart-6').then(module => module.initChartSix());
    }
    if (document.querySelector('#chartEight')) {
        import('./components/chart/chart-8').then(module => module.initChartEight());
    }
    if (document.querySelector('#chartThirteen')) {
        import('./components/chart/chart-13').then(module => module.initChartThirteen());
    }

    // Calendar init
    if (document.querySelector('#calendar')) {
        import('./components/calendar-init').then(module => module.calendarInit());
    }
};

let alpineBooted = false;

const bootAlpine = () => {
    if (alpineBooted) {
        return;
    }
    Alpine.start();
    alpineBooted = true;
};

document.addEventListener('turbo:before-cache', () => {
    if (window.Alpine && alpineBooted) {
        Alpine.destroyTree(document.body);
    }
});

document.addEventListener('turbo:load', () => {
    if (window.Alpine) {
        if (!alpineBooted) {
            bootAlpine();
        } else {
            Alpine.initTree(document.body);
        }
    }
    initPage();
    bindSwalDelete();
});

document.addEventListener('DOMContentLoaded', () => {
    if (window.Turbo) {
        return;
    }
    bootAlpine();
    initPage();
    bindSwalDelete();
});
