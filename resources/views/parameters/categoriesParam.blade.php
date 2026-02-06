@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ 'Categorias de parametros' }}" />
    <x-common.component-card title="Listado de categorias de parametros"
        desc="Gestiona las categorias de parametros registradas en el sistema.">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"><i class="ri-search-line"></i>

                    </span>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por descripcion"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.link-button size="sm" variant="primary" type="submit" href="{{ route('admin.parameters.categories.index') }}">Buscar</x-ui.link-button>
                    <x-ui.link-button size="sm" variant="outline" class="rounded-full" href="{{ route('admin.parameters.categories.index') }}">Limpiar</x-ui.link-button>
                    <x-ui.button size="md" variant="create" 
                        @click="$dispatch('open-create-category-modal')">
                        <i class="ri-add-line"></i> Crear Categoria</x-ui.link-button>
                </div>
            </form>
        </div>
        @if ($parameterCategories->count() > 0)
            <div
                class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="max-w-full overflow-x-auto custom-scrollbar">
                    <table class="w-full min-w-[880px]">
                        <thead class="text-left text-theme-xs dark:text-gray-400">
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <th class="px-5 py-3 text-center sm:px-6">
                                    ID
                                </th>
                                <th class="px-5 py-3 text-center sm:px-6">
                                    Descripcion
                                </th>
                                <th class="px-5 py-3 text-center sm:px-6">
                                    Fecha de creacion
                                </th>
                                <th class="px-5 py-3 text-center sm:px-6">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($parameterCategories as $parameterCategory)
                                <tr
                                    class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                            {{ $parameterCategory->id }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                            {{ $parameterCategory->description }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-600 text-theme-sm dark:text-gray-200">
                                            {{ $parameterCategory->created_at->format('d/m/Y H:i:s') }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <x-ui.link-button size="sm" variant="outline"
                                                x-on:click.prevent="$dispatch('open-edit-category-modal', {{ Illuminate\Support\Js::from(['id' => $parameterCategory->id, 'description' => $parameterCategory->description]) }})"
                                                variant="edit">
                                                <i class="ri-pencil-line"></i>
                                            </x-ui.link-button>
                                            <form action="{{ route('admin.parameters.categories.destroy', $parameterCategory) }}" method="POST" data-swal-title="Eliminar categoria?"
                                                class="relative group js-swal-delete"
                                                data-swal-title="Eliminar categoria?"
                                                data-swal-text="Se eliminara {{ $parameterCategory->description }}. Esta accion no se puede deshacer."
                                                data-swal-confirm="Si, eliminar"
                                                data-swal-cancel="Cancelar"
                                                data-swal-confirm-color="#ef4444"
                                                data-swal-cancel-color="#6b7280">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button size="sm" variant="eliminate" type="submit" style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;">
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
                                                {!! $BuildingIcon !!}
                                            </div>
                                            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay
                                                categorias de parametros registradas.</p>
                                            <p class="text-gray-500">Crea tu primera categoria para comenzar.</p>
                                            <x-ui.button size="sm" variant="primary" type="button" :startIcon="$PlusIcon"
                                                @click="$dispatch('open-category-modal')">
                                                Registrar categoria
                                            </x-ui.button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="p-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">
                        No hay categorias de parametros disponibles.
                    </p>
                </div>
            </div>
        @endif
        </div>
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $parameterCategories->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $parameterCategories->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $parameterCategories->total() }}</span>
            </div>
            <div>
                {{ $parameterCategories->links() }}
            </div>
            <div>
                <form method="GET" action="{{ route('admin.parameters.categories.index') }}">
                    <input type="hidden" name="search" value="{{ $search }}">
                    <select
                        name="per_page"
                        onchange="this.form.submit()"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    >
                        @foreach ($allowedPerPage as $size)
                            <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} / pagina</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-common.component-card>


    <!--Modal de creacion de categoria-->
    <x-ui.modal x-data="{ open: false }" @open-create-category-modal.window="open = true"
        @close-create-category-modal.window="open = false" :isOpen="false" class="max-w-md">
        <div class="p-6 space-y-4">
            <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Crear Categoria</h3>
            <form id="create-category-form" class="space-y-4" action="{{ route('admin.parameters.categories.store') }}"
                method="POST" enctype="multipart/form-data">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Descripcion</label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}"
                        placeholder="Ingrese la descripcion" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                <div class="flex flex-wrap gap-3 justify-end">
                    <x-ui.button class="justify-end" type="submit" size="md" variant="primary">Guardar</x-ui.button>
                    <x-ui.button class="justify-end" type="button" size="md" variant="outline"
                        @click="open = false">Cancelar</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!--Modal de edicion de categoria-->
    <x-ui.modal x-data="{ open: false, categoryId: null, description: '' }"
        @open-edit-category-modal.window="open = true; categoryId = $event.detail.id; description = $event.detail.description"
        @close-edit-category-modal.window="open = false" :isOpen="false" class="max-w-md">
        <div class="p-6 space-y-4">
            <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Editar Categoria</h3>
            <form id="edit-category-form" class="space-y-4 flex flex-col gap-4"
                x-bind:action="categoryId ? '{{ url('/admin/herramientas/parametros/categorias') }}/' + categoryId : '#'"
                method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Descripcion</label>
                    <input type="text" name="description" id="edit-description" x-model="description"
                        placeholder="Ingrese la descripcion" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                <div class="flex flex-wrap gap-3 align-end">
                    <x-ui.button class="align-end" type="submit" size="md" variant="primary">Guardar</x-ui.button>
                    <x-ui.button class="align-end" type="button" size="md" variant="outline"
                        @click="open = false">Cancelar</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
@endsection
