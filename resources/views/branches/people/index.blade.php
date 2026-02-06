@extends('layouts.app')

@section('content')
    <div x-data="{ openRow: null }">
        <x-common.page-breadcrumb
            pageTitle="Personal"
            :crumbs="[
                ['label' => 'Empresas', 'url' => route('admin.companies.index')],
                ['label' =>  $company->legal_name . ' | Sucursales', 'url' => route('admin.companies.branches.index', $company)],
                ['label' =>  $branch->legal_name . ' | Personal' ]
            ]"
        />

        <x-common.component-card
            title="Personal de {{ $branch->legal_name }}"
            desc="Gestiona el personal asociado a esta sucursal."
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
                            placeholder="Buscar por nombre, documento o email"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.companies.branches.people.index', [$company, $branch]) }}">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <x-ui.button
                    size="md"
                    variant="primary"
                    type="button"
                    style=" background-color: #12f00e; color: #111827;"
                    @click="$dispatch('open-person-modal')"
                >
                    <i class="ri-add-line"></i>
                    <span>Nuevo personal</span>
                </x-ui.button>
            </div>

            <div class="mt-4 rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-white/[0.03] overflow-x-auto">
                <div class="min-w-max">
                    <table class="w-full">
                        <thead class="bg-gray-50 text-[7px] uppercase tracking-wide text-gray-500 dark:bg-gray-800/60 dark:text-gray-400" style="font-size: 12px;">
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="w-12 px-4 py-4 text-center sticky left-0 z-20 bg-gray-50 dark:bg-gray-800"></th>
                                <th class="px-3 py-4 text-left sm:px-6 whitespace-nowrap sticky left-12 z-20 bg-gray-50 dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 w-32 max-w-[128px] sm:w-auto sm:max-w-none">Nombres</th>
                                <th class="px-5 py-4 text-left sm:px-6 whitespace-nowrap">Tipo</th>
                                <th class="px-5 py-4 text-left sm:px-6 whitespace-nowrap">Nro. Documento</th>
                                <th class="px-5 py-4 text-left sm:px-6 whitespace-nowrap">Fecha nac.</th>
                                <th class="px-5 py-4 text-left sm:px-6 whitespace-nowrap">Genero</th>
                                <th class="px-5 py-4 text-left sm:px-6 whitespace-nowrap">Ubicacion</th>
                                <th class="px-5 py-4 text-right sm:px-6 whitespace-nowrap">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($people as $person)
                            <tr class="group transition hover:bg-gray-50/80 dark:hover:bg-white/5">
                                <td class="px-4 py-4 text-center sticky left-0 z-10 bg-white dark:bg-[#121212] group-hover:bg-gray-50 dark:group-hover:bg-gray-800">
                                    <button type="button"
                                        @click="openRow === {{ $person->id }} ? openRow = null : openRow = {{ $person->id }}"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600 dark:bg-brand-500 dark:text-white">
                                        <i class="ri-add-line" x-show="openRow !== {{ $person->id }}"></i>
                                        <i class="ri-subtract-line" x-show="openRow === {{ $person->id }}"></i>
                                    </button>
                                </td>
                                <td class="px-3 py-4 sm:px-6 sticky left-12 z-10 bg-white dark:bg-[#121212] group-hover:bg-gray-50 dark:group-hover:bg-gray-800 border-r border-gray-100 dark:border-gray-700 w-32 max-w-[128px] sm:w-auto sm:max-w-none">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90 truncate" title="{{ $person->first_name }} {{ $person->last_name }}">
                                        {{ $person->first_name }} {{ $person->last_name }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $person->person_type }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-700 text-theme-sm dark:text-gray-200">{{ $person->document_number }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $person->fecha_nacimiento ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $person->genero ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $person->location?->name ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        @php
                                            $user = $person->user;
                                            $userPayload = $user ? [
                                                'name' => $user->name,
                                                'email' => $user->email,
                                                'profile' => $user->profile?->name,
                                            ] : null;
                                        @endphp
                                        <div class="relative group">
                                            <button
                                                type="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-brand-500 text-white shadow-theme-xs transition hover:bg-brand-600"
                                                aria-label="Ver usuario"
                                                @click="$dispatch('open-user-modal', { person: @js($person->first_name . ' ' . $person->last_name), user: @js($userPayload) })"
                                            >
                                                <i class="ri-user-3-line"></i>
                                            </button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Ver usuario</span>
                                        </div>
                                        <div class="relative group">
                                            <button
                                                type="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-purple-500 text-white shadow-theme-xs transition hover:bg-purple-600"
                                                aria-label="Restablecer contraseña"
                                                style="border-radius: 100%; background-color: #7617ea; color: #ffffff;"

                                                @click="$dispatch('open-reset-password', { action: '{{ route('admin.companies.branches.people.user.password', [$company, $branch, $person]) }}', person: @js($person->first_name . ' ' . $person->last_name) })"
                                            >
                                                <i class="ri-key-2-line"></i>
                                            </button>
                                            <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Restablecer</span>
                                        </div>
                                        <div class="relative group">
                                            <x-ui.link-button
                                                size="icon"
                                                variant="outline"
                                                href="{{ route('admin.companies.branches.people.edit', [$company, $branch, $person]) }}"
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
                                            action="{{ route('admin.companies.branches.people.destroy', [$company, $branch, $person]) }}"
                                            class="relative group js-swal-delete"
                                            data-swal-title="Eliminar personal?"
                                            data-swal-text="Se eliminara {{ $person->first_name }} {{ $person->last_name }}. Esta accion no se puede deshacer."
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
                            <tr x-show="openRow === {{ $person->id }}" x-cloak class="bg-gray-50/60 dark:bg-gray-800/40">
                                <td colspan="8" class="px-6 py-4">
                                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-gray-400">Email</p>
                                            <p class="font-medium text-gray-700 dark:text-gray-200">{{ $person->email }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs uppercase tracking-wide text-gray-400">Telefono</p>
                                            <p class="font-medium text-gray-700 dark:text-gray-200">{{ $person->phone }}</p>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <p class="text-xs uppercase tracking-wide text-gray-400">Direccion</p>
                                            <p class="font-medium text-gray-700 dark:text-gray-200">{{ $person->address }}</p>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-team-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay personal registrado.</p>
                                        <p class="text-gray-500">Crea el primer registro para esta sucursal.</p>
                                        <x-ui.button size="sm" variant="primary" type="button" @click="$dispatch('open-person-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar personal</span>
                                        </x-ui.button>
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
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $people->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $people->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $people->total() }}</span>
                </div>
                <div>
                    {{ $people->links() }}
                </div>
            </div>
        </x-common.component-card>

        <x-ui.modal
            x-data="{ open: false, data: null }"
            @open-user-modal.window="open = true; data = $event.detail"
            :isOpen="false"
            :showCloseButton="false"
            class="max-w-md"
        >
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-user-3-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Usuario asignado</h3>
                            <p class="mt-1 text-sm text-gray-500" x-text="data?.person ?? ''"></p>
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

                <template x-if="data && data.user">
                    <div class="grid gap-4 text-sm">
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-xs uppercase tracking-wide text-gray-400">Nombre</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90" x-text="data.user.name"></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-xs uppercase tracking-wide text-gray-400">Email</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90" x-text="data.user.email"></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-xs uppercase tracking-wide text-gray-400">Perfil</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90" x-text="data.user.profile ?? '-'"></p>
                        </div>
                        <div class="rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-800 dark:bg-white/[0.03]">
                            <p class="text-xs uppercase tracking-wide text-gray-400">Contraseña</p>
                            <p class="font-semibold text-gray-800 dark:text-white/90">********</p>
                        </div>
                    </div>
                </template>

                <template x-if="data && !data.user">
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900/30">
                        Esta persona no tiene usuario asignado.
                    </div>
                </template>
            </div>
        </x-ui.modal>

        <x-ui.modal
            x-data="{ open: false, data: null }"
            @open-reset-password.window="open = true; data = $event.detail"
            :isOpen="false"
            :showCloseButton="false"
            class="max-w-md"
        >
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-50 text-purple-500 dark:bg-purple-500/10">
                            <i class="ri-key-2-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Restablecer contraseña</h3>
                            <p class="mt-1 text-sm text-gray-500" x-text="data?.person ?? ''"></p>
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

                <form method="POST" :action="data?.action" class="space-y-5">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nueva contraseña</label>
                        <input
                            type="password"
                            name="password"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Confirmar contraseña</label>
                        <input
                            type="password"
                            name="password_confirmation"
                            required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                        />
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="md" variant="primary">
                            <i class="ri-save-line"></i>
                            <span>Actualizar</span>
                        </x-ui.button>
                        <x-ui.button type="button" size="md" variant="outline" @click="open = false">
                            <i class="ri-close-line"></i>
                            <span>Cancelar</span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>

        <x-ui.modal x-data="{ open: false }" @open-person-modal.window="open = true" @close-person-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-6xl">
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-team-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar personal</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion del personal.</p>
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

                <form method="POST" action="{{ route('admin.companies.branches.people.store', [$company, $branch]) }}" class="space-y-6">
                    @csrf

                    @include('branches.people._form', ['person' => null])

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
