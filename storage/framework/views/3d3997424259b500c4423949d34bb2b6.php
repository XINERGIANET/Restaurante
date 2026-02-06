<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="turbo-cache-control" content="no-preview">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">

    <title><?php echo e($title ?? 'Dashboard'); ?> | Xinergia FOOD</title>

    <!-- Scripts -->
    <script>
        window.crudModal = function (el) {
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
        };

        document.addEventListener('alpine:init', () => {
            if (window.Alpine && typeof window.crudModal === 'function') {
                Alpine.data('crudModal', (el) => window.crudModal(el));
            }
        });
    </script>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css">

    <!-- Alpine.js -->
    

    <!-- Theme Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('theme', {
                init() {
                    const savedTheme = localStorage.getItem('theme');
                    const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' :
                        'light';
                    this.theme = savedTheme || systemTheme;
                    this.updateTheme();
                },
                theme: 'light',
                toggle() {
                    this.theme = this.theme === 'light' ? 'dark' : 'light';
                    localStorage.setItem('theme', this.theme);
                    this.updateTheme();
                },
                updateTheme() {
                    const html = document.documentElement;
                    const body = document.body;
                    if (this.theme === 'dark') {
                        html.classList.add('dark');
                        body.classList.add('dark', 'bg-gray-900');
                    } else {
                        html.classList.remove('dark');
                        body.classList.remove('dark', 'bg-gray-900');
                    }
                }
            });

            Alpine.store('sidebar', {
                // Initialize based on screen size
                isExpanded: window.innerWidth >= 1280, // true for desktop, false for mobile
                isMobileOpen: false,
                isHovered: false,

                toggleExpanded() {
                    this.isExpanded = !this.isExpanded;
                    // When toggling desktop sidebar, ensure mobile menu is closed
                    this.isMobileOpen = false;
                },

                toggleMobileOpen() {
                    this.isMobileOpen = !this.isMobileOpen;
                    // Don't modify isExpanded when toggling mobile menu
                },

                setMobileOpen(val) {
                    this.isMobileOpen = val;
                },

                setHovered(val) {
                    // Only allow hover effects on desktop when sidebar is collapsed
                    if (window.innerWidth >= 1280 && !this.isExpanded) {
                        this.isHovered = val;
                    }
                }
            });
        });
    </script>

    <!-- Apply dark mode immediately to prevent flash -->
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme');
            const systemTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            const theme = savedTheme || systemTheme;
            const root = document.documentElement;
            const applyBody = () => {
                const body = document.body;
                if (!body) {
                    return;
                }
                if (theme === 'dark') {
                    body.classList.add('dark', 'bg-gray-900');
                } else {
                    body.classList.remove('dark', 'bg-gray-900');
                }
            };

            if (theme === 'dark') {
                root.classList.add('dark');
            } else {
                root.classList.remove('dark');
            }

            if (document.body) {
                applyBody();
            } else {
                document.addEventListener('DOMContentLoaded', applyBody, { once: true });
            }
        })();
    </script>
    
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
.swal-on-top { z-index: 2147483647 !important; }
.swal2-container { z-index: 2147483647 !important; position: fixed !important; inset: 0 !important; pointer-events: none; }
.swal2-popup { pointer-events: all; }
</style>
<style>
.swal2-container.swal2-bottom-start { padding-left: 18rem !important; }
@media (max-width: 1280px) {
  .swal2-container.swal2-bottom-start { padding-left: 1rem !important; }
}
</style>
<style>

.swal2-container { position: fixed !important; inset: 0 !important; }
</style>
<style>
body.swal2-shown > .swal2-container { z-index: 999999 !important; }
body.swal2-shown .swal2-container { z-index: 999999 !important; }
</style>
<style>
body.swal2-shown .sidebar,
body.swal2-shown [class*='sidebar'] { z-index: 10 !important; }
</style><style>
body.swal2-shown .swal2-container { z-index: 1000000 !important; position: fixed !important; inset: 0 !important; }
body.swal2-shown 
body.swal2-shown #sidebar { z-index: 1 !important; }
</style></head>

<body class="min-h-screen flex flex-col"
    x-data="{ 'loaded': true}"
    x-init="$store.sidebar.isExpanded = window.innerWidth >= 1280;
    const checkMobile = () => {
        if (window.innerWidth < 1280) {
            $store.sidebar.setMobileOpen(false);
            $store.sidebar.isExpanded = false;
        } else {
            $store.sidebar.isMobileOpen = false;
            $store.sidebar.isExpanded = true;
        }
    };
    if (window.__sidebarResizeHandler) {
        window.removeEventListener('resize', window.__sidebarResizeHandler);
    }
    window.__sidebarResizeHandler = checkMobile;
    window.addEventListener('resize', window.__sidebarResizeHandler);">

    
    <?php if (isset($component)) { $__componentOriginal33757e58bef6aaec67779bf03774fc2d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal33757e58bef6aaec67779bf03774fc2d = $attributes; } ?>
