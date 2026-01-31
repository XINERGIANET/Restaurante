@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')

{{-- CONTENEDOR PRINCIPAL --}}
<div x-data="posSystem()" class="flex flex-col h-[calc(100vh-9rem)] w-full font-sans text-slate-800 dark:text-white" style="--brand:#3B82F6; --brand-soft:rgba(59,130,246,0.14);">

    {{-- 1. CABECERA --}}
    <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4 shrink-0">
        <div>
            <h2 class="text-2xl font-bold tracking-tight text-gray-800 dark:text-white/90">
                <span x-text="areaName">Salón Principal</span>
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Panel de Control (4x4)
            </p>
        </div>

        {{-- Pestañas --}}
        <div class="flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl">
            <template x-for="area in ['salon', 'terraza', 'bar']">
                <button @click="switchArea(area)" 
                    :class="currentArea === area ? 'bg-white dark:bg-gray-700 shadow-sm text-gray-800 dark:text-white' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'" 
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-all capitalize"
                    x-text="area">
                </button>
            </template>
        </div>
    </div>

    {{-- 2. GRID DE MESAS - FORZADO A 4 COLUMNAS --}}
    <div class="flex-1 overflow-y-auto pb-10">
        
        {{-- GRID FORZADO A 4 COLUMNAS SIEMPRE EN DESKTOP --}}
            <div
                class="grid gap-5"
                style="grid-template-columns: repeat(4, minmax(0, 1fr));"
                x-show="filteredTables.length > 0"
            >
            <template x-for="table in filteredTables" :key="table.id">
                
                {{-- TARJETA EXACTA XINERGIA --}}
                    <div @click="openTable(table)" 
                     class="group relative cursor-pointer rounded-2xl border p-5 transition-all hover:shadow-lg bg-white dark:bg-gray-800 flex flex-col justify-between h-[180px]"
                     :class="table.status === 'occupied' 
                        ? 'border-gray-200 dark:border-gray-700 border-l-4' 
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300'"
                     :style="table.status === 'occupied' ? 'border-left-color: var(--brand)' : ''">
                    
                    <template x-if="table.status === 'occupied'">
                        <span class="absolute top-4 right-4 rounded-full px-2.5 py-1 text-[10px] font-semibold tracking-wide"
                              style="background: var(--brand-soft); color: var(--brand);"
                              x-text="table.elapsed">
                        </span>
                    </template>
                    <template x-if="table.status === 'free'">
                        <span class="absolute top-4 right-4 h-2.5 w-2.5 rounded-full" style="background:#69f0ae;"></span>
                    </template>

                    {{-- PARTE SUPERIOR: ICONO CUADRADO --}}
                    <div>
                        <div class="flex items-center justify-center w-12 h-12 rounded-xl transition-colors mb-4 text-sm font-bold"
                             :class="table.status === 'occupied' 
                                ? 'text-gray-800 dark:text-white' 
                                : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 group-hover:bg-gray-200'"
                             :style="table.status === 'occupied' ? 'background: var(--brand-soft); color: var(--brand);' : ''">
                            <span x-text="String(table.id).padStart(2, '0')"></span>
                        </div>

                        {{-- Títulos --}}
                        <div class="flex flex-col">
                            <div class="flex items-center justify-between gap-2 text-sm font-medium text-gray-500 dark:text-gray-400">
                                <span>Mozo: <span class="text-gray-800 dark:text-white/90" x-text="table.waiter"></span></span>
                                <span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-600 dark:text-gray-300">
                                    <i class="ri-user-3-line text-[12px]"></i>
                                    <span x-text="table.diners"></span>
                                </span>
                            </div>
                            <h4 class="mt-1 text-2xl font-bold text-gray-800 dark:text-white/90"></h4>
                        </div>
                    </div>

                    {{-- PARTE INFERIOR: BADGE Y DINERO --}}
                    <div class="flex items-center justify-between mt-2">
                        
                        {{-- Total Dinero (Izquierda) --}}
                        <div>
                            <template x-if="table.status === 'occupied'">
                                <span class="text-lg font-bold text-gray-800 dark:text-white" x-text="'$' + table.total"></span>
                            </template>
                            <template x-if="table.status === 'free'">
                                <span class="text-sm text-gray-400 font-medium">-</span>
                            </template>
                        </div>

                        {{-- Badge Píldora (Derecha - Estilo exacto imagen) --}}
                        <span class="ml-auto flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-xs font-medium"
                              :class="table.status === 'occupied' 
                                ? 'text-gray-800' 
                                : 'text-gray-800'"
                              :style="table.status === 'occupied' ? 'background: var(--brand-soft); color: var(--brand);' : 'background: rgba(105,240,174,0.2); color: #2f9e6f;'">
                            
                            {{-- Icono SVG --}}
                            <template x-if="table.status === 'occupied'">
                                <svg class="fill-current w-3 h-3" viewBox="0 0 12 12" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.31462 10.3761C5.45194 10.5293 5.65136 10.6257 5.87329 10.6257C5.8736 10.6257 5.8739 10.6257 5.87421 10.6257C6.0663 10.6259 6.25845 10.5527 6.40505 10.4062L9.40514 7.4082C9.69814 7.11541 9.69831 6.64054 9.40552 6.34754C9.11273 6.05454 8.63785 6.05438 8.34486 6.34717L6.62329 8.06753L6.62329 1.875C6.62329 1.46079 6.28751 1.125 5.87329 1.125C5.45908 1.125 5.12329 1.46079 5.12329 1.875L5.12329 8.06422L3.40516 6.34719C3.11218 6.05439 2.6373 6.05454 2.3445 6.34752C2.0517 6.64051 2.05185 7.11538 2.34484 7.40818L5.31462 10.3761Z" fill=""/></svg>
                            </template>
                            <template x-if="table.status === 'free'">
                                <svg class="fill-current w-3 h-3" viewBox="0 0 12 12" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M5.56462 1.62393C5.70193 1.47072 5.90135 1.37432 6.12329 1.37432C6.1236 1.37432 6.12391 1.37432 6.12422 1.37432C6.31631 1.37415 6.50845 1.44731 6.65505 1.59381L9.65514 4.5918C9.94814 4.88459 9.94831 5.35947 9.65552 5.65246C9.36273 5.94546 8.88785 5.94562 8.59486 5.65283L6.87329 3.93247L6.87329 10.125C6.87329 10.5392 6.53751 10.875 6.12329 10.875C5.70908 10.875 5.37329 10.5392 5.37329 10.125L5.37329 3.93578L3.65516 5.65282C3.36218 5.94562 2.8873 5.94547 2.5945 5.65248C2.3017 5.35949 2.30185 4.88462 2.59484 4.59182L5.56462 1.62393Z" fill=""/></svg>
                            </template>

                            <span x-text="table.status === 'occupied' ? 'Ocupada' : 'Libre'"></span>
                        </span>
                    </div>

                </div>
            </template>
        </div>
    </div>

