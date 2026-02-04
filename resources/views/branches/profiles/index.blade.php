@extends('layouts.app')

@section('content')
    <div x-data="{}">
        <x-common.page-breadcrumb
            pageTitle="Perfiles"
            :crumbs="[
                ['label' => 'Empresas', 'url' => route('admin.companies.index')],
                ['label' =>  $company->legal_name . ' | Sucursales', 'url' => route('admin.companies.branches.index', $company)],
                ['label' =>  $branch->legal_name . ' | Perfiles' ]
            ]"
        />

        <x-common.component-card
            title="Perfiles de {{ $branch->legal_name }}"
            desc="Lista los perfiles asignados a esta sucursal."
        >
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
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por nombre"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.companies.branches.profiles.index', [$company, $branch]) }}">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <x-ui.link-button
                    size="md"
                    variant="outline"
                    href="{{ route('admin.companies.branches.index', $company) }}"
                >
                    <i class="ri-arrow-left-line"></i>
                    <span>Volver a sucursales</span>
                </x-ui.link-button>
            </div>

            <div class="mt-4 rounded-xl border border-gray-200 bg-white overflow-visible dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Estado</p>
                            </th>
                            <th class="px-5 py-3 text-center sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($profiles as $profile)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="space-y-1">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $profile->name }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <x-ui.badge variant="light" color="{{ $profile->status ? 'success' : 'error' }}">
                                        {{ $profile->status ? 'Activo' : 'Inactivo' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="primary"
                                                href="{{ route('admin.companies.branches.profiles.operations.index', [$company, $branch, $profile, 'icon' => 'ri-tools-line']) }}"
                                                className="bg-brand-500 text-white hover:bg-brand-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #3B82F6; color: #FFFFFF;"
                                                aria-label="Ver operaciones"
                                            >
                                                <i class="ri-tools-line"></i>
                                            </x-ui.link-button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Operaciones</span>
                                        </div>
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="primary"
                                                href="{{ route('admin.companies.branches.profiles.permissions.index', [$company, $branch, $profile, 'icon' => 'ri-lock-line']) }}"
                                                className="bg-purple-500 text-white hover:bg-purple-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #7C3AED; color: #FFFFFF;"
                                                aria-label="Ver permisos"
                                            >
                                                <i class="ri-lock-line"></i>
                                            </x-ui.link-button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Permisos</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-user-settings-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay perfiles asignados.</p>
                                        <p class="text-gray-500">Todos los perfiles creados se asignan automaticamente.</p>
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
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $profiles->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $profiles->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $profiles->total() }}</span>
                </div>
                <div>
                    {{ $profiles->links() }}
                </div>
            </div>
        </x-common.component-card>
    </div>
@endsection
