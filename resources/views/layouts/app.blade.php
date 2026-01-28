<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="turbo-cache-control" content="no-preview">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Dashboard' }} | Xinergia FOOD</title>

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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/remixicon@4.5.0/fonts/remixicon.css">

    <!-- Alpine.js -->
    {{-- <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script> --}}

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
<style>\n.swal-on-top { z-index: 2147483647 !important; }\n.swal2-container { z-index: 2147483647 !important; position: fixed !important; inset: 0 !important; pointer-events: none; }\n.swal2-popup { pointer-events: all; }\n</style>\n<style>\n.swal2-container.swal2-bottom-start { padding-left: 18rem !important; }\n@media (max-width: 1280px) {\n  .swal2-container.swal2-bottom-start { padding-left: 1rem !important; }\n}\n</style>\n<style>\n\n.swal2-container { position: fixed !important; inset: 0 !important; }\n</style>\n<style>\nbody.swal2-shown > .swal2-container { z-index: 999999 !important; }\nbody.swal2-shown .swal2-container { z-index: 999999 !important; }\n</style>\n<style>\nbody.swal2-shown .sidebar,\nbody.swal2-shown [class*='sidebar'] { z-index: 10 !important; }\n</style>\n<style>
body.swal2-shown .swal2-container { z-index: 1000000 !important; position: fixed !important; inset: 0 !important; }
body.swal2-shown 
body.swal2-shown #sidebar { z-index: 1 !important; }
</style></head>

<body
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

    {{-- preloader --}}
    <x-common.preloader/>
    {{-- preloader end --}}

    <div class="min-h-screen xl:flex">
        @include('layouts.backdrop')
        @include('layouts.sidebar')

        <div class="flex-1 transition-all duration-300 ease-in-out"
            :class="{
                'xl:ml-[290px]': $store.sidebar.isExpanded || $store.sidebar.isHovered,
                'xl:ml-[90px]': !$store.sidebar.isExpanded && !$store.sidebar.isHovered,
                'ml-0': $store.sidebar.isMobileOpen
            }">
            <!-- app header start -->
            @include('layouts.app-header')
            <!-- app header end -->
            <div class="p-4 mx-auto max-w-(--breakpoint-2xl) md:p-6">
                @yield('content')
            </div>
        </div>

    </div>\n    <footer class="border-t border-gray-200 bg-white px-6 py-4 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900/40 dark:text-gray-400">
        � 2026 Foot by Xinergia.
    </footer>\n</body>

@if (session('status'))
<script>
    const showStatusToast = () => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: @json(session('status')),
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        }
    };
    document.addEventListener('DOMContentLoaded', showStatusToast, { once: true });
    document.addEventListener('turbo:load', showStatusToast);
</script>
@endif

@if (session('error'))
<script>
    const showErrorToast = () => {
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'error',
                title: @json(session('error')),
                showConfirmButton: false,
                timer: 4000,
                timerProgressBar: true
            });
        }
    };
    document.addEventListener('DOMContentLoaded', showErrorToast, { once: true });
    document.addEventListener('turbo:load', showErrorToast);
</script>
@endif
    @stack('scripts')

</html>





















