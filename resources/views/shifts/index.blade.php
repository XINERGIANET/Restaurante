@extends('layouts.app')

@php
    use Illuminate\Support\HtmlString;
    use App\Helpers\MenuHelper; 

    // --- ICONOS ---
    $SearchIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" /><path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
    $ClearIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
@endphp

@section('content')
    <div x-data="{}">
    
    <x-common.page-breadcrumb pageTitle="Turno" />

    <x-common.component-card title="Gestión de Turnos" desc="Administra los turnos del sistema.">
        
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        {!! $SearchIcon !!}
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder=" Buscar módulo..."
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.link-button size="sm" variant="primary" type="submit" href="{{ route('shifts.index') }}" :startIcon="$SearchIcon">Buscar</x-ui.link-button>
                    <x-ui.link-button  size="sm" variant="outline" href="{{ route('shifts.index') }}" :startIcon="$ClearIcon">Limpiar</x-ui.link-button>
                </div>
            </form>
            
            <x-ui.button
                size="md"
                variant="primary"
                style=" background-color: #12f00e; color: #111827;"  
                @click="$dispatch('open-shift-modal')"
            >
                <i class="ri-add-line"></i>
                <span>Nuevo turno</span>
            </x-ui-button>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>Total</span>
                <x-ui.badge size="sm" variant="light" color="info">{{ $shifts->total() }}</x-ui.badge>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[880px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800 text-left">
                            <th class="px-5 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre / Abr.</th>
                            <th class="px-5 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">Sucursal</th>
                            <th class="px-5 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400">Horario</th>
                            <th class="px-5 py-3 font-medium text-gray-500 text-theme-xs dark:text-gray-400 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($shifts as $shift)
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $shift->name }}</p>
                                        <span class="text-xs text-gray-400">{{ $shift->abbreviation }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <x-ui.badge variant="light" color="primary">
                                        {{ $shift->branch->legal_name ?? 'Sin asignar' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                                        <i class="ri-time-line"></i>
                                        <span class="text-sm font-medium">
                                            {{ \Carbon\Carbon::parse($shift->start_time)->format('H:i') }} - 
                                            {{ \Carbon\Carbon::parse($shift->end_time)->format('H:i') }}
                                        </span>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.link-button
                                            size="icon" variant="edit" href="{{ route('shifts.edit', $shift) }}"
                                            style="border-radius: 100%; background-color: #FBBF24; color: #111827;"
                                            title="Editar"
                                        >
                                            <i class="ri-pencil-line"></i>
                                        </x-ui.link-button>

                                        <form method="POST" action="{{ route('shifts.destroy', $shift) }}" 
                                              class="js-delete-shift" data-shift-name="{{ $shift->name }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button
                                                size="icon" variant="eliminate" 
                                                style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;"
                                                title="Eliminar"
                                                type="submit"
                                            >
                                                <i class="ri-delete-bin-line"></i>
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">
                                    No hay turnos registrados en esta sucursal.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $shifts->links() }}
        </div>
    </x-common.component-card>


    <x-ui.modal x-data="{ open: false }" @open-shift-modal.window="open = true" @close-shift-modal.window="open = false" :isOpen="false" class="max-w-3xl">
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Administracion</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">Registrar turno</h3>
                    <p class="mt-1 text-sm text-gray-500">Ingresa la informacion principal del turno.</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                    <i class="ri-building-line"></i>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-5">
                    <x-ui.alert variant="error" title="Revisa los campos">
                        <ul class="mt-2 list-disc list-inside text-sm">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-ui.alert>
                </div>
            @endif

            <form method="POST" action="{{ route('shifts.store') }}" class="space-y-6">
                @csrf

                @include('shifts._form', ['shift' => null])

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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    const bindDeleteSweetAlert = () => {
        document.querySelectorAll('.js-delete-shift').forEach((form) => {
            
            if (form.dataset.swalBound === 'true') return;
            form.dataset.swalBound = 'true';

            form.addEventListener('submit', (event) => {
                event.preventDefault(); 
                
                const name = form.dataset.shiftName || 'este turno';

                if (!window.Swal) {
                    console.warn('SweetAlert2 no está cargado. Enviando formulario sin confirmación.');
                    form.submit();
                    return;
                }

                Swal.fire({
                    title: '¿Eliminar turno?',
                    text: `Se eliminará "${name}". Esta acción no se puede deshacer.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#ef4444', 
                    cancelButtonColor: '#6b7280', 
                    reverseButtons: true, 
                    focusCancel: true 
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit(); 
                    }
                });
            });
        });
    };

    document.addEventListener('DOMContentLoaded', bindDeleteSweetAlert);
    document.addEventListener('turbo:load', bindDeleteSweetAlert);
</script>
@endpush
@endsection
