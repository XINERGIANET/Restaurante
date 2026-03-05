@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3 mb-4 sm:mb-6">

        {{-- Breadcrumb --}}
        <nav class="min-w-0">
            <ol class="flex flex-wrap items-end justify-end  gap-1 sm:gap-1.5 text-xs sm:text-sm">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ url('/') }}">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="min-w-0">
                    <a class="inline-flex items-center gap-1 sm:gap-1.5 text-gray-500 dark:text-gray-400 truncate max-w-[120px] sm:max-w-none"
                        href="{{ route('orders.index') }}">
                        <span class="truncate">Salones de Pedidos</span>
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="text-gray-800 dark:text-white/90 truncate max-w-[140px] sm:max-w-none">
                    Mesa {{ str_pad($table->name ?? $table->id, 2, '0', STR_PAD_LEFT) }} | Crear pedido
                </li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-300 overflow-hidden bg-blue-50 dark:bg-gray-900 fade-in max-w-full">
        <main class="w-full lg:flex-2 flex flex-col min-w-0 min-h-0 bg-white dark:bg-gray-900/50 lg:min-h-0">
            <header class="min-h-14 sm:h-20 py-3 px-3 sm:py-0 sm:px-6 flex items-center gap-2 sm:gap-4 dark:bg-gray-800/50 border-b border-gray-200 shadow-sm z-10 bg-gray-200 flex-nowrap overflow-x-auto min-w-0">
                <div class="flex items-center gap-2 sm:gap-4 md:gap-6 w-full min-w-max flex-nowrap">
                    <button onclick="goBack()" 
                        title="Volver atrás"
                        class="h-9 sm:h-10 px-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-600 transition-colors flex items-center justify-center shadow-sm shrink-0">
                        <i class="ri-arrow-left-line text-lg sm:text-xl"></i>
                        
                    </button>
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                            <h2 class="text-base font-bold text-slate-800 dark:text-white truncate">
                                Mesa <span id="pos-table-name">{{ $table->name ?? $table->id }}</span><br>
                                <p class="text-xs text-gray-500 dark:text-gray-400"><i class="ri-circle-fill" style="color: #00C950;"></i> {{ $table->area->name ?? 'Sin área' }}</p>
                            </h2>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 sm:gap-4 md:gap-6 text-xs sm:text-sm text-gray-500 font-medium min-w-0 flex-nowrap flex-1">
                        <div class="flex items-center justify-end shrink-0 gap-1">
                            <div class="w-50 sm:w-50 md:w-60 relative">
                                <input type="text" id="search-products" placeholder="Buscar producto..." autocomplete="off"
                                    class="w-full pl-8 pr-9 py-2 text-sm bg-white border border-gray-200 dark:border-slate-600 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all">
                                <i class="fas fa-search absolute left-2.5 sm:left-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                            </div>
                            <x-ui.button size="sm" variant="outline" onclick="clearProductSearch()">
                                Limpiar
                            </x-ui.button>
                        </div>
                        <div class="flex items-center gap-2 sm:gap-4 md:gap-6 flex-nowrap shrink-0 ml-auto">
                            <div class="flex items-center gap-1 sm:gap-2 group min-w-0">
                                <span class="text-gray-500 dark:text-gray-400 shrink-0">Mozo:</span>
                                <div class="relative flex items-center min-w-0">
                                    <select id="header-waiter-select" onchange="changeWaiter(this)"
                                        class="min-w-0 w-16 sm:w-24 md:min-w-[100px] md:max-w-[140px] py-1 px-2 sm:px-3 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1rem] sm:bg-[length:1.25rem] bg-[right_0.2rem_center] sm:bg-[right_0.25rem_center] bg-no-repeat truncate">
                                        <option value="{{ $user?->id }}" selected>{{ $user?->name ?? 'Sin asignar' }}</option>
                                    </select>
                                </div>
                            </div>
    
                            <div class="h-3 sm:h-4 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>
    
                            <div class="flex items-center gap-1 sm:gap-2 group min-w-0">
                                <span class="text-gray-500 dark:text-gray-400 shrink-0">Cliente:</span>
                                <div class="relative flex items-center min-w-0">
                                    <select id="header-client-select" onchange="changeClient(this)"
                                        class="min-w-0 w-20 sm:w-28 md:min-w-[110px] md:max-w-[180px] py-1 px-2 sm:px-3 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%226b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1rem] sm:bg-[length:1.25rem] bg-[right_0.2rem_center] sm:bg-[right_0.25rem_center] bg-no-repeat truncate">
                                        <option value="{{ $person?->id }}" selected>{{ $person?->name ?? 'Público General' }}</option>
                                    </select>
                                </div>
                            </div>
    
                            <div class="h-3 sm:h-4 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>
    
                            <div class="flex items-center gap-1 sm:gap-1 group min-w-0">
                                <span class="text-gray-500 dark:text-gray-400 shrink-0">Personas:</span>
                                <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">(máx. {{ $table->capacity ?? 1 }})</span>
                                <div class="flex items-center gap-0.5 sm:gap-1">
                                    <button type="button" onclick="updateDiners(-1)"
                                        class="w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-md bg-gray-100 dark:bg-slate-600 hover:bg-blue-100 dark:hover:bg-blue-700/50 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-300 transition-colors border border-gray-200 dark:border-slate-500 text-xs font-bold leading-none select-none">
                                        <i class="fas fa-minus text-[9px] sm:text-[10px]"></i>
                                    </button>
                                    <input type="number"
                                        id="diners-input"
                                        value="{{ $pendingPeopleCount ?? 1 }}"
                                        min="1"
                                        onchange="updateDiners(0)"
                                        class="w-8 sm:w-9 py-1 px-0.5 text-center text-xs sm:text-sm bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="button" onclick="updateDiners(1)"
                                        class="w-5 h-5 sm:w-6 sm:h-6 flex items-center justify-center rounded-md bg-gray-100 dark:bg-slate-600 hover:bg-blue-100 dark:hover:bg-blue-700/50 text-slate-600 dark:text-slate-300 hover:text-blue-600 dark:hover:text-blue-300 transition-colors border border-gray-200 dark:border-slate-500 text-xs font-bold leading-none select-none">
                                        <i class="fas fa-plus text-[9px] sm:text-[10px]"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex flex-row flex-1 min-h-0 min-w-0 p-5 overflow-y-auto overflow-x-hidden" style="-webkit-overflow-scrolling: touch;">
                <div class="flex-1 min-w-0 p-3 sm:p-5 md:p-6 bg-white flex flex-col min-h-0">                
                    <div class="flex flex-col flex-1 min-h-0 min-w-0 overflow-hidden">
                        <div class="shrink-0 border-gray-300 px-2 sm:px-4 pt-3 pb-4">
                            <div class="flex items-center justify-between">
                            </div>
                            <div id="categories-grid" class="flex flex-row flex-wrap gap-1.5 sm:gap-2 overflow-x-auto pb-3 overscroll-x-contain">
                            </div>
                        </div>
                        <div class="flex-1 min-h-0 min-w-0 pt-2 sm:pt-3 flex flex-col overflow-hidden min-h-[200px]">
                            <div id="products-grid" class="px-2 sm:px-4 md:px-5 p-3 grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-2 sm:gap-4 overflow-y-auto overflow-x-hidden min-h-0 flex-1 content-start overscroll-contain" style="-webkit-overflow-scrolling: touch;">
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="flex flex-col rounded-2xl shadow-2xl overflow-hidden w-[400px] sm:w-[400px] md:w-[400px] shrink-0 bg-white dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 min-h-0 rounded-l-2xl" style="min-height: 550px;">
                    {{-- Tabs Resumen | Cobro --}}
                    <div class="flex shrink-0 border-b border-gray-200 dark:border-gray-700">
                        <button type="button" id="tab-resumen" onclick="switchAsideTab('resumen')"
                            class="flex-1 py-3 px-4 text-sm font-bold transition-colors rounded-tl-2xl bg-brand-500 text-white">
                            Resumen
                        </button>
                        <button type="button" id="tab-cobro" onclick="switchAsideTab('cobro')"
                            class="flex-1 py-3 px-4 text-sm font-bold transition-colors bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 hover:text-orange-600 dark:hover:text-orange-400">
                            Cobro
                        </button>
                    </div>

                    {{-- Contenido Resumen --}}
                    <div id="aside-resumen" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                        <div id="cart-container" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2 sm:space-y-3 bg-gray-50 dark:bg-gray-900/50 min-h-0 overscroll-contain" style="-webkit-overflow-scrolling: touch;"></div>
                        <div id="cancelled-platos-container" class="shrink-0 hidden border-t border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20 p-3 sm:p-4 max-h-40 overflow-y-auto">
                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-1"><i class="ri-error-warning-line"></i> Platos anulados</p>
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
                                <div class="border-t border-dashed border-gray-300 dark:border-gray-600 my-2"></div>
                                <div class="flex justify-between items-center">
                                    <span class="text-base sm:text-lg font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                                    <span class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400" id="ticket-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contenido Cobro --}}
                    <div id="aside-cobro" class="hidden flex-col flex-1 min-h-0 overflow-y-auto p-4 sm:p-5">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Cliente</label>
                                <div class="relative">
                                    <input type="text" id="cobro-client-input" readonly
                                        value="{{ $person?->name ?? 'Público General' }}"
                                        class="w-full pl-3 pr-8 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                    <button type="button" onclick="clearCobroClient()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <i class="ri-close-line text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Documento</label>
                                    <select id="cobro-document-type" class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($documentTypes ?? []) as $dt)
                                            <option value="{{ $dt?->id }}">{{ $dt?->name ?? '' }}</option>
                                        @empty
                                            <option value="">Sin documentos</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Caja</label>
                                    <select id="cobro-cash-register" class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($cashRegisters ?? []) as $cr)
                                            <option value="{{ $cr?->id }}">{{ $cr?->number ?? 'Caja ' . ($cr?->id ?? '') }}</option>
                                        @empty
                                            <option value="">Sin cajas</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Métodos de pago</label>
                                    <button type="button" onclick="addCobroPaymentMethod()"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 active:scale-95 transition-colors shrink-0">
                                        <i class="ri-add-line text-sm"></i> Agregar
                                    </button>
                                </div>
                                <div id="cobro-payment-methods-list" class="space-y-3 max-h-48 overflow-y-auto pr-1"></div>
                                <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800/80 px-3 py-2.5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total pagado</span>
                                        <span class="text-base font-bold text-slate-800 dark:text-white tabular-nums" id="cobro-total-paid">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Botones Guardar y Cobrar (siempre visibles; Mozo no puede cobrar) --}}
                    <div class="shrink-0 p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" id="btn-guardar" onclick="processOrder()"
                                class="py-2.5 px-3 rounded-xl bg-gray-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-gray-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                                <i class="ri-save-line text-base"></i>
                                <span>Guardar</span>
                            </button>
                            @if($canCharge ?? true)
                            <button onclick="processOrderPayment()"
                                class="py-2.5 px-3 rounded-xl bg-brand-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-brand-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                                <i class="ri-bank-card-line text-base"></i>
                                <span>Cobrar</span>
                            </button>
                            @else
                            <button type="button" disabled
                                class="py-2.5 px-3 rounded-xl bg-gray-300 text-gray-500 font-bold text-xs sm:text-sm cursor-not-allowed flex justify-center items-center gap-2"
                                title="Tu perfil (Mozo) no puede cobrar. Solo puedes guardar pedidos.">
                                <i class="ri-bank-card-line text-base"></i>
                                <span>Cobrar</span>
                            </button>
                            @endif
                        </div>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <div id="notification" class="fixed top-24 right-8 z-50 max-w-sm opacity-0 pointer-events-none transition-opacity duration-300" aria-live="polite"></div>

    <script>
        (function() {
            @php
                $serverTableData = [
                    'id' => $table->id,
                    'table_id' => $table->id,
                    'area_id' => $table->area_id ?? ($area->id ?? null),
                    'name' => $table->name ?? $table->id,
                    'waiter' => $user?->name ?? 'Sin asignar',
                    'clientName' => $person?->name ?? 'Sin cliente',
                    'status' => $table->situation ?? 'libre',
                    'items' => [],
                    'people_count' => (int) ($table->capacity ?? 1),
                ];
            @endphp
            const serverTable = @json($serverTableData);
            const startFresh = @json($startFresh ?? false);
            // IDs del pedido pendiente que viene directo del servidor (fuente de verdad)
            const serverOrderMovementId = @json($pendingOrderMovementId ?? null);
            const serverMovementId = @json($pendingMovementId ?? null);
            const serverPendingCancelledDetails = @json($pendingCancelledDetails ?? []);
            const waiterPinEnabled = @json($waiterPinEnabled ?? false);
            const validateWaiterPinUrl = @json(route('orders.validateWaiterPin'));
            const waiterPinBranchId = @json((int) session('branch_id'));
            const cobroPaymentMethods = @json($paymentMethods ?? []);
            const cobroPaymentGateways = @json($paymentGateways ?? []);
            const cobroCards = @json($cards ?? []);
            const cobroDigitalWallets = @json($digitalWallets ?? []);

            let db = JSON.parse(localStorage.getItem('restaurantDB'));
            if (!db) db = {};
            let activeKey = `table-{{ $table->id }}`;
            let autoSaveTimer = null;

            // Si la mesa no tiene pedido pendiente (startFresh) o está libre: pedido nuevo, borrar borrador
            const tableIsFree = (serverTable.status || '').toLowerCase() === 'libre';
            const useFreshOrder = startFresh || tableIsFree;
            if (useFreshOrder && db[activeKey]) {
                delete db[activeKey];
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }

            let currentTable = (useFreshOrder || !db[activeKey]) ? serverTable : db[activeKey];
            // Inicializar people_count si no existe en el estado guardado (default: capacity de mesa)
            if (!currentTable.people_count) currentTable.people_count = {{ $pendingPeopleCount ?? 1 }};
            // Siempre sincronizar order_movement_id y movement_id con el servidor para evitar duplicados
            if (serverOrderMovementId) {
                currentTable.order_movement_id = serverOrderMovementId;
                currentTable.movement_id = serverMovementId;
            } else {
                // No hay pedido pendiente en servidor: asegurar que no usamos un ID viejo del localStorage
                currentTable.order_movement_id = null;
                currentTable.movement_id = null;
            }
            // Inicializar estructura de cancelaciones por plato
            if (!currentTable.cancellations) currentTable.cancellations = [];

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
                }).catch(() => {});

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
                refreshCartPricesFromServer();
                renderCategories();
                renderProducts();
                renderTicket();
                renderCancelledSection();
                fixScrollLayout();
                const searchProductsInput = document.getElementById('search-products');
                const searchProductsClearBtn = document.getElementById('search-products-clear');
                function updateSearchClearVisibility() {
                    if (searchProductsClearBtn) {
                        searchProductsClearBtn.classList.toggle('hidden', !searchProductsInput || !searchProductsInput.value.trim());
                    }
                }
                window.clearProductSearch = function() {
                    if (searchProductsInput) {
                        searchProductsInput.value = '';
                        productSearchQuery = '';
                        searchProductsInput.focus();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                };
                if (searchProductsInput) {
                    searchProductsInput.addEventListener('input', function() {
                        productSearchQuery = this.value.trim();
                        updateSearchClearVisibility();
                        renderProducts();
                    });
                    searchProductsInput.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') clearProductSearch();
                    });
                    updateSearchClearVisibility();
                }
                if (currentTable.items && currentTable.items.length > 0) {
                    setTimeout(scheduleAutoSave, 800);
                }
                if (typeof addCobroPaymentMethod === 'function' && document.getElementById('cobro-payment-methods-list')?.children.length === 0) {
                    addCobroPaymentMethod();
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

            // Datos de productos, categorías y productBranches desde el servidor
            const serverProducts = @json($products ?? []);
            const serverProductBranches = @json($productBranches ?? []);
            const serverCategories = @json(collect($categories ?? [])->map(fn($c) => ['id' => $c->id, 'name' => $c->description ?? '', 'img' => $c->image ? asset('storage/' . $c->image) : null])->values()->all());

            function getItemTaxRatePercent(item) {
                const rate = parseFloat(item?.tax_rate);
                return !isNaN(rate) && rate >= 0 ? rate : 10;
            }

            // Los precios del POS incluyen IGV.
            function calculateTotalsFromItems(items) {
                let subtotal = 0;
                let tax = 0;
                let total = 0;

                (items || []).forEach(item => {
                    const qty = parseFloat(item.qty) || 0;
                    const price = parseFloat(item.price) || 0;
                    const lineTotal = qty * price;
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

            let selectedCategoryId = null;
            let productSearchQuery = '';

            function renderCategories() {
                const grid = document.getElementById('categories-grid');
                if (!grid) return; 
                
                grid.innerHTML = '';

                if (!serverCategories || serverCategories.length === 0) {
                    grid.innerHTML = '<div class="text-center text-gray-500 py-2 text-sm w-full">No hay categorías</div>';
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

                    el.onclick = function(e) {
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
                grid.innerHTML = '';

                if (!serverProducts || serverProducts.length === 0) {
                    grid.innerHTML =
                        '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles</div>';
                    return;
                }

                // Filtrar por categoría seleccionada (si hay una)
                let productsToShow = selectedCategoryId == null
                    ? serverProducts
                    : serverProducts.filter(p => p.category_id == selectedCategoryId);

                // Filtrar por texto de búsqueda (nombre o categoría del producto)
                const q = String(productSearchQuery || '').trim().toLowerCase();
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
                    el.onclick = function(e) {
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
                    } else {
                        grid.innerHTML = selectedCategoryId != null
                            ? '<div class="col-span-full text-center text-gray-500 py-8">No hay productos en esta categoría</div>'
                            : '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles para esta sucursal</div>';
                    }
                }

            }

            function addToCart(prod, productBranch) {
                if (!currentTable.items) currentTable.items = [];

                if (!productBranch || !productBranch.price) {

                    return;
                }

                const price = parseFloat(productBranch.price);
                if (isNaN(price) || price <= 0) {
                    return;
                }

                const stock = parseFloat(productBranch.stock ?? 0) || 0;

                // Asegurar que el ID del producto sea un número entero para la comparación
                const productId = parseInt(prod.id, 10);
                if (isNaN(productId) || productId <= 0) {

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
                if (qtyToAdd > stock) {
                    showNotification('Stock insuficiente', (prod.name || 'Producto') + ': solo hay ' + stock + ' disponible(s).', 'error');
                    return;
                }

                if (existing) {
                    // Si existe, solo aumentar la cantidad
                    existing.qty++;
                    if (existing.tax_rate === undefined || existing.tax_rate === null) {
                        existing.tax_rate = parseFloat(productBranch.tax_rate ?? 10);
                    }
                } else {
                    // Si no existe, agregarlo como nuevo item

                    currentTable.items.push({
                        pId: productId, // Guardar como número entero
                        name: prod.name || 'Sin nombre',
                        qty: 1,
                        price: price,
                        tax_rate: parseFloat(productBranch.tax_rate ?? 10),
                        note: ""
                    });
                }
                saveDB();
                renderTicket();
            }

            async function updateQty(index, change) {
                const item = currentTable.items[index];
                const oldQty = item.qty;
                const newQty = oldQty + change;

                // Aumentar cantidad: no requiere razón
                if (change > 0) {
                    item.qty = newQty;
                    saveDB();
                    renderTicket();
                    return;
                }

                // Disminuir cantidad
                const hasSavedOrder = !!currentTable.order_movement_id;
                const isEditingExistingOrder = hasSavedOrder && !!serverOrderMovementId;

                // Si es pedido nuevo (primera vez) o no venía guardado al abrir: bajar sin pedir razón
                if (!isEditingExistingOrder) {
                    item.qty = newQty;
                    if (item.qty <= 0) currentTable.items.splice(index, 1);
                    saveDB();
                    renderTicket();
                    return;
                }

                // Pedido ya existía al abrir (se va a modificar): pedir razón por CADA unidad que se cancela
                let reason = null;
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Razón de anulación del plato',
                        input: 'textarea',
                        inputPlaceholder: 'Escribe la razón de la anulación...',
                        showCancelButton: true,
                        confirmButtonText: 'Anular',
                        cancelButtonText: 'Volver',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) {
                                return 'Debes ingresar una razón';
                            }
                            return null;
                        }
                    });

                    // Si se cierra o cancela el diálogo, no cambiar cantidad ni registrar nada
                    if (!result.isConfirmed || !result.value) {
                        return;
                    }
                    reason = result.value.trim();
                } else {
                    const p = window.prompt('Razón de anulación del plato:');
                    if (!p || !p.trim()) return;
                    reason = p.trim();
                }

                const qtyToCancel = Math.min(oldQty, Math.abs(change));
                currentTable.cancellations = currentTable.cancellations || [];
                const prod = serverProducts.find(p => p.id === item.pId);

                currentTable.cancellations.push({
                    pId: item.pId,
                    name: item.name,
                    qtyCanceled: qtyToCancel,
                    price: item.price,
                    note: item.note || null,
                    cancel_reason: reason,
                    product_snapshot: prod ? { ...prod } : null
                });

                // Aplicar la disminución luego de registrar la cancelación
                item.qty = newQty;
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
                    saveDB();
                    renderTicket();
                    return;
                }
                await updateQty(index, newQty - oldQty);
            }

            function toggleNoteInput(index) {
                const box = document.getElementById(`note-box-${index}`);
                if (box) box.classList.toggle('hidden');
            }

            async function removeFromCart(index) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const qty = parseInt(item.qty, 10) || 1;
                await updateQty(index, -qty);
            }

            function saveNote(index, val) {
                currentTable.items[index].note = val;
                saveDB();
            }

            function renderTicket() {
                const container = document.getElementById('cart-container');
                if (!container) {
                    return;
                }
                container.innerHTML = '';
                let subtotal = 0;

                if (!currentTable.items || currentTable.items.length === 0) {
                    container.innerHTML = `
                    <div class="flex flex-col items-center justify-center  text-gray-300 opacity-60">
                        <i class="fas fa-utensils text-3xl mb-2"></i>
                        <p class="font-medium text-sm">Sin productos</p>
                    </div>`;
                } else {
                    currentTable.items.forEach((item, index) => {
                        const prod = serverProducts.find(p => p.id === item.pId);
                        if (!prod) {
                            return;
                        }

                        const itemPrice = parseFloat(item.price) || 0;
                        const itemQty = parseInt(item.qty) || 0;
                        subtotal += itemPrice * itemQty;
                        const hasNote = item.note && item.note.trim() !== "";

                        const row = document.createElement('div');
                        row.className =
                            "bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl p-3 shadow-sm relative overflow-hidden group mb-2 border-l-4 border-l-blue-500";

                        const productName = escapeHtml(prod.name || 'Sin nombre');
                        const productImage = getImageUrl(prod.img || null);
                        const itemNote = escapeHtml(item.note || '');

                        row.innerHTML = `
                        <div class="flex flex-col gap-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex-1 min-w-0">
                                    <div class="flex justify-between items-baseline gap-2">
                                        <span class="font-bold text-slate-800 dark:text-slate-200 text-sm truncate">${productName}</span>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 shrink-0">S/ ${parseFloat(item.price).toFixed(2)}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-0.5 bg-gray-100 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 p-0.5 shrink-0">
                                    <button type="button" onclick="updateQty(${index}, -1)" class="w-7 h-7 flex items-center justify-center rounded-full text-gray-500 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                        <i class="ri-subtract-line text-sm"></i>
                                    </button>
                                    <input type="number" value="${item.qty}" min="1" onchange="setQtyFromInput(${index}, this)" class="w-8 h-7 text-center text-xs font-bold text-slate-700 dark:text-slate-200 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-1 focus:ring-blue-500 focus:border-blue-500">
                                    <button type="button" onclick="updateQty(${index}, 1)" class="w-7 h-7 flex items-center justify-center rounded-full text-gray-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                        <i class="ri-add-line text-sm"></i>
                                    </button>
                                </div>
                                <span class="font-bold text-slate-800 dark:text-slate-200 text-sm shrink-0 w-14 text-right">S/ ${(item.price * item.qty).toFixed(2)}</span>
                                <button type="button" onclick="removeFromCart(${index})" class="p-1.5 text-gray-400 hover:text-red-500 transition-colors shrink-0" title="Eliminar">
                                    <i class="ri-delete-bin-line text-base"></i>
                                </button>
                            </div>
                            <div class="flex items-center">
                                <button type="button" onclick="toggleNoteInput(${index})" class="text-xs flex items-center gap-1 transition-colors ${hasNote ? 'text-blue-600 font-medium' : 'text-blue-500 hover:text-blue-600'}">
                                    <i class="fas fa-comment-alt text-[10px]"></i> Nota
                                </button>
                            </div>
                            <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} animate-fadeIn">
                                <input type="text" value="${itemNote}" oninput="saveNote(${index}, this.value)" placeholder="Escribe una nota..." class="w-full text-xs bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-2 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            </div>
                        </div>
                    `;
                        container.appendChild(row);
                    });
                }
                const totals = calculateTotalsFromItems(currentTable.items || []);
                const tax = totals.tax;
                const total = totals.total;
                subtotal = totals.subtotal;

                const subtotalEl = document.getElementById('ticket-subtotal');
                const taxEl = document.getElementById('ticket-tax');
                const totalEl = document.getElementById('ticket-total');

                if (subtotalEl) subtotalEl.innerText = `$${subtotal.toFixed(2)}`;
                if (taxEl) taxEl.innerText = `$${tax.toFixed(2)}`;
                if (totalEl) totalEl.innerText = `$${total.toFixed(2)}`;

                renderCancelledSection();
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
                        scheduleAutoSave();
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

            // Limpiar auto-guardado al navegar con Turbo para no dispararlo en otra página
            document.addEventListener('turbo:before-visit', function() {
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
                const totals = calculateTotalsFromItems(items);
                const order = {
                    items: items,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: totals.subtotal,
                    tax: totals.tax,
                    total: totals.total,
                    people_count: currentTable.people_count ?? 0,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
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
                        if (data.order_movement_id) currentTable.order_movement_id = data.order_movement_id;
                        if (data.movement_id) currentTable.movement_id = data.movement_id;
                        // Cancelaciones de este ciclo ya fueron persistidas
                        currentTable.cancellations = [];
                        saveDB();
                    } else if (data && isMesaYaCobradaMessage(data.message)) {
                        if (typeof showNotification === 'function') {
                            showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                        }
                    }
                })
                .catch(() => {});
            }

            async function processOrder() {
                if (waiterPinEnabled) {
                    const ok = await ensureWaiterPin();
                    if (!ok) return;
                }
                const btnGuardar = document.getElementById('btn-guardar');
                if (btnGuardar) { btnGuardar.disabled = true; }
                const items = currentTable.items || [];
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
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
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
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

                const totals = calculateTotalsFromItems(items);
                const payload = {
                    items: items,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: totals.subtotal,
                    tax: totals.tax,
                    total: totals.total,
                    people_count: currentTable.people_count ?? 0,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };

                try {
                    const processRes = await fetch('{{ route('orders.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    const ct = processRes.headers.get('content-type');
                    let processData = null;
                    if (ct && ct.includes('application/json')) {
                        processData = await processRes.json();
                    } else {
                        throw new Error(processRes.status === 419 ? 'Sesión expirada. Recarga la página.' : (processRes.status === 401 ? 'Debes iniciar sesión.' : (processRes.status === 500 ? 'Error al procesar. Intenta de nuevo.' : 'Error del servidor.')));
                    }

                    if (processData && processData.success && processData.movement_id) {
                        currentTable.cancellations = [];
                        saveDB();
                        const url = new URL("{{ route('orders.charge') }}", window.location.origin);
                        url.searchParams.set('movement_id', processData.movement_id);
                        url.searchParams.set('_t', Date.now());
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(url.toString(), { action: 'advance' });
                        } else {
                            window.location.href = url.toString();
                        }
                    } else {
                        const msg = processData?.message || 'No se pudo guardar el pedido. Intenta de nuevo.';
                        if (String(msg || '').indexOf('PIN') !== -1) {
                            sessionStorage.removeItem(`waiterPin:${waiterPinBranchId}`);
                        }
                        if (typeof showNotification === 'function') {
                            showNotification('Error', msg, 'error');
                        } else {
                            alert(msg);
                        }
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (typeof showNotification === 'function') {
                        showNotification('Error', error?.message || 'Error al procesar.', 'error');
                    } else {
                        alert(error?.message || 'Error al procesar.');
                    }
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
                        /* seguir igual, redirigir */ })
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
                const items = currentTable?.items || [];
                const cancels = currentTable?.cancellations || [];
                // Si no hay productos en la mesa, liberar la mesa y volver sin guardar nada
                if (!items.length && !cancels.length) {
                    releaseTableAndGoBack();
                    return;
                }
                // Si hay productos, guardar el pedido y volver al listado
                processOrder();
            }

            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
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
                if (tab === 'cobro') {
                    resumen?.classList.add('hidden');
                    cobro?.classList.remove('hidden');
                    cobro?.classList.add('flex');
                    btnResumen?.classList.remove('bg-brand-500', 'text-white');
                    btnResumen?.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnCobro?.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnCobro?.classList.add('bg-brand-500', 'text-white');
                } else {
                    cobro?.classList.add('hidden');
                    cobro?.classList.remove('flex');
                    resumen?.classList.remove('hidden');
                    btnCobro?.classList.remove('bg-brand-500', 'text-white');
                    btnCobro?.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnResumen?.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnResumen?.classList.add('bg-brand-500', 'text-white');
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
                const headerSelect = document.getElementById('header-client-select');
                if (headerSelect) {
                    for (let i = 0; i < headerSelect.options.length; i++) {
                        if (headerSelect.options[i].text === 'Público General') {
                            headerSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            function getCobroOrderTotal() {
                const totals = calculateTotalsFromItems(currentTable?.items || []);
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

            function toggleCobroExtraFields(row) {
                const methodSelect = row.querySelector('.cobro-pm-method');
                const cardGroup = row.querySelector('.cobro-pm-card-group');
                const walletGroup = row.querySelector('.cobro-pm-wallet-group');
                if (!methodSelect || !cardGroup || !walletGroup) return;
                const sel = methodSelect.options[methodSelect.selectedIndex];
                const desc = sel ? sel.text : '';
                const isCard = isCobroMethodCard(desc);
                const isWallet = isCobroMethodWallet(desc);
                cardGroup.classList.toggle('hidden', !isCard);
                walletGroup.classList.toggle('hidden', !isWallet);
                const gw = row.querySelector('.cobro-pm-gateway');
                const card = row.querySelector('.cobro-pm-card');
                const wallet = row.querySelector('.cobro-pm-wallet');
                if (!isCard && gw) gw.value = '';
                if (!isCard && card) card.value = '';
                if (!isWallet && wallet) wallet.value = '';
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

            // Exponer funciones usadas desde onclick en el HTML (mismo ámbito tras re-render)
            window.toggleNoteInput = toggleNoteInput;
            window.updateQty = updateQty;
            window.setQtyFromInput = setQtyFromInput;
            window.removeFromCart = removeFromCart;
            window.saveNote = saveNote;
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
        })();
    </script>
@endsection
