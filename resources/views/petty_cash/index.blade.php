@extends('layouts.app')

@php
    use Illuminate\Support\HtmlString;
    // --- ICONOS (Estilo Módulos) ---
    $SearchIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" /><path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
    $ClearIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
@endphp

@section('content')
    <div x-data="{ 
        open: {{ $errors->any() ? 'true' : 'false' }}, 
        type: '{{ old('type', 'income') }}' 
    }">
    
    <x-common.page-breadcrumb pageTitle="Movimientos de Caja" />

    <x-common.component-card title="Gestión de Movimientos" desc="Control de ingresos, egresos y traslados de fondos.">
        
        {{-- 1. BOTONERA SUPERIOR --}}
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                 {{-- Buscador --}}
                <form method="GET" class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        {!! $SearchIcon !!}
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder=" Buscar movimiento..."
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                </form>
            </div>
            
            <div class="flex flex-wrap gap-2">
                @if(!$hasOpening)
                    <x-ui.link-button
                        size="md"
                        variant="primary"
                        style="background-color: #3B82F6; color: #FFFFFF;"  
                        @click="$dispatch('open-movement-modal', { concept: 'Apertura de caja', docId: '{{ $ingresoDocId }}' })"
                    >
                        <i class="ri-key-2-line"></i>
                        <span>Aperturar Caja</span>
                    </x-ui.link-button>
                @else
                    <x-ui.link-button
                        size="md"
                        variant="primary"
                        style="background-color: #00A389; color: #FFFFFF;"  
                        @click="$dispatch('open-movement-modal', { concept: '', docId: '{{ $ingresoDocId }}' })"
                    >
                        <i class="ri-add-line"></i>
                        <span>Ingreso</span>
                    </x-ui.link-button>

                    <x-ui.link-button
                        size="md"
                        variant="primary"
                        style="background-color: #EF4444; color: #FFFFFF; border: none;" 
                        @click="$dispatch('open-movement-modal', { concept: '', docId: '{{ $egresoDocId }}' })"
                    >
                        <i class="ri-subtract-line mr-1"></i>
                        <span>Egreso</span>
                    </x-ui.link-button>

                    <x-ui.link-button size="md" style="background-color: #FACC15; color: #111827;">
                        <i class="ri-lock-2-line"></i> Cerrar
                    </x-ui.link-button>
                @endif
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>Total</span>
                <x-ui.badge size="sm" variant="light" color="info">{{ $movements->total() }}</x-ui.badge>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[880px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Orden</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Número</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Tipo</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Comentario</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Monto</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Fecha</p></th>
                            <th class="px-5 py-3 text-right sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6"><span class="font-bold text-[#00A389]">#{{ $loop->iteration }}</span></td>
                                <td class="px-5 py-4 sm:px-6"><span class="text-sm font-mono text-gray-600">{{ $movement->number }}</span></td>
                                <td class="px-5 py-4 sm:px-6">
                                    @php
                                        $typeName = optional($movement->movementType)->name ?? 'General';
                                        $isIngreso = stripos($typeName, 'ingreso') !== false || stripos($movement->comment, 'Apertura') !== false;
                                        $colorClass = $isIngreso ? 'success' : 'error';
                                    @endphp
                                    <x-ui.badge variant="light" color="{{ $colorClass }}">
                                        {{ $typeName }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 sm:px-6"><p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $movement->comment }}</p></td>
                                <td class="px-5 py-4 sm:px-6"><span class="font-bold text-gray-800">S/. {{ number_format($movement->amount ?? 0, 2) }}</span></td>
                                <td class="px-5 py-4 sm:px-6"><span class="text-xs text-gray-500">{{ $movement->moved_at ? $movement->moved_at->format('d/m/Y H:i') : '-' }}</span></td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.link-button size="icon" variant="edit" href="#" style="border-radius: 100%; background-color: #FBBF24; color: #111827;">
                                            <i class="ri-pencil-line"></i>
                                        </x-ui.link-button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                    No hay movimientos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </x-common.component-card>
    
    {{-- MODAL PARA REGISTRAR MOVIMIENTO --}}
    <x-ui.modal 
            x-data="{ 
                open: {{ $errors->any() ? 'true' : 'false' }}, 
                formConcept: '{{ old('concept') }}', 
                formDocId: '{{ old('document_type_id') }}',
                ingresoId: '{{ $ingresoDocId }}' 
            }" 
            
            @open-movement-modal.window="
                open = true; 
                formConcept = $event.detail.concept || ''; 
                formDocId = $event.detail.docId || '';
            " 
            @close-module-modal.window="open = false" 
            :isOpen="false" 
            class="max-w-3xl"
        >
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">
                        <span x-text="formDocId == ingresoId ? 'Ingreso' : 'Egreso'"></span>
                    </p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90" 
                        x-text="formDocId == ingresoId ? 'Registrar Ingreso' : 'Registrar Egreso'">
                    </h3>
                    <p class="mt-1 text-sm text-gray-500" 
                    x-text="formDocId == ingresoId ? 'Ingrese los detalles del ingreso.' : 'Ingrese los detalles del egreso.'">
                    </p>
                </div>
                
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl transition-colors duration-300"
                    :class="formDocId == ingresoId ? 'bg-brand-50 text-brand-500' : 'bg-red-50 text-red-500'">
                    <i class="text-xl" :class="formDocId == ingresoId ? 'ri-add-line' : 'ri-subtract-line'"></i>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.petty-cash.store') }}" class="space-y-6">
                @csrf

                <input type="hidden" name="document_type_id" x-model="formDocId">

                @include('petty_cash._form', ['movement' => null])

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" size="md" variant="primary">
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
        
    </div> 
@endsection