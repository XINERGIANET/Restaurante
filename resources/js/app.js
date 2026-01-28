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