<?php $component = App\View\Components\Common\Preloader::resolve([] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.preloader'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\App\View\Components\Common\Preloader::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal33757e58bef6aaec67779bf03774fc2d)): ?>
<?php $attributes = $__attributesOriginal33757e58bef6aaec67779bf03774fc2d; ?>
<?php unset($__attributesOriginal33757e58bef6aaec67779bf03774fc2d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal33757e58bef6aaec67779bf03774fc2d)): ?>
<?php $component = $__componentOriginal33757e58bef6aaec67779bf03774fc2d; ?>
<?php unset($__componentOriginal33757e58bef6aaec67779bf03774fc2d); ?>
<?php endif; ?>
    
    <?php if (isset($component)) { $__componentOriginalfbd41a2441aa05314db5f465fa5a44df = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfbd41a2441aa05314db5f465fa5a44df = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.common.loading-overlay','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('common.loading-overlay'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfbd41a2441aa05314db5f465fa5a44df)): ?>
<?php $attributes = $__attributesOriginalfbd41a2441aa05314db5f465fa5a44df; ?>
<?php unset($__attributesOriginalfbd41a2441aa05314db5f465fa5a44df); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfbd41a2441aa05314db5f465fa5a44df)): ?>
<?php $component = $__componentOriginalfbd41a2441aa05314db5f465fa5a44df; ?>
<?php unset($__componentOriginalfbd41a2441aa05314db5f465fa5a44df); ?>
<?php endif; ?>

    <div class="flex-1 xl:flex">
        <?php echo $__env->make('layouts.backdrop', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php echo $__env->make('layouts.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="flex-1 flex flex-col transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            <?php echo $__env->make('layouts.app-header', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            <!-- app header end -->
            
            <div class="flex-1 p-4 mx-auto w-full max-w-(--breakpoint-2xl) md:p-6">
                <?php echo $__env->yieldContent('content'); ?>
            </div>

            <footer class="mt-auto border-t border-gray-200 bg-[#F4F6FA] px-6 py-4 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900 dark:text-gray-400">
                © 2026 Pie por Xinergia.
            </footer>
        </div>

    </div>

</body>

<?php if(session('status')): ?>
<script>
    const showStatusToast = () => {
        const message = <?php echo json_encode(session('status'), 15, 512) ?>;
        const key = 'toast:status';
        if (window.sessionStorage && sessionStorage.getItem(key) === message) {
            return;
        }
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: message,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        }
        if (window.sessionStorage) {
            sessionStorage.setItem(key, message);
        }
    };
    document.addEventListener('DOMContentLoaded', showStatusToast, { once: true });
    document.addEventListener('turbo:load', showStatusToast);
</script>
<?php endif; ?>

<?php if(session('error')): ?>
<script>
    const showErrorToast = () => {
        const message = <?php echo json_encode(session('error'), 15, 512) ?>;
        const key = 'toast:error';
        if (window.sessionStorage && sessionStorage.getItem(key) === message) {
            return;
        }
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'error',
                title: message,
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        }
        if (window.sessionStorage) {
            sessionStorage.setItem(key, message);
        }
    };
    document.addEventListener('DOMContentLoaded', showErrorToast, { once: true });
    document.addEventListener('turbo:load', showErrorToast);
</script>
<?php endif; ?>
<script>
    if (!window.__globalSwalDeleteHandler) {
        document.addEventListener('submit', (event) => {
            const form = event.target.closest('.js-swal-delete');
            if (!form) return;
            if (form.dataset.swalBound === 'true') return;
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

            const isDark = document.documentElement.classList.contains('dark');
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
                background: isDark ? '#111827' : '#ffffff',
                color: isDark ? '#e5e7eb' : '#111827',
                customClass: {
                    backdrop: 'swal-backdrop-blur',
                },
                didOpen: (popup) => {
                    popup.classList.toggle('swal-dark', isDark);
                },
            }).then((result) => {
                if (result.isConfirmed) {
                    if (window.showLoadingModal) {
                        window.showLoadingModal();
                    }
                    form.dataset.swalBound = 'true';
                    form.submit();
                }
            });
        });
        window.__globalSwalDeleteHandler = true;
    }
</script>
<style>
    .swal2-container.swal2-backdrop-show .swal-backdrop-blur {
        background-color: rgba(156, 163, 175, 0.5) !important;
        opacity: 1 !important;
        backdrop-filter: blur(32px) !important;
        -webkit-backdrop-filter: blur(32px) !important;
    }
</style>
    <?php echo $__env->yieldPushContent('scripts'); ?>

</html>











<?php /**PATH C:\laragon\www\Restaurante\resources\views/layouts/app.blade.php ENDPATH**/ ?>