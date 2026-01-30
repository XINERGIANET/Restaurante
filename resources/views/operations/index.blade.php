@extends('layouts.app')
@section('content')
    <div x-data="{}">
        <x-common.page-breadcrumb pageTitle="Operaciones" />
        <x-common.component-card title="Gestión de Operaciones" desc="Administra las operaciones registradas en el sistema.">

            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center" id="search-form">
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ request('search') }}"
                            placeholder=" Buscar operación..."
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </x-ui.button>
                        <x-ui.button size="sm" variant="outline" type="reset" form="search-form">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.button>
                    </div>
                </form>

                <x-ui.button size="md" variant="primary" type="button"
                    style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-operation-modal')">
                    <i class="ri-add-line"></i>
                    <span>Nueva operación</span>
                </x-ui.button>
            </div>

            <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <span>Total</span>
                    <x-ui.badge size="sm" variant="light" color="info">{{ $operations->total() }}</x-ui.badge>
                </div>
            </div>

            {{-- TABLA --}}
            <div
                class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="max-w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full min-w-[880px]">
                        <thead>
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-left sm:px-6">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Orden</p>
                                </th>
                                <th class="px-5 py-3 text-left sm:px-6">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre</p>
                                </th>
                                <th class="px-5 py-3 text-left sm:px-6">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Icono</p>
                                </th>
                                <th class="px-5 py-3 text-left sm:px-6">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Estado</p>
                                </th>
                                <th class="px-5 py-3 text-right sm:px-6">
                                    <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($operations as $operation)
                                <tr
                                    class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                    <td class="px-5 py-4 sm:px-6"><span
                                            class="font-bold text-gray-700 dark:text-gray-200">#{{ $operation->order_num }}</span>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                            {{ $operation->name }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-8 w-8 items-center justify-center rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                                <span class="w-5 h-5 fill-current"><i
                                                        class="ri-{{ $operation->icon }}"></i></span>
                                            </div>
                                            <span class="text-xs text-gray-500">{{ $operation->icon }}</span>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <x-ui.badge variant="light" color="{{ $operation->status ? 'success' : 'error' }}">
                                            {{ $operation->status ? 'Activo' : 'Inactivo' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-2">
                                            <div class="relative group">
                                                <x-ui.link-button size="icon" variant="edit"
                                                    href="{{ route('admin.operations.edit', $operation) }}"
                                                    aria-label="Editar">
                                                    <i class="ri-pencil-line"></i>
                                                </x-ui.link-button>
                                                <span
                                                    class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                                    style="transition-delay: 0.5s;">Editar</span>
                                            </div>
                                            <form method="POST"
                                                action="{{ route('admin.operations.destroy', $operation) }}"
                                                class="relative group js-delete-operation"
                                                data-operation-name="{{ $operation->name }}">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button size="icon" variant="eliminate"
                                                    className="bg-error-500 text-white hover:bg-error-600 ring-0 rounded-full"
                                                    aria-label="Eliminar" type="submit">
                                                    <i class="ri-delete-bin-line"></i>
                                                </x-ui.button>
                                                <span
                                                    class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                                    style="transition-delay: 0.5s;">Eliminar</span>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-sm text-center text-gray-500">
                                        No hay operaciones registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-4">
                {{ $operations->links() }}
            </div>
        </x-common.component-card>
    </div>

    <x-ui.modal x-data="{ open: false }" @open-operation-modal.window="open = true"
        @close-operation-modal.window="open = false" :isOpen="true" class="max-w-xl">
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Administracion</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90">Registrar operación</h3>
                    <p class="mt-1 text-sm text-gray-500">Ingresa la informacion principal de la operación.</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.operations.store') }}">
                @csrf
                <div class="grid grid-cols-2 gap-5">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nombre</label>
                        <input type="text" name="name" id="name"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Accion</label>
                        <input type="text" name="action" id="action"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Vista</label>
                        <select name="view_id" id="view_id"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                            <option value="">Seleccione una vista</option>
                            <option value="1">Vista General</option>
                            <option value="2">Vista de Pedidos</option>
                            <option value="3">Vista de Clientes</option>
                            <option value="4">Vista de Productos</option>
                            <option value="5">Vista de Facturas</option>
                            <option value="6">Vista de Pagos</option>
                            <option value="7">Vista de Inventario</option>
                            <option value="8">Vista de Reportes</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Icono</label>
                        <input type="text" name="icon" id="icon"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Estado</label>
                        <select name="status" id="status"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Color</label>
                        <input type="text" name="color" id="color"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                            required>
                    </div>
                </div>
                <div class="flex mt-5 flex-wrap gap-3">
                    <x-ui.button type="submit" size="md" variant="primary">Guardar</x-ui.button>
                    <x-ui.button type="button" size="md" variant="outline" @click="open = false">Cancelar</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
@endsection
