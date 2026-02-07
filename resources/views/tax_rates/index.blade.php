@extends('layouts.app')

@section('content')
    <div x-data="{}">
        <x-common.page-breadcrumb pageTitle="Tasas de impuesto" />

        <x-common.component-card title="Listado de tasas de impuesto" desc="Gestiona las tasas de impuesto registradas.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="w-29">
                        <select
                            name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()"
                        >
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} / pagina</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por descripcion"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit" style="background-color: #63B7EC; border-color: #63B7EC;">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.tax_rates.index') }}">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <x-ui.button size="md" variant="primary" type="button"
                    style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-tax-rate-modal')">
                    <i class="ri-add-line"></i>
                    <span>Nueva tasa de impuesto</span>
                </x-ui.button>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white overflow-x-auto dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[700px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th style="background-color: #63B7EC;" class="px-3 py-3 text-left sm:px-6 first:rounded-tl-xl sticky left-0 z-20 w-24 max-w-[96px] sm:w-auto sm:max-w-none shadow-[2px_0_5px_rgba(0,0,0,0.1)]">
                                <p class="font-semibold text-gray-100 text-theme-xs truncate">Codigo</p>
                            </th>
                            <th style="background-color: #63B7EC;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-gray-100 text-theme-xs uppercase">Descripcion</p>
                            </th>
                            <th style="background-color: #63B7EC;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-gray-100 text-theme-xs uppercase">Tasa de impuesto</p>
                            </th>
                            <th style="background-color: #63B7EC;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-gray-100 text-theme-xs uppercase">Orden</p>
                            </th>
                            <th style="background-color: #63B7EC;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-gray-100 text-theme-xs uppercase">Estado</p>
                            </th>
                            <th style="background-color: #63B7EC;" class="px-5 py-3 text-right sm:px-6 last:rounded-tr-xl">
                                <p class="font-semibold text-gray-100 text-theme-xs uppercase">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($taxRates as $taxRate)
                            <tr class="group/row border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-3 py-4 sm:px-6 sticky left-0 z-10 bg-white dark:bg-[#121212] group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-800 shadow-[2px_0_5px_rgba(0,0,0,0.05)] w-24 max-w-[96px] sm:w-auto sm:max-w-none">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate" title="{{ $taxRate->code }}">{{ $taxRate->code }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $taxRate->description }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $taxRate->tax_rate }} %</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $taxRate->order_num }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $taxRate->status ? 'Activo' : 'Inactivo' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="edit"
                                                href="{{ route('admin.tax_rates.edit', $taxRate) }}"
                                                className="bg-primary-500 text-white hover:bg-primary-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #f59e0b; color: #111827;"
                                                aria-label="Editar"
                                            >
                                                <i class="ri-pencil-line"></i>
                                            </x-ui.link-button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Editar</span>
                                        </div>
                                        <form
                                            method="POST"
                                            action="{{ route('admin.tax_rates.destroy', $taxRate) }}"
                                            class="relative group js-swal-delete"
                                            data-swal-title="Eliminar tasa de impuesto?"
                                            data-swal-text="Se eliminara la tasa de impuesto {{ $taxRate->description }}. Esta accion no se puede deshacer."
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
                                <td colspan="7" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-shopping-bag-3-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay tasas de impuesto registradas.</p>
                                        <p class="text-gray-500">Crea la primera tasa de impuesto para comenzar.</p>
                                        <x-ui.button size="sm" variant="primary" type="button" @click="$dispatch('open-tax-rate-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar tasa de impuesto</span>
                                        </x-ui.button>
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
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $taxRates->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $taxRates->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $taxRates->total() }}</span>
                </div>
                <div>
                    {{ $taxRates->links() }}
                </div>
            </div>
        </x-common.component-card>

        <x-ui.modal
            x-data="{ open: false }"
            @open-tax-rate-modal.window="open = true"
            @close-tax-rate-modal.window="open = false"
            :isOpen="false"
            :showCloseButton="false"
            class="max-w-3xl"
        >
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-percent-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar tasa de impuesto</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion de la tasa de impuesto.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Cerrar"
                    >
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-5">
                        <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.tax_rates.store') }}" class="space-y-6">
                    @csrf

                    @include('tax_rates._form', ['taxRate' => null])

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
