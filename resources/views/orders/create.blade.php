@extends('layouts.app')

@push('head')
    <meta name="qz-sign-url" content="{{ route('qz.sign') }}">
    <meta name="qz-certificate-url" content="{{ route('qz.certificate') }}">
@endpush

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-col h-full bg-gray-50 dark:bg-gray-950">
        @php
            $serverTableData = [
                'id' => $table->id,
                'table_id' => $table->id,
                'area_id' => $table->area_id ?? ($area->id ?? null),
                'name' => $table->name ?? $table->id,
                'waiter' => $user?->name ?? 'Sin asignar',
                'person_id' => $pendingClientId ?? null,
                'clientName' => $pendingClientName ?? ($person?->name ?? 'Sin cliente'),
                'status' => $table->situation ?? 'libre',
                'items' => [],
                'people_count' => (int) ($pendingPeopleCount ?? ($table->capacity ?? 1)),
                'service_type' => $pendingServiceType ?? 'IN_SITU',
                'delivery_address' => $pendingDeliveryAddress ?? '',
                'contact_phone' => $pendingContactPhone ?? '',
                'delivery_amount' => (float) ($pendingDeliveryAmount ?? 0),
                'takeaway_disposable_charge' => ((float) ($pendingTakeawayDisposableAmount ?? 0)) > 0,
                'takeaway_disposable_amount' => (float) ($pendingTakeawayDisposableAmount ?? 0),
                'original_area_id' => $area->id ?? null,
                'original_area_name' => $area->name ?? 'Sin área',
                'delivery_area_id' => $deliveryAreaId ?? null,
            ];
        @endphp
        <script>
            (function () {
                const serverTable = @json($serverTableData);
                const startFresh = @json($startFresh ?? false);
                const serverOrderMovementId = @json($pendingOrderMovementId ?? null);
                const serverMovementId = @json($pendingMovementId ?? null);
                const serverPendingItems = @json($pendingItems ?? []);
                let db = JSON.parse(localStorage.getItem('restaurantDB')) || {};
                let activeKey = `table-{{ $table->id }}`;
                const tableIsFree = (serverTable.status || '').toLowerCase() === 'libre';
                const useFreshOrder = startFresh || tableIsFree;
                if (useFreshOrder && db[activeKey]) { delete db[activeKey]; localStorage.setItem('restaurantDB', JSON.stringify(db)); }
                let currentTable = (useFreshOrder || !db[activeKey]) ? serverTable : db[activeKey];
                if (!currentTable.people_count) currentTable.people_count = {{ $pendingPeopleCount ?? 1 }};
                if (serverOrderMovementId) {
                    currentTable = { ...currentTable, ...serverTable };
                    currentTable.order_movement_id = serverOrderMovementId;
                    currentTable.movement_id = serverMovementId;
                    currentTable.items = Array.isArray(serverPendingItems) ? serverPendingItems : [];
                    currentTable.items.forEach(it => {
                        it.savedQty = parseFloat(it.qty) ?? parseFloat(it.quantity) ?? 0;
                        if (it.takeawayQty == null || isNaN(parseFloat(it.takeawayQty))) it.takeawayQty = 0;
                        const q = parseFloat(it.qty) || 0;
                        let t = parseFloat(it.takeawayQty) || 0;
                        if (t > q) it.takeawayQty = q;
                    });
                    db[activeKey] = currentTable;
                    localStorage.setItem('restaurantDB', JSON.stringify(db));
                } else {
                    currentTable.order_movement_id = null;
                    currentTable.movement_id = null;
                    currentTable.items = currentTable.items || [];
                    (currentTable.items || []).forEach(it => {
                        if (it.takeawayQty == null || isNaN(parseFloat(it.takeawayQty))) it.takeawayQty = 0;
                        const q = parseFloat(it.qty) || 0;
                        let t = parseFloat(it.takeawayQty) || 0;
                        if (t > q) it.takeawayQty = q;
                    });
                }
                currentTable.cancellations = currentTable.cancellations || [];
                if (currentTable.takeaway_disposable_charge === undefined) {
                    currentTable.takeaway_disposable_charge = !!serverTable.takeaway_disposable_charge;
                }
                if (currentTable.takeaway_disposable_amount == null || isNaN(parseFloat(currentTable.takeaway_disposable_amount))) {
                    currentTable.takeaway_disposable_amount = parseFloat(serverTable.takeaway_disposable_amount) || 0;
                }
                window.currentTable = currentTable;
                window.serverTable = serverTable;
                window.activeKey = activeKey;
                window.db = db;
            })();
        </script>
        <header
            class="flex bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 min-h-[4rem] sm:h-18 flex flex-wrap sm:flex-nowrap items-center gap-2 sm:gap-3 px-3 sm:px-6 py-2 backdrop-blur-md z-50 sticky top-0 shadow-sm overflow-visible">
            <div class="order-1 flex shrink-0 items-center gap-2 sm:gap-3">
                <button onclick="goBack()" title="Volver atrás"
                    class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300 transition-all flex items-center justify-center shadow-sm shrink-0">
                    <i class="ri-arrow-left-line text-lg sm:text-xl"></i>
                </button>
                <div class="flex flex-col justify-center min-w-0 max-w-[40vw] sm:max-w-none">
                    <h2 class="text-sm sm:text-base font-bold text-slate-800 dark:text-white leading-tight truncate">
                        Mesa <span id="pos-table-name">{{ $table->name ?? $table->id }}</span>
                    </h2>
                    <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate"><i
                            class="ri-circle-fill" style="color: #00C950;"></i> <span
                            id="pos-table-area">{{ $table->area->name ?? 'Sin área' }}</span></p>
                </div>
            </div>

            {{-- Una sola franja horizontal con scroll (móvil/tablet): todo el toolbar junto --}}
            <div
                class="order-3 basis-full w-full min-w-0 flex items-center overflow-x-auto overflow-y-hidden overscroll-x-contain touch-pan-x [-webkit-overflow-scrolling:touch] [scrollbar-width:thin] pb-0.5 sm:order-2 sm:basis-auto sm:w-auto sm:flex-1">
                <div
                    class="flex w-max min-h-full items-center gap-3 sm:gap-4 lg:gap-5 text-sm font-medium pr-1 shrink-0">
                    <!-- Buscador -->
                    <div class="flex items-center gap-1.5 shrink-0 bg-white p-1 rounded-xl border border-gray-200 shadow-sm">
                        <div class="w-28 sm:w-36 md:w-44 xl:w-56 relative">
                            <input type="text" id="search-products" placeholder="Buscar producto..." autocomplete="off"
                                class="w-full pl-8 pr-3 py-1.5 text-xs sm:text-sm bg-transparent border-transparent rounded-lg focus:ring-0 focus:border-transparent outline-none">
                            <i
                                class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <x-ui.button size="xs" variant="outline" onclick="clearProductSearch()" class="!px-2 h-7"
                            id="search-products-clear">
                            <i class="ri-close-line"></i>
                        </x-ui.button>
                    </div>

                    <!-- Mozo -->
                    @if(!($isMozo ?? false))
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm">Mozo:</span>
                            <select id="header-waiter-select" onchange="changeWaiter(this)"
                                class="w-24 sm:w-32 py-1.5 px-2 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 outline-none shadow-sm truncate">
                                <option value="{{ $user?->id }}" selected><span
                                        id="pos-waiter-name">{{ $user?->name ?? 'Sin asignar' }}</span></option>
                            </select>
                        </div>
                    @endif

                    <!-- Servicio (automático por área) -->
                    <input type="hidden" id="header-service-type-val" value="{{ $pendingServiceType ?? 'IN_SITU' }}">

                </div>
            </div>

            <div class="order-2 w-full flex items-center gap-2 sm:w-auto sm:ml-auto sm:flex-nowrap sm:gap-4 lg:gap-5 sm:order-3 sm:pl-2">
                <!-- Cliente -->
                <div class="flex items-center gap-1.5 flex-1 min-w-0 sm:flex-none sm:shrink-0">
                    <span class="hidden sm:inline text-gray-500 dark:text-gray-400 text-xs sm:text-sm whitespace-nowrap">Cliente:</span>
                    <div class="flex items-center gap-1 flex-1 min-w-0 sm:flex-none">
                        @php
                            $peopleCollection = $people ?? collect();
                            $clientOptions = $peopleCollection->map(function ($p) {
                                $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                                if ($name === '' && !empty($p->document_number)) {
                                    $name = $p->document_number;
                                }
                                return [
                                    'id' => $p->id,
                                    'description' => $name,
                                ];
                            })->values()->all();
                        @endphp
                        <script>window.__orderClientOptions = @json($clientOptions); </script>
                        <div class="flex items-center gap-1 flex-1 min-w-0 sm:flex-none sm:shrink-0"
                            id="order-client-picker"
                            x-data="{
                                clientId: @json($pendingClientId ?? null),
                                init() {
                                    if (window.currentTable?.person_id) {
                                        this.clientId = window.currentTable.person_id;
                                    }
                                    this.$watch('clientId', v => {
                                        const opts = window.__orderClientOptions || [];
                                        const sel = opts.find(o => String(o.id) === String(v));
                                        const name = sel ? sel.description : 'Público General';
                                        if (window.currentTable) {
                                            window.currentTable.person_id = v ? parseInt(v, 10) : null;
                                            window.currentTable.clientName = name;
                                            if (typeof saveDB === 'function') saveDB();
                                        }
                                        const ci = document.getElementById('cobro-client-input');
                                        if (ci) ci.value = name;
                                    });
                                }
                            }">
                            <x-form.select.combobox :options="$clientOptions" x-model="clientId"
                                name="header_client_id" placeholder="Cliente..."
                                :compact="true"
                                input-id="order_client_search"
                                class="w-full sm:w-36 lg:w-44" />
                        </div>
                        <button type="button"
                            class="inline-flex shrink-0 items-center justify-center h-8 w-8 rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300 shadow-sm transition-colors"
                            @click="$dispatch('open-person-modal')" title="Nuevo cliente">
                            <i class="ri-user-add-line text-sm sm:text-base"></i>
                        </button>
                    </div>
                </div>

                <div class="hidden sm:block h-6 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                <!-- Personas -->
                <div id="diners-section" class="flex items-center gap-1.5 shrink-0">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm leading-none">Personas</span>
                        <span class="text-[9px] sm:text-[10px] text-gray-400">(máx. {{ $table->capacity ?? 1 }})</span>
                    </div>
                    <div class="flex items-center gap-0.5 p-0.5 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm">
                        <button type="button" onclick="updateDiners(-1)"
                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-50 hover:bg-blue-100 text-slate-600 hover:text-blue-600 transition-colors">
                            <i class="ri-subtract-line text-xs"></i>
                        </button>
                        <input type="number" id="diners-input" value="{{ $pendingOrderMovement?->people_count ?? 1 }}"
                            min="1" onchange="updateDiners(0)"
                            class="w-8 py-1 text-center text-xs sm:text-sm bg-transparent border-none text-slate-700 font-bold focus:ring-0 p-0 m-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                        <button type="button" onclick="updateDiners(1)"
                            class="w-7 h-7 flex items-center justify-center rounded-md bg-gray-50 hover:bg-blue-100 text-slate-600 hover:text-blue-600 transition-colors">
                            <i class="ri-add-line text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        {{-- &lt; lg: columna (tablet/móvil) + Resumen a ancho completo. lg+: productos + aside fijo al costado (laptop). --}}
        <div class="flex-1 flex flex-col lg:flex-row min-h-0 overflow-hidden bg-gray-50/50 dark:bg-gray-950/50 gap-3 p-3">
            <div
                class="flex-1 min-w-0 min-h-[320px] lg:min-h-0 lg:overflow-hidden p-3 sm:p-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col min-h-0">
                <div class="flex flex-col flex-1 min-h-0 min-w-0 overflow-hidden">
                    <div class="shrink-0 border-gray-300 px-2 sm:px-4 pt-3 pb-4">
                        <div class="flex items-center justify-between">
                        </div>
                        <div id="categories-grid"
                            class="flex flex-row flex-wrap gap-1.5 sm:gap-2 overflow-x-auto pb-3 overscroll-x-contain">
                        </div>
                    </div>
                    <div class="flex-1 overflow-y-auto pt-2 sm:pt-3">
                        <div id="products-grid"
                            class="px-2 sm:px-4 md:px-5 p-3 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-2 sm:gap-4 content-start pb-6">
                        </div>
                    </div>
                </div>
            </div>
            <aside
            class="lg:w-[450px] w-full md:w-[350px] lg:shrink-0 mx-auto lg:mx-0 flex-none bg-white dark:bg-gray-900 border-t lg:border-t-0 lg:border-l border-gray-200 dark:border-gray-800 flex flex-col min-h-0 lg:h-full rounded-2xl shadow-sm"
            >
            {{-- Tabs Resumen | Cobro (Cobro oculto para Mozo) --}}
            <div class="flex w-full shrink-0 border-b border-gray-200 dark:border-gray-700">
                <button type="button" id="tab-resumen" onclick="switchAsideTab('resumen')"
                        class="flex-1 py-3 px-4 text-sm font-bold transition-colors rounded-tl-2xl bg-brand-500 text-white">
                        Resumen
                    </button>
                    @if($canCharge ?? true)
                        <button type="button" id="tab-cobro" onclick="switchAsideTab('cobro')"
                            class="flex-1 py-3 px-4 text-sm font-bold transition-colors bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 hover:text-orange-600 dark:hover:text-orange-400">
                            Cobro
                        </button>
                    @endif
            </div>

                {{-- Contenido Resumen --}}
                <div id="aside-resumen" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                    {{-- Datos Delivery --}}
                    <div id="delivery-info-container"
                        class="hidden p-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800 space-y-2 overflow-hidden">
                        <div class="flex flex-col gap-2">
                            <div class="flex-1 min-w-0">
                                <label
                                    class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Dirección
                                    de Entrega</label>
                                <input type="text" id="delivery-address" oninput="updateDeliveryInfo()"
                                    placeholder="Av. Siempre Viva 123"
                                    class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                            </div>
                            <div class="flex gap-2">
                                <div class="flex-1 min-w-0">
                                    <label
                                        class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Teléfono
                                        Contacto</label>
                                    <input type="text" id="delivery-phone" oninput="updateDeliveryInfo()"
                                        placeholder="999..."
                                        class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                                </div>
                                <div class="w-24">
                                    <label
                                        class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Costo
                                        Delivery</label>
                                    <input type="number" step="0.5" id="delivery-amount" oninput="updateDeliveryInfo()"
                                        placeholder="0.00"
                                        class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Datos Para Llevar --}}
                    <div id="takeaway-info-container"
                        class="hidden p-3 bg-orange-50 dark:bg-orange-900/20 border-b border-orange-100 dark:border-orange-800 space-y-2">
                        <div class="flex flex-col gap-2">
                            <div class="flex gap-2">
                                <div class="flex-1 min-w-0">
                                    <label
                                        class="block text-[10px] font-bold uppercase text-orange-600 dark:text-orange-400 mb-1">Nombre
                                        de Retiro (Opcional)</label>
                                    <input type="text" id="takeaway-client-name" oninput="updateTakeAwayInfo()"
                                        placeholder="Quien recoge..."
                                        class="w-full py-1.5 px-2 text-xs rounded border border-orange-200 focus:ring-1 focus:ring-orange-400 outline-none">
                                </div>
                                <div class="w-28">
                                    <label
                                        class="block text-[10px] font-bold uppercase text-orange-600 dark:text-orange-400 mb-1">Hora
                                        Retiro</label>
                                    <input type="time" id="takeaway-time" oninput="updateTakeAwayInfo()"
                                        class="w-full py-1.5 px-2 text-xs rounded border border-orange-200 focus:ring-1 focus:ring-orange-400 outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Descartables (para llevar): opcional, suma al total --}}
                    <div id="takeaway-disposable-panel"
                        class="hidden shrink-0 p-3 bg-amber-50/80 dark:bg-amber-950/25 border-b border-amber-100 dark:border-amber-900/50 space-y-2">
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <input type="checkbox" id="takeaway-disposable-charge"
                                class="rounded border-amber-300 text-orange-600 focus:ring-orange-500"
                                onchange="updateTakeawayDisposableInfo()">
                            <span class="text-[11px] font-bold uppercase text-amber-800 dark:text-amber-200">Cobrar
                                descartables (llevar)</span>
                        </label>
                        <div class="flex items-end gap-2">
                            <div class="flex-1 min-w-0">
                                <label for="takeaway-disposable-amount"
                                    class="block text-[10px] font-bold uppercase text-amber-700 dark:text-amber-300 mb-1">Monto
                                    descartables (S/)</label>
                                <input type="number" step="0.01" min="0" id="takeaway-disposable-amount"
                                    disabled
                                    oninput="updateTakeawayDisposableInfo(false)"
                                    onblur="updateTakeawayDisposableInfo(true)"
                                    placeholder="0.00"
                                    class="w-full py-1.5 px-2 text-xs rounded border border-amber-200 dark:border-amber-800 bg-white dark:bg-zinc-900 focus:ring-1 focus:ring-amber-400 outline-none disabled:opacity-50">
                            </div>
                        </div>
                    </div>

                    <div id="cart-container"
                        class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2 sm:space-y-3 bg-white dark:bg-gray-900 min-h-0 overscroll-contain"
                        style="-webkit-overflow-scrolling: touch;"></div>
                    <div id="cancelled-platos-container"
                        class="shrink-0 hidden border-t border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20 p-3 sm:p-4 max-h-40 overflow-y-auto">
                        <p class="text-xs font-semibold text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-1"><i
                                class="ri-error-warning-line"></i> Platos anulados</p>
                        <div id="cancelled-platos-list" class="space-y-1.5 text-xs"></div>
                    </div>
                    <div class="shrink-0 p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700">
                        <div class="space-y-2 sm:space-y-3 text-xs sm:text-sm">
                            <div class="flex justify-between text-gray-500 font-medium">
                                <span>Subtotal</span>
                                <span class="text-slate-700 dark:text-slate-300" id="ticket-subtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between text-gray-500 font-medium">
                                <span>Impuestos</span>
                                <span class="text-slate-700 dark:text-slate-300" id="ticket-tax">$0.00</span>
                            </div>
                            <div id="ticket-delivery-row"
                                class="hidden flex justify-between text-gray-500 font-medium">
                                <span>Delivery</span>
                                <span class="text-slate-700 dark:text-slate-300" id="ticket-delivery">$0.00</span>
                            </div>
                            <div id="ticket-takeaway-disposable-row"
                                class="hidden flex justify-between text-gray-500 font-medium">
                                <span>Descartables (llevar)</span>
                                <span class="text-slate-700 dark:text-slate-300" id="ticket-takeaway-disposable">$0.00</span>
                            </div>
                            <div class="border-t border-dashed border-gray-300 dark:border-gray-600 my-2"></div>
                            <div class="flex justify-between items-center">
                                <span class="text-base sm:text-lg font-bold text-slate-800 dark:text-white">Total a
                                    Pagar</span>
                                <span class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400"
                                    id="ticket-total">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Contenido Cobro (oculto para Mozo) --}}
                @if($canCharge ?? true)
                    <div id="aside-cobro" class="hidden flex-col flex-1 min-h-0 overflow-y-auto p-4 sm:p-5">
                        <div class="space-y-4">
                            <div>
                                <label
                                    class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Cliente</label>
                                <div class="relative">
                                    <input type="text" id="cobro-client-input" readonly
                                        value="{{ $person?->name ?? 'Público General' }}"
                                        class="w-full pl-3 pr-8 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                    <button type="button" onclick="clearCobroClient()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <i class="ri-close-line text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Documento</label>
                                    <select id="cobro-document-type"
                                        class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($documentTypes ?? []) as $dt)
                                            <option value="{{ optional($dt)->id }}">{{ optional($dt)->name ?? '' }}</option>
                                        @empty
                                            <option value="">Sin documentos</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Caja</label>
                                    <select id="cobro-cash-register"
                                        class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($cashRegisters ?? []) as $cr)
                                            <option value="{{ optional($cr)->id }}">
                                                {{ optional($cr)->number ?? 'Caja ' . optional($cr)->id }}</option>
                                        @empty
                                            <option value="">Sin cajas</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label
                                        class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Métodos
                                        de pago</label>
                                    <button type="button" onclick="addCobroPaymentMethod()"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 active:scale-95 transition-colors shrink-0">
                                        <i class="ri-add-line text-sm"></i> Agregar
                                    </button>
                                </div>
                                <div id="cobro-payment-methods-list" class="space-y-3 max-h-48 overflow-y-auto pr-1"></div>
                                <div
                                    class="mt-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800/80 px-3 py-2.5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total pagado</span>
                                        <span class="text-base font-bold text-slate-800 dark:text-white tabular-nums"
                                            id="cobro-total-paid">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Botones Guardar / Cobrar: visibles según pestaña activa --}}
                <div class="shrink-0 p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                    {{-- Footer Resumen: solo Guardar y Precuenta --}}
                    <div id="footer-resumen" class="flex justify-between">
                        <x-ui.button id="btn-precuenta" type="button" variant="secondary" size="sm">
                            <i class="ri-save-line text-base"></i>
                            <span>Precuenta</span>
                        </x-ui.button>
                        <button type="button" id="btn-guardar" onclick="processOrder()"
                            class="py-2.5 px-4 rounded-xl bg-gray-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-gray-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                            <i class="ri-save-line text-base"></i>
                            <span>Guardar</span>
                        </button>
                    </div>
                    {{-- Footer Cobro: solo Cobrar (oculto para Mozo) --}}
                    @if($canCharge ?? true)
                        <div id="footer-cobro" class="hidden justify-end">
                            <button type="button" onclick="processOrderPayment()"
                                class="py-2.5 px-4 rounded-xl bg-brand-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-brand-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                                <i class="ri-bank-card-line text-base"></i>
                                <span>Cobrar</span>
                            </button>
                        </div>
                    @endif
                </div>
            </aside>
        
    </div>

    {{-- Modal para crear/editar cliente rápido --}}
    <x-ui.modal x-data="{ open: false }" @open-person-modal.window="open = true" @close-person-modal.window="open = false"
        :isOpen="false" :showCloseButton="false" class="max-w-4xl z-[100]">
        <div class="p-6 sm:p-8 bg-white dark:bg-gray-800">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                        <i class="ri-user-add-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Registrar / Editar Cliente</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ingresa DNI y nombre de la persona.</p>
                    </div>
                </div>
                <button type="button" @click="open = false"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>

            <form method="POST"
                action="{{ route('admin.companies.branches.people.store', [$branch->company_id ?? '0', $branch->id ?? '0']) }}"
                class="space-y-6">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                <input type="hidden" name="location_id" value="{{ $branch->location_id ?? '' }}">
                <input type="hidden" name="from_pos" value="1">
                <input type="hidden" name="document_number" value="00000000">
                @include('branches.people._form', ['person' => null, 'hidePinAndRoles' => true])

                <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                    <button type="button" @click="open = false"
                        class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                        <i class="ri-save-line mr-1"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <div id="notification"
        class="fixed top-24 right-8 z-50 max-w-sm opacity-0 pointer-events-none transition-opacity duration-300"
        aria-live="polite"></div>

    {{-- Toast: producto agregado (igual que en ventas) --}}
    <div id="toast-container"
        class="fixed top-20 left-1/2 -translate-x-1/2 z-50 pointer-events-none flex flex-col gap-2 w-auto max-w-sm">
        <div id="add-to-cart-notification"
            class="transform transition-all duration-300 -translate-y-10 opacity-0 pointer-events-none bg-slate-800 text-white shadow-2xl rounded-full px-6 py-3 flex items-center gap-3 min-w-[200px]">
            <i class="ri-check-line text-green-400 text-xl"></i>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400">Agregado</p>
                <p id="notification-product-name" class="text-sm font-bold text-white truncate max-w-[180px]">Producto</p>
            </div>
        </div>
    </div>

    {{-- removeQuantityModal debe existir en window antes de que Alpine procese el modal --}}
    <script>
        window.removeQuantityModal = function () {
            return {
                open: false,
                indexToRemove: null,
                quantityToRemove: 1,
                maxQty: 1,
                productName: '',
                reasonToRemove: '',
                isComandado: false
            };
        };
    </script>
    {{-- Modal eliminar por cantidad (se abre al presionar la basurita en una línea del pedido) --}}
    <x-ui.modal x-data="removeQuantityModal()" @open-remove-quantity-modal.window="
                    if ($event.detail && $event.detail.maxQty >= 1) {
                        open = true;
                        indexToRemove = $event.detail.index ?? null;
                        maxQty = Math.max(1, $event.detail.maxQty ?? 1);
                        productName = $event.detail.productName || 'Producto';
                        quantityToRemove = 1;
                        reasonToRemove = '';
                        isComandado = !!$event.detail.isComandado;
                        $nextTick(() => { if ($refs.qtyInput) $refs.qtyInput.value = 1; });
                    }
                "
        @close-remove-quantity-modal.window="open = false; indexToRemove = null; quantityToRemove = 1; maxQty = 1; productName = ''; reasonToRemove = ''; isComandado = false"
        :showCloseButton="false" class="max-w-4xl z-[100]">
        <div class="p-6 sm:p-8 bg-white dark:bg-gray-800">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        <i class="ri-delete-bin-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Eliminar cantidad del pedido</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5"
                            x-text="productName ? (productName + ' · Cantidad actual: ' + maxQty) : ''"></p>
                    </div>
                </div>
                <button type="button" @click="$dispatch('close-remove-quantity-modal')"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad a eliminar</label>
                <input type="number" x-ref="qtyInput" @input="
                                let val = $event.target.value;
                                if (val === '' || val === null) { return; }
                                let v = parseInt(val, 10);
                                quantityToRemove = (isNaN(v) || v < 1) ? 1 : Math.min(maxQty, Math.max(1, v));
                                $event.target.value = quantityToRemove;
                            " @blur="
                                if ($event.target.value === '' || isNaN(parseInt($event.target.value, 10))) {
                                    quantityToRemove = 1;
                                    $event.target.value = 1;
                                }
                            " min="1" :max="maxQty"
                    class="w-24 text-center text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1"
                    x-text="'Entre 1 y ' + maxQty + (maxQty === 1 ? ' unidad' : ' unidades')"></p>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2" x-show="maxQty >= 1">
                    Quedarán: <span x-text="Math.max(0, maxQty - quantityToRemove)"></span> <span
                        x-text="(maxQty - quantityToRemove) === 1 ? 'unidad' : 'unidades'"></span>
                </p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Razón <span x-show="isComandado" class="text-red-600">(requerida)</span>
                </label>
                <textarea x-model="reasonToRemove" rows="2" placeholder="Ej: pedido equivocado, cliente canceló..."
                    class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="button" @click="$dispatch('close-remove-quantity-modal')"
                    class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
                <button type="button" @click="
                                if (indexToRemove != null && quantityToRemove >= 1 && (!isComandado || reasonToRemove.trim())) {
                                    var q = Math.min(quantityToRemove, maxQty);
                                    window.applyRemoveQuantity(indexToRemove, q, reasonToRemove.trim());
                                    $dispatch('close-remove-quantity-modal');
                                }
                            " :disabled="isComandado && !reasonToRemove.trim()"
                    :class="isComandado && !reasonToRemove.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Eliminar <span x-text="quantityToRemove > 1 ? quantityToRemove + ' unidades' : '1 unidad'"></span>
                </button>
            </div>
        </div>
    </x-ui.modal>
    <style>
        .notification-show {
            transform: translateY(0) !important;
            opacity: 1 !important;
        }
    </style>

    <script>
        document.addEventListener('alpine:init', function () {
            window.Alpine.data('removeQuantityModal', function () {
                return {
                    open: false,
                    indexToRemove: null,
                    quantityToRemove: 1,
                    maxQty: 1,
                    productName: '',
                    reasonToRemove: '',
                    isComandado: false
                };
            });
        });
    </script>
    <script>
        (function () {
            const serverPendingCancelledDetails = @json($pendingCancelledDetails ?? []);
            const serverOrderMovementId = @json($pendingOrderMovementId ?? null);
            const serverMovementId = @json($pendingMovementId ?? null);
            const waiterPinEnabled = @json($waiterPinEnabled ?? false);
            const validateWaiterPinUrl = @json(route('orders.validateWaiterPin'));
            const waiterPinBranchId = @json((int) session('branch_id'));
            const cobroPaymentMethods = @json($paymentMethods ?? []);
            const cobroPaymentGateways = @json($paymentGateways ?? []);
            const cobroCards = @json($cards ?? []);
            const cobroDigitalWallets = @json($digitalWallets ?? []);
            const cobroBanks = @json($banks ?? []);
            const salesThermalPrintUrl = @json(route('sales.print.ticket.thermal'));

            let autoSaveTimer = null;

            function getStoredWaiter() {
                try {
                    const key = `waiterPin:${waiterPinBranchId}`;
                    const raw = sessionStorage.getItem(key);
                    if (!raw) return null;
                    const data = JSON.parse(raw);
                    if (!data || !data.person_id) return null;
                    const ts = Number(data.ts || 0);
                    if (!ts || (Date.now() - ts) > (12 * 60 * 60 * 1000)) {
                        sessionStorage.removeItem(key);
                        return null;
                    }
                    return data;
                } catch (e) {
                    return null;
                }
            }

            async function ensureWaiterPin() {
                if (!waiterPinEnabled) return true;
                const existing = getStoredWaiter();
                if (existing) {
                    // Mostrar mozo real en la UI
                    currentTable.waiter = existing.name || currentTable.waiter;
                    const el = document.getElementById('pos-waiter-name');
                    if (el && existing.name) el.innerText = existing.name;
                    return true;
                }
                if (!window.Swal) return false;

                while (true) {
                    const result = await Swal.fire({
                        title: 'PIN de mozo',
                        input: 'password',
                        inputLabel: 'Ingrese su PIN para tomar pedidos',
                        inputPlaceholder: 'PIN',
                        inputAttributes: { autocomplete: 'off' },
                        showCancelButton: true,
                        confirmButtonText: 'Ingresar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        inputValidator: (value) => {
                            if (!value || !String(value).trim()) {
                                return 'Ingrese el PIN.';
                            }
                            return null;
                        }
                    });
                    if (!result.isConfirmed) {
                        return false;
                    }
                    try {
                        const pin = String(result.value || '').trim();
                        const res = await fetch(validateWaiterPinUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ pin })
                        });
                        const data = await res.json().catch(() => null);
                        if (data && data.success && data.waiter && data.waiter.person_id) {
                            const key = `waiterPin:${waiterPinBranchId}`;
                            const payload = { ...data.waiter, ts: Date.now() };
                            sessionStorage.setItem(key, JSON.stringify(payload));
                            currentTable.waiter = payload.name || currentTable.waiter;
                            const el = document.getElementById('pos-waiter-name');
                            if (el && payload.name) el.innerText = payload.name;
                            saveDB();
                            return true;
                        }
                        await Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'error',
                            title: data?.message || 'PIN inválido.',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    } catch (e) {
                        await Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'error',
                            title: 'No se pudo validar el PIN.',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }
                }
            }

            function init() {
                // Si requiere PIN, pedirlo al abrir la mesa
                if (waiterPinEnabled) {
                    ensureWaiterPin();
                }
                // Marcar la mesa como ocupada al abrir la vista
                const tableId = currentTable.table_id ?? currentTable.id ?? {{ $table->id }};
                fetch('{{ route('orders.openTable') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ table_id: tableId })
                }).catch(() => { });

                // Inicializar datos de la mesa
                if (document.getElementById('pos-table-name')) {
                    document.getElementById('pos-table-name').innerText = currentTable.name ||
                        "{{ str_pad($table->name ?? $table->id, 2, '0', STR_PAD_LEFT) }}";
                }
                if (document.getElementById('pos-table-area')) {
                    document.getElementById('pos-table-area').innerText = currentTable.area ||
                        "{{ $table->area?->name ?? ($area?->name ?? 'Sin área') }}";
                }
                if (document.getElementById('pos-waiter-name')) {
                    document.getElementById('pos-waiter-name').innerText = currentTable.waiter ||
                        "{{ $user?->name ?? 'Sin asignar' }}";
                }
                if (document.getElementById('pos-client-name')) {
                    document.getElementById('pos-client-name').innerText = currentTable.clientName ||
                        "{{ $person?->name ?? 'Sin cliente' }}";
                }
                const cobroClientInput = document.getElementById('cobro-client-input');
                if (cobroClientInput) {
                    cobroClientInput.value = currentTable.clientName || "{{ $person?->name ?? 'Público General' }}";
                }
                const dinersInput = document.getElementById('diners-input');
                if (dinersInput && currentTable.people_count) {
                    dinersInput.value = currentTable.people_count;
                }

                // Inicializar datos de servicio y delivery (automático por área)
                if (currentTable.area_id == serverTable.delivery_area_id) {
                    currentTable.service_type = 'DELIVERY';
                } else {
                    currentTable.service_type = 'IN_SITU';
                }

                // Actualizar visibilidad de datos delivery / takeaway
                if (typeof changeServiceType === 'function') {
                    changeServiceType(currentTable.service_type || 'IN_SITU');
                }

                const addrInput = document.getElementById('delivery-address');
                const phoneInput = document.getElementById('delivery-phone');
                const amountInput = document.getElementById('delivery-amount');
                const takeawayNameInput = document.getElementById('takeaway-client-name');
                const takeawayTimeInput = document.getElementById('takeaway-time');

                if (addrInput) addrInput.value = currentTable.delivery_address || '';
                if (phoneInput) phoneInput.value = currentTable.contact_phone || '';
                if (amountInput) amountInput.value = currentTable.delivery_amount || '';
                if (takeawayNameInput) takeawayNameInput.value = currentTable.client_name_extra || ''; // Usamos un campo extra para no pisar el clientName principal
                if (takeawayTimeInput) takeawayTimeInput.value = currentTable.delivery_time || '';
                const dispChk = document.getElementById('takeaway-disposable-charge');
                const dispAmt = document.getElementById('takeaway-disposable-amount');
                if (dispChk) dispChk.checked = !!currentTable.takeaway_disposable_charge;
                if (dispAmt) {
                    const dr = parseFloat(currentTable.takeaway_disposable_amount);
                    dispAmt.value = (!isNaN(dr) && dr > 0 ? dr : 0).toFixed(2);
                    dispAmt.disabled = !dispChk || !dispChk.checked;
                }
                refreshCartPricesFromServer();
                renderCategories();
                renderProducts();
                renderTicket();
                renderCancelledSection();
                fixScrollLayout();
                function updateSearchClearVisibility() {
                    const inp = document.getElementById('search-products');
                    const btn = document.getElementById('search-products-clear');
                    if (btn) btn.classList.toggle('hidden', !inp || !inp.value.trim());
                }
                window.clearProductSearch = function () {
                    const inp = document.getElementById('search-products');
                    if (inp) {
                        inp.value = '';
                        productSearchQuery = '';
                        inp.focus();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                };
                document.addEventListener('input', function (e) {
                    if (e.target && e.target.id === 'search-products') {
                        productSearchQuery = (e.target.value || '').trim();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                });
                document.addEventListener('keyup', function (e) {
                    if (e.target && e.target.id === 'search-products') {
                        productSearchQuery = (e.target.value || '').trim();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.target && e.target.id === 'search-products' && e.key === 'Escape') clearProductSearch();
                });
                updateSearchClearVisibility();
                if (currentTable.items && currentTable.items.length > 0) {
                    // setTimeout(scheduleAutoSave, 800);
                }
                if (typeof addCobroPaymentMethod === 'function' && document.getElementById('cobro-payment-methods-list')?.children.length === 0) {
                    addCobroPaymentMethod();
                }
                // Si viene con cobro=1 (desde botón Cobrar en mesas), abrir pestaña Cobro
                if (new URLSearchParams(window.location.search).get('cobro') === '1' && typeof switchAsideTab === 'function') {
                    setTimeout(() => switchAsideTab('cobro'), 100);
                }
                const btnPrecuenta = document.getElementById('btn-precuenta');
                if (btnPrecuenta && !btnPrecuenta.dataset.boundPrecuenta) {
                    btnPrecuenta.dataset.boundPrecuenta = '1';
                    btnPrecuenta.addEventListener('click', () => {
                        printPreAccountTicket();
                    });
                }
                if (new URLSearchParams(window.location.search).get('pre_account') === '1') {
                    setTimeout(() => {
                        printPreAccountTicket();
                    }, 250);
                }
            }

            function fixScrollLayout() {
                function run() {
                    const grid = document.getElementById('products-grid');
                    const cart = document.getElementById('cart-container');
                    if (grid) {
                        grid.scrollTop = 0;
                        void grid.offsetHeight;
                    }
                    if (cart) {
                        cart.scrollTop = 0;
                        void cart.offsetHeight;
                    }
                    window.scrollTo(0, 0);
                }
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        run();
                        setTimeout(run, 100);
                    });
                });
            }

            // Función para escapar HTML y prevenir XSS
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            const PLACEHOLDER_IMG = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23e5e7eb" width="200" height="200"/%3E%3Ctext fill="%239ca3af" font-family="sans-serif" font-size="14" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3ESin imagen%3C/text%3E%3C/svg%3E';

            // Función para obtener la URL de la imagen
            function getImageUrl(imagePath) {
                if (!imagePath || imagePath === 'null' || imagePath === null || imagePath === '') {
                    return PLACEHOLDER_IMG;
                }
                // Si ya es una URL completa, retornarla
                if (imagePath.startsWith('http://') || imagePath.startsWith('https://') || imagePath.startsWith(
                    'data:')) {
                    return imagePath;
                }
                // Si es una ruta relativa que empieza con /, retornarla tal cual
                if (imagePath.startsWith('/')) {
                    return imagePath;
                }
                // Si es una ruta de storage, ya viene con asset() desde el servidor
                return imagePath;
            }
            window.getImageUrl = getImageUrl;

            // Datos de productos, categorías y productBranches desde el servidor.
            const serverProductBranches = @json($productBranches ?? []);
            const serverCategories = @json(collect($categories ?? [])->map(fn($c) => ['id' => $c->id, 'name' => $c->description ?? '', 'img' => $c->image ? asset('storage/' . $c->image) : null])->values()->all());
            const serverProductsRaw = @json($products ?? []);
            const categoryIdsInBranch = (serverCategories || []).map(c => Number(c.id));
            // Solo productos que tienen productBranch en esta sucursal Y cuya categoría está en category_branch (está en serverCategories).
            const serverProducts = (serverProductsRaw || []).filter(p =>
                serverProductBranches.some(pb => Number(pb.product_id) === Number(p.id)) &&
                categoryIdsInBranch.includes(Number(p.category_id))
            );

            /** Nombre de impresora QZ (printers_branch) asignado al producto en esta sucursal (primera ticketera). */
            function resolveQzPrinterName(productId) {
                const id = parseInt(productId, 10);
                if (!id || !serverProductBranches?.length) return null;
                const pb = serverProductBranches.find(p => Number(p.product_id) === id);
                const n = pb && pb.qz_printer_name ? String(pb.qz_printer_name).trim() : '';
                return n || null;
            }

            /** Nombres de impresora QZ asignadas por pivote product_branch_printer (puede ser varias). */
            function resolveQzPrinterNames(productId) {
                const id = parseInt(productId, 10);
                if (!id || !serverProductBranches?.length) return [];
                const pb = serverProductBranches.find(p => Number(p.product_id) === id);
                const list = Array.isArray(pb?.qz_printer_names) ? pb.qz_printer_names : [];
                const cleaned = list
                    .map(n => String(n || '').trim())
                    .filter(n => !!n);
                if (cleaned.length) return cleaned;
                const single = resolveQzPrinterName(id);
                return single ? [single] : [];
            }

            /** Devuelve metadatos de impresoras QZ para un producto (name, width). */
            function resolveQzPrinters(productId) {
                const id = parseInt(productId, 10);
                if (!id || !serverProductBranches?.length) return [];
                const pb = serverProductBranches.find(p => Number(p.product_id) === id);
                const list = Array.isArray(pb?.qz_printers) ? pb.qz_printers : [];
                return list
                    .map((it) => ({
                        name: String(it?.name || '').trim(),
                        width: String(it?.width || '').trim(),
                    }))
                    .filter((it) => it.name);
            }

            /** Ancho de ticket por impresora (sale de printers_branch.width). */
            function resolvePrinterWidthByName(printerName) {
                const target = String(printerName || '').trim().toLowerCase();
                if (!target) return 58;
                for (let i = 0; i < serverProductBranches.length; i++) {
                    const pb = serverProductBranches[i];
                    const plist = Array.isArray(pb?.qz_printers) ? pb.qz_printers : [];
                    for (let j = 0; j < plist.length; j++) {
                        const p = plist[j];
                        const n = String(p?.name || '').trim().toLowerCase();
                        if (n && n === target) {
                            const w = parseInt(String(p?.width || '').replace(/[^\d]/g, ''), 10);
                            if (!isNaN(w) && (w === 58 || w === 80)) return w;
                        }
                    }
                }
                return 58; // default seguro
            }

            /** Escapa texto para HTML (comanda impresa como pixel/HTML en Epson u otras no-RAW). */
            function escapeHtmlForQzPrint(text) {
                return String(text)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/\"/g, '&quot;');
            }

            /**
             * RAW para térmicas; si falla (p. ej. Epson tinta), reintenta como HTML/pixel.
             * Esto permite "imprimir en todos" aunque no todos acepten RAW.
             */
            async function printTicketWithQz(qzApi, printerName, plainText) {
                const paperWidth = resolvePrinterWidthByName(printerName);
                const paperMm = paperWidth === 80 ? 80 : 58;
                const config = qzApi.configs.create(printerName, {
                    units: 'mm',
                    size: { width: paperMm, height: 200 },
                    margins: 0,
                });
                try {
                    // Reemplaza caracteres especiales del español a equivalentes ASCII
                    // para que la ticketera térmica los imprima correctamente (PC437/PC850).
                    function toEscPos(text) {
                        return String(text || '')
                            .replace(/á/g, 'a').replace(/Á/g, 'A')
                            .replace(/é/g, 'e').replace(/É/g, 'E')
                            .replace(/í/g, 'i').replace(/Í/g, 'I')
                            .replace(/ó/g, 'o').replace(/Ó/g, 'O')
                            .replace(/ú/g, 'u').replace(/Ú/g, 'U')
                            .replace(/ü/g, 'u').replace(/Ü/g, 'U')
                            .replace(/ñ/g, 'n').replace(/Ñ/g, 'N')
                            .replace(/¿/g, '?').replace(/¡/g, '!');
                    }
                    // ESC/POS: init + código de página PC850 (español) + contenido + feeds + corte
                    // Separa líneas para aplicar tamaños distintos:
                    //   - primera línea (COMANDA / ANULADO / PRECUENTA): doble alto + doble ancho (título)
                    //   - separadores (===...): tamaño normal
                    //   - cabecera (Mesa, Mozo, Fecha, Area, Salon, Hora): tamaño normal
                    //   - líneas de producto (Producto + cantidad al final): negrita + doble alto
                    //   - resto (Nota, Estado, Motivo): normal
                    const rawContent = toEscPos(plainText);
                    const rawLines = rawContent.split('\n');
                    let formattedContent = '';
                    for (let li = 0; li < rawLines.length; li++) {
                        const line = rawLines[li];
                        const trimmed = line.trim();
                        const isSep = /^=+$/.test(trimmed);
                        const isHeader = /^(Mesa|Mozo|Fecha\/Hora|Fecha|Area|Salon|Hora|Producto|Cant|Total|Subtotal)/.test(trimmed)
                            || /^(Mesa |COMANDA|COCINA|PRECUENTA|ANULADO)/.test(trimmed);
                        const isMeta = /^(Nota|Estado|Motivo|DETALLE|S\/\.)/.test(trimmed) || trimmed === '';
                        if (li === 0) {
                            // Título principal: solo negrita
                            formattedContent += '\x1B\x45\x01' + line + '\x1B\x45\x00\n';
                        } else if (isSep || isHeader || isMeta) {
                            // Separadores, cabecera y metadatos: tamaño normal
                            formattedContent += '\x1B\x21\x00' + line + '\n';
                        } else {
                            // Líneas de producto: negrita + doble alto
                            formattedContent += '\x1B\x45\x01' +  // ESC E 1 → negrita ON
                                '\x1B\x21\x10' +                  // ESC ! 0x10 → doble alto
                                line +
                                '\x1B\x45\x00' +                  // ESC E 0 → negrita OFF
                                '\x1B\x21\x00\n';                 // tamaño normal
                        }
                    }
                    const ticketCommands =
                        '\x1B\x40' +               // ESC @ (init/reset)
                        '\x1B\x74\x02' +           // ESC t 2 → code page PC850 (Latin-1, incluye español)
                        formattedContent +
                        '\n\n' +
                        '\x1D\x56\x42\x10';        // GS V B 16 → avance + corte parcial

                    await qzApi.print(config, [{
                        type: 'raw',
                        format: 'command',
                        flavor: 'plain',
                        data: ticketCommands,
                    }]);
                    return;
                } catch (rawErr) {
                    console.warn('QZ Tray: RAW no disponible en "' + printerName + '", usando HTML.', rawErr);
                }

                const htmlLines = String(plainText || '')
                    .split('\n')
                    .map((line) => {
                        const t = String(line || '');
                        const trimmed = t.trimStart();
                        if (trimmed.startsWith('Hora:') || trimmed.startsWith('Nota:')) {
                            return '<span class="meta">' + escapeHtmlForQzPrint(t) + '</span>';
                        }
                        return escapeHtmlForQzPrint(t);
                    })
                    .join('\n');

                const html = '<!DOCTYPE html><html><head><meta charset="utf-8" style="font-size:15pt;">' +
                    '<style>@page{size:' + paperMm + 'mm auto;margin:0;}html,body{width:' + paperMm + 'mm;margin:0;padding:0;}' +
                    'body{font-family:Segoe UI,Arial,sans-serif;}' +
                    'pre{white-space:pre-wrap;word-wrap:break-word;margin:0;padding:0;font-family:inherit;line-height:1.2;}' +
                    '.meta{font-size:8pt;color:#555;}</style></head><body><pre>' +
                    htmlLines + '</pre></body></html>';

                await qzApi.print(config, [{
                    type: 'pixel',
                    format: 'html',
                    flavor: 'plain',
                    data: html,
                }]);
            }

            function formatDateTimeForTicket(dt) {
                const d = dt instanceof Date ? dt : new Date();
                const dd = String(d.getDate()).padStart(2, '0');
                const mm = String(d.getMonth() + 1).padStart(2, '0');
                const yy = d.getFullYear();
                const hh = String(d.getHours()).padStart(2, '0');
                const mi = String(d.getMinutes()).padStart(2, '0');
                return `${dd}/${mm}/${yy} ${hh}:${mi}`;
            }

            function normalizeCancellationsForTicket(rawCancellations) {
                const list = Array.isArray(rawCancellations) ? rawCancellations : [];
                const map = new Map();
                list.forEach((c) => {
                    const name = String(c?.name ?? c?.description ?? 'Producto').trim() || 'Producto';
                    const reason = String(c?.cancel_reason ?? '').trim();
                    const productId = parseInt(c?.product_id ?? c?.pId ?? 0, 10) || 0;
                    const qty = parseFloat(c?.qtyCanceled ?? c?.quantity ?? 1) || 1;
                    const key = `${productId}|${name}|${reason}`;
                    if (!map.has(key)) {
                        map.set(key, { ...c, name, cancel_reason: reason, qtyCanceled: qty });
                        return;
                    }
                    const current = map.get(key);
                    current.qtyCanceled = (parseFloat(current.qtyCanceled ?? 0) || 0) + qty;
                    map.set(key, current);
                });
                return Array.from(map.values());
            }

            function buildPreAccountTicketText(table, groupedItems, canceledItems, paperWidth = 58) {
                const lineWidth = paperWidth === 80 ? 48 : 24;
                const colQty = 4;
                const colPrice = 10;
                const colName = lineWidth - colQty - colPrice;
                const sep = '='.repeat(lineWidth) + '\n';
                const normalizedCanceledItems = normalizeCancellationsForTicket(canceledItems);
                const hasCanceled = normalizedCanceledItems.length > 0;

                function padEndSafe(str, length) {
                    const s = String(str ?? '').trim();
                    if (s.length >= length) return s.slice(0, length);
                    return s + ' '.repeat(length - s.length);
                }   
                function padCenterSafe(str, length) {
                    const s = String(str ?? '').trim();
                    if (s.length >= length) return s.slice(0, length);
                    return ' '.repeat(Math.floor((length - s.length) / 2)) + s + ' '.repeat(length - s.length - Math.floor((length - s.length) / 2));
                }

                function padStartSafe(str, length) {
                    const s = String(str ?? '').trim();
                    if (s.length >= length) return s.slice(-length);
                    return ' '.repeat(length - s.length) + s;
                }

                const area = String(table?.original_area_name || 'Sin area').trim();
                const salon = String(table?.original_location_name || table?.location_name || 'Salon').trim();
                const mesaLabel = String(table?.name ?? table?.table_id ?? '-');
                const mozo = String(table?.waiter || 'Sin asignar').trim();
                const fechaHora = formatDateTimeForTicket(new Date());

                let txt = '';
                if (hasCanceled) txt += 'ANULADO\n';
                txt += padCenterSafe('PRECUENTA', lineWidth) + '\n';
                txt += `Salon: ${salon}\n`;
                txt += `Area: ${area} (Mesa: ${mesaLabel})\n`;
                txt += `Mozo: ${mozo}\n`;
                txt += `Fecha/Hora: ${fechaHora}\n`;
                txt += sep;
                txt += padEndSafe('Producto', colName) + padCenterSafe('Cant', colQty) + padStartSafe('P. unitario', colPrice) + '\n';
                txt += sep;

                (groupedItems || []).forEach((it) => {
                    const name = String(it?.name || 'Producto').trim();
                    const qty = parseFloat(it?.qty ?? 1) || 1;
                    const price = parseFloat(it?.price ?? 0) || 0;
                    const courtesyQty = Math.max(0, Math.min(qty, parseFloat(it?.courtesyQty ?? 0) || 0));
                    const takeawayQty = Math.max(0, Math.min(qty, parseFloat(it?.takeawayQty ?? 0) || 0));
                    txt += padEndSafe(name, colName) + padCenterSafe(String(qty), colQty) + padStartSafe('S/.' + price.toFixed(2), colPrice) + '\n';
                    if (courtesyQty > 0 || takeawayQty > 0) {
                        const tags = [];
                        if (courtesyQty > 0) tags.push('Cortesia: ' + courtesyQty);
                        if (takeawayQty > 0) tags.push('Llevar: ' + takeawayQty);
                        txt += '  * ' + tags.join(' | ') + '\n';
                    }
                });

                if (hasCanceled) {
                    txt += sep;
                    txt += 'DETALLE ANULADO\n';
                    txt += sep;
                    normalizedCanceledItems.forEach((c) => {
                        const cName = String(c?.name || c?.description || 'Producto').trim();
                        const cQty = parseFloat(c?.qtyCanceled ?? c?.quantity ?? 1) || 1;
                        txt += padEndSafe(cName, colName) + padStartSafe('x' + String(cQty), colQty) + '\n';
                        if (c?.cancel_reason && String(c.cancel_reason).trim()) {
                            txt += 'Motivo: ' + String(c.cancel_reason).trim() + '\n';
                        }
                    });
                }

                // Totales alineados con el panel (descuenta cortesias y suma cargos aplicables).
                const totals = getTotalsWithDelivery(groupedItems || []);
                txt += sep;
                txt += padEndSafe('Subtotal', lineWidth - 10) + padStartSafe('S/. ' + (totals.subtotal || 0).toFixed(2), 10) + '\n';
                txt += padEndSafe('Impuestos', lineWidth - 10) + padStartSafe('S/. ' + (totals.tax || 0).toFixed(2), 10) + '\n';
                if ((totals.deliveryFee || 0) > 0) {
                    txt += padEndSafe('Delivery', lineWidth - 10) + padStartSafe('S/. ' + totals.deliveryFee.toFixed(2), 10) + '\n';
                }
                if ((totals.takeawayDisposableFee || 0) > 0) {
                    txt += padEndSafe('Descartables', lineWidth - 10) + padStartSafe('S/. ' + totals.takeawayDisposableFee.toFixed(2), 10) + '\n';
                }
                txt += padEndSafe('TOTAL', lineWidth - 10) + padStartSafe('S/. ' + (totals.total || 0).toFixed(2), 10) + '\n';
                txt += sep;

                txt += '\n';
                return txt;
            }

            function openPreAccountPdfTab() {
                const groupedItems = getItemsGroupedByProduct();
                const canceledItems = Array.isArray(currentTable?.cancellations) ? currentTable.cancellations : [];
                if (!groupedItems.length && !canceledItems.length) {
                    if (typeof showNotification === 'function') {
                        showNotification('Precuenta', 'No hay productos para generar la precuenta.', 'warning');
                    }
                    return;
                }

                const ticketText = buildPreAccountTicketText(currentTable, groupedItems, canceledItems, 58);
                const escaped = escapeHtmlForQzPrint(ticketText);
                const html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                    '<title>Precuenta</title>' +
                    '<style>body{font-family:Courier New,monospace;margin:24px;background:#f5f5f5;}' +
                    '.paper{width:58mm;max-width:58mm;background:#fff;border:1px solid #ddd;padding:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);}' +
                    'pre{margin:0;white-space:pre-wrap;word-wrap:break-word;line-height:1.25;font-size:11px;}' +
                    '.hint{font:12px/1.4 Segoe UI,Arial,sans-serif;color:#444;margin-top:12px;}</style>' +
                    '</head><body><div class="paper"><pre>' + escaped + '</pre></div>' +
                    '<div class="hint">Vista previa temporal sin ticketera. Presiona Ctrl+P y guarda como PDF.</div>' +
                    '</body></html>';

                const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank', 'noopener,noreferrer');
                setTimeout(() => URL.revokeObjectURL(url), 10000);
            }

            function openKitchenCommandPdfTab(items, table) {
                const activeItems = Array.isArray(items) ? items : [];
                const cancellations = normalizeCancellationsForTicket(table?.cancellations);
                if (!activeItems.length && !cancellations.length) {
                    if (typeof showNotification === 'function') {
                        showNotification('Comanda', 'No hay productos para generar la comanda.', 'warning');
                    }
                    return;
                }

                const firstPid = parseInt(activeItems[0]?.pId ?? activeItems[0]?.product_id, 10) || 0;
                const firstPrinterName = firstPid ? (resolveQzPrinterNames(firstPid)[0] || '') : '';
                const paperWidth = firstPrinterName ? resolvePrinterWidthByName(firstPrinterName) : 58;
                const lineWidth = paperWidth === 80 ? 32 : 24;
                const colQty = 4;
                const colTime = 6;
                const colName = lineWidth - colQty - colTime;
                const sep = '='.repeat(lineWidth) + '\n';
                const tableLabel = table?.name ?? table?.table_id ?? 'Mesa';
                const areaLabel = (table?.original_area_name || '').trim();

                const padEnd = (s, len) => {
                    const v = String(s ?? '');
                    return v.length >= len ? v.slice(0, len) : (v + ' '.repeat(len - v.length));
                };
                const padStart = (s, len) => {
                    const v = String(s ?? '');
                    return v.length >= len ? v.slice(-len) : (' '.repeat(len - v.length) + v);
                };
                const padCenter = (s, len) => {
                    const v = String(s ?? '');
                    if (v.length >= len) return v.slice(0, len);
                    const l = Math.floor((len - v.length) / 2);
                    const r = len - v.length - l;
                    return ' '.repeat(l) + v + ' '.repeat(r);
                };

                let text = '';
                text += padCenter('COMANDA', lineWidth) + '\n';
                if (areaLabel) text += padCenter(areaLabel, lineWidth) + '\n';
                text += padCenter('Mesa ' + tableLabel, lineWidth) + '\n';
                text += 'Mozo: ' + (table?.waiter || '-') + '\n';
                text += 'Fecha: ' + formatDateTimeForTicket(new Date()) + '\n';
                text += sep;
                text += padEnd('Producto', colName) + padCenter('Hora', colTime) + padStart('Cant', colQty) + '\n';
                text += sep;

                activeItems.forEach((it) => {
                    const qty = it?.qty ?? 1;
                    const name = (it?.name || 'Producto').trim();
                    const hour = (it?.commandTime || '').trim();
                    const courtesyQty = Math.max(0, Math.min(parseFloat(qty) || 0, parseFloat(it?.courtesyQty ?? 0) || 0));
                    const takeawayQty = Math.max(0, Math.min(parseFloat(qty) || 0, parseFloat(it?.takeawayQty ?? 0) || 0));
                    const status = String(it?.status ?? '').toUpperCase();
                    const isDelivered = !!it?.delivered || status === 'ENTREGADO' || status === 'E';
                    text += '  ' + padEnd(name, Math.max(0, colName - 2)) + padCenter(hour, colTime) + padStart('x' + qty, colQty) + '\n';
                    if (isDelivered) text += '  [ENTREGADO]\n';
                    if (courtesyQty > 0 || takeawayQty > 0) {
                        const tags = [];
                        if (courtesyQty > 0) tags.push('Cortesia: ' + courtesyQty);
                        if (takeawayQty > 0) tags.push('Llevar: ' + takeawayQty);
                        text += '    * ' + tags.join(' | ') + '\n';
                    }
                    if (it?.note && String(it.note).trim()) text += '      Nota: ' + String(it.note).trim() + '\n';
                    text += '\n';
                });

                // Cancelados: cada uno con "ANULADO" encima y cantidad anulada
                if (cancellations.length) {
                    text += sep;
                    cancellations.forEach((c) => {
                        const cName = String(c?.name ?? c?.description ?? 'Producto').trim();
                        const cQty = parseFloat(c?.qtyCanceled ?? c?.quantity ?? 1) || 1;
                        text += 'ANULADO\n';
                        text += padEnd(cName, colName) + padCenter('', colTime) + padStart('x' + cQty, colQty) + '\n';
                        if (c?.cancel_reason && String(c.cancel_reason).trim()) text += 'Motivo: ' + String(c.cancel_reason).trim() + '\n';
                        text += '\n';
                    });
                }

                const escaped = escapeHtmlForQzPrint(text);
                const hasCancellations = cancellations.length > 0;
                const html = '<!DOCTYPE html><html><head><meta charset="utf-8">' +
                    '<title>' + (hasCancellations ? 'ANULADO - Comanda' : 'Comanda') + '</title>' +
                    '<style>body{font-family:Courier New,monospace;margin:24px;background:#f5f5f5;}' +
                    '.paper{width:' + (paperWidth === 80 ? '80mm' : '58mm') + ';max-width:' + (paperWidth === 80 ? '80mm' : '58mm') + ';background:#fff;border:' + (hasCancellations ? '2px solid #dc2626' : '1px solid #ddd') + ';padding:8px;box-shadow:0 2px 8px rgba(0,0,0,.08);}' +
                    '.anulado-badge{background:#dc2626;color:#fff;font-weight:bold;text-align:center;padding:4px;font-size:13px;letter-spacing:2px;margin-bottom:6px;}' +
                    'pre{margin:0;white-space:pre-wrap;word-wrap:break-word;line-height:1.25;font-size:11px;}' +
                    '.hint{font:12px/1.4 Segoe UI,Arial,sans-serif;color:#444;margin-top:12px;}</style>' +
                    '</head><body>' +
                    '<div class="paper">' +
                    (hasCancellations ? '<div class="anulado-badge">&#9733; ANULADO &#9733;</div>' : '') +
                    '<pre>' + escaped + '</pre></div>' +
                    '<div class="hint">Vista previa temporal sin ticketera. Presiona Ctrl+P y guarda como PDF.</div>' +
                    '</body></html>';

                const blob = new Blob([html], { type: 'text/html;charset=utf-8' });
                const url = URL.createObjectURL(blob);
                window.open(url, '_blank', 'noopener,noreferrer');
                setTimeout(() => URL.revokeObjectURL(url), 10000);
            }

            async function printPreAccountTicket() {
                const qzApi = window.qz;
                const groupedItems = getItemsGroupedByProduct();
                const canceledItems = Array.isArray(currentTable?.cancellations) ? currentTable.cancellations : [];
                if (!groupedItems.length && !canceledItems.length) {
                    if (typeof showNotification === 'function') {
                        showNotification('Precuenta', 'No hay productos para imprimir.', 'warning');
                    }
                    return;
                }

                const resolvePreAccountPrinterName = () => {
                    // 1. Buscar por producto
                    const productIds = new Set();
                    (groupedItems || []).forEach((it) => {
                        const pid = parseInt(it?.pId ?? it?.product_id, 10) || 0;
                        if (pid) productIds.add(pid);
                    });
                    for (const pid of productIds) {
                        const defs = resolveQzPrinters(pid);
                        if (defs.length && defs[0]?.name) return String(defs[0].name).trim();
                        const names = resolveQzPrinterNames(pid);
                        if (names.length) return String(names[0]).trim();
                        const single = resolveQzPrinterName(pid);
                        if (single) return String(single).trim();
                    }
                    // 2. Fallback: config global QZ
                    if (window.__qzConfig && window.__qzConfig.printerName)
                        return String(window.__qzConfig.printerName).trim();
                    return '';
                };

                // Si QZ está disponible, imprimir por QZ
                if (qzApi) {
                    let printerName = resolvePreAccountPrinterName();
                    const paperWidth = resolvePrinterWidthByName(printerName) || 58;
                    const ticketText = buildPreAccountTicketText(currentTable, groupedItems, canceledItems, paperWidth);
                    try {
                        if (!qzApi.websocket.isActive()) await qzApi.websocket.connect();
                        // Si no hay impresora asignada en productos, usar la impresora por defecto de QZ
                        if (!printerName) {
                            printerName = await qzApi.printers.getDefault();
                        }
                        if (!printerName) {
                            if (typeof showNotification === 'function') {
                                showNotification('Impresión', 'No se encontró ninguna impresora disponible en QZ Tray.', 'error');
                            }
                            return;
                        }
                        await printTicketWithQz(qzApi, printerName, ticketText);
                        if (typeof showNotification === 'function') {
                            showNotification('Precuenta', 'Ticket enviado a "' + printerName + '".', 'success');
                        }
                    } catch (e) {
                        console.error('Precuenta QZ:', e);
                        if (typeof showNotification === 'function') {
                            showNotification('Impresión', 'Error al imprimir precuenta: ' + (e?.message || e), 'error');
                        }
                    }
                    return;
                }

                // Fallback: ticketera por red (server-side ESC/POS)
                const movementId = currentTable?.movement_id;
                if (!movementId) {
                    if (typeof showNotification === 'function') {
                        showNotification('Precuenta', 'Guarda el pedido antes de imprimir la precuenta.', 'warning');
                    }
                    return;
                }
                try {
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    const tr = await fetch(salesThermalPrintUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify({ movement_id: movementId }),
                    });
                    const td = tr.headers.get('content-type')?.includes('application/json') ? await tr.json() : null;
                    if (tr.ok && td?.success) {
                        if (typeof showNotification === 'function') showNotification('Precuenta', td.message || 'Ticket enviado.', 'success');
                    } else {
                        const msg = td?.message || 'No se pudo enviar el ticket.';
                        if (typeof showNotification === 'function') showNotification('Impresión', msg, 'error');
                    }
                } catch (e) {
                    if (typeof showNotification === 'function') showNotification('Impresión', 'Error de red al imprimir precuenta.', 'error');
                }
            }

            /**
             * Imprime comandas agrupadas por ticketera (nombre exacto como en Windows / QZ).
             * Requiere window.qz (bundle qz-tray-init) y productos con ticketera en catálogo.
             */
            async function printKitchenTickets(items, table) {
                const activeItems = Array.isArray(items) ? items : [];
                const hasSavedOrder = !!(table?.order_movement_id);
                const isCurrentPendingOrder = hasSavedOrder && (table?.order_movement_id === serverOrderMovementId);
                const serverCancelled = (isCurrentPendingOrder && Array.isArray(serverPendingCancelledDetails))
                    ? serverPendingCancelledDetails
                    : [];
                const clientCancelled = Array.isArray(table?.cancellations) ? table.cancellations : [];
                const mergedCancellations = [
                    ...serverCancelled.map((d) => ({
                        pId: d?.product_id ?? null,
                        name: d?.description ?? 'Producto',
                        qtyCanceled: d?.quantity ?? 0,
                        cancel_reason: d?.comment ?? '',
                    })),
                    ...clientCancelled,
                ];
                if (!activeItems.length && !mergedCancellations.length) return;
                const qzApi = window.qz;
                if (!qzApi) {
                    console.warn('QZ Tray: script no cargado. Ejecuta npm run dev o npm run build.');
                    return;
                }
                const byPrinter = {};
                activeItems.forEach((it) => {
                    const pId = parseInt(it.pId, 10) || 0;
                    if (!pId) return;
                    const pdefs = resolveQzPrinters(pId);
                    const pnames = pdefs.length ? pdefs.map(p => p.name) : resolveQzPrinterNames(pId);
                    if (!pnames.length) return;
                    // Si un producto está asignado a varias impresoras (pivote), se imprime en todas.
                    pnames.forEach((pname) => {
                        if (!byPrinter[pname]) byPrinter[pname] = [];
                        byPrinter[pname].push(it);
                    });
                });
                const canceledByPrinter = {};
                mergedCancellations.forEach((c) => {
                    const pId = parseInt(c?.pId ?? c?.product_id, 10) || 0;
                    const qty = parseFloat(c?.qtyCanceled ?? c?.quantity ?? 0) || 0;
                    if (!pId || qty <= 0) return;
                    const pdefs = resolveQzPrinters(pId);
                    const pnames = pdefs.length ? pdefs.map(p => p.name) : resolveQzPrinterNames(pId);
                    if (!pnames.length) return;
                    pnames.forEach((pname) => {
                        if (!canceledByPrinter[pname]) canceledByPrinter[pname] = [];
                        canceledByPrinter[pname].push({
                            pId,
                            name: String(c?.name ?? c?.description ?? 'Producto').trim(),
                            qty,
                            reason: String(c?.cancel_reason ?? c?.comment ?? '').trim(),
                        });
                    });
                });
                const names = Array.from(new Set([
                    ...Object.keys(byPrinter),
                    ...Object.keys(canceledByPrinter),
                ]));
                if (!names.length) {
                    if (typeof showNotification === 'function') {
                        showNotification('Impresión', 'No hay impresoras asignadas a los productos (product_branch_printer).', 'warning');
                    }
                    return;
                }

                try {
                    if (!qzApi.websocket.isActive()) {
                        await qzApi.websocket.connect();
                    }
                } catch (e) {
                    console.error('QZ Tray: no se pudo conectar.', e);
                    if (typeof showNotification === 'function') {
                        showNotification('Impresión', 'No se pudo conectar con QZ Tray. ¿Está instalado y en ejecución?', 'warning');
                    }
                    return;
                }
                function padEnd(str, length) {
                    const s = String(str ?? '');
                    if (s.length >= length) return s.slice(0, length);
                    return s + ' '.repeat(length - s.length);
                }

                function padCenter(str, length) {
                    const s = String(str ?? '');
                    if (s.length >= length) return s.slice(0, length);
                    return ' '.repeat(Math.floor((length - s.length) / 2)) + s + ' '.repeat(length - s.length - Math.floor((length - s.length) / 2));
                }

                function padStart(str, length) {
                    const s = String(str ?? '');
                    if (s.length >= length) return s.slice(-length);
                    return ' '.repeat(length - s.length) + s;
                }

                const tableLabel = table?.name ?? table?.table_id ?? 'Mesa';
                const areaLabel = (table?.original_area_name || '').trim();

                for (let i = 0; i < names.length; i++) {
                    const pname = names[i];
                    const lines = byPrinter[pname] || [];
                    let body = '';
                    const paperWidth = resolvePrinterWidthByName(pname);
                    const LINE_WIDTH = paperWidth === 80 ? 48 : 24;
                    const COL_QTY = 4; // x99
                    const COL_TIME = 6; // "09:54"
                    const COL_NAME = LINE_WIDTH - COL_TIME - COL_QTY;
                    const separator = '='.repeat(LINE_WIDTH) + '\n';
                    const orderNumber = String(table?.order_movement_number ?? '').trim();
                    const orderDate = String(table?.order_movement_date ?? '').trim();
                    const comandaLabel = [`COMANDA: ${table?.order_movement_id}`, orderNumber].filter(Boolean).join(': ');
                    const comandaSub = orderDate ? orderDate : 'COCINA';
                    const header = padCenter(comandaLabel, LINE_WIDTH) + '\n' +
                        padCenter(comandaSub, LINE_WIDTH) + '\n' +
                        (areaLabel ? areaLabel + '\n' : '') +
                        'Mesa ' + tableLabel + '\n' +
                        'Mozo: ' + (table?.waiter || '-') + '\n' +
                        'Fecha: ' + new Date().toLocaleString() + '\n' +
                        separator +
                        padEnd('Producto', COL_NAME) + padCenter('Hora', COL_TIME) + padStart('Cant', COL_QTY) + '\n' +
                        separator;
                    const canceledByProduct = {};
                    (canceledByPrinter[pname] || []).forEach((c) => {
                        const pid = parseInt(c?.pId, 10) || 0;
                        const qty = parseFloat(c?.qty ?? 0) || 0;
                        if (!pid || qty <= 0) return;
                        canceledByProduct[pid] = (canceledByProduct[pid] || 0) + qty;
                    });

                    lines.forEach((it) => {
                        const qty = it.qty ?? 1;
                        const nm = (it.name || 'Producto').trim();
                        const qtyCol = 'x' + qty;
                        const timeCol = (it.commandTime ? String(it.commandTime).trim() : '');
                        const courtesyQty = Math.max(0, Math.min(parseFloat(qty) || 0, parseFloat(it?.courtesyQty ?? 0) || 0));
                        const takeawayQty = Math.max(0, Math.min(parseFloat(qty) || 0, parseFloat(it?.takeawayQty ?? 0) || 0));
                        body += padEnd(nm, COL_NAME) + padCenter(timeCol, COL_TIME) + padStart(qtyCol, COL_QTY) + '\n';
                        if (courtesyQty > 0 || takeawayQty > 0) {
                            const tags = [];
                            if (courtesyQty > 0) tags.push('Cortesia: ' + courtesyQty);
                            if (takeawayQty > 0) tags.push('Llevar: ' + takeawayQty);
                            body += '  * ' + tags.join(' | ') + '\n';
                        }
                        if (it.note && String(it.note).trim()) {
                            body += 'Nota: ' + String(it.note).trim() + '\n';
                        }
                        const status = String(it?.status || '').toUpperCase();
                        const isDelivered = !!it?.delivered || status === 'ENTREGADO' || status === 'E';
                        // Cancelado: desde el item (si existe) o desde la lista de cancelaciones de la mesa.
                        const pId = parseInt(it?.pId ?? it?.product_id, 10) || 0;
                        const canceledQty = canceledByProduct[pId] || 0;
                        const isCanceled = status === 'CANCELADO' || status === 'C' || canceledQty > 0;
                        const statusLabel = isCanceled
                            ? ('CANCELADO' + (canceledQty > 0 ? ' x' + canceledQty : ''))
                            : (isDelivered ? 'ENTREGADO' : 'PENDIENTE');
                        body += 'Estado: ' + statusLabel + '\n';
                        body += '\n';
                    });
                    const canceledItems = canceledByPrinter[pname] || [];
                    if (canceledItems.length) {
                        body += separator;
                        body += padCenter('ANULADO', LINE_WIDTH) + '\n';
                        body += separator;
                        canceledItems.forEach((c) => {
                            const qtyCol = 'x' + (c.qty ?? 1);
                            body += padEnd('ANULADO ' + (c.name || 'Producto'), COL_NAME) + padCenter('', COL_TIME) + padStart(qtyCol, COL_QTY) + '\n';
                            if (c.reason) {
                                body += 'Motivo: ' + c.reason + '\n';
                            }
                            body += '\n';
                        });
                    }
                    const data = header + body + '\n\n';
                    try {
                        // Validar que la impresora exista en QZ (por nombre exacto del sistema).
                        try {
                            await qzApi.printers.find(pname);
                        } catch (notFoundErr) {
                            const msg = 'QZ no encontró la impresora "' + pname + '". Verifica el nombre exacto en Windows/QZ.';
                            console.error(msg, notFoundErr);
                            if (typeof showNotification === 'function') {
                                showNotification('Impresión', msg, 'error');
                            }
                            continue;
                        }

                        await printTicketWithQz(qzApi, pname, data);
                    } catch (e) {
                        console.error('QZ Tray: error al imprimir en ' + pname, e);
                        if (typeof showNotification === 'function') {
                            showNotification('Impresión', 'No se pudo imprimir en "' + pname + '". ' + (e?.message || ''), 'error');
                        }
                    }
                }
            }

            function getItemTaxRatePercent(item) {
                const rate = parseFloat(item?.tax_rate);
                return !isNaN(rate) && rate >= 0 ? rate : 10;
            }

            /** Cantidad para llevar por línea: 0 … qty (no aplica en DELIVERY en UI). */
            function clampTakeawayQty(item) {
                if (!item) return;
                const q = parseFloat(item.qty) || 0;
                let t = parseFloat(item.takeawayQty);
                if (isNaN(t) || t < 0) t = 0;
                if (t > q) t = q;
                item.takeawayQty = t;
            }

            // Los precios del POS incluyen IGV.
            function calculateTotalsFromItems(items) {
                let subtotal = 0;
                let tax = 0;
                let total = 0;

                (items || []).forEach(item => {
                    const qty = parseFloat(item.qty) || 0;
                    const price = parseFloat(item.price) || 0;
                    const courtesyQty = parseInt(item.courtesyQty) || 0;
                    const paidQty = Math.max(0, qty - courtesyQty);
                    const lineTotal = paidQty * price;
                    const rate = getItemTaxRatePercent(item) / 100;
                    const lineSubtotal = rate > 0 ? (lineTotal / (1 + rate)) : lineTotal;
                    const lineTax = lineTotal - lineSubtotal;
                    subtotal += lineSubtotal;
                    tax += lineTax;
                    total += lineTotal;
                });

                return {
                    subtotal: Math.round(subtotal * 100) / 100,
                    tax: Math.round(tax * 100) / 100,
                    total: Math.round(total * 100) / 100,
                };
            }

            /** Costo delivery (solo servicio DELIVERY). */
            function getDeliveryFeeAmount() {
                if (!currentTable || currentTable.service_type !== 'DELIVERY') return 0;
                const v = parseFloat(currentTable.delivery_amount);
                return !isNaN(v) && v > 0 ? Math.round(v * 100) / 100 : 0;
            }

            function hasAnyTakeawayInCart() {
                return (currentTable?.items || []).some(it => (parseFloat(it.takeawayQty) || 0) > 0);
            }

            /** Costo descartables para llevar (checkbox + monto). */
            function getTakeawayDisposableFee() {
                if (!currentTable || currentTable.service_type === 'DELIVERY') return 0;
                if (!currentTable.takeaway_disposable_charge) return 0;
                const ctx = currentTable.service_type === 'TAKE_AWAY' || hasAnyTakeawayInCart();
                if (!ctx) return 0;
                const v = parseFloat(currentTable.takeaway_disposable_amount);
                return !isNaN(v) && v > 0 ? Math.round(v * 100) / 100 : 0;
            }

            /**
             * Totales de productos + delivery + descartables (llevar) al total final.
             */
            function getTotalsWithDelivery(items) {
                const base = calculateTotalsFromItems(items);
                const fee = getDeliveryFeeAmount();
                const disp = getTakeawayDisposableFee();
                const grand = Math.round((base.total + fee + disp) * 100) / 100;
                return {
                    subtotal: base.subtotal,
                    tax: base.tax,
                    productsTotal: base.total,
                    deliveryFee: fee,
                    takeawayDisposableFee: disp,
                    total: grand,
                };
            }

            function syncTakeawayDisposablePanel() {
                const el = document.getElementById('takeaway-disposable-panel');
                if (!el || !currentTable) return;
                const st = currentTable.service_type || 'IN_SITU';
                const show = st !== 'DELIVERY' && (st === 'TAKE_AWAY' || hasAnyTakeawayInCart());
                el.classList.toggle('hidden', !show);
                const chk = document.getElementById('takeaway-disposable-charge');
                const inp = document.getElementById('takeaway-disposable-amount');
                if (chk) chk.checked = !!currentTable.takeaway_disposable_charge;
                if (inp) {
                    const raw = parseFloat(currentTable.takeaway_disposable_amount);
                    inp.value = (!isNaN(raw) && raw > 0 ? raw : 0).toFixed(2);
                    inp.disabled = !chk || !chk.checked;
                }
            }

            function updateTakeawayDisposableInfo(normalizeInput = false) {
                if (!currentTable) return;
                const chk = document.getElementById('takeaway-disposable-charge');
                const inp = document.getElementById('takeaway-disposable-amount');
                if (chk) currentTable.takeaway_disposable_charge = chk.checked;
                if (inp) {
                    if (!chk || !chk.checked) {
                        inp.disabled = true;
                        currentTable.takeaway_disposable_amount = 0;
                    } else {
                        inp.disabled = false;
                        let n = parseFloat(inp.value);
                        if (isNaN(n) || n < 0) n = 0;
                        currentTable.takeaway_disposable_amount = Math.round(n * 100) / 100;
                        if (normalizeInput) {
                            inp.value = currentTable.takeaway_disposable_amount.toFixed(2);
                        }
                    }
                }
                saveDB();
                renderTicket();
            }

            // Actualizar precios del carrito con los precios actuales del servidor
            function refreshCartPricesFromServer() {
                if (!currentTable?.items || !serverProductBranches?.length) return;
                let updated = false;
                currentTable.items.forEach(item => {
                    const pId = parseInt(item.pId || item.product_id, 10);
                    const pb = serverProductBranches.find(p => p.product_id === pId || parseInt(p.product_id, 10) === pId);
                    if (pb) {
                        const newPrice = parseFloat(pb.price);
                        if (!isNaN(newPrice) && newPrice >= 0 && newPrice !== parseFloat(item.price)) {
                            item.price = newPrice;
                            updated = true;
                        }
                        const newTaxRate = parseFloat(pb.tax_rate);
                        if (!isNaN(newTaxRate) && newTaxRate >= 0 && newTaxRate !== parseFloat(item.tax_rate)) {
                            item.tax_rate = newTaxRate;
                            updated = true;
                        }
                    }
                });
                if (updated) saveDB();
            }

            const CATEGORY_ALL_ID = '__all__';
            const CATEGORY_FAVORITES_ID = '__favorites__';
            let selectedCategoryId = CATEGORY_FAVORITES_ID;
            let productSearchQuery = '';

            function isProductFavorite(productId) {
                const pb = serverProductBranches.find(p => Number(p.product_id) === Number(productId));
                return pb && String(pb.favorite || 'N').toUpperCase() === 'S';
            }

            function renderCategories() {
                const grid = document.getElementById('categories-grid');
                if (!grid) return;

                grid.innerHTML = '';

                // Favoritos (predeterminado)
                const favBtn = document.createElement('button');
                favBtn.type = 'button';
                const isFavActive = selectedCategoryId === CATEGORY_FAVORITES_ID;
                favBtn.className = [
                    'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold',
                    'border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0',
                    isFavActive
                        ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                        : 'bg-white dark:bg-slate-800 text-gray-700 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400'
                ].join(' ');
                favBtn.onclick = function (e) {
                    e.preventDefault();
                    selectedCategoryId = CATEGORY_FAVORITES_ID;
                    renderCategories();
                    renderProducts();
                };
                favBtn.innerHTML = `<i class="ri-star-fill text-lg"></i><span>Favoritos</span>`;
                grid.appendChild(favBtn);

                // Botón "Todos" (lista todos los productos de la sucursal con categoría)
                const allBtn = document.createElement('button');
                allBtn.type = 'button';
                const isAllActive = selectedCategoryId === CATEGORY_ALL_ID;
                allBtn.className = [
                    'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold',
                    'border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0',
                    isAllActive
                        ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                        : 'bg-white dark:bg-slate-800 text-gray-700 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400'
                ].join(' ');
                allBtn.onclick = function (e) {
                    e.preventDefault();
                    selectedCategoryId = CATEGORY_ALL_ID;
                    renderCategories();
                    renderProducts();
                };
                allBtn.innerHTML = `<i class="ri-apps-line text-lg"></i><span>Todos</span>`;
                grid.appendChild(allBtn);

                if (!serverCategories || serverCategories.length === 0) {
                    renderProducts();
                    return;
                }

                serverCategories.forEach(cat => {
                    const el = document.createElement('button');
                    const categoryName = escapeHtml(cat.name || 'Sin nombre');
                    const imageUrl = getImageUrl(cat.img);
                    const isActive = selectedCategoryId === cat.id;

                    el.type = 'button';
                    el.className = [
                        'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold',
                        'border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0',
                        isActive
                            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400'
                    ].join(' ');

                    el.onclick = function (e) {
                        e.preventDefault();
                        selectedCategoryId = cat.id;
                        renderCategories();
                        renderProducts();
                    };

                    el.innerHTML = `
                                <img src="${imageUrl}" alt="${categoryName}"
                                    class="w-6 h-6 rounded-full object-cover shrink-0 border ${isActive ? 'border-blue-300' : 'border-gray-200 dark:border-slate-600'}"
                                    onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22200%22 height=%22200%22/%3E%3C/svg%3E'">
                                <span>${categoryName}</span>
                            `;
                    grid.appendChild(el);
                });
            }

            function renderProducts() {
                const grid = document.getElementById('products-grid');
                if (!grid) return;
                grid.innerHTML = '';

                if (!serverProducts || serverProducts.length === 0) {
                    grid.innerHTML =
                        '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles</div>';
                    return;
                }

                // Filtrar por categoría seleccionada (Favoritos / Todos / categoría)
                let productsToShow = serverProducts;
                if (selectedCategoryId === CATEGORY_ALL_ID) {
                    productsToShow = serverProducts;
                } else if (selectedCategoryId === CATEGORY_FAVORITES_ID) {
                    productsToShow = serverProducts.filter(p => isProductFavorite(p.id));
                } else {
                    productsToShow = serverProducts.filter(p => p.category_id == selectedCategoryId);
                }

                // Filtrar por texto de búsqueda: leer siempre del input para filtrar desde la primera letra
                const searchInput = document.getElementById('search-products');
                const q = (searchInput && searchInput.value !== undefined)
                    ? String(searchInput.value || '').trim().toLowerCase()
                    : String(productSearchQuery || '').trim().toLowerCase();
                if (q.length > 0) {
                    productsToShow = productsToShow.filter(p => {
                        const name = String(p.name || '').toLowerCase();
                        const category = String(p.category || '').toLowerCase();
                        return name.includes(q) || category.includes(q);
                    });
                }

                let productsRendered = 0;
                productsToShow.forEach(prod => {
                    const productBranch = serverProductBranches.find(p => p.product_id === prod.id || p.id === prod.id);
                    if (!productBranch) return;

                    const el = document.createElement('div');
                    el.className = "group cursor-pointer transition-transform duration-200 hover:scale-105 h-full flex";

                    // Prevenir múltiples clics rápidos
                    let isAdding = false;
                    el.onclick = function (e) {
                        e.preventDefault();

                        isAdding = true;
                        addToCart(prod, productBranch);

                        // Permitir agregar de nuevo después de un breve delay
                        setTimeout(() => {
                            isAdding = false;
                        }, 500);
                    };

                    const productName = escapeHtml(prod.name || 'Sin nombre');
                    const imageUrl = getImageUrl(prod.img);
                    const priceFormatted = 'S/ ' + parseFloat(productBranch.price).toFixed(2);
                    const hasImg = prod.img && String(prod.img).trim() !== '';
                    const stockVal = Number(productBranch.stock);
                    const stockText = !isNaN(stockVal) ? stockVal.toFixed(2) : '0.00';

                    el.innerHTML = `
                                <div class="rounded-2xl overflow-hidden p-4 sm:p-5 bg-white dark:bg-slate-800/60 border-2 border-blue-200 dark:border-blue-500/40 hover:border-blue-400 dark:hover:border-blue-400 transition-all duration-200 hover:-translate-y-0.5 flex flex-col items-center text-center h-full w-full">
                                    <div class="w-20 h-16 sm:w-20 sm:h-20 rounded-full bg-blue-500 flex items-center justify-center shrink-0 overflow-hidden mb-3">
                                        ${hasImg
                            ? `<img src="${imageUrl}" alt="${productName}" class="w-full h-full object-contain rounded-full object-cover object-center" loading="lazy" onerror="this.parentElement.innerHTML='<i class=\\'ri-restaurant-2-line text-2xl sm:text-3xl text-white\\'></i>'">`
                            : `<i class="ri-restaurant-2-line text-2xl sm:text-3xl text-white"></i>`
                        }
                                    </div>
                                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base line-clamp-2 leading-tight mb-1 min-h-[2.5rem]">
                                        ${productName}
                                    </h4>
                                    <span class="text-base sm:text-lg font-bold text-blue-600 dark:text-blue-400">
                                        ${priceFormatted}
                                    </span>
                                    <span class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Stock: ` + stockText + `</span>
                                </div>
                            `;
                    grid.appendChild(el);
                    productsRendered++;
                });

                if (productsRendered === 0) {
                    if (q.length > 0) {
                        grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">No se encontraron productos para "' + escapeHtml(productSearchQuery) + '"</div>';
                    } else if (selectedCategoryId === CATEGORY_FAVORITES_ID) {
                        grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">No hay productos favoritos en esta sucursal. Marca favoritos en el producto o elige la categoría Todos.</div>';
                    } else {
                        grid.innerHTML = selectedCategoryId === CATEGORY_ALL_ID
                            ? '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles para esta sucursal</div>'
                            : '<div class="col-span-full text-center text-gray-500 py-8">No hay productos en esta categoría</div>';
                    }
                }

            }

            function addToCart(prod, productBranch) {
                if (!currentTable.items) currentTable.items = [];

                if (!productBranch || !productBranch.price) {
                    showNotification('Precio no disponible', 'El producto no tiene un precio disponible.', 'error');
                    return;
                }

                const price = parseFloat(productBranch.price);
                if (isNaN(price) || price <= 0) {
                    showNotification('Precio inválido', 'El precio del producto es inválido.', 'error');
                    return;
                }

                const stock = parseFloat(productBranch.stock ?? 0) || 0;
                const allowZeroStockSales = @json($allowZeroStockSales ?? true);

                // Asegurar que el ID del producto sea un número entero para la comparación
                const productId = parseInt(prod.id, 10);
                if (isNaN(productId) || productId <= 0) {

                    showNotification('ID de producto inválido', 'El ID del producto es inválido.', 'error');
                    return;
                }

                // Limpiar items inválidos antes de buscar
                currentTable.items = currentTable.items.filter(i => {
                    const itemPId = parseInt(i.pId, 10);
                    return !isNaN(itemPId) && itemPId > 0;
                });

                // Buscar si el producto ya existe en el carrito
                const existing = currentTable.items.find(i => {
                    const itemPId = parseInt(i.pId, 10);
                    return !isNaN(itemPId) && itemPId === productId;
                });

                const qtyToAdd = existing ? existing.qty + 1 : 1;
                if (!allowZeroStockSales && qtyToAdd > stock) {
                    showNotification('Stock insuficiente', (prod.name || 'Producto') + ': solo hay ' + stock + ' disponible(s).', 'error');
                    return;
                }

                const st = currentTable.service_type || 'IN_SITU';
                if (existing) {
                    // Si existe, solo aumentar la cantidad
                    existing.qty++;
                    if (st === 'TAKE_AWAY') {
                        existing.takeawayQty = (parseFloat(existing.takeawayQty) || 0) + 1;
                        clampTakeawayQty(existing);
                    }
                    if (existing.tax_rate === undefined || existing.tax_rate === null) {
                        existing.tax_rate = parseFloat(productBranch.tax_rate ?? 10);
                    }
                } else {
                    // Si no existe, agregarlo como nuevo item
                    currentTable.items.push({
                        pId: productId,
                        name: prod.name || 'Sin nombre',
                        qty: 1,
                        price: price,
                        tax_rate: parseFloat(productBranch.tax_rate ?? 10),
                        note: "",
                        delivered: false,
                        courtesyQty: 0,
                        takeawayQty: st === 'TAKE_AWAY' ? 1 : 0
                    });
                }
                saveDB();
                renderTicket();
                showNotification('Producto agregado', 'El producto ' + (prod.name || 'Producto') + ' ha sido agregado al carrito.', 'success');
            }

            async function updateQty(index, change) {
                const item = currentTable.items[index];
                const oldQty = item.qty;
                let newQty = oldQty + change;

                // Aumentar cantidad: no requiere razón
                if (change > 0) {
                    item.qty = newQty;
                    if ((currentTable.service_type || '') === 'TAKE_AWAY') {
                        item.takeawayQty = (parseFloat(item.takeawayQty) || 0) + change;
                        clampTakeawayQty(item);
                    }
                    saveDB();
                    renderTicket();
                    return;
                }

                // Disminuir cantidad (si pedido guardado, no bajar de savedQty)
                const savedQty = Number.isFinite(parseFloat(item.savedQty)) ? parseFloat(item.savedQty) : 0;
                if (currentTable.order_movement_id && savedQty > 0) {
                    newQty = Math.max(savedQty, newQty);
                }
                item.qty = newQty;
                const cq = parseFloat(item.courtesyQty) || 0;
                if (cq > item.qty) item.courtesyQty = item.qty;
                clampTakeawayQty(item);
                if (item.qty <= 0) currentTable.items.splice(index, 1);
                saveDB();
                renderTicket();
            }

            async function setQtyFromInput(index, inputEl) {
                const item = currentTable.items[index];
                if (!item) return;
                const raw = parseInt(inputEl.value, 10);
                const newQty = isNaN(raw) || raw < 1 ? 1 : raw;
                const oldQty = item.qty;
                if (newQty === oldQty) {
                    inputEl.value = newQty;
                    return;
                }
                if (newQty > oldQty) {
                    item.qty = newQty;
                    if ((currentTable.service_type || '') === 'TAKE_AWAY') {
                        item.takeawayQty = (parseFloat(item.takeawayQty) || 0) + (newQty - oldQty);
                        clampTakeawayQty(item);
                    }
                    saveDB();
                    renderTicket();
                    return;
                }
                const savedQty = parseFloat(item.savedQty) ?? 0;
                if (currentTable.order_movement_id && savedQty > 0 && newQty < savedQty) {
                    inputEl.value = savedQty;
                    showNotification('Cantidad no válida', 'La cantidad no puede ser menor que la cantidad guardada.', 'error');
                    item.qty = savedQty;
                    return;
                }
                await updateQty(index, newQty - oldQty);
            }

            function toggleNoteInput(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const nt = typeof item.note === 'string' ? item.note.trim() : '';
                const hasN = nt !== '';
                const shown = item.noteOpen === true || (item.noteOpen === undefined && hasN);
                item.noteOpen = !shown;
                saveDB();
                renderTicket();
            }

            function toggleCourtesyInput(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const hasC = (parseFloat(item.courtesyQty) || 0) > 0;
                const shown = item.courtesyOpen === true || (item.courtesyOpen === undefined && hasC);
                item.courtesyOpen = !shown;
                saveDB();
                renderTicket();
            }

            async function confirmRemoveLine(index) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const qty = parseInt(item.qty, 10) || 1;
                const prod = serverProducts.find(p => p.id === item.pId);
                const name = (prod && prod.name) ? prod.name : 'este producto';
                const msg = qty === 1
                    ? `¿Desea eliminar 1 unidad de **${escapeHtml(name)}** del pedido?`
                    : `¿Desea eliminar **${qty}** unidades de **${escapeHtml(name)}** del pedido?`;
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Eliminar del pedido',
                        html: msg.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>'),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626'
                    });
                    if (!result.isConfirmed) return;
                } else if (!window.confirm(msg.replace(/\*\*/g, ''))) {
                    return;
                }
                await removeFromCart(index);
            }

            async function removeFromCart(index) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const qty = parseInt(item.qty, 10) || 1;
                await updateQty(index, -qty);
            }

            function applyRemoveQuantity(index, qtyToRemove, reason) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const prod = serverProducts.find(p => p.id === item.pId);
                const qty = parseFloat(item.qty) || 0;
                const toCancel = Math.min(qtyToRemove, qty);
                if (toCancel <= 0) return;
                // Siempre registrar la cancelación (no solo cuando hay order_movement_id)
                currentTable.cancellations = currentTable.cancellations || [];
                currentTable.cancellations.push({
                    pId: item.pId,
                    product_id: item.pId,
                    name: item.name || (prod && prod.name),
                    qtyCanceled: toCancel,
                    price: item.price,
                    note: item.note || null,
                    cancel_reason: reason || null,
                    product_snapshot: prod ? { ...prod } : null
                });
                item.qty = qty - toCancel;
                clampTakeawayQty(item);
                if (item.qty <= 0) currentTable.items.splice(index, 1);
                saveDB();
                renderTicket();
            }

            function saveNote(index, val) {
                currentTable.items[index].note = val;
                saveDB();
            }

            function toggleDelivered(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                currentTable.items[index].delivered = !currentTable.items[index].delivered;
                saveDB();
                renderTicket();
            }

            function setCourtesyQty(index, inputEl) {
                if (!currentTable.items || !currentTable.items[index]) return;
                let val = parseFloat(inputEl.value);
                if (isNaN(val) || val < 0) val = 0;
                const maxQty = parseFloat(currentTable.items[index].qty) || 0;
                if (val > maxQty) val = maxQty;
                currentTable.items[index].courtesyQty = val;
                inputEl.value = val;
                clampTakeawayQty(currentTable.items[index]);
                saveDB();
                renderTicket();
            }

            function changeCourtesyQty(index, delta) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const maxQty = parseFloat(item.qty) || 0;
                let v = (parseFloat(item.courtesyQty) || 0) + delta;
                v = Math.max(0, Math.min(maxQty, v));
                item.courtesyQty = v;
                clampTakeawayQty(item);
                saveDB();
                renderTicket();
            }
            window.setCourtesyQty = setCourtesyQty;
            window.changeCourtesyQty = changeCourtesyQty;

            function setTakeawayQty(index, inputEl) {
                if (!currentTable.items || !currentTable.items[index]) return;
                let val = parseFloat(inputEl.value);
                if (isNaN(val) || val < 0) val = 0;
                const maxQty = parseFloat(currentTable.items[index].qty) || 0;
                if (val > maxQty) val = maxQty;
                currentTable.items[index].takeawayQty = val;
                inputEl.value = val;
                saveDB();
                renderTicket();
            }

            function changeTakeawayQty(index, delta) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const maxQty = parseFloat(item.qty) || 0;
                let v = (parseFloat(item.takeawayQty) || 0) + delta;
                v = Math.max(0, Math.min(maxQty, v));
                item.takeawayQty = v;
                saveDB();
                renderTicket();
            }

            function toggleTakeawayInput(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const hasT = (parseFloat(item.takeawayQty) || 0) > 0;
                const shown = item.takeawayOpen === true || (item.takeawayOpen === undefined && hasT);
                item.takeawayOpen = !shown;
                saveDB();
                renderTicket();
            }
            window.setTakeawayQty = setTakeawayQty;
            window.changeTakeawayQty = changeTakeawayQty;
            window.toggleTakeawayInput = toggleTakeawayInput;

            function renderTicket() {
                const container = document.getElementById('cart-container');
                if (!container) {
                    return;
                }
                container.innerHTML = '';
                let subtotal = 0;

                if (!currentTable.items || currentTable.items.length === 0) {
                    container.innerHTML = `
                            <div class="flex flex-col items-center justify-center py-10 text-gray-400 opacity-60">
                                <i class="ri-restaurant-line text-4xl mb-3"></i>
                                <p class="font-medium text-sm">Sin productos en el pedido</p>
                            </div>`;
                } else {
                    // Badge de tipo de servicio + Toggle para "Para Llevar"
                    const serviceHeader = document.createElement('div');
                    const st = currentTable.service_type || 'IN_SITU';
                    const isDelivery = st === 'DELIVERY';
                    const isTakeAway = st === 'TAKE_AWAY';

                    let serviceLabel = '🏠 En Mesa';
                    let serviceColor = 'bg-blue-100/50 text-blue-700 border-blue-200';
                    if (isTakeAway) {
                        serviceLabel = '🥡 Para Llevar';
                        serviceColor = 'bg-orange-100 text-orange-700 border-orange-200';
                    } else if (isDelivery) {
                        serviceLabel = '🛵 Delivery';
                        serviceColor = 'bg-purple-100 text-purple-700 border-purple-200';
                    }

                    serviceHeader.className = `flex items-center justify-between px-3 py-2.5 mb-4 rounded-xl border ${serviceColor} shadow-sm`;
                    serviceHeader.innerHTML = `
                                <div class="flex flex-col">
                                    <span class="font-bold text-xs uppercase tracking-wider">${serviceLabel}</span>
                                    ${isTakeAway && currentTable.client_name_extra ? `<span class="text-[10px] font-normal lowercase italic text-orange-600">${escapeHtml(currentTable.client_name_extra)}</span>` : ''}
                                </div>

                               
                            `;
                    container.appendChild(serviceHeader);

                    const isComandado = !!currentTable.order_movement_id;
                    currentTable.items.forEach((item, index) => {
                        const prod = serverProducts.find(p => p.id === item.pId);
                        if (!prod) return;
                        const itemPrice = parseFloat(item.price) || 0;
                        const itemQty = parseInt(item.qty) || 0;
                        const courtesyQty = Math.min(parseFloat(item.courtesyQty) || 0, itemQty);
                        const paidQty = Math.max(itemQty - courtesyQty, 0);
                        const lineBase = itemPrice * paidQty;
                        const lineTotal = lineBase;
                        // si quieres que el subtotal ignore las cortesías:
                        subtotal += lineBase;
                        const noteTime = item.commandTime || "";
                        const noteText = typeof item.note === "string" ? item.note.trim() : "";
                        const hasNote = noteText !== "";
                        const row = document.createElement('div');
                        row.className = "cart-item-row group relative mb-3 rounded-xl overflow-hidden border border-slate-200 bg-white text-slate-900 shadow-md dark:border-zinc-600/50 dark:bg-[#252526] dark:text-zinc-100 dark:shadow-lg dark:shadow-black/40";

                        const productName = escapeHtml(prod.name || 'Sin nombre');
                        const itemNote = escapeHtml(noteText || '');
                        const isDelivered = !!item.delivered;

                        const statusLabel = isDelivered ? 'Entregado' : (isComandado ? 'Comandado' : 'Pendiente');
                        const statusClass = isDelivered
                            ? 'bg-emerald-500/20 text-emerald-600 border border-emerald-500/35 dark:text-emerald-400 dark:border-emerald-500/40'
                            : (isComandado ? 'bg-sky-500/15 text-sky-700 border border-sky-500/35 dark:text-sky-300 dark:border-sky-500/40' : 'bg-zinc-200/90 text-zinc-600 border border-zinc-300 dark:bg-zinc-700/60 dark:text-zinc-300 dark:border-zinc-600');

                        const rawSaved = item.savedQty != null && item.savedQty !== '' ? parseFloat(item.savedQty) : NaN;
                        const savedQtyItem = Number.isFinite(rawSaved) ? rawSaved : (isComandado ? (parseFloat(item.qty) || 0) : 0);
                        const canReduce = !isComandado || (parseFloat(item.qty) || 0) > savedQtyItem;

                        const qtyMinusDisabled = canReduce ? '' : ' disabled';
                        const qtyMinusClass = canReduce ? ' hover:bg-slate-100 dark:hover:bg-slate-700 font-bold' : ' opacity-30 cursor-not-allowed';
                        const qtyMinusOnclick = canReduce ? `onclick="updateQty(${index}, -1)"` : '';
                        const trashOnclick = `onclick="window.dispatchEvent(new CustomEvent('open-remove-quantity-modal', { detail: { index: ${index}, maxQty: ${itemQty}, productName: '${String(prod.name || 'Producto').replace(/\\/g, '\\\\').replace(/'/g, "\\'")}', isComandado: ${isComandado} } }))"`;
                        const hasCourtesy = (parseFloat(item.courtesyQty) || 0) > 0;
                        const showNoteBox = item.noteOpen === true || (item.noteOpen === undefined && hasNote);
                        const showCourtesyBox = item.courtesyOpen === true || (item.courtesyOpen === undefined && hasCourtesy);
                        const noteBtnActive = hasNote || showNoteBox;
                        const courtesyBtnActive = hasCourtesy || showCourtesyBox;
                        const courtesyMinusDisabled = courtesyQty <= 0 ? ' disabled' : '';
                        const courtesyMinusClass = courtesyQty <= 0 ? ' opacity-30 cursor-not-allowed' : ' hover:bg-slate-100 dark:hover:bg-zinc-700 font-bold';
                        const courtesyMinusOnclick = courtesyQty > 0 ? `onclick="changeCourtesyQty(${index}, -1)"` : '';
                        const courtesyPlusDisabled = courtesyQty >= itemQty ? ' disabled' : '';
                        const courtesyPlusClass = courtesyQty >= itemQty ? ' opacity-30 cursor-not-allowed' : ' hover:bg-slate-100 dark:hover:bg-zinc-700 font-bold';
                        const courtesyPlusOnclick = courtesyQty < itemQty ? `onclick="changeCourtesyQty(${index}, 1)"` : '';

                        const takeawayQty = Math.min(Math.max(parseFloat(item.takeawayQty) || 0, 0), itemQty);
                        const hasTakeaway = takeawayQty > 0;
                        const showTakeawayBox = item.takeawayOpen === true || (item.takeawayOpen === undefined && hasTakeaway);
                        const takeawayBtnActive = hasTakeaway || showTakeawayBox;
                        const takeawayMinusDisabled = !isDelivery && takeawayQty <= 0 ? ' disabled' : '';
                        const takeawayMinusClass = !isDelivery && takeawayQty <= 0 ? ' opacity-30 cursor-not-allowed' : ' hover:bg-slate-100 dark:hover:bg-zinc-700 font-bold';
                        const takeawayMinusOnclick = !isDelivery && takeawayQty > 0 ? `onclick="changeTakeawayQty(${index}, -1)"` : '';
                        const takeawayPlusDisabled = !isDelivery && takeawayQty >= itemQty ? ' disabled' : '';
                        const takeawayPlusClass = !isDelivery && takeawayQty >= itemQty ? ' opacity-30 cursor-not-allowed' : ' hover:bg-slate-100 dark:hover:bg-zinc-700 font-bold';
                        const takeawayPlusOnclick = !isDelivery && takeawayQty < itemQty ? `onclick="changeTakeawayQty(${index}, 1)"` : '';
                        const takeawayBadge = !isDelivery && hasTakeaway
                            ? `<span class="mt-1 inline-flex items-center gap-1 rounded-full bg-orange-500/15 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-orange-700 dark:text-orange-300"><i class="ri-shopping-bag-3-line"></i> ${takeawayQty} p. llevar</span>`
                            : '';

                        row.innerHTML = `
                                <div class="flex flex-col gap-3 p-3.5 sm:p-4">
                                        <div class="flex items-start justify-between gap-2">
                                            <div class="min-w-0 flex-1">
                                                <h3 class="font-bold text-[15px] sm:text-base leading-snug tracking-tight text-slate-900 dark:text-white">${productName}</h3>
                                                ${takeawayBadge}
                                                <p class="mt-1 text-[11px] sm:text-xs text-slate-500 dark:text-zinc-400 font-medium tabular-nums">${noteTime ? noteTime + ' · ' : ''}S/ ${itemPrice.toFixed(2)} <span class="text-slate-400 dark:text-zinc-500 font-normal">c/u</span></p>
                                            </div>
                                            <span class="shrink-0 px-2.5 py-1 rounded-full text-[9px] font-black uppercase tracking-wide ${statusClass}">${statusLabel}</span>
                                        </div>

                                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 rounded-lg bg-slate-50 border border-slate-200 px-2.5 py-2.5 dark:bg-zinc-900/50 dark:border-zinc-600/50">
                                            <div class="flex justify-center items-center gap-0.5 rounded-lg bg-white px-0.5 py-0.5 border border-slate-200 dark:bg-zinc-800/80 dark:border-zinc-600/60">
                                                <button type="button" ${qtyMinusOnclick} class="w-9 h-9 flex items-center justify-center rounded-md transition-all text-slate-600 dark:text-zinc-300 ${qtyMinusClass}"${qtyMinusDisabled}>
                                                    <i class="ri-subtract-line text-base"></i>
                                                </button>
                                                <input type="number" value="${item.qty}" min="1" onchange="setQtyFromInput(${index}, this)" class="w-11 h-9 text-center text-sm font-bold bg-transparent border-none focus:ring-0 focus:outline-none tabular-nums text-slate-900 dark:text-white p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none" ${canReduce ? '' : 'readonly'}>
                                                <button type="button" onclick="updateQty(${index}, 1)" class="w-9 h-9 flex items-center justify-center rounded-md hover:bg-slate-100 dark:hover:bg-zinc-700 text-slate-600 dark:text-zinc-300 transition-all font-bold">
                                                    <i class="ri-add-line text-base"></i>
                                                </button>
                                            </div>
                                            <div class="text-center sm:text-right flex flex-col justify-center">
                                                <span class="text-[10px] text-slate-500 dark:text-zinc-500 uppercase font-bold tracking-wider leading-none mb-0.5">Subtotal</span>
                                                <span class="text-lg font-bold tabular-nums leading-none text-slate-900 dark:text-white">S/ ${lineTotal.toFixed(2)}</span>
                                            </div>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 border-t border-slate-200 pt-2.5 dark:border-zinc-700/60">
                                            <button type="button" onclick="toggleDelivered(${index})" class="inline-flex shrink-0 items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-500 dark:text-sky-400 dark:hover:text-sky-300 transition-colors">
                                                <i class="${isDelivered ? 'ri-check-double-line' : 'ri-checkbox-blank-circle-line'}"></i>
                                                ${isDelivered ? 'Entregado' : 'Pendiente'}
                                            </button>
                                            <button type="button" onclick="toggleNoteInput(${index})" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium transition-colors ${noteBtnActive ? 'bg-blue-50 text-blue-700 dark:bg-sky-500/15 dark:text-sky-300' : 'text-slate-500 hover:bg-slate-100 hover:text-blue-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-sky-400'}">
                                                <i class="${hasNote ? 'ri-chat-1-fill' : 'ri-chat-1-line'}"></i> ${hasNote ? 'Editar nota' : 'Nota'}
                                            </button>
                                            <button type="button" onclick="toggleCourtesyInput(${index})" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium transition-colors ${courtesyBtnActive ? 'bg-emerald-50 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'text-slate-500 hover:bg-slate-100 hover:text-emerald-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-emerald-400'}">
                                                <i class="${courtesyBtnActive ? 'ri-star-fill' : 'ri-star-line'}"></i> Cortesía
                                            </button>
                                            ${isDelivery ? '' : `<button type="button" onclick="toggleTakeawayInput(${index})" class="inline-flex shrink-0 items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium transition-colors ${takeawayBtnActive ? 'bg-orange-50 text-orange-800 dark:bg-orange-500/15 dark:text-orange-300' : 'text-slate-500 hover:bg-slate-100 hover:text-orange-600 dark:text-zinc-400 dark:hover:bg-zinc-800 dark:hover:text-orange-400'}">
                                                <i class="${takeawayBtnActive ? 'ri-shopping-bag-3-fill' : 'ri-shopping-bag-3-line'}"></i> Llevar
                                            </button>`}
                                            <button type="button" ${trashOnclick} class="ml-auto flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-zinc-400 transition-colors hover:bg-red-500/10 hover:text-red-500 dark:text-zinc-500 dark:hover:text-red-400" title="Quitar o anular cantidad">
                                                <i class="ri-delete-bin-line text-lg"></i>
                                            </button>
                                        </div>

                                        <div id="note-box-${index}" class="${showNoteBox ? '' : 'hidden'}">
                                            <textarea rows="2" onblur="saveNote(${index}, this.value)" placeholder="Ej: Sin cebolla, término medio..." class="w-full min-h-[3.25rem] resize-y rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-800 placeholder:text-slate-400 focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 dark:border-zinc-600 dark:bg-zinc-900/80 dark:text-zinc-100 dark:placeholder:text-zinc-500 dark:focus:border-sky-500">${itemNote}</textarea>
                                        </div>

                                        <div id="courtesy-box-${index}" class="${showCourtesyBox ? '' : 'hidden'}">
                                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2.5 dark:border-zinc-600/50 dark:bg-zinc-900/40">
                                                <span class="text-[11px] font-semibold text-slate-700 dark:text-zinc-300">Cortesía <span class="font-normal text-slate-500 dark:text-zinc-500">(sin cargo)</span></span>
                                                <div class="flex items-center gap-0.5 rounded-lg bg-white px-0.5 py-0.5 border border-slate-200 dark:bg-zinc-800/90 dark:border-zinc-600/60">
                                                    <button type="button" ${courtesyMinusOnclick} class="w-8 h-8 flex items-center justify-center rounded-md transition-all text-slate-700 dark:text-zinc-200${courtesyMinusClass}"${courtesyMinusDisabled}>
                                                        <i class="ri-subtract-line text-sm"></i>
                                                    </button>
                                                    <input type="number" min="0" max="${itemQty}" value="${courtesyQty}" onchange="setCourtesyQty(${index}, this)" class="w-10 h-8 text-center text-sm font-bold bg-transparent border-none focus:ring-0 focus:outline-none tabular-nums text-slate-900 dark:text-white p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                    <button type="button" ${courtesyPlusOnclick} class="w-8 h-8 flex items-center justify-center rounded-md transition-all text-slate-700 dark:text-zinc-200${courtesyPlusClass}"${courtesyPlusDisabled}>
                                                        <i class="ri-add-line text-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>

                                        ${isDelivery ? '' : `
                                        <div id="takeaway-box-${index}" class="${showTakeawayBox ? '' : 'hidden'}">
                                            <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-orange-200 bg-orange-50/80 px-3 py-2.5 dark:border-orange-700/50 dark:bg-orange-950/30">
                                                <span class="text-[11px] font-semibold text-slate-800 dark:text-zinc-200">Para llevar <span class="font-normal text-slate-600 dark:text-zinc-300">(de ${itemQty} u.)</span></span>
                                                <div class="flex items-center gap-0.5 rounded-lg bg-white px-0.5 py-0.5 border border-orange-200 dark:bg-zinc-800/90 dark:border-orange-700/50">
                                                    <button type="button" ${takeawayMinusOnclick} class="w-8 h-8 flex items-center justify-center rounded-md transition-all text-slate-700 dark:text-zinc-200${takeawayMinusClass}"${takeawayMinusDisabled}>
                                                        <i class="ri-subtract-line text-sm"></i>
                                                    </button>
                                                    <input type="number" min="0" max="${itemQty}" value="${takeawayQty}" onchange="setTakeawayQty(${index}, this)" class="w-10 h-8 text-center text-sm font-bold bg-transparent border-none focus:ring-0 focus:outline-none tabular-nums text-slate-900 dark:text-white p-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                                    <button type="button" ${takeawayPlusOnclick} class="w-8 h-8 flex items-center justify-center rounded-md transition-all text-slate-700 dark:text-zinc-200${takeawayPlusClass}"${takeawayPlusDisabled}>
                                                        <i class="ri-add-line text-sm"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>`}
                                </div>
                            `;
                        container.appendChild(row);
                    });
                }
                const totals = getTotalsWithDelivery(currentTable.items || []);
                const tax = totals.tax;
                const total = totals.total;
                subtotal = totals.subtotal;
                const deliveryFee = totals.deliveryFee;
                const takeawayDispFee = totals.takeawayDisposableFee ?? 0;

                const subtotalEl = document.getElementById('ticket-subtotal');
                const taxEl = document.getElementById('ticket-tax');
                const totalEl = document.getElementById('ticket-total');
                const deliveryRow = document.getElementById('ticket-delivery-row');
                const deliveryEl = document.getElementById('ticket-delivery');
                const takeawayDispRow = document.getElementById('ticket-takeaway-disposable-row');
                const takeawayDispEl = document.getElementById('ticket-takeaway-disposable');

                if (subtotalEl) subtotalEl.innerText = `$${subtotal.toFixed(2)}`;
                if (taxEl) taxEl.innerText = `$${tax.toFixed(2)}`;
                if (deliveryRow && deliveryEl) {
                    if (currentTable?.service_type === 'DELIVERY' && deliveryFee > 0) {
                        deliveryRow.classList.remove('hidden');
                        deliveryEl.innerText = `$${deliveryFee.toFixed(2)}`;
                    } else {
                        deliveryRow.classList.add('hidden');
                    }
                }
                if (takeawayDispRow && takeawayDispEl) {
                    if (takeawayDispFee > 0) {
                        takeawayDispRow.classList.remove('hidden');
                        takeawayDispEl.innerText = `$${takeawayDispFee.toFixed(2)}`;
                    } else {
                        takeawayDispRow.classList.add('hidden');
                    }
                }
                if (totalEl) totalEl.innerText = `$${total.toFixed(2)}`;

                syncTakeawayDisposablePanel();
                syncCobroAmountsWithCart(total);
                renderCancelledSection();
                if (typeof updateMobileSummary === 'function') updateMobileSummary();
            }

            function syncCobroAmountsWithCart(orderTotal) {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return;
                const rows = list.querySelectorAll('.cobro-pm-row');
                if (rows.length === 0) return;
                if (rows.length === 1) {
                    const input = rows[0].querySelector('.cobro-pm-amount');
                    if (input) input.value = orderTotal.toFixed(2);
                } else {
                    const totalPaid = Array.from(rows).slice(1).reduce((s, row) => {
                        const inp = row.querySelector('.cobro-pm-amount');
                        return s + (parseFloat(inp?.value || 0) || 0);
                    }, 0);
                    const firstInput = rows[0].querySelector('.cobro-pm-amount');
                    if (firstInput) firstInput.value = Math.max(0, orderTotal - totalPaid).toFixed(2);
                }
                if (typeof updateCobroTotalPaid === 'function') updateCobroTotalPaid();
            }

            function renderCancelledSection() {
                const container = document.getElementById('cancelled-platos-container');
                const listEl = document.getElementById('cancelled-platos-list');
                if (!container || !listEl) return;

                const hasSavedOrder = !!currentTable.order_movement_id;
                const isCurrentPendingOrder = hasSavedOrder && (currentTable.order_movement_id === serverOrderMovementId);
                const serverCancelled = (isCurrentPendingOrder && serverPendingCancelledDetails && serverPendingCancelledDetails.length) ? serverPendingCancelledDetails : [];
                const clientCancelled = currentTable.cancellations || [];
                const hasAny = serverCancelled.length > 0 || clientCancelled.length > 0;

                if (!hasSavedOrder || !hasAny) {
                    container.classList.add('hidden');
                    listEl.innerHTML = '';
                    return;
                }

                container.classList.remove('hidden');
                let html = '';
                serverCancelled.forEach(function (d) {
                    const desc = escapeHtml(d.description || 'Producto');
                    const qty = d.quantity != null ? d.quantity : 1;
                    const reason = escapeHtml((d.comment || '').trim() || '—');
                    html += `<div class="rounded border border-amber-200 dark:border-amber-700/50 p-1.5 bg-white/60 dark:bg-gray-800/60"><span class="font-medium text-slate-700 dark:text-slate-200">${desc}</span> <span class="text-amber-700 dark:text-amber-300">×${qty}</span><br><span class="text-amber-800 dark:text-amber-200 italic">${reason}</span></div>`;
                });
                clientCancelled.forEach(function (c) {
                    const name = escapeHtml(c.name || 'Producto');
                    const qty = c.qtyCanceled != null ? c.qtyCanceled : 1;
                    const reason = escapeHtml((c.cancel_reason || '').trim() || '—');
                    html += `<div class="rounded border border-amber-200 dark:border-amber-700/50 p-1.5 bg-white/60 dark:bg-gray-800/60"><span class="font-medium text-slate-700 dark:text-slate-200">${name}</span> <span class="text-amber-700 dark:text-amber-300">×${qty}</span><br><span class="text-amber-800 dark:text-amber-200 italic">${reason}</span></div>`;
                });
                listEl.innerHTML = html;
            }

            /** Devuelve los ítems del pedido agrupados por producto (mismo pId → un ítem con qty sumada) para enviar al servidor. */
            function getItemsGroupedByProduct() {
                const items = currentTable.items || [];
                const byPid = {};
                items.forEach((it) => {
                    const id = parseInt(it.pId, 10) || 0;
                    if (!id) return;
                    if (!byPid[id]) {
                        byPid[id] = {
                            pId: it.pId,
                            name: it.name,
                            price: parseFloat(it.price) || 0,
                            tax_rate: it.tax_rate,
                            note: it.note || '',
                            commandTime: it.commandTime || null,
                            delivered: !!it.delivered,
                            courtesyQty: 0,
                            takeawayQty: 0,
                            qty: 0
                        };
                    }
                    byPid[id].qty = (byPid[id].qty || 0) + (parseInt(it.qty, 10) || 1);
                    byPid[id].courtesyQty = (byPid[id].courtesyQty || 0) + (parseInt(it.courtesyQty) || 0);
                    byPid[id].takeawayQty = (byPid[id].takeawayQty || 0) + (parseFloat(it.takeawayQty) || 0);
                    if (it.delivered) byPid[id].delivered = true;
                });
                const st = currentTable?.service_type || 'IN_SITU';
                const vals = Object.values(byPid);
                vals.forEach((v) => {
                    const q = parseFloat(v.qty) || 0;
                    let t = parseFloat(v.takeawayQty) || 0;
                    if (t > q) t = q;
                    if (t < 0) t = 0;
                    v.takeawayQty = st === 'DELIVERY' ? q : t;
                });
                return vals;
            }

            function saveDB() {
                if (db && currentTable) {
                    // Agregar timestamp para saber cuándo se guardó
                    currentTable.lastUpdated = new Date().toISOString();
                    currentTable.isActive = true;
                    db[activeKey] = currentTable;
                    localStorage.setItem('restaurantDB', JSON.stringify(db));
                    const hasItems = currentTable.items && currentTable.items.length > 0;
                    const hasCancels = currentTable.cancellations && currentTable.cancellations.length > 0;
                    if (hasItems || hasCancels) {
                        // scheduleAutoSave(); // Deshabilitado auto-guardado automático al servidor
                    }
                }
            }

            function goToIndexWithTurbo() {
                const base = "{{ route('orders.index') }}";
                const params = new URLSearchParams(window.location.search);
                const viewId = params.get('view_id');
                let url = base + '?_=' + Date.now();
                if (viewId) url += '&view_id=' + encodeURIComponent(viewId);
                const win = (typeof window.top !== 'undefined' ? window.top : window);
                try {
                    win.location.replace(url);
                } catch (e1) {
                    try {
                        win.location.href = url;
                    } catch (e2) {
                        const a = document.createElement('a');
                        a.href = url;
                        a.target = '_top';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                }
            }

            function isMesaYaCobradaMessage(msg) {
                if (!msg || typeof msg !== 'string') return false;
                const m = msg.toLowerCase();
                return m.indexOf('ya fue cobrada') !== -1 || m.indexOf('ya fue cobrado') !== -1;
            }

            function isNoActiveShiftMessage(msg) {
                if (!msg || typeof msg !== 'string') return false;
                const m = msg.toLowerCase();
                return m.indexOf('no hay un turno activo') !== -1
                    || m.indexOf('apertura de caja') !== -1
                    || m.indexOf('apertura de caja primero') !== -1;
            }

            // Limpiar auto-guardado al navegar con Turbo para no dispararlo en otra página
            document.addEventListener('turbo:before-visit', function () {
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }
            });

            function scheduleAutoSave() {
                if (autoSaveTimer) clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(autoSaveToServer, 1500);
            }

            function autoSaveToServer() {
                autoSaveTimer = null;
                const items = currentTable.items || [];
                if (items.length === 0 && (!currentTable.cancellations || currentTable.cancellations.length === 0)) return;
                // No auto-guardar si hay cancelaciones pendientes de razón (se pide al hacer Guardar / Cobrar)
                const cancels = currentTable.cancellations || [];
                if (cancels.some(c => !(c.cancel_reason && String(c.cancel_reason).trim()))) return;
                const itemsToSend = getItemsGroupedByProduct();
                const totals = calculateTotalsFromItems(itemsToSend);
                const order = {
                    items: itemsToSend,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: totals.subtotal,
                    tax: totals.tax,
                    total: totals.total,
                    people_count: currentTable.people_count ?? 0,
                    client_id: currentTable.person_id ?? null,
                    client_name: (currentTable.service_type === 'TAKE_AWAY' && currentTable.client_name_extra) ? currentTable.client_name_extra : (currentTable.clientName || null),
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    takeaway_disposable_charge: !!currentTable.takeaway_disposable_charge,
                    takeaway_disposable_amount: parseFloat(currentTable.takeaway_disposable_amount) || 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };
                fetch('{{ route('orders.process') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(order)
                })
                    .then(res => res.headers.get('content-type')?.includes('application/json') ? res.json() : Promise.reject(new Error('Respuesta inválida')))
                    .then(data => {
                        if (data && data.success) {
                            currentTable.cancellations = [];
                            saveDB();
                        } else if (data && isMesaYaCobradaMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                            }
                        }
                    })
                    .catch(() => { });
            }

            /** Pide una sola razón de anulación y la asigna a todas las cancelaciones que no la tengan. Devuelve false si el usuario cancela. */
            async function ensureCancellationReasons() {
                const cancels = currentTable.cancellations || [];
                const pending = cancels.filter(c => !(c.cancel_reason && String(c.cancel_reason).trim()));
                if (pending.length === 0) return true;

                let reason = null;
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Razón de anulación',
                        html: `Hay <strong>${pending.length}</strong> ítem(s) anulados. Indica la razón (aplica a todos).`,
                        input: 'textarea',
                        inputPlaceholder: 'Escribe la razón de la anulación...',
                        showCancelButton: true,
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) return 'Debes ingresar una razón';
                            return null;
                        }
                    });
                    if (!result.isConfirmed || !result.value) return false;
                    reason = result.value.trim();
                } else {
                    const p = window.prompt('Razón de anulación (aplica a todos los ítems anulados):');
                    if (!p || !p.trim()) return false;
                    reason = p.trim();
                }

                cancels.forEach(c => {
                    if (!(c.cancel_reason && String(c.cancel_reason).trim())) c.cancel_reason = reason;
                });
                return true;
            }

            async function processOrder() {
                if (waiterPinEnabled) {
                    const ok = await ensureWaiterPin();
                    if (!ok) return;
                }
                const okReason = await ensureCancellationReasons();
                if (!okReason) return;
                const btnGuardar = document.getElementById('btn-guardar');
                if (btnGuardar) { btnGuardar.disabled = true; }
                let items = getItemsGroupedByProduct();

                // Hora de comanda: solo se fija la primera vez; la nota va siempre solo como texto.
                const now = new Date();
                const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
                items.forEach((it) => {
                    if (!it) return;
                    if (!it.commandTime) it.commandTime = timeString;
                });

                const totals = calculateTotalsFromItems(items);
                const subtotal = totals.subtotal;
                const tax = totals.tax;
                const total = totals.total;

                const order = {
                    items: items,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: subtotal,
                    tax: tax,
                    total: total,
                    people_count: currentTable.people_count ?? 0,
                    client_id: currentTable.person_id ?? null,
                    client_name: (currentTable.service_type === 'TAKE_AWAY' && currentTable.client_name_extra) ? currentTable.client_name_extra : (currentTable.clientName || null),
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    takeaway_disposable_charge: !!currentTable.takeaway_disposable_charge,
                    takeaway_disposable_amount: parseFloat(currentTable.takeaway_disposable_amount) || 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };
                fetch('{{ route('orders.process') }}', {
                    method: 'POST',
                    cache: 'no-store',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                            'content') ||
                            '',
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(order)
                })
                    .then(async (response) => {
                        const ct = response.headers.get('content-type');
                        if (ct && ct.includes('application/json')) {
                            return response.json();
                        }
                        throw new Error(response.status === 419 ? 'Sesión expirada. Recarga la página.' : (response.status === 401 ? 'Debes iniciar sesión.' : 'Error del servidor. Intenta de nuevo.'));
                    })
                    .then(async data => {
                        if (data && data.success) {
                            try {
                                await printKitchenTickets(items, currentTable);
                            } catch (pzErr) {
                                console.error('QZ Tray:', pzErr);
                            }
                            // Limpiar cancelaciones ya persistidas
                            currentTable.cancellations = [];
                            saveDB();
                            sessionStorage.setItem('flash_success_message', data.message);
                            goToIndexWithTurbo();
                        } else if (data && isMesaYaCobradaMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                            } else {
                                alert(data.message || 'Esta mesa ya fue cobrada.');
                            }
                        } else if (data && data.message && String(data.message).indexOf('PIN') !== -1) {
                            sessionStorage.removeItem(`waiterPin:${waiterPinBranchId}`);
                            if (typeof showNotification === 'function') {
                                showNotification('PIN requerido', data.message || 'Ingrese el PIN del mozo e intente guardar de nuevo.', 'error');
                            } else {
                                sessionStorage.setItem('flash_error_message', data.message || 'Ingrese el PIN del mozo e intente guardar de nuevo.');
                            }
                        } else if (data && isNoActiveShiftMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Caja cerrada', data.message || 'No hay un turno activo. Realice una Apertura de Caja primero.', 'error');
                            } else {
                                sessionStorage.setItem('flash_error_message', data.message || 'No hay un turno activo. Realice una Apertura de Caja primero.');
                            }
                        } else {
                            console.error('Error al guardar:', data);
                            sessionStorage.setItem('flash_error_message', data?.message || 'Error al guardar.');
                        }
                        if (btnGuardar) { btnGuardar.disabled = false; }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sessionStorage.setItem('flash_error_message', 'Error al guardar el pedido. Revisa la consola.');
                        if (btnGuardar) { btnGuardar.disabled = false; }
                    });

            }

            function getCobroPaymentMethodsFromForm() {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return [];
                const result = [];
                const rows = list.querySelectorAll('.cobro-pm-row');
                rows.forEach(row => {
                    const methodSelect = row.querySelector('.cobro-pm-method');
                    const input = row.querySelector('.cobro-pm-amount');
                    if (!methodSelect || !input) return;
                    const pmId = parseInt(methodSelect.value, 10);
                    const amount = parseFloat(String(input.value || 0).replace(',', '.')) || 0;
                    if (!pmId || amount <= 0) return;
                    const obj = { payment_method_id: pmId, amount };
                    const desc = (methodSelect.options[methodSelect.selectedIndex]?.text || '').toLowerCase();
                    const isCard = (desc.includes('tarjeta') || desc.includes('card')) && !desc.includes('billetera');
                    const isWallet = desc.includes('billetera');
                    const isTransfer = desc.includes('transferencia') || desc.includes('transfer') || desc.includes('deposito') || desc.includes('depósito');
                    if (isCard) {
                        const gw = row.querySelector('.cobro-pm-gateway');
                        const card = row.querySelector('.cobro-pm-card');
                        if (gw?.value) obj.payment_gateway_id = parseInt(gw.value, 10);
                        if (card?.value) obj.card_id = parseInt(card.value, 10);
                    }
                    if (isWallet) {
                        const wallet = row.querySelector('.cobro-pm-wallet');
                        if (wallet?.value) obj.digital_wallet_id = parseInt(wallet.value, 10);
                    }
                    if (isTransfer) {
                        const bank = row.querySelector('.cobro-pm-bank');
                        if (bank?.value) obj.bank_id = parseInt(bank.value, 10);
                    }
                    result.push(obj);
                });
                return result;
            }

            function getCobroTotalPaid() {
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let total = 0;
                inputs.forEach(inp => {
                    total += parseFloat(String(inp.value || 0).replace(',', '.')) || 0;
                });
                return total;
            }

            async function sendThermalTicketAfterSale(movementId, saleResponse) {
                if (!movementId) return;
                const qzApi = window.qz;
                const sel = document.getElementById('cobro-thermal-printer');
                const printerId = sel && sel.value ? parseInt(sel.value, 10) : null;
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const body = { movement_id: movementId };
                if (printerId) body.printer_id = printerId;

                // Si QZ Tray está activo, obtener el payload del servidor e imprimir por QZ (USB o red)
                if (qzApi) {
                    try {
                        const tr = await fetch(salesThermalPrintUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                            credentials: 'same-origin',
                            body: JSON.stringify({ ...body, mode: 'qz' })
                        });
                        const td = tr.headers.get('content-type')?.includes('application/json') ? await tr.json() : null;
                        if (!tr.ok || !td?.success || !td?.payload_b64) {
                            if (typeof showNotification === 'function')
                                showNotification('Impresión', td?.message || 'No se pudo obtener el ticket del servidor.', 'error');
                            return;
                        }
                        if (!qzApi.websocket.isActive()) await qzApi.websocket.connect();
                        let printerName = td.printer_name || '';
                        if (!printerName) printerName = await qzApi.printers.getDefault();
                        if (!printerName) {
                            if (typeof showNotification === 'function')
                                showNotification('Impresión', 'No se encontró ninguna impresora en QZ Tray.', 'error');
                            return;
                        }
                        const paperMm = (parseInt(td.paper_width) || 58) === 80 ? 80 : 58;
                        const config = qzApi.configs.create(printerName, { units: 'mm', size: { width: paperMm, height: 200 }, scaleContent: false });
                        await qzApi.print(config, [{ type: 'raw', format: 'base64', data: td.payload_b64 }]);
                        if (typeof showNotification === 'function')
                            showNotification('Impresión', 'Ticket enviado a "' + printerName + '".', 'success');
                    } catch (e) {
                        console.warn('QZ Ticket:', e);
                        if (typeof showNotification === 'function')
                            showNotification('Impresión', 'Error al imprimir con QZ Tray: ' + (e?.message || e), 'error');
                    }
                    return;
                }

                // Fallback: impresión TCP por red (requiere red local e IP en impresora)
                try {
                    const tr = await fetch(salesThermalPrintUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                        credentials: 'same-origin',
                        body: JSON.stringify(body)
                    });
                    const td = tr.headers.get('content-type')?.includes('application/json') ? await tr.json() : null;
                    if (tr.ok && td?.success) {
                        if (typeof showNotification === 'function')
                            showNotification('Impresión', td.message || 'Ticket enviado a la ticketera.', 'success');
                    } else {
                        const msg = td?.message || 'No se pudo enviar el ticket a la ticketera.';
                        console.warn('Ticketera red:', msg);
                        if (typeof showNotification === 'function')
                            showNotification('Impresión', msg, 'error');
                    }
                } catch (e) {
                    console.warn('Ticketera red:', e);
                    if (typeof showNotification === 'function')
                        showNotification('Impresión', 'Error de red al conectar con la ticketera.', 'error');
                }
            }

            async function processOrderPayment() {
                if (waiterPinEnabled) {
                    const ok = await ensureWaiterPin();
                    if (!ok) return;
                }
                const items = currentTable.items || [];
                if (items.length === 0) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'Agrega productos a la orden antes de cobrar.', 'error');
                    } else {
                        sessionStorage.setItem('flash_error_message', 'Agrega productos a la orden antes de cobrar.');
                    }
                    return;
                }
                const totals = getTotalsWithDelivery(items);
                const total = totals.total;
                const paymentMethodsData = getCobroPaymentMethodsFromForm();
                const totalPaid = paymentMethodsData.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);

                if (paymentMethodsData.length === 0) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'Agrega al menos un método de pago.', 'error');
                    } else {
                        alert('Agrega al menos un método de pago.');
                    }
                    return;
                }
                if (Math.abs(totalPaid - total) > 0.01) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'La suma de los métodos de pago debe ser igual al total (S/ ' + total.toFixed(2) + ').', 'error');
                    } else {
                        alert('La suma de los métodos de pago debe ser igual al total (S/ ' + total.toFixed(2) + ').');
                    }
                    return;
                }

                if (window.Swal) {
                    const confirmPayment = await Swal.fire({
                        title: 'Confirmar Cobro',
                        text: `Total a cobrar: S/ ${total.toFixed(2)}. ¿Deseas proceder?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, cobrar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmPayment.isConfirmed) return;
                }

                const okReason = await ensureCancellationReasons();
                if (!okReason) return;
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

                const itemsToSend = getItemsGroupedByProduct();
                const productTotals = calculateTotalsFromItems(itemsToSend);
                const processPayload = {
                    items: itemsToSend,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: productTotals.subtotal,
                    tax: productTotals.tax,
                    total: productTotals.total,
                    people_count: currentTable.people_count ?? 0,
                    client_id: currentTable.person_id ?? null,
                    client_name: (currentTable.service_type === 'TAKE_AWAY' && currentTable.client_name_extra) ? currentTable.client_name_extra : (currentTable.clientName || null),
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    takeaway_disposable_charge: !!currentTable.takeaway_disposable_charge,
                    takeaway_disposable_amount: parseFloat(currentTable.takeaway_disposable_amount) || 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };

                let btn = document.querySelector('button[onclick*="processOrderPayment"]');
                if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-base"></i><span>Procesando...</span>'; }

                try {
                    let movementId = currentTable.movement_id;

                    if (!movementId) {
                        const processRes = await fetch('{{ route('orders.process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(processPayload)
                        });
                        const ct = processRes.headers.get('content-type');
                        let processData = null;
                        if (ct && ct.includes('application/json')) {
                            processData = await processRes.json();
                        } else {
                            throw new Error(processRes.status === 419 ? 'Sesión expirada. Recarga la página.' : (processRes.status === 401 ? 'Debes iniciar sesión.' : (processRes.status === 500 ? 'Error al procesar. Intenta de nuevo.' : 'Error del servidor.')));
                        }
                        if (!processData || !processData.success || !processData.movement_id) {
                            throw new Error(processData?.message || 'No se pudo guardar el pedido. Intenta de nuevo.');
                        }
                        movementId = processData.movement_id;
                        currentTable.cancellations = [];
                        currentTable.order_movement_id = processData.order_movement_id;
                        currentTable.movement_id = movementId;
                        saveDB();
                    }

                    const docTypeEl = document.getElementById('cobro-document-type');
                    const cashRegEl = document.getElementById('cobro-cash-register');
                    const paymentPayload = {
                        movement_id: movementId,
                        table_id: currentTable.table_id ?? currentTable.id,
                        document_type_id: docTypeEl?.value ? parseInt(docTypeEl.value, 10) : null,
                        cash_register_id: cashRegEl?.value ? parseInt(cashRegEl.value, 10) : null,
                        client_id: currentTable.person_id ?? null,
                        client_name: currentTable.clientName || null,
                        payment_methods: paymentMethodsData,
                        notes: '',
                    };

                    const payRes = await fetch('{{ route('orders.processOrderPayment') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(paymentPayload)
                    });

                    const payData = payRes.headers.get('content-type')?.includes('application/json') ? await payRes.json() : null;
                    if (!payRes.ok) {
                        throw new Error(payData?.message || payData?.error || 'Error al procesar el cobro.');
                    }
                    if (!payData?.success) {
                        throw new Error(payData?.message || payData?.error || 'Error al procesar el cobro.');
                    }

                    const payMovementId = payData?.movement_id;
                    await sendThermalTicketAfterSale(payMovementId, payData);

                    if (db && activeKey && db[activeKey]) {
                        delete db[activeKey];
                        localStorage.setItem('restaurantDB', JSON.stringify(db));
                    }
                    sessionStorage.setItem('flash_success_message', payData.message || 'Cobro de pedido procesado correctamente');
                    const indexUrl = "{{ route('orders.index') }}";
                    setTimeout(() => {
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(indexUrl, { action: 'replace' });
                        } else {
                            window.location.href = indexUrl;
                        }
                    }, 600);
                } catch (error) {
                    console.error('Error:', error);
                    if (String(error?.message || '').indexOf('PIN') !== -1) {
                        sessionStorage.removeItem(`waiterPin:${waiterPinBranchId}`);
                    }
                    if (typeof showNotification === 'function') {
                        showNotification('Error', error?.message || 'Error al procesar.', 'error');
                    } else {
                        alert(error?.message || 'Error al procesar.');
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ri-bank-card-line text-base"></i><span>Cobrar</span>'; }
                }
            }

            function releaseTableAndGoBack() {
                const tableId = currentTable?.table_id ?? currentTable?.id ?? {{ $table->id }};
                const url = "{{ route('orders.cancelOrder') }}";
                const indexUrl = "{{ route('orders.index') }}";
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ table_id: tableId }),
                })
                    .then(async (r) => {
                        if (r.headers.get('content-type')?.includes('application/json')) {
                            return r.json();
                        }
                        return null;
                    })
                    .then((data) => {
                        // Limpiar borrador local de esta mesa para que no reaparezca como guardada
                        if (db && activeKey && db[activeKey]) {
                            delete db[activeKey];
                            localStorage.setItem('restaurantDB', JSON.stringify(db));
                        }
                        if (currentTable) {
                            currentTable.items = [];
                            currentTable.order_movement_id = null;
                            currentTable.movement_id = null;
                            currentTable.isActive = false;
                        }
                    })
                    .catch(() => {
                        /* seguir igual, redirigir */
    })
                    .finally(() => {
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(indexUrl, {
                                action: 'replace'
                            });
                        } else {
                            window.location.href = indexUrl;
                        }
                    });
            }

            function goBack() {
                //Solo regresar a ordenes, con turbo
                if (window.Turbo && typeof window.Turbo.visit === 'function') {
                    window.Turbo.visit("{{ route('orders.index') }}", {
                        action: 'replace'
                    });
                } 
            }

            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            function showAddToCartNotification(productName) {
                const notification = document.getElementById('add-to-cart-notification');
                const productNameEl = document.getElementById('notification-product-name');
                if (!notification || !productNameEl) return;
                productNameEl.textContent = productName;
                notification.classList.add('notification-show');
                setTimeout(() => notification.classList.remove('notification-show'), 1200);
            }

            function showNotification(title, message, type = 'info') {
                const notification = document.getElementById('notification');
                if (!notification) return;
                const isError = type === 'error';
                notification.innerHTML = `
                            <div class="rounded-xl border p-4 shadow-lg ${isError ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'}">
                                <div class="flex items-start gap-3">
                                    <div class="${isError ? 'text-red-500' : 'text-green-500'}"><i class="fas fa-${isError ? 'exclamation-circle' : 'check-circle'} text-xl"></i></div>
                                    <div>
                                        <h3 class="font-semibold ${isError ? 'text-red-800 dark:text-red-200' : 'text-green-800 dark:text-green-200'}">${title}</h3>
                                        <p class="text-sm mt-1 ${isError ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300'}">${message}</p>
                                    </div>
                                </div>
                            </div>
                        `;
                notification.classList.remove('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'pointer-events-none');
                }, 3500);
            }

            function updateDiners(delta) {
                const input = document.getElementById('diners-input');
                if (!input) return;
                let value = delta === 0
                    ? parseInt(input.value, 10)
                    : parseInt(input.value, 10) + delta;
                if (isNaN(value) || value < 1) value = 1;
                input.value = value;
                if (currentTable) {
                    currentTable.people_count = value;
                    if (db && activeKey) {
                        db[activeKey] = currentTable;
                        localStorage.setItem('restaurantDB', JSON.stringify(db));
                    }
                }
            }

            function switchAsideTab(tab) {
                const resumen = document.getElementById('aside-resumen');
                const cobro = document.getElementById('aside-cobro');
                const btnResumen = document.getElementById('tab-resumen');
                const btnCobro = document.getElementById('tab-cobro');
                const footerResumen = document.getElementById('footer-resumen');
                const footerCobro = document.getElementById('footer-cobro');
                const productsGrid = document.getElementById('products-grid');
                const categoriesGrid = document.getElementById('categories-grid');
                const searchInput = document.getElementById('search-products');
                if (tab === 'cobro') {
                    resumen?.classList.add('hidden');
                    cobro?.classList.remove('hidden');
                    cobro?.classList.add('flex');
                    footerResumen?.classList.add('hidden');
                    footerCobro?.classList.remove('hidden');
                    btnResumen?.classList.remove('bg-brand-500', 'text-white');
                    btnResumen?.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnCobro?.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnCobro?.classList.add('bg-brand-500', 'text-white');
                    // Deshabilitar agregar/modificar productos mientras se está en Cobro
                    if (productsGrid) {
                        productsGrid.classList.add('pointer-events-none', 'opacity-60');
                    }
                    if (categoriesGrid) {
                        categoriesGrid.classList.add('pointer-events-none', 'opacity-60');
                    }
                    if (searchInput) {
                        searchInput.setAttribute('disabled', 'disabled');
                        searchInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                    }
                } else {
                    cobro?.classList.add('hidden');
                    cobro?.classList.remove('flex');
                    resumen?.classList.remove('hidden');
                    footerCobro?.classList.add('hidden');
                    footerResumen?.classList.remove('hidden');
                    btnCobro?.classList.remove('bg-brand-500', 'text-white');
                    btnCobro?.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnResumen?.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnResumen?.classList.add('bg-brand-500', 'text-white');
                    // Volver a habilitar productos al regresar a Resumen
                    if (productsGrid) {
                        productsGrid.classList.remove('pointer-events-none', 'opacity-60');
                    }
                    if (categoriesGrid) {
                        categoriesGrid.classList.remove('pointer-events-none', 'opacity-60');
                    }
                    if (searchInput) {
                        searchInput.removeAttribute('disabled');
                        searchInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    }
                }
            }

            function clearCobroClient() {
                const input = document.getElementById('cobro-client-input');
                if (input) input.value = 'Público General';
                if (currentTable) {
                    currentTable.clientName = 'Público General';
                    currentTable.person_id = null;
                    saveDB();
                }
                const picker = document.getElementById('order-client-picker');
                if (picker && window.Alpine) {
                    const d = Alpine.$data(picker);
                    if (d) d.clientId = null;
                }
                window.dispatchEvent(new CustomEvent('clear-combobox', {
                    detail: { name: 'header_client_id' }
                }));
            }

            function getCobroOrderTotal() {
                const totals = getTotalsWithDelivery(currentTable?.items || []);
                return totals.total || 0;
            }

            function getCobroRemainingAmount(excludeInput) {
                const orderTotal = getCobroOrderTotal();
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let paid = 0;
                inputs.forEach(inp => {
                    if (inp !== excludeInput) {
                        paid += parseFloat(inp.value || 0) || 0;
                    }
                });
                return Math.max(0, orderTotal - paid);
            }

            function autocompleteCobroAmount(inputEl) {
                if (!inputEl) return;
                const val = parseFloat(inputEl.value || 0) || 0;
                if (val > 0) {
                    inputEl.select();
                    return;
                }
                const remaining = getCobroRemainingAmount(inputEl);
                if (remaining > 0) {
                    inputEl.value = remaining.toFixed(2);
                    inputEl.select();
                    updateCobroTotalPaid();
                }
            }

            function isCobroMethodCard(desc) {
                const d = (desc || '').toLowerCase();
                return (d.includes('tarjeta') || d.includes('card')) && !d.includes('billetera');
            }
            function isCobroMethodWallet(desc) {
                return ('' + (desc || '')).toLowerCase().includes('billetera');
            }
            function isCobroMethodTransfer(desc) {
                const d = ('' + (desc || '')).toLowerCase();
                return d.includes('transferencia') || d.includes('transfer') || d.includes('deposito') || d.includes('depósito');
            }

            function buildCobroGatewayOptions() {
                const gws = cobroPaymentGateways || [];
                const opts = gws.map(g => `<option value="${g.id}">${escapeHtml(g.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar pasarela</option>' + opts : '<option value="">Sin pasarelas</option>';
            }
            function buildCobroCardOptions() {
                const cards = cobroCards || [];
                const credit = cards.filter(c => (c.type || '').toUpperCase() === 'C');
                const debit = cards.filter(c => (c.type || '').toUpperCase() === 'D');
                let html = '<option value="">Seleccionar tarjeta</option>';
                if (credit.length) {
                    html += '<optgroup label="Crédito">' + credit.map(c => `<option value="${c.id}">${escapeHtml(c.description || '')}</option>`).join('') + '</optgroup>';
                }
                if (debit.length) {
                    html += '<optgroup label="Débito">' + debit.map(c => `<option value="${c.id}">${escapeHtml(c.description || '')}</option>`).join('') + '</optgroup>';
                }
                return html || '<option value="">Sin tarjetas</option>';
            }
            function buildCobroWalletOptions() {
                const wls = cobroDigitalWallets || [];
                const opts = wls.map(w => `<option value="${w.id}">${escapeHtml(w.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar billetera</option>' + opts : '<option value="">Sin billeteras</option>';
            }
            function buildCobroBankOptions() {
                const banks = cobroBanks || [];
                const opts = banks.map(b => `<option value="${b.id}">${escapeHtml(b.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar banco</option>' + opts : '<option value="">Sin bancos</option>';
            }

            function toggleCobroExtraFields(row) {
                const methodSelect = row.querySelector('.cobro-pm-method');
                const cardGroup = row.querySelector('.cobro-pm-card-group');
                const walletGroup = row.querySelector('.cobro-pm-wallet-group');
                const bankGroup = row.querySelector('.cobro-pm-bank-group');
                if (!methodSelect || !cardGroup || !walletGroup) return;
                const sel = methodSelect.options[methodSelect.selectedIndex];
                const desc = sel ? sel.text : '';
                const isCard = isCobroMethodCard(desc);
                const isWallet = isCobroMethodWallet(desc);
                const isTransfer = isCobroMethodTransfer(desc);
                cardGroup.classList.toggle('hidden', !isCard);
                walletGroup.classList.toggle('hidden', !isWallet);
                const gw = row.querySelector('.cobro-pm-gateway');
                const card = row.querySelector('.cobro-pm-card');
                const wallet = row.querySelector('.cobro-pm-wallet');
                if (!isCard && gw) gw.value = '';
                if (!isCard && card) card.value = '';
                if (!isWallet && wallet) wallet.value = '';
                if (bankGroup) {
                    if (isTransfer) {
                        bankGroup.classList.remove('hidden');
                        bankGroup.classList.add('flex');
                    } else {
                        bankGroup.classList.add('hidden');
                        bankGroup.classList.remove('flex');
                        const bank = row.querySelector('.cobro-pm-bank');
                        if (bank) bank.value = '';
                    }
                }
            }

            function addCobroPaymentMethod() {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return;
                const methods = cobroPaymentMethods || [];
                const opts = methods.map(pm =>
                    `<option value="${pm.id}">${escapeHtml(pm.description || '')}</option>`
                ).join('');
                const autoAmount = getCobroRemainingAmount();
                const row = document.createElement('div');
                row.className = 'cobro-pm-row rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-3 space-y-2';
                row.innerHTML = `
                            <div class="flex gap-2 items-end flex-wrap">
                                <div class="flex-1 min-w-[120px]">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Método</label>
                                    <select class="cobro-pm-method w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400" onchange="toggleCobroExtraFields(this.closest('.cobro-pm-row'))">
                                        ${opts}
                                    </select>
                                </div>
                                <div class="w-24 shrink-0">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Monto</label>
                                    <input type="number" step="0.01" min="0" value="${autoAmount > 0 ? autoAmount.toFixed(2) : '0.00'}" placeholder="0.00"
                                        class="w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm tabular-nums cobro-pm-amount"
                                        oninput="updateCobroTotalPaid()" onfocus="autocompleteCobroAmount(this)">
                                </div>
                                <button type="button" onclick="this.closest('.cobro-pm-row').remove(); updateCobroTotalPaid();" class="p-2 h-9 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0" title="Eliminar">
                                    <i class="ri-delete-bin-line text-lg"></i>
                                </button>
                            </div>
                            <div class="cobro-pm-card-group hidden flex gap-2 items-end flex-wrap">
                                <div class="flex-1 min-w-[100px]">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Pasarela</label>
                                    <select class="cobro-pm-gateway w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                        ${buildCobroGatewayOptions()}
                                    </select>
                                </div>
                                <div class="flex-1 min-w-[100px]">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tarjeta</label>
                                    <select class="cobro-pm-card w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                        ${buildCobroCardOptions()}
                                    </select>
                                </div>
                            </div>
                            <div class="cobro-pm-wallet-group hidden flex gap-2 items-end flex-wrap">
                                <div class="flex-1 min-w-[120px]">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Billetera (Yape, Plin...)</label>
                                    <select class="cobro-pm-wallet w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                        ${buildCobroWalletOptions()}
                                    </select>
                                </div>
                            </div>
                            <div class="cobro-pm-bank-group hidden flex gap-2 items-end flex-wrap">
                                <div class="flex-1 min-w-[120px]">
                                    <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Banco destino</label>
                                    <select class="cobro-pm-bank w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                        ${buildCobroBankOptions()}
                                    </select>
                                </div>
                            </div>
                        `;
                list.appendChild(row);
                toggleCobroExtraFields(row);
                updateCobroTotalPaid();
            }

            function updateCobroTotalPaid() {
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let total = 0;
                inputs.forEach(inp => {
                    total += parseFloat(inp.value || 0) || 0;
                });
                const el = document.getElementById('cobro-total-paid');
                if (el) el.textContent = 'S/ ' + total.toFixed(2);
            }

            function changeClient(selectEl) {
                if (!selectEl || !currentTable) return;
                const name = selectEl.options[selectEl.selectedIndex]?.text || 'Público General';
                const personId = selectEl.value ? parseInt(selectEl.value, 10) : null;
                currentTable.clientName = name;
                currentTable.person_id = personId;
                saveDB();
                const cobroInput = document.getElementById('cobro-client-input');
                if (cobroInput) cobroInput.value = name;
            }

            function changeWaiter(selectEl) {
                if (!selectEl || !currentTable) return;
                const name = selectEl.options[selectEl.selectedIndex]?.text || 'Sin asignar';
                currentTable.waiter = name;
                saveDB();
            }

            function toggleOrderTakeAway() {
                if (!currentTable) return;
                const newType = currentTable.service_type === 'TAKE_AWAY' ? 'IN_SITU' : 'TAKE_AWAY';
                changeServiceType(newType);
            }

            function changeServiceType(valOrEl) {
                if (!currentTable) return;
                const val = (valOrEl && typeof valOrEl === 'object') ? valOrEl.value : valOrEl;
                currentTable.service_type = val;

                // Mostrar/ocultar paneles
                const deliveryContainer = document.getElementById('delivery-info-container');
                const takeawayContainer = document.getElementById('takeaway-info-container');
                const dinersSection = document.getElementById('diners-section');

                if (deliveryContainer) deliveryContainer.classList.toggle('hidden', val !== 'DELIVERY');
                if (takeawayContainer) takeawayContainer.classList.toggle('hidden', val !== 'TAKE_AWAY');
                if (dinersSection) dinersSection.classList.toggle('hidden', val !== 'IN_SITU');

                // Sincronizar input oculto si existe
                const hiddenInput = document.getElementById('header-service-type-val');
                if (hiddenInput) {
                    hiddenInput.value = val;
                }

                saveDB();
                renderTicket();
            }

            function updateTakeAwayInfo() {
                if (!currentTable) return;
                const nameInp = document.getElementById('takeaway-client-name');
                const timeInp = document.getElementById('takeaway-time');

                if (nameInp) currentTable.client_name_extra = nameInp.value;
                if (timeInp) currentTable.delivery_time = timeInp.value;

                saveDB();
                renderTicket();
            }

            function updateDeliveryInfo() {
                if (!currentTable) return;
                const addr = document.getElementById('delivery-address');
                const phone = document.getElementById('delivery-phone');
                const amount = document.getElementById('delivery-amount');

                if (addr) currentTable.delivery_address = addr.value;
                if (phone) currentTable.contact_phone = phone.value;
                if (amount) currentTable.delivery_amount = parseFloat(amount.value) || 0;

                saveDB();
                renderTicket();
            }

            // Exponer funciones usadas desde onclick en el HTML (mismo ámbito tras re-render)
            window.toggleCourtesyInput = toggleCourtesyInput;
            window.toggleNoteInput = toggleNoteInput;
            window.updateQty = updateQty;
            window.setQtyFromInput = setQtyFromInput;
            window.confirmRemoveLine = confirmRemoveLine;
            window.removeFromCart = removeFromCart;
            window.applyRemoveQuantity = applyRemoveQuantity;
            window.saveNote = saveNote;
            window.toggleDelivered = toggleDelivered;
            window.getImageUrl = getImageUrl;
            window.goBack = goBack;
            window.processOrder = processOrder;
            window.processOrderPayment = processOrderPayment;
            window.updateDiners = updateDiners;
            window.switchAsideTab = switchAsideTab;
            window.clearCobroClient = clearCobroClient;
            window.addCobroPaymentMethod = addCobroPaymentMethod;
            window.updateCobroTotalPaid = updateCobroTotalPaid;
            window.autocompleteCobroAmount = autocompleteCobroAmount;
            window.toggleCobroExtraFields = toggleCobroExtraFields;
            window.changeClient = changeClient;
            window.changeWaiter = changeWaiter;
            window.changeServiceType = changeServiceType;
            window.toggleOrderTakeAway = toggleOrderTakeAway;
            window.updateDeliveryInfo = updateDeliveryInfo;
            window.updateTakeAwayInfo = updateTakeAwayInfo;
            window.updateTakeawayDisposableInfo = updateTakeawayDisposableInfo;
            window.printPreAccountTicket = printPreAccountTicket;
            window.openPreAccountPdfTab = openPreAccountPdfTab;
        })();
    </script>

    @vite(['resources/js/qz-tray-init.js'])
@endsection