</div>

{{-- SCRIPT LÓGICA --}}
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('posSystem', () => ({
            currentArea: 'salon',
            areaName: 'Salón Principal',
            
            // GENERANDO 16 MESAS PARA LLENAR EL 4x4
            tables: [
                    { id: 1, area: 'salon', status: 'free', diners: 4, total: 0, waiter: 'Carlos' },
                    { id: 2, area: 'salon', status: 'occupied', diners: 4, total: 5359, waiter: 'Ana', elapsed: '00:18' },
                    { id: 3, area: 'salon', status: 'free', diners: 2, total: 0, waiter: 'Luis' },
                    { id: 4, area: 'salon', status: 'occupied', diners: 6, total: 1250, waiter: 'Rosa', elapsed: '01:04' },
                    { id: 5, area: 'salon', status: 'free', diners: 4, total: 0, waiter: 'Miguel' },
                    { id: 6, area: 'salon', status: 'occupied', diners: 2, total: 3400, waiter: 'Elena', elapsed: '00:42' },
                    { id: 7, area: 'salon', status: 'free', diners: 4, total: 0, waiter: 'Jorge' },
                    { id: 8, area: 'salon', status: 'free', diners: 2, total: 0, waiter: 'Patricia' },
                    { id: 9, area: 'salon', status: 'occupied', diners: 3, total: 850, waiter: 'Diego', elapsed: '00:27' },
                    { id: 10, area: 'salon', status: 'free', diners: 4, total: 0, waiter: 'Lucia' },
                    { id: 11, area: 'salon', status: 'free', diners: 2, total: 0, waiter: 'Raul' },
                    { id: 12, area: 'salon', status: 'occupied', diners: 5, total: 2100, waiter: 'Nadia', elapsed: '00:55' },
                    { id: 13, area: 'salon', status: 'free', diners: 4, total: 0, waiter: 'Mario' },
                    { id: 14, area: 'salon', status: 'free', diners: 2, total: 0, waiter: 'Sofia' },
                    { id: 15, area: 'salon', status: 'occupied', diners: 2, total: 150, waiter: 'Bruno', elapsed: '00:09' },
                    { id: 16, area: 'salon', status: 'free', diners: 6, total: 0, waiter: 'Celia' },
                    { id: 17, area: 'terraza', status: 'free', diners: 0, total: 0, waiter: 'Renato' },
                ],

            get filteredTables() {
                return this.tables.filter(t => t.area === this.currentArea);
            },

            switchArea(area) {
                this.currentArea = area;
                const names = { 'salon': 'Salón Principal', 'terraza': 'Terraza Exterior', 'bar': 'Zona de Bar' };
                this.areaName = names[area] || area;
            },

            openTable(table) {
                console.log('Mesa seleccionada:', table.id);
            }
        }));
    });
</script>
@endsection
