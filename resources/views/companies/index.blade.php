@extends('layouts.app')

@section('content')
    <div x-data="{}">
        <x-common.page-breadcrumb pageTitle="Empresas" />


        <x-common.component-card title="Listado de empresas" desc="Gestiona las empresas registradas en el sistema.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por razon social, RUC o direccion"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.companies.index') }}">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>
                <x-ui.button size="md" variant="primary" type="button"
                    style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-company-modal')">
                    <i class="ri-add-line"></i>
                    <span>Nueva empresa</span>
                </x-ui.button>
            </div>


            <div
                class="mt-4 rounded-xl border border-gray-200 bg-white overflow-visible dark:border-gray-800 dark:bg-white/[0.03]">

                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Razon social</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">RUC</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Direccion</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr
                                class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="space-y-1">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                            {{ $company->legal_name }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <p class="font-medium text-gray-700 text-theme-sm dark:text-gray-200">
                                        {{ $company->tax_id }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center    ">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $company->address }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-ui.link-button size="icon" variant="primary"
                                            href="{{ route('admin.companies.branches.index', $company) }}">
                                            <i class="ri-store-2-line"></i>
                                        </x-ui.link-button>
                                        <x-ui.link-button size="icon" variant="edit"
                                            href="{{ route('admin.companies.edit', $company) }}">
                                            <i class="ri-pencil-line"></i>
                                        </x-ui.link-button>
                                        <x-ui.button size="icon" variant="eliminate" type="button"
                                            x-on:click.prevent="$dispatch('open-delete-company-modal', {{ Illuminate\Support\Js::from(['id' => $company->id]) }})">
                                            <i class="ri-delete-bin-line"></i>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div
                                            class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-building-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay empresas
                                            registradas.</p>
                                        <p class="text-gray-500">Crea tu primera empresa para comenzar.</p>
                                        <x-ui.button size="sm" variant="primary" type="button"
                                            @click="$dispatch('open-company-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar empresa</span>
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
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->total() }}</span>
                </div>
                <div>
                    {{ $companies->links() }}
                </div>
                <div class="w-28">
                    <form method="GET" action="{{ route('admin.companies.index') }}">
                        <input type="hidden" name="search" value="{{ $search }}">
                        <select name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} /
                                    pagina</option>
                            @endforeach
                        </select>
                    </form>
                </div>
            </div>
        </x-common.component-card>

        <x-ui.modal x-data="{ open: false }" @open-company-modal.window="open = true"
            @close-company-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-3xl">
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-building-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar empresa</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion principal de la empresa.</p>
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
                        <x-ui.alert variant="error" title="Revisa los campos"
                            message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.companies.store') }}" class="space-y-6">
                    @csrf

                    @include('companies._form', ['company' => null])

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

    <!--modal de confirmacion de eliminar empresa-->
    <x-ui.modal x-data="{ open: false, companyId: null }" @open-delete-company-modal.window="open = true; companyId = $event.detail.id"
    @close-delete-company-modal.window="open = false" :isOpen="false" class="max-w-md">
    <div class="p-6 space-y-4">
        <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Eliminar Empresa</h3>
        <p class="text-gray-600 dark:text-gray-200">¿Estás seguro de querer eliminar esta empresa?</p>
    </div>
    <form id="delete-company-form" class="space-y-4 flex flex-col gap-4 justify-end items-end"
        x-bind:action="companyId ? '{{ route('admin.companies.destroy', 0) }}'.replace(/\/0$/, '/' + companyId) : '#'"
        method="POST">
        @csrf
        @method('DELETE')
        <div class="flex flex-wrap gap-3 justify-end  p-5 items-end">
            <x-ui.button type="submit" size="md" variant="eliminate">Eliminar</x-ui.button>
            <x-ui.button type="button" size="md" variant="outline"
                @click="open = false">Cancelar</x-ui.button>
        </div>
    </form>
</x-ui.modal>
@endsection
