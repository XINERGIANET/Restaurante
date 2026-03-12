@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 p-5">
        <x-common.page-breadcrumb pageTitle="Salones de Pedidos" />
        <div x-data="posSystem()" x-cloak
            class="flex flex-col min-h-[calc(100vh-9rem)] w-full font-sans text-slate-800 dark:text-white"
            style="--brand:#3B82F6; --brand-soft:rgba(59,130,246,0.14);">

            {{-- CONTROLES SUPERIORES: Áreas, Buscador y Chips --}}
            <div class="flex flex-col w-full mb-6 gap-4 shrink-0">

                {{-- Fila 1: Botones de área y buscador --}}
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4 w-full">

                    <template x-if="areas && areas.length > 0">
                        <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
                            <template x-for="area in areas" :key="area.id">
                                <button @click="switchArea(area)"
                                    :class="currentAreaId === area.id ?
                                        'bg-white dark:bg-gray-700 shadow-sm text-gray-800 dark:text-white' :
                                        'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'"
                                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all" x-text="area.name">
                                </button>
                            </template>
                        </div>
                    </template>
                    <template x-if="!areas || areas.length === 0">
                        <div class="text-gray-500 dark:text-gray-400 text-sm">
                            No hay áreas disponibles
                        </div>
                    </template>

                    {{-- Buscador --}}
                    <div class="relative w-full max-w-2xl min-w-0 flex-1 sm:min-w-[20rem]">
                        <input type="text" x-model="searchQuery" @keydown.enter.prevent="addSearchChip"
                            placeholder="Buscar y presionar Enter para añadir filtro..."
                            class="w-full pl-10 pr-4 py-2.5 text-sm bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:ring-2 focus:ring-blue-500/30 focus:border-blue-500 outline-none transition-all">
                        <svg class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>

                </div>

                {{-- Fila 2: Contenedor de "Nubes" (Chips) en una línea nueva --}}
                <template x-if="activeFilters && activeFilters.length > 0">
                    <div class="flex flex-wrap gap-2 w-full">
                        <template x-for="(chip, index) in activeFilters" :key="index">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200 dark:border-blue-800 shadow-sm transition-all hover:shadow">
                                <span x-text="chip"></span>
                                <button @click="removeSearchChip(index)" type="button"
                                    class="flex items-center justify-center w-4 h-4 text-blue-600 hover:text-red-500 dark:text-blue-400 dark:hover:text-red-400 focus:outline-none transition-colors">
                                    <i class="ri-close-line text-sm"></i>
                                </button>
                            </span>
                        </template>

                        {{-- Botón para limpiar todos --}}
                        <button @click="clearAllChips" type="button"
                            class="text-xs text-gray-500 hover:text-red-500 dark:text-gray-400 dark:hover:text-red-400 underline ml-2 self-center transition-colors">
                            Limpiar todo
                        </button>
                    </div>
                </template>
            </div>

            {{-- 2. GRID DE MESAS - RESPONSIVO --}}
            <div class="flex justify-center w-full pb-10">
                <template x-if="filteredTables?.length > 0">
                    <div
                        class="grid grid-cols-1 xs:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-5 gap-5 w-full auto-rows-[220px]">
                        <template x-for="table in (filteredTables || [])" :key="table.id">
                            <div @click="openTable(table)"
                                class="relative rounded-2xl p-2 shadow-lg border overflow-hidden bg-white dark:bg-gray-800 transition-all hover:shadow-lg cursor-pointer flex h-[220px] min-h-[220px] max-h-[220px]"
                                :class="table.situation === 'ocupada' ?
                                    'border-gray-100 dark:border-gray-700 border-l-4' :
                                    'border-blue-200 dark:border-blue-700 border-l-4'"
                                :style="table.situation === 'ocupada' ? 'border-left-color: #F37022' :
                                    'border-left-color: rgb(59, 130, 246)'">
                                <div class="p-3 py-4 pl-3 flex flex-col w-full flex-1 min-h-0 overflow-hidden">
                                    {{-- Header --}}
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-4">
                                            <template x-if="table.situation === 'ocupada'">
                                                <div class="bg-gray-100 font-bold text-lg w-11 h-11 flex items-center justify-center rounded-xl shadow-sm dark:bg-gray-700 border-2"
                                                    style="color: #F37022; border-color: #F37022;">
                                                    <span x-text="String(table.name || table.id).padStart(2, '0')"></span>
                                                </div>
                                            </template>
                                            <template x-if="table.situation === 'libre'">
                                                <div
                                                    class="bg-gray-100 border border-blue-500 text-blue-500 font-bold text-lg w-11 h-11 flex items-center justify-center rounded-xl shadow-sm dark:bg-gray-700 dark:text-red-400">

                                                    <span x-text="String(table.name || table.id).padStart(2, '0')"></span>
                                                </div>
                                            </template>
                                            <div class="min-w-0 flex-1 overflow-hidden">
                                                <div class="flex min-w-0">
                                                    <p class="font-semibold text-theme-xs text-gray-500 dark:text-gray-400 shrink-0">
                                                        Mozo:
                                                    </p>
                                                    <p class="font-medium text-gray-800 text-theme-xs dark:text-white/90 ml-1 truncate min-w-0"
                                                        x-text="table.waiter || '-'" :title="(table.waiter || '-')"></p>
                                                </div>
                                                <div class="flex min-w-0">
                                                    <p class="font-semibold text-theme-xs text-gray-500 dark:text-gray-400 shrink-0">
                                                        Cliente:</p>
                                                    <p class="font-medium text-gray-800 text-theme-xs dark:text-white/90 ml-1 truncate min-w-0"
                                                        x-text="(table.client && table.client !== 'Público General') ? table.client : 'Público'">
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-1 text-gray-600 dark:text-gray-400">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M17 20h5v-1a4 4 0 00-3-3.87M9 20H4v-1a4 4 0 013-3.87m6-4.13a4 4 0 110-8 4 4 0 010 8z" />
                                            </svg>
                                            <span class="text-sm font-medium"
                                                x-text="table.situation === 'ocupada' ? (table.people_count || 0) : (table.diners || 0)"></span>
                                        </div>
                                    </div>

                                    {{-- Total + Estado --}}
                                    <div class="flex justify-between items-center mb-5">
                                        <template x-if="table.situation === 'ocupada'">
                                            <p class="text-xl font-bold text-gray-900 dark:text-white"
                                                x-text="'S/. ' + parseFloat(table.total).toFixed(2)"></p>
                                        </template>
                                        <template x-if="table.situation === 'libre'">
                                            <p class="text-xl font-bold text-gray-400">-</p>
                                        </template>
                                        <div class="text-right">
                                            <template x-if="table.situation === 'ocupada'">
                                                <span
                                                    class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-orange-600 dark:text-orange-400 font-medium"
                                                    x-text="table.elapsed"></span>
                                            </template>
                                            <template x-if="table.situation === 'libre'">
                                                <span
                                                    class="text-xs bg-gray-100 dark:bg-gray-700 px-3 py-1 rounded-full text-gray-500 dark:text-gray-400 font-medium">-</span>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Botones --}}
                                    <template x-if="table && table.situation === 'ocupada'">
                                        <div
                                            class="border-blue-500 dark:border-blue-700 pt-3 flex justify-end gap-2 items-center">
                                            <template x-if="canCharge">
                                                <button type="button" @click.stop="chargeTable(table)" title="Cobrar"
                                                    class="inline-flex items-center justify-center bg-green-500 hover:bg-green-600 active:bg-green-700 text-white py-1.5 px-3 rounded-lg transition shadow-sm hover:shadow w-9">
                                                    <i class="ri-bank-card-line text-white"></i>
                                                </button>
                                            </template>
                                            <button type="button"
                                                @click.stop="$dispatch('open-move-table-modal', { table: table })"
                                                title="Mover mesa"
                                                class="inline-flex items-center justify-center bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white py-1.5 px-3 rounded-lg transition shadow-sm hover:shadow w-9">
                                                <i class="ri-drag-move-2-line text-white"></i>
                                            </button>
                                            <button type="button" @click.stop="closeTable(table)" title="Cerrar mesa"
                                                class="inline-flex items-center justify-center bg-red-400 hover:bg-red-600 active:bg-red-700 text-white py-1.5 px-3 rounded-lg transition shadow-sm hover:shadow w-9">
                                                <i class="ri-close-circle-line text-white"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </template>

                {{-- Mensaje cuando no hay mesas --}}
                <template x-if="!filteredTables || filteredTables.length === 0">
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center">
                            <p class="text-gray-500 dark:text-gray-400 text-lg">No hay mesas disponibles en esta área</p>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </div>

    @php
        $areaOptions = collect($areas ?? [])
            ->map(
                fn($a) => [
                    'id' => is_array($a) ? $a['id'] ?? null : $a->id,
                    'description' => is_array($a) ? $a['name'] ?? '' : $a->name ?? '',
                ],
            )
            ->values()
            ->all();
        $tableOptions = collect($tables ?? [])
            ->map(
                fn($t) => [
                    'id' => is_array($t) ? $t['id'] ?? null : $t->id,
                    'description' => is_array($t) ? $t['name'] ?? '' : $t->name ?? '',
                    'area_id' => is_array($t) ? $t['area_id'] ?? null : $t->area_id,
                    'situation' => is_array($t) ? $t['situation'] ?? 'libre' : $t->situation ?? 'libre',
                ],
            )
            ->values()
            ->all();
        $initialAreaId = $areaOptions[0]['id'] ?? null;
        $tableOptionsInitial = collect($tableOptions)
            ->when($initialAreaId, fn($q) => $q->where('area_id', $initialAreaId))
            ->values()
            ->all();
    @endphp
    <x-ui.modal x-data="moveTableModal()" x-effect="if (open) { syncAreaOptions(); updateTables(); }"
        @open-move-table-modal.window="open = true; tableId = null; if ($event.detail && $event.detail.table) { sourceTableId = $event.detail.table.id; sourceTableName = $event.detail.table.name || 'Mesa'; } $nextTick(() => updateTables())"
        @close-move-table-modal.window="open = false" :isOpen="false" :showCloseButton="false" :scrollable="false"
        class="max-w-3xl">
        <div class="p-3">
            <div class="flex flex-col p-3 gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-0.5">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Mover mesa</h3>
                    <p x-show="sourceTableId" class="text-sm text-gray-500 dark:text-gray-400">Mesa a mover: <span
                            x-text="sourceTableName"></span> #<span x-text="sourceTableId"></span></p>
                </div>
                <i class="ri-drag-move-2-line text-2xl text-blue-500"></i>
                <button type="button" @click="open = false"
                    class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
        </div>
        <div class="p-5">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col gap-2">
                    <x-form.select.combobox x-model="currentAreaId" label="Área" :options="$areaOptions" name="area_id" />
                </div>
                <div class="flex flex-col gap-2">
                    <x-form.select.combobox x-model="tableId" label="Mesa" :options="$tableOptionsInitial" name="table_id" />
                </div>
            </div>
            <div class="mt-4">
                <button type="button" @click="moveTable"
                    class="bg-blue-500 hover:bg-blue-600 active:bg-blue-700 text-white py-1.5 px-3 rounded-lg transition shadow-sm hover:shadow ">
                    <i class="ri-arrow-right-line text-white"></i>
                    Mover
                </button>
            </div>
        </div>
    </x-ui.modal>
    {{-- SCRIPT LÃ“GICA --}}
    <script>
        @php
            $areasData = $areas ?? [];
            $tablesData = $tables ?? [];
            $firstAreaId = !empty($areasData) && count($areasData) > 0 ? $areasData[0]['id'] : null;
        @endphp

        window.registerPosSystem = function() {
            if (!window.Alpine) {
                return;
            }

            if (window.__posSystemRegistered) {
                return;
            }
            window.__posSystemRegistered = true;

            const moveTableInitialAreaId = @json($initialAreaId);
            const moveTableAllTables = @json($tableOptions);
            const moveTableAllAreas = @json($areaOptions);
            const moveTableUrl = @json(route('orders.moveTable'));
            const moveTableCsrf = @json(csrf_token());
            Alpine.data('moveTableModal', () => ({
                open: false,
                currentAreaId: moveTableInitialAreaId,
                tableId: null,
                sourceTableId: null,
                sourceTableName: 'Mesa',
                allTables: Array.isArray(moveTableAllTables) ? moveTableAllTables : [],
                allAreaOptions: Array.isArray(moveTableAllAreas) ? moveTableAllAreas : [],
                init() {
                    this.syncAreaOptions();
                    this.updateTables();
                    this.$watch('currentAreaId', () => {
                        this.tableId = null;
                        this.$nextTick(() => this.updateTables());
                    });
                },
                syncAreaOptions() {
                    window.dispatchEvent(new CustomEvent('update-combobox-options', {
                        detail: {
                            name: 'area_id',
                            options: this.allAreaOptions
                        }
                    }));
                },
                // Solo mesas libres, excluyendo la mesa origen
                updateTables() {
                    const areaId = this.currentAreaId;
                    const srcId = this.sourceTableId;
                    let options = this.allTables.filter(t =>
                        String(t.situation ?? '').toLowerCase() !== 'ocupada' &&
                        String(t.id) !== String(srcId)
                    );
                    if (areaId != null && areaId !== '') {
                        options = options.filter(t => String(t.area_id) === String(areaId));
                    }
                    window.dispatchEvent(new CustomEvent('update-combobox-options', {
                        detail: {
                            name: 'table_id',
                            options
                        }
                    }));
                },
                async moveTable() {
                    if (!this.sourceTableId) {
                        if (window.Swal) Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'No se ha seleccionado ninguna mesa para mover.'
                        });
                        return;
                    }
                    if (!this.tableId) {
                        if (window.Swal) Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: 'Selecciona una mesa destino libre.'
                        });
                        return;
                    }
                    try {
                        const res = await fetch(moveTableUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': moveTableCsrf,
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                table_id: this.sourceTableId,
                                new_table_id: this.tableId,
                            })
                        });
                        const data = await res.json().catch(() => ({}));
                        if (data.success) {
                            this.open = false;
                            // Migrar estado local del pedido (POS) a la mesa destino
                            try {
                                const db = JSON.parse(localStorage.getItem('restaurantDB') || '{}') || {};
                                const srcKey = `table-${this.sourceTableId}`;
                                const dstKey = `table-${this.tableId}`;
                                if (db[srcKey]) {
                                    const moved = { ...db[srcKey] };
                                    moved.id = this.tableId;
                                    moved.table_id = this.tableId;
                                    moved.area_id = this.currentAreaId ?? moved.area_id;
                                    moved.status = 'ocupada';
                                    db[dstKey] = moved;
                                    delete db[srcKey];
                                    localStorage.setItem('restaurantDB', JSON.stringify(db));
                                }
                            } catch (e) {}
                            if (window.Swal) {
                                Swal.fire({
                                    toast: true,
                                    position: 'bottom-end',
                                    icon: 'success',
                                    title: data.message || 'Pedido movido correctamente',
                                    showConfirmButton: false,
                                    timer: 3000,
                                    timerProgressBar: true
                                });
                            }
                            if (typeof window.__posRefreshTables === 'function') window
                                .__posRefreshTables();
                        } else {
                            const msg = data.message || 'No se pudo mover el pedido.';
                            if (window.Swal) Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: msg
                            });
                        }
                    } catch (e) {
                        if (window.Swal) Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error de conexión. Intenta de nuevo.'
                        });
                    }
                }
            }));

            Alpine.data('posSystem', () => {
                const areasData = @json($areasData);
                const tablesData = @json($tablesData);
                const firstAreaId = @json($firstAreaId);
                const cancelOrderUrl = @json(route('orders.cancelOrder'));
                const calculateInitialFilteredTables = () => {
                    try {
                        const areas = Array.isArray(areasData) ? areasData : [];
                        const tables = Array.isArray(tablesData) ? tablesData : [];
                        const areaId = firstAreaId ? Number(firstAreaId) : null;

                        if (!tables || tables.length === 0) return [];
                        if (!areas || areas.length === 0) return tables;
                        if (!areaId || isNaN(areaId)) return tables;

                        return tables.filter(t => {
                            if (!t || typeof t.area_id === 'undefined') return false;
                            const tableAreaId = Number(t.area_id);
                            return !isNaN(tableAreaId) && tableAreaId === areaId;
                        });
                    } catch (error) {
                        return [];
                    }
                };

                const initialFilteredTables = calculateInitialFilteredTables();
                const safeFilteredTables = Array.isArray(initialFilteredTables) ? initialFilteredTables : [];

                const componentData = {
                    areas: Array.isArray(areasData) ? areasData : [],
                    tables: Array.isArray(tablesData) ? tablesData : [],
                    currentAreaId: firstAreaId ? Number(firstAreaId) : null,
                    searchQuery: '',
                    activeFilters: [],
                    statusChip: '',
                    createUrl: @json(route('orders.create')),
                    chargeUrl: @json(route('orders.charge')),
                    tablesDataUrl: @json(route('orders.tablesData')),
                    cancelOrderUrl: cancelOrderUrl,
                    cancelOrderToken: @json(csrf_token()),
                    canCharge: @json($canCharge ?? true),
                    waiterPinEnabled: @json($waiterPinEnabled ?? false),
                    waiterPinBranchId: @json((int) session('branch_id')),
                    validateWaiterPinUrl: @json(route('orders.validateWaiterPin')),
                    filteredTables: safeFilteredTables,
                    waiter: null,

                    getStoredWaiter() {
                        try {
                            const key = `waiterPin:${this.waiterPinBranchId}`;
                            const raw = sessionStorage.getItem(key);
                            if (!raw) return null;
                            const data = JSON.parse(raw);
                            if (!data || !data.person_id) return null;
                            // expirar en 12 horas
                            const ts = Number(data.ts || 0);
                            if (!ts || (Date.now() - ts) > (12 * 60 * 60 * 1000)) {
                                sessionStorage.removeItem(key);
                                return null;
                            }
                            return data;
                        } catch (e) {
                            return null;
                        }
                    },

                    async ensureWaiterPin(forceAsk = false) {
                        if (!this.waiterPinEnabled) return true;
                        if (!forceAsk) {
                            const existing = this.getStoredWaiter();
                            if (existing) {
                                this.waiter = existing;
                                return true;
                            }
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
                                const res = await fetch(this.validateWaiterPinUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': this.cancelOrderToken,
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({ pin })
                                });
                                const data = await res.json().catch(() => null);
                                if (data && data.success && data.waiter && data.waiter.person_id) {
                                    const key = `waiterPin:${this.waiterPinBranchId}`;
                                    const payload = { ...data.waiter, ts: Date.now() };
                                    sessionStorage.setItem(key, JSON.stringify(payload));
                                    this.waiter = payload;
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
                    },

                    async refreshTables() {
                        try {
                            const res = await fetch(this.tablesDataUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });
                            if (!res.ok) return;
                            const data = await res.json();
                            if (data.tables && Array.isArray(data.tables)) {
                                this.tables = data.tables;
                            }
                            if (data.areas && Array.isArray(data.areas)) {
                                this.areas = data.areas;
                            }
                            this.updateFilteredTables();
                        } catch (e) {
                            console.warn('No se pudo actualizar mesas:', e);
                        }
                    },

                    addSearchChip() {
                        const val = String(this.searchQuery || '').trim();
                        if (val.length > 0 && !this.activeFilters.includes(val)) {
                            this.activeFilters.push(val);
                        }
                        this.searchQuery = '';
                        this.updateFilteredTables();
                    },

                    removeSearchChip(index) {
                        this.activeFilters.splice(index, 1);
                        this.updateFilteredTables();
                    },

                    clearAllChips() {
                        this.activeFilters = [];
                        this.searchQuery = '';
                        this.updateFilteredTables();
                    },

                    init() {
                        if (this.currentAreaId) {
                            this.currentAreaId = Number(this.currentAreaId);
                        }
                        if (!this.currentAreaId && this.areas && this.areas.length > 0) {
                            this.currentAreaId = Number(this.areas[0].id);
                        }
                        this.updateFilteredTables();
                        this.refreshTables();
                        this.$watch('currentAreaId', () => {
                            this.updateFilteredTables();
                        });
                        this.$watch('searchQuery', () => {
                            this.updateFilteredTables();
                        });
                        this.$watch('statusChip', () => {
                            this.updateFilteredTables();
                        });
                        const self = this;
                        window.__posRefreshTables = function() {
                            self.refreshTables();
                        };
                    },

                    updateFilteredTables() {
                        try {
                            if (!this.tables || !Array.isArray(this.tables)) {
                                this.filteredTables = [];
                                return;
                            }

                            let list = [...this.tables];

                            // Filtro por Área
                            if (this.areas && this.areas.length > 0 && this.currentAreaId) {
                                const areaId = Number(this.currentAreaId);
                                if (!isNaN(areaId)) {
                                    list = list.filter(t => {
                                        if (!t || typeof t.area_id === 'undefined') return false;
                                        return Number(t.area_id) === areaId;
                                    });
                                }
                            }

                            // Filtro por Estado (libre/ocupada)
                            if (this.statusChip) {
                                const status = String(this.statusChip).toLowerCase();
                                list = list.filter(t => String(t.situation || '').toLowerCase() === status);
                            }

                            // NUEVO FILTRO MULTIPLE: Combinar input actual + chips guardados
                            const currentInput = String(this.searchQuery || '').trim().toLowerCase();

                            // Extraemos todos los términos de búsqueda activos
                            let searchTerms = [...this.activeFilters.map(f => f.toLowerCase())];

                            // Si el usuario está escribiendo algo pero aún no presiona Enter, lo consideramos también
                            if (currentInput.length > 0) {
                                searchTerms.push(...currentInput.split(/\s+/).filter(term => term.length >
                                    0));
                            }

                            // Si hay términos que buscar, aplicamos el filtro
                            if (searchTerms.length > 0) {
                                list = list.filter(t => {
                                    const name = String(t.name ?? t.id ?? '').toLowerCase();
                                    const waiter = String(t.waiter ?? t.user_name ?? '')
                                        .toLowerCase();
                                    const client = String(t.client ?? t.clientName ?? t
                                        .person_name ?? '').toLowerCase();
                                    const productsText = String(t.products_text ?? '')
                                        .toLowerCase();

                                    // Unimos todo el texto de la mesa para buscar ahí
                                    const searchable = [name, waiter, client, productsText].join(
                                        ' ');

                                    // La mesa DEBE coincidir con TODOS los términos/chips activos (AND logic)
                                    return searchTerms.every(term => searchable.includes(term));
                                });
                            }

                            this.filteredTables = list;
                        } catch (error) {
                            console.error('Error en updateFilteredTables:', error);
                            this.filteredTables = [];
                        }
                    },

                    switchArea(area) {
                        this.currentAreaId = Number(area.id);
                    },

                    async openTable(table) {
                        const ok = await this.ensureWaiterPin(true);
                        if (!ok) {
                            return;
                        }
                        const target = new URL(this.createUrl, window.location.origin);
                        target.searchParams.set('table_id', table.id);
                        target.searchParams.set('_t', Date.now()); // Evitar caché de Turbo
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(target.toString(), {
                                action: 'advance'
                            });
                        } else {
                            window.location.href = target.toString();
                        }
                    },

                    async chargeTable(table) {
                        const ok = await this.ensureWaiterPin();
                        if (!ok) return;
                        if (table && table.movement_id) {
                            // Ir a orders.create (pestaña Cobro) en vez de orders.charge
                            const url = new URL(this.createUrl, window.location.origin);
                            url.searchParams.set('table_id', table.id);
                            url.searchParams.set('cobro', '1');
                            url.searchParams.set('_t', Date.now());
                            if (window.Turbo && typeof window.Turbo.visit === 'function') {
                                window.Turbo.visit(url.toString(), {
                                    action: 'advance'
                                });
                            } else {
                                window.location.href = url.toString();
                            }
                        } else {
                            this.openTable(table);
                        }
                    },


                    closeTable(table) {
                        if (!window.Swal) {
                            return;
                        }
                        Swal.fire({
                            title: '¿Estás seguro de querer cerrar la mesa?',
                            text: 'Esta acción no se puede deshacer.',
                            icon: 'warning',
                            input: 'text',
                            inputLabel: 'Razon de anulación',
                            inputPlaceholder: 'Ej: Cliente se retiró sin consumir',
                            inputValidator: (value) => {
                                if (!value) {
                                    return 'Por favor, ingrese una razón de anulación.';
                                }
                                return null;
                            },
                            showCancelButton: true,
                            confirmButtonText: 'Sí, cerrar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                // Usuario canceló: no hacer nada
                                return;
                            }
                            const reason = (result.value || '').trim();
                            const formData = new FormData();
                            formData.append('table_id', table.id);
                            formData.append('_token', this.cancelOrderToken);

                            fetch(this.cancelOrderUrl, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': this.cancelOrderToken,
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                    },
                                    body: JSON.stringify({
                                        table_id: table.id,
                                        cancel_reason: reason,
                                    }),
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success) {
                                        const idx = this.tables.findIndex(t => t.id == table
                                            .id);
                                        if (idx !== -1) {
                                            this.tables[idx] = {
                                                ...this.tables[idx],
                                                situation: 'libre',
                                                total: 0,
                                                elapsed: null,
                                                waiter: null,
                                                client: null,
                                                diners: 0
                                            };
                                            this.updateFilteredTables();
                                        }
                                        Swal.fire({
                                            toast: true,
                                            position: 'bottom-end',
                                            icon: 'success',
                                            title: data.message ||
                                                'Mesa cerrada correctamente',
                                            showConfirmButton: false,
                                            timer: 3500,
                                            timerProgressBar: true
                                        });
                                        // Sin reload: la mesa ya se actualizó en this.tables y updateFilteredTables()
                                    } else if (data && data.message) {
                                        Swal.fire({
                                            toast: true,
                                            position: 'bottom-end',
                                            icon: 'error',
                                            title: data.message,
                                            showConfirmButton: false,
                                            timer: 3500,
                                            timerProgressBar: true
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.fire({
                                        toast: true,
                                        position: 'bottom-end',
                                        icon: 'error',
                                        title: 'Error al cerrar la mesa.',
                                        showConfirmButton: false,
                                        timer: 3500,
                                        timerProgressBar: true
                                    });
                                });
                        });
                    },
                };

                return componentData;
            });

        };


        registerPosSystem();
        document.addEventListener('alpine:init', registerPosSystem);
        document.addEventListener('turbo:load', registerPosSystem);
        document.addEventListener('turbo:render', registerPosSystem);
        document.addEventListener('turbo:load', function() {
            const path = window.location.pathname || '';
            if (path.indexOf('/Pedidos') !== -1 && path.indexOf('/cobrar') === -1 && path.indexOf('/crear') === -
                1 && path.indexOf('/reporte') === -1) {
                if (typeof window.__posRefreshTables === 'function') {
                    setTimeout(window.__posRefreshTables, 100);
                }
            }
        });
        (function() {
            function collapseSidebar() {
                if (window.Alpine && Alpine.store && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').isExpanded = false;
                }
            }

            function expandSidebar() {
                if (window.Alpine && Alpine.store && Alpine.store('sidebar')) {
                    Alpine.store('sidebar').isExpanded = true;
                    if (window.innerWidth >= 1280) {
                        localStorage.setItem('sidebarExpanded', 'true');
                    }
                }
            }

            // Contraer al cargar la página de Pedidos
            collapseSidebar();

            document.addEventListener('turbo:load', function() {
                const path = window.location.pathname || '';
                if (path.indexOf('/Pedidos') !== -1 &&
                    path.indexOf('/cobrar') === -1 &&
                    path.indexOf('/crear') === -1 &&
                    path.indexOf('/reporte') === -1) {
                    collapseSidebar();
                }
            });

            // Antes de ir a otra página distinta de Pedidos, volver a expandir
            document.addEventListener('turbo:before-visit', function(e) {
                const url = e.detail.url || '';
                if (url.indexOf('/Pedidos') === -1) {
                    expandSidebar();
                }
            });
        })();
    </script>

    @push('scripts')
        <script>
            (function() {
                function showFlashToast() {
                    const msg = sessionStorage.getItem('flash_success_message');
                    if (!msg) return;
                    sessionStorage.removeItem('flash_success_message');
                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'success',
                            title: msg,
                            showConfirmButton: false,
                            timer: 3500,
                            timerProgressBar: true
                        });
                    }
                }
                showFlashToast();
                document.addEventListener('turbo:load', showFlashToast);
            })();
        </script>
    @endpush
@endsection
