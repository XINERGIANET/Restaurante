@extends('layouts.app')
@section('content')
    <x-common.page-breadcrumb pageTitle="Mesas" />
    <x-common.component-card title="Mesas" desc="Gestiona las mesas de la area.">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                        <i class="ri-search-line"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar mesa"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.link-button size="sm" variant="primary" type="submit"
                        href="{{ route('areas.tables.index', $area) }}">Buscar</x-ui.link-button>
                    <x-ui.link-button size="sm" variant="outline"
                        href="{{ route('areas.tables.index', $area) }}">Limpiar</x-ui.link-button>
                    <x-ui.link-button size="md" variant="primary" type="button"
                        style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-tables-modal')">
                        <i class="ri-add-line"></i>
                        <span>Nueva mesa</span>
                    </x-ui.link-button>
                </div>
            </form>
        </div>
        <div
        class="mt-4 rounded-xl border border-gray-200 bg-white overflow-visible dark:border-gray-800 dark:bg-white/[0.03]">

        <table class="w-full">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="px-5 py-3 text-center sm:px-6">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre</p>
                    </th>
                    <th class="px-5 py-3 text-center sm:px-6">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Area</p>
                    </th>
                    <th class="px-5 py-3 text-center sm:px-6">
                        <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($tables as $table)
                    <tr
                        class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                        <td class="px-5 py-4 sm:px-6 text-center">
                            <div class="space-y-1">
                                <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                    {{ $table->name }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4 sm:px-6 text-center">
                            <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                {{ $table->area?->name }}</p>
                            </div>
                        </td>
                        <td class="px-5 py-4 sm:px-6 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <div class="relative group">
                                    <x-ui.link-button size="icon" variant="edit"
                                        href="{{ route('areas.tables.edit', [$area, $table]) }}"
                                        className="bg-warning-500 text-white hover:bg-warning-600 ring-0 rounded-full"
                                        style="border-radius: 100%; background-color: #FBBF24; color: #111827;"
                                        aria-label="Editar mesa">
                                        <i class="ri-pencil-line"></i>
                                    </x-ui.link-button>
                                    <span
                                        class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                        style="transition-delay: 0.5s;">Editar mesa</span>
                                </div>  
                                <form method="POST" action="{{ route('areas.tables.destroy', [$area, $table]) }}"
                                    class="relative group js-swal-delete" data-swal-title="Eliminar mesa?"
                                    data-swal-text="Se eliminara {{ $table->name }}. Esta accion no se puede deshacer."
                                    data-swal-confirm="Si, eliminar" data-swal-cancel="Cancelar"
                                    data-swal-confirm-color="#ef4444" data-swal-cancel-color="#6b7280">
                                    @csrf
                                    @method('DELETE')
                                    <x-ui.button size="icon" variant="eliminate" type="submit"
                                        style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;"
                                        aria-label="Eliminar">
                                        <i class="ri-delete-bin-line"></i>
                                    </x-ui.button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="3" class="px-6 py-12">
                            <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                <div
                                    class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="ri-building-line"></i>
                                </div>
                                    <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay mesas
                                    registradas.</p>
                                <p class="text-gray-500">Crea tu primera mesa para comenzar.</p>
                                <x-ui.button size="sm" variant="primary" type="button"
                                    @click="$dispatch('open-tables-modal')">
                                    <i class="ri-add-line"></i>
                                    <span>Registrar mesa</span>
                                </x-ui.button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    </x-common.component-card>

    <x-ui.modal x-data="{ open: false }" @open-tables-modal.window="open = true" @close-tables-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-xl">
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Pedidos</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">Registrar mesa</h3>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                    <i class="ri-table-line"></i>
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-5">
                    <x-ui.alert variant="error" title="Revisa los campos" message="{{ $errors->first('error') }}" />
                </div>
            @endif

            <form id="create-table-form" class="space-y-6" action="{{ route('areas.tables.store', $area) }}" method="POST">
                @csrf
                
                <div class="grid gap-5 sm:grid-cols-1">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Nombre<span class="text-error-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name') }}" 
                            placeholder="Ingrese el nombre de la mesa" 
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        >
                        @error('name')
                            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

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
@endsection
