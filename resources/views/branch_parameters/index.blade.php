@extends('layouts.app')

@section('title', $title ?? 'Configuración de Sistema')

@section('content')
    <div x-data="{ open: false }">
        @php
            use Illuminate\Support\Facades\Route;

            $viewId = request('view_id');
            $operacionesCollection = collect($operaciones ?? []);
            $topOperations = $operacionesCollection->where('type', 'T');

            $resolveActionUrl = function ($action, $company = null, $operation = null) use ($viewId) {
                if (!$action) return '#';

                if (str_starts_with($action, '/') || str_starts_with($action, 'http')) {
                    $url = $action;
                } else {
                    $routeCandidates = [$action];
                    if (!str_starts_with($action, 'admin.')) {
                        $routeCandidates[] = 'admin.' . $action;
                    }
                    $routeCandidates = array_merge(
                        $routeCandidates,
                        array_map(fn ($name) => $name . '.index', $routeCandidates)
                    );

                    $routeName = null;
                    foreach ($routeCandidates as $candidate) {
                        if (Route::has($candidate)) {
                            $routeName = $candidate;
                            break;
                        }
                    }

                    if ($routeName) {
                        try {
                            $url = $company ? route($routeName, $company) : route($routeName);
                        } catch (\Exception $e) {
                            $url = '#';
                        }
                    } else {
                        $url = '#';
                    }
                }

                $targetViewId = $viewId;
                if ($operation && !empty($operation->view_id_action)) {
                    $targetViewId = $operation->view_id_action;
                }

                if ($targetViewId && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'view_id=' . urlencode($targetViewId);
                }

                if ($viewId && $operation && !empty($operation->view_id_action) && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'company_view_id=' . urlencode($viewId);
                }

                if ($operation && !empty($operation->icon) && str_contains($action, 'branches') && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'icon=' . urlencode($operation->icon);
                }

                return $url;
            };

            $resolveTextColor = function ($operation) {
                return '#FFFFFF';
            };
        @endphp

        <x-common.page-breadcrumb pageTitle="Configuración de Sistema" />
        
        <x-common.component-card>
        
            @if($topOperations->count() > 0)
                <div class="flex flex-wrap items-center gap-3 mb-6 justify-end border-b border-gray-100 dark:border-gray-800 pb-4">
                    @foreach ($topOperations as $operation)
                        @php
                            $topTextColor = $resolveTextColor($operation);
                            $topColor = $operation->color ?: '#10B981';
                            $topStyle = "background-color: {$topColor}; color: {$topTextColor}; border-color: {$topColor};";
                            $topActionUrl = $resolveActionUrl($operation->action ?? '', null, $operation);
                        @endphp
                        
                        <x-ui.link-button size="md" variant="primary"
                            class="w-full sm:w-auto h-11 px-6 shadow-sm hover:opacity-90 transition-opacity rounded-lg font-semibold"
                            style="{{ $topStyle }}"
                            href="{{ $topActionUrl }}">
                            <i class="{{ $operation->icon }} text-lg me-2"></i>
                            <span>{{ $operation->name }}</span>
                        </x-ui.link-button>
                    @endforeach
                </div>
            @endif

            <section class="mb-8 rounded-2xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-700 dark:bg-gray-900/50">
                <div class="mb-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">Estaciones de impresión QZ Tray</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Registra cada PC de la sucursal con su IP y su propio certificado. La clave se guarda cifrada y nunca se muestra.</p>
                    </div>
                    <a href="{{ route('printers_branch.index', $viewId ? ['view_id' => $viewId] : []) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-[#C43B25] px-4 text-sm font-semibold text-white">
                        <i class="ri-node-tree"></i> Ver red de impresión
                    </a>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    @foreach($printStations ?? [] as $station)
                        <form action="{{ route('print-stations.update', $station) }}" method="POST" enctype="multipart/form-data" class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                            @csrf @method('PUT')
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-500/10"><i class="ri-computer-line text-xl"></i></span>
                                    <div><p class="font-bold text-gray-900 dark:text-white">{{ $station->name }}</p><p class="text-xs text-gray-500">{{ $station->printers_count }} ticketera(s) · {{ $station->ip_address }}</p></div>
                                </div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $station->hasCredentials() ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">{{ $station->hasCredentials() ? 'Certificado listo' : 'Sin certificado' }}</span>
                            </div>
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <input name="name" value="{{ $station->name }}" required placeholder="Nombre de PC" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                                <input name="ip_address" value="{{ $station->ip_address }}" required placeholder="IP de la PC" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                                <input name="hostname" value="{{ $station->hostname }}" placeholder="Nombre en Windows" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                                <input name="location" value="{{ $station->location }}" placeholder="Ubicación: Caja 1" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                                <select name="status" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white"><option value="E" @selected($station->status === 'E')>Activa</option><option value="I" @selected($station->status === 'I')>Inactiva</option></select>
                                <button type="button" data-activate-station="{{ $station->uuid }}" data-station-name="{{ $station->name }}" class="activate-print-station h-10 rounded-lg border border-blue-200 bg-blue-50 px-3 text-sm font-semibold text-blue-700 hover:bg-blue-100">Usar esta PC</button>
                            </div>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Reemplazar certificado<input type="file" name="qz_certificate" accept=".txt,.pem,.crt,.cer" class="mt-1 block w-full text-xs"></label>
                                <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Reemplazar private key<input type="file" name="qz_private_key" accept=".pem,.key,.txt" class="mt-1 block w-full text-xs"></label>
                            </div>
                            <div class="mt-4 flex justify-end"><button class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white dark:bg-white dark:text-gray-900">Guardar estación</button></div>
                        </form>
                    @endforeach

                    <form action="{{ route('print-stations.store') }}" method="POST" enctype="multipart/form-data" class="rounded-xl border-2 border-dashed border-gray-300 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                        @csrf
                        <h3 class="mb-3 font-bold text-gray-900 dark:text-white"><i class="ri-add-circle-line mr-1 text-[#C43B25]"></i> Registrar nueva PC</h3>
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <input name="name" value="{{ old('name') }}" required placeholder="Nombre: PC Caja 1" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                            <input name="ip_address" value="{{ old('ip_address') }}" required placeholder="IP: 192.168.1.20" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                            <input name="hostname" value="{{ old('hostname') }}" placeholder="Nombre en Windows" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                            <input name="location" value="{{ old('location') }}" placeholder="Ubicación: Caja 1" class="h-10 rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Certificado QZ<input required type="file" name="qz_certificate" accept=".txt,.pem,.crt,.cer" class="mt-1 block w-full text-xs"></label>
                            <label class="text-xs font-medium text-gray-600 dark:text-gray-300">Private key QZ<input required type="file" name="qz_private_key" accept=".pem,.key,.txt" class="mt-1 block w-full text-xs"></label>
                        </div>
                        <button class="mt-4 rounded-lg bg-[#C43B25] px-4 py-2 text-sm font-semibold text-white">Registrar estación</button>
                    </form>
                </div>
                @if($errors->any())<div class="mt-4 rounded-lg bg-red-50 p-3 text-sm text-red-700">{{ $errors->first() }}</div>@endif
            </section>

            <form action="{{ route('branch-parameter.store') }}" method="POST" class="mt-2">
                @csrf
                <input type="hidden" name="branch_payment_methods_include" value="1">

                <div class="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto no-scrollbar mb-8 gap-6">
                    @foreach($categories as $category)
                        <button type="button" 
                                class="tab-btn pb-3 text-sm font-bold relative whitespace-nowrap transition-colors duration-200 
                                       {{ $loop->first ? 'text-blue-700 dark:text-blue-500' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400' }}"
                                data-target="tab-category-{{ $category->id }}"
                                aria-selected="{{ $loop->first ? 'true' : 'false' }}">
                            
                            {{ $category->description }}
                            
                            <span class="tab-indicator absolute bottom-0 left-0 w-full h-[3px] bg-blue-700 dark:bg-blue-500 transition-transform duration-300 origin-left 
                                         {{ $loop->first ? 'scale-x-100' : 'scale-x-0' }}"></span>
                        </button>
                    @endforeach
                </div>

                <div class="tabs-content-container">
                    @foreach($categories as $category)
                        <div id="tab-category-{{ $category->id }}" 
                             class="tab-pane animate-fade-in {{ $loop->first ? 'block' : 'hidden' }}">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 items-start">
                                @foreach($category->parameters as $parameter)
                                
                                    <div class="bg-white dark:bg-gray-900 p-5 rounded-xl border border-gray-200 dark:border-gray-800 flex flex-col justify-start h-fit shadow-sm hover:shadow-md transition-shadow">
                                        
                                        <label class="block text-sm font-bold text-gray-900 dark:text-gray-100 mb-3 uppercase">
                                            {{ $parameter->description }}
                                        </label>
                                        @php
                                            $paramKey = $parameter->branch_parameter_id ?? 'p' . $parameter->id;
                                            $desc = trim($parameter->description ?? '');
                                            $descLower = mb_strtolower($desc, 'UTF-8');
                                            $isRequerirPinMozo = strcasecmp($desc, 'Requerir PIN a mozo') === 0;
                                            $isIgvDefecto = strcasecmp($desc, 'igv_defecto') === 0;
                                            // Por descripción (evita confundir con ids de contraseñas si "METODOS DE PAGO" tiene mal el id)
                                            $isMetodosPagoParam = str_contains($descLower, 'metodo') && str_contains($descLower, 'pago');
                                            // Solo por descripción para evitar confundir parámetros mal rotulados en producción.
                                            $showMetodosPagoUi = $isMetodosPagoParam;
                                            $isAllowZeroStockSalesParam =
                                                str_contains($descLower, 'permitir') &&
                                                str_contains($descLower, 'stock') &&
                                                (str_contains($descLower, '0') || str_contains($descLower, 'cero'));
                                            // Detectar "TIPO VENTA POR DEFECTO" por descripción (no por ID hardcodeado)
                                            $isTipoVentaParam =
                                                (str_contains($descLower, 'tipo') && str_contains($descLower, 'venta')) ||
                                                str_contains($descLower, 'tipo_venta') ||
                                                str_contains($descLower, 'comprobante') ||
                                                str_contains($descLower, 'tipo de comprobante');
                                            // Detectar parámetros de contraseña por descripción
                                            $isPasswordParam =
                                                str_contains($descLower, 'contrase') ||
                                                str_contains($descLower, 'password') ||
                                                str_contains($descLower, 'clave') ||
                                                (str_contains($descLower, 'pin') && !$isRequerirPinMozo);
                                            // Detectar parámetros de tipo IGV por descripción (además del id=2)
                                            $isIgvParam = $isIgvDefecto ||
                                                (str_contains($descLower, 'igv') && str_contains($descLower, 'defecto'));
                                        @endphp
                                        @if($isRequerirPinMozo)
                                            {{-- REQUERIR PIN A MOZO: 0 o 1 --}}
                                            <select name="parameters[{{ $paramKey }}]" 
                                                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                <option value="0" {{ ($parameter->branch_value ?? '') == '0' ? 'selected' : '' }}>No (0)</option>
                                                <option value="1" {{ ($parameter->branch_value ?? '') == '1' ? 'selected' : '' }}>Sí (1)</option>
                                            </select>
                                        @elseif($isIgvParam)
                                            {{-- IGV POR DEFECTO: selector de tasas de impuesto --}}
                                            <select name="parameters[{{ $paramKey }}]" 
                                                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                <option value="">Seleccionar...</option>
                                                @foreach($igv ?? [] as $igvItem)
                                                    <option value="{{ $igvItem->id }}" {{ $parameter->branch_value == $igvItem->id ? 'selected' : '' }}>
                                                        {{ trim(str_ireplace('de venta', '', $igvItem->description)) }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif($showMetodosPagoUi)
                                            {{-- Métodos de pago por sucursal (pivote branch_payment_methods) --}}
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">
                                                Marca los métodos que podrán usarse en ventas y cobros de esta sucursal.
                                                Si marcas todos o ninguno y guardas, se considera "sin restricción" (aplican todos los métodos activos del sistema, incluidos los nuevos).
                                            </p>
                                            <div class="max-h-48 overflow-y-auto space-y-2 border border-gray-200 dark:border-gray-700 rounded-lg p-3 bg-gray-50 dark:bg-gray-800/50">
                                                @foreach($paymentMethods ?? [] as $method)
                                                    <label class="flex items-center gap-2 text-sm text-gray-800 dark:text-gray-200 cursor-pointer">
                                                        <input type="checkbox"
                                                               name="branch_payment_method_ids[]"
                                                               value="{{ $method->id }}"
                                                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                               {{ in_array((int) $method->id, $branchPaymentMethodIds ?? [], true) ? 'checked' : '' }}>
                                                        <span>{{ trim(str_ireplace('de venta', '', $method->description)) }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @elseif($isAllowZeroStockSalesParam)
                                            {{-- Permitir vender con stock 0 (o insuficiente): 0/1 --}}
                                            <select name="parameters[{{ $paramKey }}]"
                                                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                <option value="0" {{ (string) ($parameter->branch_value ?? '') === '0' ? 'selected' : '' }}>No (0)</option>
                                                <option value="1" {{ (string) ($parameter->branch_value ?? '') === '1' ? 'selected' : '' }}>Sí (1)</option>
                                            </select>
                                        @elseif($isTipoVentaParam)
                                            {{-- TIPO DE VENTA POR DEFECTO: Ticket / Boleta / Factura (detectado por descripción) --}}
                                            <select name="parameters[{{ $paramKey }}]" 
                                                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                <option value="">Seleccionar...</option>
                                                @foreach($tiposVenta ?? [] as $tipo)
                                                    <option value="{{ $tipo->id }}" {{ $parameter->branch_value == $tipo->id ? 'selected' : '' }}>
                                                        {{ $tipo->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        @elseif($isPasswordParam)
                                            {{-- PARÁMETROS DE CONTRASEÑA (detectado por descripción) --}}
                                            @php
                                                $tienePass = !empty($parameter->branch_value) && $parameter->branch_value !== 'No';
                                            @endphp
                                            <div x-data="{ pedirPass: '{{ $tienePass ? 'Si' : 'No' }}', showPass: false }" class="w-full">
                                                
                                                <select x-model="pedirPass" 
                                                        class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                    <option value="No">No (Sin contraseña)</option>
                                                    <option value="Si">Sí (Requiere contraseña)</option>
                                                </select>

                                                <input type="hidden" 
                                                       name="parameters[{{ $paramKey }}]" 
                                                       value="No" 
                                                       x-bind:disabled="pedirPass === 'Si'">

                                                <div x-show="pedirPass === 'Si'" 
                                                     x-transition:enter="transition ease-out duration-200"
                                                     x-transition:enter-start="opacity-0 -translate-y-2"
                                                     x-transition:enter-end="opacity-100 translate-y-0"
                                                     class="mt-3 relative">
                                                    <input :type="showPass ? 'text' : 'password'" 
                                                           name="parameters[{{ $paramKey }}]" 
                                                           value="{{ $tienePass ? e($parameter->branch_value) : '' }}"
                                                           x-bind:disabled="pedirPass === 'No'"
                                                           class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 px-3 py-2.5 pr-10 text-sm font-medium text-gray-900 dark:text-gray-100 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-inner"
                                                           placeholder="Escribe la contraseña...">
                                                           
                                                    <button type="button" 
                                                            @click="showPass = !showPass" 
                                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-blue-600 transition-colors focus:outline-none">
                                                        <i :class="showPass ? 'ri-eye-off-line' : 'ri-eye-line'" class="text-lg text-gray-800"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            {{-- POR DEFECTO: SELECT DE SÍ/NO --}}
                                            <select name="parameters[{{ $paramKey }}]" 
                                                    class="w-full border border-gray-200 dark:border-gray-700 rounded-lg bg-white dark:bg-gray-800 px-3 py-2.5 text-sm font-medium text-gray-700 dark:text-gray-200 focus:outline-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500 transition-colors shadow-sm">
                                                <option value="">Seleccionar...</option>
                                                <option value="Si" {{ $parameter->branch_value == 'Si' ? 'selected' : '' }}>Sí</option>
                                                <option value="No" {{ $parameter->branch_value == 'No' ? 'selected' : '' }}>No</option>
                                            </select>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                        </div>
                    @endforeach
                </div>

                <div class="mt-12 pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-end gap-3">
                    <x-ui.button type="submit" size="md" variant="primary">
                        <i class="ri-save-line"></i>
                        <span>Guardar</span>
                    </x-ui.button>
                </div>
            </form>

        </x-common.component-card>
    </div>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        .animate-fade-in { animation: fadeIn 0.3s ease-in-out; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>

    <script>
        function initBranchParameterTabs() {
            const container = document.querySelector('.tabs-content-container');
            if (!container || container.dataset.tabsInitialized === '1') return;
            container.dataset.tabsInitialized = '1';

            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabPanes = document.querySelectorAll('.tab-pane');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    // 1. Resetear todos los botones
                    tabBtns.forEach(b => {
                        b.classList.remove('text-blue-700', 'dark:text-blue-500');
                        b.classList.add('text-gray-500', 'hover:text-gray-800', 'dark:text-gray-400');
                        b.setAttribute('aria-selected', 'false');
                        const indicator = b.querySelector('.tab-indicator');
                        if(indicator) {
                            indicator.classList.replace('scale-x-100', 'scale-x-0');
                        }
                    });

                    // 2. Ocultar todos los paneles
                    tabPanes.forEach(p => {
                        p.classList.remove('block');
                        p.classList.add('hidden');
                    });

                    // 3. Activar el botón seleccionado
                    btn.classList.remove('text-gray-500', 'hover:text-gray-800', 'dark:text-gray-400');
                    btn.classList.add('text-blue-700', 'dark:text-blue-500');
                    btn.setAttribute('aria-selected', 'true');
                    const activeIndicator = btn.querySelector('.tab-indicator');
                    if(activeIndicator) {
                        activeIndicator.classList.replace('scale-x-0', 'scale-x-100');
                    }

                    // 4. Mostrar el panel vinculado
                    const targetId = btn.getAttribute('data-target');
                    const targetPane = document.getElementById(targetId);
                    if(targetPane) {
                        targetPane.classList.remove('hidden');
                        targetPane.classList.add('block');
                    }
                });
            });
        }

        function initPrintStationButtons() {
            document.querySelectorAll('.activate-print-station').forEach((button) => {
                if (button.dataset.ready === '1') return;
                button.dataset.ready = '1';
                const uuid = button.dataset.activateStation || '';
                const active = localStorage.getItem('restaurant_print_station_uuid') === uuid;
                if (active) {
                    button.textContent = 'PC activa en este navegador';
                    button.classList.add('ring-2', 'ring-emerald-400');
                }
                button.addEventListener('click', () => {
                    localStorage.setItem('restaurant_print_station_uuid', uuid);
                    localStorage.setItem('restaurant_print_station_name', button.dataset.stationName || '');
                    window.location.reload();
                });
            });
        }

        document.addEventListener('DOMContentLoaded', initBranchParameterTabs);
        document.addEventListener('turbo:load', initBranchParameterTabs);
        document.addEventListener('DOMContentLoaded', initPrintStationButtons);
        document.addEventListener('turbo:load', initPrintStationButtons);
    </script>
@endsection
