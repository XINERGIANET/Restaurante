@extends('layouts.app')

@section('content')
    @php
        use Illuminate\Support\Facades\Route;

        $viewId = request('view_id');
        $operacionesCollection = collect($operaciones ?? []);
        $topOperations = $operacionesCollection->where('type', 'T');
        $rowOperations = $operacionesCollection->where('type', 'R');

        $allPrintersCollection = collect($printers->items());
        $networkPrinters = $allPrintersCollection->filter(fn($p) => ($p->connection_type ?? 'network') === 'network' || filled($p->ip));
        $usbPrinters = $allPrintersCollection->filter(fn($p) => ($p->connection_type ?? 'network') === 'usb');
        $unassignedUsbPrinters = $usbPrinters->filter(fn($p) => empty($p->print_station_id));

        $productBranchesCollection = collect($productBranches ?? []);
        $allProductIds = $productBranchesCollection->pluck('id')->map(fn($id) => (int)$id)->values()->all();
        $groupedProducts = $productBranchesCollection->groupBy(fn($pb) => $pb->product?->category?->description ?? 'Sin Categoría');
    @endphp

    <x-common.page-breadcrumb pageTitle="Impresoras de Sucursal" />

    <div x-data="{
        viewMode: 'map',
        testingPrinterId: null,
        testingMessage: '',
        activeStationUuid: localStorage.getItem('restaurant_print_station_uuid') || '',
        
        // Modal de Asignación Masiva de Productos
        assignModalOpen: false,
        assignPrinterId: null,
        assignPrinterName: '',
        assignSelectedProducts: [],
        assignSearch: '',
        assignSaving: false,
        allProductIds: @js($allProductIds),

        // Mapeo dinámico de conteos de productos asignados por ID de impresora
        printerProductCounts: {
            @foreach($allPrintersCollection as $p)
                '{{ $p->id }}': {{ $p->productBranches->count() }},
            @endforeach
        },

        // Mapeo dinámico de IDs de productos asignados por ID de impresora
        printerAssignedProductIds: {
            @foreach($allPrintersCollection as $p)
                '{{ $p->id }}': @js($p->productBranches->pluck('id')->map(fn($id) => (int)$id)->values()->all()),
            @endforeach
        },

        openAssignModal(printerId, printerName) {
            this.assignPrinterId = printerId;
            this.assignPrinterName = printerName;
            this.assignSelectedProducts = [...(this.printerAssignedProductIds[printerId] || [])];
            this.assignSearch = '';
            this.assignModalOpen = true;
        },

        selectAllAssign() {
            this.assignSelectedProducts = [...this.allProductIds];
        },

        deselectAllAssign() {
            this.assignSelectedProducts = [];
        },

        toggleCategoryAssign(categoryIds) {
            const catIds = categoryIds.map(Number);
            const allSelected = catIds.every(id => this.assignSelectedProducts.includes(id));
            if (allSelected) {
                this.assignSelectedProducts = this.assignSelectedProducts.filter(id => !catIds.includes(id));
            } else {
                const newSet = new Set([...this.assignSelectedProducts, ...catIds]);
                this.assignSelectedProducts = Array.from(newSet);
            }
        },

        isCategorySelectedAssign(categoryIds) {
            if (!categoryIds || categoryIds.length === 0) return false;
            return categoryIds.every(id => this.assignSelectedProducts.includes(Number(id)));
        },

        async saveAssignedProducts() {
            if (!this.assignPrinterId) return;
            this.assignSaving = true;
            try {
                const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '';
                const response = await fetch('/restaurante/configuracion/impresoras-sucursal/' + this.assignPrinterId + '/assign-products', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        product_branch_ids: this.assignSelectedProducts
                    })
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    Swal.fire({ icon: 'error', title: 'Error', text: result.message || 'No se pudo guardar la asignación.' });
                    return;
                }

                this.printerProductCounts[this.assignPrinterId] = result.count;
                this.printerAssignedProductIds[this.assignPrinterId] = result.assigned_ids;
                this.assignModalOpen = false;

                Swal.fire({
                    icon: 'success',
                    title: '¡Asignación Guardada!',
                    text: result.message,
                    timer: 2500
                });
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Excepción', text: e.message || 'Error de red.' });
            } finally {
                this.assignSaving = false;
            }
        },

        async testPrinter(printerId, printerName) {
            this.testingPrinterId = printerId;
            this.testingMessage = 'Probando conexión con ' + printerName + '...';
            try {
                const csrfToken = document.querySelector('meta[name=csrf-token]')?.content || '';
                const response = await fetch('/restaurante/configuracion/impresoras-sucursal/' + printerId + '/test', {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const result = await response.json();
                if (!response.ok || !result.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de Impresión',
                        text: result.message || 'No se pudo completar la prueba de impresión.'
                    });
                    return;
                }

                if (result.is_usb && result.b64) {
                    const qzApi = window.qz;
                    if (!qzApi) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'QZ Tray no detectado',
                            text: 'El servidor generó el ticket pero QZ Tray no está instalado o activo en este navegador.'
                        });
                        return;
                    }
                    const driver = result.driver_name || printerName;
                    if (typeof window.__qzConnectWithCertPairFallback === 'function') {
                        const connected = await window.__qzConnectWithCertPairFallback(qzApi, driver);
                        if (!connected) {
                            Swal.fire({ icon: 'error', title: 'QZ Tray', text: 'No se pudo conectar a QZ Tray local.' });
                            return;
                        }
                    }
                    const config = qzApi.configs.create(driver, { units: 'mm', size: { width: 80, height: 200 }, margins: 0 });
                    const rawData = atob(result.b64);
                    await qzApi.print(config, [{ type: 'raw', format: 'command', flavor: 'plain', data: rawData }]);
                    Swal.fire({
                        icon: 'success',
                        title: '¡Prueba Exitosa!',
                        text: 'Ticket de prueba enviado a QZ Tray (' + driver + ').',
                        timer: 2500
                    });
                } else {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Prueba Exitosa!',
                        text: result.message,
                        timer: 3000
                    });
                }
            } catch (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'Excepción de Red',
                    text: err.message || 'Error al comunicarse con el servidor.'
                });
            } finally {
                this.testingPrinterId = null;
                this.testingMessage = '';
            }
        },

        setActiveStation(uuid, name) {
            localStorage.setItem('restaurant_print_station_uuid', uuid);
            localStorage.setItem('restaurant_print_station_name', name);
            this.activeStationUuid = uuid;
            Swal.fire({
                icon: 'success',
                title: 'PC Activa Configurada',
                text: 'Esta PC (' + name + ') ahora firmará las solicitudes QZ Tray en este navegador.',
                timer: 2000
            });
        }
    }">

        <x-common.component-card title="Red de Impresión de Sucursal" desc="Visualiza y gestiona computadoras, ticketeras USB, impresoras LAN y sus productos asignados.">
            
            {{-- Toolbar Superior: Búsqueda, Acciones y Selector de Vista --}}
            <div class="mb-6 flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif

                    <x-ui.per-page-selector :per-page="$perPage" />

                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar impresora o PC..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-[#FF4622] focus:ring-[#FF4622]/10 dark:focus:border-[#FF4622] h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="md" variant="primary" type="submit"
                            class="flex-1 sm:flex-none h-11 px-5 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95"
                            style="background-color: #C43B25; border-color: #C43B25;">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>

                        <x-ui.link-button size="md" variant="outline"
                            href="{{ route('printers_branch.index', $viewId ? ['view_id' => $viewId] : []) }}"
                            class="flex-1 sm:flex-none h-11 px-5 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <div class="flex items-center gap-3">
                    {{-- Toggle de Vista: Mapa vs Tabla --}}
                    <div class="inline-flex rounded-xl border border-gray-200 bg-gray-100 p-1 dark:border-gray-800 dark:bg-gray-900">
                        <button type="button" @click="viewMode = 'map'"
                            :class="viewMode === 'map' ? 'bg-white text-[#C43B25] shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                            class="flex h-9 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition">
                            <i class="ri-node-tree text-base"></i> Estructura de Red
                        </button>
                        <button type="button" @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white text-[#C43B25] shadow-xs dark:bg-gray-800 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900'"
                            class="flex h-9 items-center gap-2 rounded-lg px-3.5 text-xs font-bold transition">
                            <i class="ri-table-line text-base"></i> Tabla Lista
                        </button>
                    </div>

                    <a href="{{ route('branch-parameter.index', $viewId ? ['view_id' => $viewId] : []) }}"
                        class="inline-flex h-11 items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200">
                        <i class="ri-settings-4-line text-lg text-[#C43B25]"></i>
                        <span>Gestionar PCs (Certificados)</span>
                    </a>

                    <x-ui.button size="md" variant="primary" type="button"
                        style="background-color: #12f00e; color: #111827;"
                        @click="$dispatch('open-create-printer-modal')" class="h-11 font-bold">
                        <i class="ri-add-line text-lg"></i>
                        <span>Nueva Ticketera</span>
                    </x-ui.button>
                </div>
            </div>

            {{-- Tarjetas Resumen de Red --}}
            <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                        <i class="ri-computer-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Estaciones PC</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $stations->count() }} <span class="text-xs font-normal text-gray-400">registradas</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400">
                        <i class="ri-usb-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Ticketeras USB</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $usbPrinters->count() }} <span class="text-xs font-normal text-gray-400">en PCs</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
                        <i class="ri-router-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Ticketeras LAN</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ $networkPrinters->count() }} <span class="text-xs font-normal text-gray-400">IP Directa</span></p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-4 shadow-xs dark:border-gray-800 dark:bg-gray-900">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                        <i class="ri-restaurant-2-line text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase text-gray-500">Productos en Sucursal</p>
                        <p class="text-2xl font-black text-gray-900 dark:text-white">{{ count($productBranchesCollection) }} <span class="text-xs font-normal text-gray-400">asignables</span></p>
                    </div>
                </div>
            </div>

            {{-- VISTA 1: ESTRUCTURA Y MAPA GRÁFICO DE LA RED DE IMPRESIÓN --}}
            <div x-show="viewMode === 'map'" x-transition class="rounded-2xl border border-gray-200 bg-slate-950 p-6 text-white shadow-xl dark:border-gray-800">
                <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-800 pb-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#C43B25]/20 text-[#FF4622]">
                            <i class="ri-node-tree text-2xl"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-bold text-white">Mapa de Topología y Estructura de Red</h3>
                            <p class="text-xs text-slate-400">Visualiza la comunicación entre el servidor, las computadoras (con QZ Tray) y las impresoras.</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-semibold text-emerald-400 border border-emerald-500/20">
                            <span class="h-2 w-2 rounded-full bg-emerald-400 animate-ping"></span> Red Activa
                        </span>
                    </div>
                </div>

                {{-- Diagrama Interactivo Nodal --}}
                <div class="relative overflow-x-auto py-4">
                    <div class="min-w-[900px]">

                        {{-- NODO CENTRAL: Servidor / Router Central --}}
                        <div class="mb-10 flex justify-center">
                            <div class="relative flex items-center gap-4 rounded-2xl border border-red-500/30 bg-slate-900/90 px-6 py-4 shadow-lg shadow-red-950/40">
                                <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-gradient-to-br from-[#FF4622] to-[#991B1B] text-white shadow-md">
                                    <i class="ri-server-line text-3xl"></i>
                                </div>
                                <div>
                                    <span class="text-[10px] font-bold tracking-widest text-[#FF4622] uppercase">Servidor / Gateway Central</span>
                                    <h4 class="text-lg font-black text-white">Red Local Sucursal</h4>
                                    <p class="text-xs text-slate-400">IP Gateway: <code class="text-emerald-400">192.168.1.1</code> · Puerto RAW 9100 / WebSocket QZ</p>
                                </div>
                            </div>
                        </div>

                        {{-- CONECTORES Y RAMAS: PCs (Izquierda) y Red Directa (Derecha) --}}
                        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                            {{-- SECCIÓN ESTACIONES PC (Con QZ Tray) --}}
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
                                <div class="mb-4 flex items-center justify-between border-b border-slate-800/80 pb-3">
                                    <h4 class="flex items-center gap-2 text-sm font-bold text-blue-400">
                                        <i class="ri-computer-line text-lg"></i> Estaciones PC Registradas (QZ Tray)
                                    </h4>
                                    <span class="rounded-md bg-blue-500/10 px-2 py-0.5 text-xs font-medium text-blue-300 border border-blue-500/20">
                                        {{ $stations->count() }} Computadoras
                                    </span>
                                </div>

                                <div class="space-y-6">
                                    @forelse($stations as $station)
                                        @php
                                            $stationPrinters = $station->printers ?? collect();
                                        @endphp
                                        <div class="relative rounded-xl border border-slate-800 bg-slate-900 p-4 transition hover:border-slate-700">
                                            {{-- Encabezado Estación PC --}}
                                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-800 pb-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600/20 text-blue-400 border border-blue-500/30">
                                                        <i class="ri-computer-line text-xl"></i>
                                                    </span>
                                                    <div>
                                                        <div class="flex items-center gap-2">
                                                            <h5 class="font-bold text-white">{{ $station->name }}</h5>
                                                            @if($station->location)
                                                                <span class="text-[10px] rounded bg-slate-800 px-1.5 py-0.5 text-slate-300">{{ $station->location }}</span>
                                                            @endif
                                                        </div>
                                                        <p class="text-xs text-slate-400">
                                                            IP: <code class="text-blue-300 font-mono">{{ $station->ip_address }}</code>
                                                            @if($station->hostname) · {{ $station->hostname }} @endif
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-semibold {{ $station->hasCredentials() ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-400 border border-amber-500/30' }}">
                                                        <i class="{{ $station->hasCredentials() ? 'ri-shield-check-line' : 'ri-alert-line' }}"></i>
                                                        {{ $station->hasCredentials() ? 'Certificado PEM' : 'Sin Certificado' }}
                                                    </span>

                                                    <button type="button" @click="setActiveStation('{{ $station->uuid }}', '{{ $station->name }}')"
                                                        :class="activeStationUuid === '{{ $station->uuid }}' ? 'bg-emerald-600 text-white' : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
                                                        class="rounded-lg px-2.5 py-1 text-xs font-bold transition">
                                                        <span x-text="activeStationUuid === '{{ $station->uuid }}' ? '✓ Esta PC' : 'Usar esta PC'"></span>
                                                    </button>
                                                </div>
                                            </div>

                                            {{-- Ticketeras USB asociadas a esta PC --}}
                                            <div class="mt-3 pl-4 border-l-2 border-dashed border-blue-500/40 space-y-2">
                                                <p class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Ticketeras conectadas por USB:</p>
                                                @forelse($stationPrinters as $printer)
                                                    <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-800/80 bg-slate-950/70 p-2.5">
                                                        <div class="flex items-center gap-2.5">
                                                            <span class="flex h-7 w-7 items-center justify-center rounded bg-orange-500/10 text-orange-400">
                                                                <i class="ri-usb-line text-sm"></i>
                                                            </span>
                                                            <div>
                                                                <p class="text-xs font-bold text-white">{{ $printer->name }}</p>
                                                                <p class="text-[10px] text-slate-400">Driver: <span class="text-slate-200 font-mono">{{ $printer->driver_name ?: $printer->name }}</span> · {{ $printer->width ?? '80' }}mm</p>
                                                            </div>
                                                        </div>

                                                        <div class="flex items-center gap-2">
                                                            {{-- Badge de Productos Asignados --}}
                                                            <button type="button" @click="openAssignModal({{ $printer->id }}, '{{ $printer->name }}')"
                                                                class="inline-flex h-7 items-center gap-1 rounded bg-purple-500/20 px-2 text-[11px] font-bold text-purple-300 hover:bg-purple-500/30 border border-purple-500/30">
                                                                <i class="ri-restaurant-2-line"></i>
                                                                <span x-text="(printerProductCounts[{{ $printer->id }}] || 0) + ' Productos'"></span>
                                                            </button>

                                                            <button type="button" @click="testPrinter({{ $printer->id }}, '{{ $printer->name }}')"
                                                                :disabled="testingPrinterId === {{ $printer->id }}"
                                                                class="inline-flex h-7 items-center gap-1.5 rounded bg-blue-600/80 px-2 text-[11px] font-bold text-white hover:bg-blue-500 disabled:opacity-50">
                                                                <i class="ri-printer-line"></i>
                                                                <span>Probar USB</span>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @empty
                                                    <p class="text-xs text-slate-500 italic py-1">No hay ticketeras USB asignadas a esta PC.</p>
                                                @endforelse
                                            </div>
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-slate-800 p-6 text-center text-slate-500">
                                            <i class="ri-computer-line text-3xl text-slate-600"></i>
                                            <p class="mt-2 text-sm">No hay estaciones PC registradas en esta sucursal.</p>
                                            <a href="{{ route('branch-parameter.index', $viewId ? ['view_id' => $viewId] : []) }}" class="mt-2 inline-block text-xs font-bold text-[#FF4622] hover:underline">+ Registrar PC en Parámetros</a>
                                        </div>
                                    @endforelse

                                    @if($unassignedUsbPrinters->count() > 0)
                                        <div class="rounded-xl border border-amber-500/30 bg-amber-500/5 p-3">
                                            <p class="text-xs font-bold text-amber-400 flex items-center gap-1.5 mb-2">
                                                <i class="ri-alert-line"></i> Ticketeras USB sin PC asignada ({{ $unassignedUsbPrinters->count() }}):
                                            </p>
                                            <div class="space-y-1.5">
                                                @foreach($unassignedUsbPrinters as $unPrinter)
                                                    <div class="flex items-center justify-between text-xs text-slate-300 bg-slate-900/80 p-2 rounded border border-slate-800">
                                                        <span>{{ $unPrinter->name }}</span>
                                                        <button type="button" @click="openAssignModal({{ $unPrinter->id }}, '{{ $unPrinter->name }}')" class="text-purple-400 font-bold hover:underline">
                                                            <span x-text="(printerProductCounts[{{ $unPrinter->id }}] || 0) + ' Prods.'"></span>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- SECCIÓN TICKETERAS DE RED LAN (IP Directa) --}}
                            <div class="rounded-2xl border border-slate-800 bg-slate-900/50 p-5">
                                <div class="mb-4 flex items-center justify-between border-b border-slate-800/80 pb-3">
                                    <h4 class="flex items-center gap-2 text-sm font-bold text-emerald-400">
                                        <i class="ri-router-line text-lg"></i> Ticketeras de Red LAN (IP Directa / Puerto 9100)
                                    </h4>
                                    <span class="rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-300 border border-emerald-500/20">
                                        {{ $networkPrinters->count() }} Impresoras LAN
                                    </span>
                                </div>

                                <div class="space-y-4">
                                    @forelse($networkPrinters as $netPrinter)
                                        <div class="relative rounded-xl border border-slate-800 bg-slate-900 p-4 transition hover:border-slate-700">
                                            <div class="flex flex-wrap items-center justify-between gap-3">
                                                <div class="flex items-center gap-3">
                                                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-600/20 text-emerald-400 border border-emerald-500/30">
                                                        <i class="ri-wifi-line text-xl"></i>
                                                    </span>
                                                    <div>
                                                        <h5 class="font-bold text-white">{{ $netPrinter->name }}</h5>
                                                        <p class="text-xs text-slate-400">
                                                            IP LAN: <code class="text-emerald-400 font-mono">{{ $netPrinter->ip ?: 'Sin IP' }}</code>
                                                            : <code class="text-slate-300">{{ $netPrinter->port ?? 9100 }}</code>
                                                            · {{ $netPrinter->width ?? '80' }}mm
                                                        </p>
                                                    </div>
                                                </div>

                                                <div class="flex items-center gap-2">
                                                    {{-- Badge de Productos Asignados --}}
                                                    <button type="button" @click="openAssignModal({{ $netPrinter->id }}, '{{ $netPrinter->name }}')"
                                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-purple-500/20 px-3 text-xs font-bold text-purple-300 hover:bg-purple-500/30 border border-purple-500/30">
                                                        <i class="ri-restaurant-2-line"></i>
                                                        <span x-text="(printerProductCounts[{{ $netPrinter->id }}] || 0) + ' Productos'"></span>
                                                    </button>

                                                    <button type="button" @click="testPrinter({{ $netPrinter->id }}, '{{ $netPrinter->name }}')"
                                                        :disabled="testingPrinterId === {{ $netPrinter->id }}"
                                                        class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-xs font-bold text-white hover:bg-emerald-500 disabled:opacity-50 shadow-sm">
                                                        <i class="ri-send-plane-line"></i>
                                                        <span>Probar LAN</span>
                                                    </button>
                                                </div>
                                            </div>

                                            @if($netPrinter->notes || $netPrinter->location)
                                                <div class="mt-2.5 pt-2 border-t border-slate-800/60 flex items-center gap-3 text-[11px] text-slate-400">
                                                    @if($netPrinter->location) <span><i class="ri-map-pin-line"></i> {{ $netPrinter->location }}</span> @endif
                                                    @if($netPrinter->notes) <span><i class="ri-file-text-line"></i> {{ $netPrinter->notes }}</span> @endif
                                                </div>
                                            @endif
                                        </div>
                                    @empty
                                        <div class="rounded-xl border border-dashed border-slate-800 p-6 text-center text-slate-500">
                                            <i class="ri-router-line text-3xl text-slate-600"></i>
                                            <p class="mt-2 text-sm">No hay ticketeras de red LAN con IP registradas.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            {{-- VISTA 2: TABLA TRADICIONAL DE LISTA DE IMPRESORAS --}}
            <div x-show="viewMode === 'table'" x-transition class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[900px]">
                    <thead style="background-color: #FF4622; color: #FFFFFF;">
                        <tr class="text-white">
                            <th class="px-5 py-3 text-center sm:px-6 first:rounded-tl-xl">
                                <p class="font-semibold text-white text-theme-xs uppercase">Nombre / Ticketera</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Conexión</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Estación PC / IP</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Productos Asignados</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Estado</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6 last:rounded-tr-xl">
                                <p class="font-semibold text-white text-theme-xs uppercase">Acciones</p>
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($printers as $printer)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <p class="font-bold text-gray-800 text-theme-sm dark:text-white/90">{{ $printer->name }}</p>
                                    @if($printer->driver_name && ($printer->connection_type ?? 'network') === 'usb')
                                        <p class="text-xs text-gray-400 font-mono">Driver: {{ $printer->driver_name }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    @if(($printer->connection_type ?? 'network') === 'usb')
                                        <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-500/10 dark:text-blue-400">
                                            <i class="ri-usb-line"></i> USB / PC
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400">
                                            <i class="ri-wifi-line"></i> Red LAN
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    @if(($printer->connection_type ?? 'network') === 'usb')
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-200">{{ $printer->station?->name ?? 'Sin PC' }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $printer->station?->ip_address ?? '-' }}</p>
                                    @else
                                        <p class="text-sm font-mono text-gray-800 dark:text-gray-200">{{ $printer->ip ?? '-' }}</p>
                                        <p class="text-xs text-gray-400">Puerto: {{ $printer->port ?? 9100 }}</p>
                                    @endif
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <button type="button" @click="openAssignModal({{ $printer->id }}, '{{ $printer->name }}')"
                                        class="inline-flex items-center gap-1 rounded-full bg-purple-50 px-3 py-1 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-500/10 dark:text-purple-400">
                                        <i class="ri-restaurant-2-line"></i>
                                        <span x-text="(printerProductCounts[{{ $printer->id }}] || 0) + ' Productos'"></span>
                                    </button>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $printer->status === 'E' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400' : 'bg-red-50 text-red-700' }}">
                                        {{ $printer->status === 'E' ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button" @click="testPrinter({{ $printer->id }}, '{{ $printer->name }}')"
                                            class="inline-flex h-9 items-center gap-1 rounded-xl bg-blue-50 px-3 text-xs font-bold text-blue-700 hover:bg-blue-100 dark:bg-blue-500/10 dark:text-blue-400">
                                            <i class="ri-printer-line"></i> Probar
                                        </button>

                                        <x-ui.link-button size="icon" variant="edit"
                                            href="{{ route('printers_branch.edit', ['printerBranch' => $printer->id] + ($viewId ? ['view_id' => $viewId] : [])) }}"
                                            className="rounded-xl"
                                            aria-label="Editar">
                                            <i class="ri-edit-line"></i>
                                        </x-ui.link-button>

                                        <form method="POST"
                                            action="{{ route('printers_branch.destroy', ['printerBranch' => $printer->id] + ($viewId ? ['view_id' => $viewId] : [])) }}"
                                            class="relative group js-swal-delete"
                                            data-swal-title="¿Eliminar ticketera?"
                                            data-swal-text="Se eliminará {{ $printer->name }}. Esta acción no se puede deshacer."
                                            data-swal-confirm="Sí, eliminar"
                                            data-swal-cancel="Cancelar"
                                            data-swal-confirm-color="#ef4444"
                                            data-swal-cancel-color="#6b7280">
                                            @csrf
                                            @method('DELETE')
                                            @if ($viewId)
                                                <input type="hidden" name="view_id" value="{{ $viewId }}">
                                            @endif
                                            <x-ui.button size="icon" variant="eliminate" type="submit" className="rounded-xl" aria-label="Eliminar">
                                                <i class="ri-delete-bin-line"></i>
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <i class="ri-inbox-line text-3xl"></i>
                                        <p>No hay impresoras registradas en esta sucursal.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500">
                    Mostrando
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $printers->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $printers->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $printers->total() }}</span>
                </div>
                <div>
                    {{ $printers->links() }}
                </div>
            </div>
        </x-common.component-card>

        {{-- MODAL RÁPIDO: ASIGNACIÓN MASIVA DE PRODUCTOS A TICKETERA --}}
        <div x-show="assignModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs">
            <div @click.outside="assignModalOpen = false" class="w-full max-w-3xl rounded-2xl border border-gray-200 bg-white p-6 shadow-2xl dark:border-gray-800 dark:bg-gray-900">
                <div class="mb-4 flex items-center justify-between border-b border-gray-100 pb-3 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50 text-purple-600 dark:bg-purple-500/10 dark:text-purple-400">
                            <i class="ri-restaurant-2-line text-2xl"></i>
                        </span>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white">Asignación Masiva de Productos</h3>
                            <p class="text-xs text-gray-500">Ticketera: <strong class="text-[#C43B25]" x-text="assignPrinterName"></strong></p>
                        </div>
                    </div>
                    <button type="button" @click="assignModalOpen = false" class="rounded-full p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-gray-800">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                {{-- Toolbar de Controles del Modal --}}
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative flex-1">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line text-sm"></i>
                        </span>
                        <input type="text" x-model="assignSearch" placeholder="Buscar producto por nombre..."
                            class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 pl-9 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    </div>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="selectAllAssign()" class="rounded-lg bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400">
                            <i class="ri-checkbox-circle-line"></i> Seleccionar Todos
                        </button>
                        <button type="button" @click="deselectAllAssign()" class="rounded-lg bg-gray-200 px-3 py-2 text-xs font-bold text-gray-700 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300">
                            <i class="ri-close-circle-line"></i> Desmarcar Todos
                        </button>
                    </div>
                </div>

                {{-- Lista de Productos agrupados por Categoría --}}
                <div class="max-h-96 overflow-y-auto space-y-4 rounded-xl border border-gray-200 bg-gray-50/50 p-4 dark:border-gray-800 dark:bg-gray-900/50">
                    @forelse($groupedProducts as $categoryName => $pItems)
                        @php
                            $catIds = $pItems->pluck('id')->map(fn($id) => (int)$id)->values()->all();
                        @endphp
                        <div x-data="{
                            categoryName: @js($categoryName),
                            catIds: @js($catIds),
                            matchesAssignSearch(name) {
                                if (!assignSearch.trim()) return true;
                                return name.toLowerCase().includes(assignSearch.toLowerCase());
                            }
                        }" class="space-y-2">
                            <div class="flex items-center justify-between rounded-lg bg-white px-3 py-2 shadow-xs dark:bg-gray-800">
                                <span class="text-xs font-bold uppercase text-gray-800 dark:text-gray-200">
                                    {{ $categoryName }} ({{ count($pItems) }})
                                </span>
                                <button type="button" @click="toggleCategoryAssign(catIds)" class="text-xs font-bold text-blue-600 hover:underline dark:text-blue-400">
                                    <span x-text="isCategorySelectedAssign(catIds) ? 'Desmarcar Categoría' : 'Marcar Categoría'"></span>
                                </button>
                            </div>

                            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                @foreach($pItems as $pb)
                                    @php
                                        $pbId = (int) $pb->id;
                                        $pName = $pb->product?->name ?? 'Producto ID ' . $pb->id;
                                    @endphp
                                    <label x-show="matchesAssignSearch(@js($pName))"
                                        class="flex items-center gap-2.5 rounded-lg border border-gray-200 bg-white p-2.5 text-xs hover:border-[#C43B25] cursor-pointer dark:border-gray-800 dark:bg-gray-900 dark:hover:border-[#C43B25]">
                                        <input type="checkbox" value="{{ $pbId }}" x-model.number="assignSelectedProducts"
                                            class="rounded border-gray-300 text-[#C43B25] focus:ring-[#C43B25]">
                                        <span class="truncate font-medium text-gray-800 dark:text-gray-200">{{ $pName }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="p-4 text-center text-xs text-gray-400">No hay productos disponibles en esta sucursal.</p>
                    @endforelse
                </div>

                {{-- Footer con Conteo y Guardado --}}
                <div class="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 dark:border-gray-800">
                    <span class="text-xs font-bold text-gray-600 dark:text-gray-400">
                        <span class="text-[#C43B25]" x-text="assignSelectedProducts.length"></span> de {{ count($productBranchesCollection) }} productos seleccionados
                    </span>

                    <div class="flex items-center gap-2">
                        <button type="button" @click="assignModalOpen = false" class="rounded-xl border border-gray-300 px-4 py-2 text-xs font-bold text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300">
                            Cancelar
                        </button>
                        <button type="button" @click="saveAssignedProducts()" :disabled="assignSaving"
                            class="inline-flex items-center gap-2 rounded-xl bg-[#C43B25] px-5 py-2 text-xs font-bold text-white hover:bg-[#a8301d] disabled:opacity-50">
                            <i class="ri-save-line"></i>
                            <span x-text="assignSaving ? 'Guardando...' : 'Guardar Asignación'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal: Crear impresora/ticketera --}}
    @php
        $sessionBranchId = session('branch_id');
    @endphp

    <x-ui.modal x-data="{ open: false }" @open-create-printer-modal.window="open = true"
        @close-create-printer-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="w-full max-w-2xl">
        <div x-show="open" x-cloak class="flex w-full flex-col min-h-0 p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-[#FF4622]/10 text-[#FF4622] dark:bg-[#FF4622]/20">
                        <i class="ri-printer-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Nueva ticketera</h3>
                        <p class="mt-1 text-sm text-gray-500">Registra una ticketera y asigna sus productos.</p>
                    </div>
                </div>
                <button type="button" @click="open = false"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                    aria-label="Cerrar">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            @if (!$sessionBranchId)
                <x-ui.alert variant="error" title="No hay sucursal activa"
                    message="Selecciona una sucursal antes de registrar ticketeras." />
            @endif

            <form method="POST" action="{{ route('printers_branch.store', $viewId ? ['view_id' => $viewId] : []) }}"
                class="mt-5 flex w-full flex-col min-h-0 space-y-5">
                @csrf

                @if ($viewId)
                    <input type="hidden" name="view_id" value="{{ $viewId }}">
                @endif

                <input type="hidden" name="branch_id" value="{{ $sessionBranchId }}">
                <input type="hidden" name="status" value="E">

                @include('printers_branch._form', ['printer' => null])

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-ui.button type="submit" size="md" variant="primary" :disabled="!$sessionBranchId">
                        <i class="ri-save-line"></i>
                        <span>Guardar</span>
                    </x-ui.button>
                    <x-ui.button type="button" size="md" variant="outline" @click="open = false">
                        <i class="ri-close-line"></i>
                        <span>Cancelar</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
@endsection
