@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-col gap-4 rounded-2xl border border-gray-200 bg-white dark:bg-gray-900 p-5">
        <x-common.page-breadcrumb pageTitle="Salones de Pedidos" />
        <div x-data="posSystem()" x-cloak
            class="flex flex-col min-h-[calc(100vh-9rem)] w-full font-sans text-slate-800 dark:text-white"
            style="--brand:#3B82F6; --brand-soft:rgba(59,130,246,0.14);">

            <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4 shrink-0">
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
            </div>

            {{-- 2. GRID DE MESAS - RESPONSIVO --}}
            <div class="flex-1 pb-10">
                <template x-if="filteredTables?.length > 0">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-4 gap-5">
                        <template x-for="table in (filteredTables || [])" :key="table.id">
                            <div @click="openTable(table)"
                                class="relative rounded-2xl shadow-lg border overflow-hidden bg-white dark:bg-gray-800 transition-all hover:shadow-lg max-w-[320px] cursor-pointer"
                                :class="table.situation === 'ocupada' ?
                                    'border-gray-200 dark:border-gray-700 border-l-4' :
                                    'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
                                :style="table.situation === 'ocupada' ? 'border-left-color: #F37022' : ''">
                                <div class="p-6 pl-8">
                                    {{-- Header --}}
                                    <div class="flex justify-between items-start mb-4">
                                        <div class="flex items-center gap-4">
                                            <template x-if="table.situation === 'ocupada'">
                                                <div class="bg-gray-100 font-bold  text-lg w-14 h-14 flex items-center justify-center rounded-xl shadow-sm dark:bg-gray-700 dark:text-red-400"
                                                    style="color: #F37022; border: 2px solid #F37022;">
                                                    <span x-text="String(table.name || table.id).padStart(2, '0')"></span>
                                                </div>
                                            </template>
                                            <template x-if="table.situation === 'libre'">
                                                <div
                                                    class="bg-gray-100 border border-blue-500 text-blue-500 font-bold text-lg w-14 h-14 flex items-center justify-center rounded-xl shadow-sm dark:bg-gray-700 dark:text-red-400">
                                                    <span x-text="String(table.name || table.id).padStart(2, '0')"></span>
                                                </div>
                                            </template>
                                            <div>
                                                <div class="flex">
                                                    <p class="font-semibold text-sm text-gray-500 dark:text-gray-400">Mozo:
                                                    </p>
                                                    <p class="font-medium text-gray-800 text-sm dark:text-white/90 ml-1"
                                                        x-text="table.waiter || '-'"></p>
                                                </div>
                                                <div class="flex min-w-0 flex-1">
                                                    <p
                                                        class="font-semibold text-sm text-gray-500 dark:text-gray-400 shrink-0">
                                                        Cliente:</p>
                                                    <p class="font-medium text-gray-800 text-sm dark:text-white/90 ml-1"
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
                                            <span class="text-sm font-medium" x-text="table.diners || 0"></span>
                                        </div>
                                    </div>

                                    {{-- Total + Estado --}}
                                    <div class="flex justify-between items-center mb-5">
                                        <template x-if="table.situation === 'ocupada'">
                                            <p class="text-2xl font-bold text-gray-900 dark:text-white"
                                                x-text="'S/. ' + parseFloat(table.total).toFixed(2)"></p>
                                        </template>
                                        <template x-if="table.situation === 'libre'">
                                            <p class="text-2xl font-bold text-gray-400">-</p>
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
                                            <button type="button" @click.stop="chargeTable(table)" title="Cobrar"
                                                class="inline-flex items-center justify-center bg-green-500 hover:bg-green-600 active:bg-green-700 text-white py-1.5 px-3 rounded-lg transition shadow-sm hover:shadow w-9">
                                                <i class="ri-bank-card-line text-white"></i>
                                            </button>
                                            <button type="button" @click.stop="$dispatch('open-move-table-modal', { table: table })"
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
                    'id'          => is_array($t) ? $t['id'] ?? null : $t->id,
                    'description' => is_array($t) ? $t['name'] ?? '' : $t->name ?? '',
                    'area_id'     => is_array($t) ? $t['area_id'] ?? null : $t->area_id,
                    'situation'   => is_array($t) ? ($t['situation'] ?? 'libre') : ($t->situation ?? 'libre'),
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
        @close-move-table-modal.window="open = false" :isOpen="false"
        :showCloseButton="false" :scrollable="false" class="max-w-3xl">
        <div class="p-3">
            <div class="flex flex-col p-3 gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-col gap-0.5">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Mover mesa</h3>
                    <p x-show="sourceTableId" class="text-sm text-gray-500 dark:text-gray-400">Mesa a mover: <span x-text="sourceTableName"></span> #<span x-text="sourceTableId"></span></p>
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
                        detail: { name: 'area_id', options: this.allAreaOptions }
                    }));
                },
                // Solo mesas libres, excluyendo la mesa origen
                updateTables() {
                    const areaId = this.currentAreaId;
                    const srcId  = this.sourceTableId;
                    let options = this.allTables.filter(t =>
                        String(t.situation ?? '').toLowerCase() !== 'ocupada' &&
                        String(t.id) !== String(srcId)
                    );
                    if (areaId != null && areaId !== '') {
                        options = options.filter(t => String(t.area_id) === String(areaId));
                    }
                    window.dispatchEvent(new CustomEvent('update-combobox-options', {
                        detail: { name: 'table_id', options }
                    }));
                },
                async moveTable() {
                    if (!this.sourceTableId) {
                        if (window.Swal) Swal.fire({ icon: 'warning', title: 'Error', text: 'No se ha seleccionado ninguna mesa para mover.' });
                        return;
                    }
                    if (!this.tableId) {
                        if (window.Swal) Swal.fire({ icon: 'warning', title: 'Error', text: 'Selecciona una mesa destino libre.' });
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
                                table_id:     this.sourceTableId,
                                new_table_id: this.tableId,
                            })
                        });
                        const data = await res.json().catch(() => ({}));
                        if (data.success) {
                            this.open = false;
                            if (window.Swal) {
                                Swal.fire({ toast: true, position: 'bottom-end', icon: 'success', title: data.message || 'Pedido movido correctamente', showConfirmButton: false, timer: 3000, timerProgressBar: true });
                            }
                            if (typeof window.__posRefreshTables === 'function') window.__posRefreshTables();
                        } else {
                            const msg = data.message || 'No se pudo mover el pedido.';
                            if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: msg });
                        }
                    } catch (e) {
                        if (window.Swal) Swal.fire({ icon: 'error', title: 'Error', text: 'Error de conexión. Intenta de nuevo.' });
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
                    createUrl: @json(route('orders.create')),
                    chargeUrl: @json(route('orders.charge')),
                    tablesDataUrl: @json(route('orders.tablesData')),
                    cancelOrderUrl: cancelOrderUrl,
                    cancelOrderToken: @json(csrf_token()),
                    filteredTables: safeFilteredTables,

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
                            if (!this.areas || !Array.isArray(this.areas) || this.areas.length === 0) {
                                this.filteredTables = [...this.tables];
                                return;
                            }
                            if (!this.currentAreaId) {
                                this.filteredTables = [...this.tables];
                                return;
                            }
                            const areaId = Number(this.currentAreaId);
                            if (isNaN(areaId)) {
                                this.filteredTables = [...this.tables];
                                return;
                            }
                            this.filteredTables = this.tables.filter(t => {
                                if (!t || typeof t.area_id === 'undefined') return false;
                                const tableAreaId = Number(t.area_id);
                                return !isNaN(tableAreaId) && tableAreaId === areaId;
                            });
                        } catch (error) {
                            console.error('Error en updateFilteredTables:', error);
                            this.filteredTables = [];
                        }
                    },

                    switchArea(area) {
                        this.currentAreaId = Number(area.id);
                    },

                    openTable(table) {
                        const target = new URL(this.createUrl, window.location.origin);
                        target.searchParams.set('table_id', table.id);
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(target.toString(), {
                                action: 'advance'
                            });
                        } else {
                            window.location.href = target.toString();
                        }
                    },

                    chargeTable(table) {
                        if (table && table.movement_id) {
                            const url = new URL(this.chargeUrl, window.location.origin);
                            url.searchParams.set('movement_id', table.movement_id);
                            url.searchParams.set('table_id', table.id);
                            url.searchParams.set('_t', Date.now());
                            if (window.Turbo && typeof window.Turbo.visit === 'function') {
                                window.Turbo.visit(url.toString(), { action: 'advance' });
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
                            showCancelButton: true,
                            confirmButtonText: 'Sí, cerrar',
                            cancelButtonText: 'Cancelar',
                            reverseButtons: true,
                        }).then((result) => {
                            if (!result.isConfirmed) {
                                // Usuario canceló: no hacer nada
                                return;
                            }

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
                                        table_id: table.id
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
