@extends('layouts.app')

@php
    use Illuminate\Support\HtmlString;

    $SearchIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" />
            <path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
    ');

    $ClearIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            <path d="M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
    ');

    $PlusIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 5V19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
            <path d="M5 12H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
        </svg>
    ');

    $ViewIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M2 12C2 12 5.5 6 12 6C18.5 6 22 12 22 12C22 12 18.5 18 12 18C5.5 18 2 12 2 12Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.6" />
        </svg>
    ');

    $EditIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M16.5 3.5L20.5 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            <path d="M4 20L8.5 19L19.5 8L15.5 4L4.5 15L4 20Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
        </svg>
    ');

    $TrashIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 6H21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M8 6V4C8 3.44772 8.44772 3 9 3H15C15.5523 3 16 3.44772 16 4V6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M6.5 6L7.5 20C7.5 20.5523 7.94772 21 8.5 21H15.5C16.0523 21 16.5 20.5523 16.5 20L17.5 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
    ');

    $BranchIcon = new HtmlString('
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 7C4 5.89543 4.89543 5 6 5H10C11.1046 5 12 5.89543 12 7V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M12 9H18C19.1046 9 20 9.89543 20 11V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M4 21H20" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M7 9H9" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M15 13H17" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
    ');
@endphp

@section('content')
    <x-common.page-breadcrumb
        pageTitle="Sucursales"
        :crumbs="[
            ['label' => 'Empresas', 'url' => route('admin.companies.index')],
            ['label' => 'Sucursales de la empresa ' . $company->legal_name]
        ]"
    />

    <x-common.component-card
        title="Sucursales de {{ $company->legal_name }}"
        desc="Gestiona las sucursales asociadas a esta empresa."
    >
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        {!! $SearchIcon !!}
                    </span>
                    <input
                        type="text"
                        name="search"
                        value="{{ $search }}"
                        placeholder="Buscar por nombre, RUC o direccion"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="primary" type="submit" :startIcon="$SearchIcon">Buscar</x-ui.button>
                    <x-ui.link-button
                        size="sm"
                        variant="outline"
                        href="{{ route('admin.companies.branches.index', $company) }}"
                        :startIcon="$ClearIcon"
                    >
                        Limpiar
                    </x-ui.link-button>
                </div>
            </form>
            <x-ui.link-button
                size="md"
                variant="primary"
                href="{{ route('admin.companies.branches.create', $company) }}"
                :startIcon="$PlusIcon"
            >
                Nueva sucursal
            </x-ui.link-button>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[900px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Sucursal</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">RUC</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Direccion</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Ubicacion</p>
                            </th>
                            <th class="px-5 py-3 text-right sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($branches as $branch)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="space-y-1">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $branch->legal_name }}</p>
                                        <p class="text-gray-500 text-theme-xs dark:text-gray-400">ID: {{ $branch->id }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-700 text-theme-sm dark:text-gray-200">{{ $branch->tax_id }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $branch->address ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $branch->location_id }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.link-button
                                            size="sm"
                                            variant="outline"
                                            href="{{ route('admin.companies.branches.show', [$company, $branch]) }}"
                                            :startIcon="$ViewIcon"
                                        >
                                            Ver
                                        </x-ui.link-button>
                                        <x-ui.link-button
                                            size="sm"
                                            variant="outline"
                                            href="{{ route('admin.companies.branches.edit', [$company, $branch]) }}"
                                            :startIcon="$EditIcon"
                                        >
                                            Editar
                                        </x-ui.link-button>
                                        <form method="POST" action="{{ route('admin.companies.branches.destroy', [$company, $branch]) }}" onsubmit="return confirm('Eliminar esta sucursal?')">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button size="sm" variant="outline" className="text-error-500 ring-error-500/30 hover:bg-error-500/10" :startIcon="$TrashIcon">
                                                Eliminar
                                            </x-ui.button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            {!! $BranchIcon !!}
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay sucursales registradas.</p>
                                        <p class="text-gray-500">Crea la primera sucursal para esta empresa.</p>
                                        <x-ui.link-button
                                            size="sm"
                                            variant="primary"
                                            href="{{ route('admin.companies.branches.create', $company) }}"
                                            :startIcon="$PlusIcon"
                                        >
                                            Registrar sucursal
                                        </x-ui.link-button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $branches->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $branches->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $branches->total() }}</span>
            </div>
            <div>
                {{ $branches->links() }}
            </div>
        </div>
    </x-common.component-card>
@endsection
