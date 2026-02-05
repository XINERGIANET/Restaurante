´ñ{+@extends('layouts.app')

@section('content')
    <div x-data="{ editOpen: {{ isset($area) ? 'true' : 'false' }} }">
        <x-common.page-breadcrumb pageTitle="Areas" />

        @if (isset($area))
            <x-common.component-card title="Editar area" desc="Actualiza la informacion del area.">
                @if ($errors->any())
                    <div class="mb-5">
                        <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form method="POST" action="{{ route('areas.update', $area) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    @include('areas._form', ['area' => $area])

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="md" variant="primary">
                            <i class="ri-save-line"></i>
                            <span>Actualizar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline" href="{{ route('areas.index') }}">
                            <i class="ri-close-line"></i>
                            <span>Cancelar</span>
                        </x-ui.link-button>
                    </div>
                </form>
            </x-common.component-card>
        @endif

        <x-common.component-card title="Areas" desc="Gestiona las areas de tus sucursales.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <div class="text-sm text-gray-500">
                    Total: <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $areas->count() }}</span>
                </div>
                <x-ui.button size="md" variant="primary" type="button"
                    style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-area-modal')">
                    <i class="ri-add-line"></i>
                    <span>Nueva area</span>
                </x-ui.button>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white overflow-visible dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Sucursal</p>
                            </th>
                            <th class="px-5 py-3 text-right sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($areas as $item)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $item->name }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $item->branch?->legal_name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="primary"
                                                href="{{ route('areas.tables.index', $item) }}"
                                                className="bg-brand-500 text-white hover:bg-brand-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #3B82F6; color: #FFFFFF;"
                                                aria-label="Ver mesas"
                                            >
                                                <i class="ri-table-line"></i>
                                            </x-ui.link-button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Mesas</span>
                                        </div>
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="edit"
                                                href="{{ route('areas.edit', $item) }}"
                                                className="bg-warning-500 text-white hover:bg-warning-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #FBBF24; color: #111827;"
                                                aria-label="Editar"
                                            >
                                                <i class="ri-pencil-line"></i>
                                            </x-ui.link-button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Editar</span>
                                        </div>
                                        <form
                                            method="POST"
                                            action="{{ route('areas.destroy', $item) }}"
                                            class="relative group js-swal-delete"
                                            data-swal-title="Eliminar area?"
                                            data-swal-text="Se eliminara {{ $item->name }}. Esta accion no se puede deshacer."
                                            data-swal-confirm="Si, eliminar"
                                            data-swal-cancel="Cancelar"
                                            data-swal-confirm-color="#ef4444"
                                            data-swal-cancel-color="#6b7280"
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button
                                                size="icon"
                                                variant="eliminate"
                                                type="submit"
                                                className="bg-error-500 text-white hover:bg-error-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;"
                                                aria-label="Eliminar"
                                            >
                                                <i class="ri-delete-bin-line"></i>
                                            </x-ui.button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Eliminar</span>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-layout-grid-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay areas registradas.</p>
                                        <p class="text-gray-500">Crea la primera area para comenzar.</p>
                                        <x-ui.button size="sm" variant="primary" type="button" @click="$dispatch('open-area-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar area</span>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-common.component-card>

        <x-ui.modal x-data="{ open: false }" @open-area-modal.window="open = true" @close-area-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-3xl">
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-layout-grid-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar area</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion del area.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Cerrar">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-5">
                        <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form method="POST" action="{{ route('areas.store') }}" class="space-y-6">
                    @csrf

                    @include('areas._form', ['area' => null])

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
