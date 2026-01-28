@extends('layouts.app')

@php
    use Illuminate\Support\HtmlString;
    use App\Helpers\MenuHelper; 

    // --- ICONOS ---
    $SearchIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.8" /><path d="M20 20L16.5 16.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
    $ClearIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M18 6L6 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M6 6L18 18" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
    $PlusIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 5V19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /><path d="M5 12H19" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" /></svg>');
    $SaveIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" /></svg>');
    $EditIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M16.5 3.5L20.5 7.5" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /><path d="M4 20L8.5 19L19.5 8L15.5 4L4.5 15L4 20Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>');
    $TrashIcon = new HtmlString('<svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 6H21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /><path d="M8 6V4C8 3.44772 8.44772 3 9 3H15C15.5523 3 16 3.44772 16 4V6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /><path d="M6.5 6L7.5 20C7.5 20.5523 7.94772 21 8.5 21H15.5C16.0523 21 16.5 20.5523 16.5 20L17.5 6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>');
    $ModuleIcon = new HtmlString('<svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 4h6v6H4V4zm10 0h6v6h-6V4zM4 14h6v6H4v-6zm10 0h6v6h-6v-6z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>');
@endphp

@section('content')
    {{-- EL ALCANCE DE ALPINE DEBE CUBRIR TODO EL CONTENIDO PARA MANEJAR EVENTOS GLOBALES --}}
    <div x-data="{}"> 
    
    <x-common.page-breadcrumb pageTitle="Módulos" />

    @if (session('status'))
        <div class="mb-5">
            <x-ui.alert variant="success" title="Listo" :message="session('status')" />
        </div>
    @endif

    <x-common.component-card title="Gestión de Módulos" desc="Administra los elementos principales del menú lateral.">
        
        {{-- FILTROS Y BOTONES --}}
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
                        placeholder="Buscar módulo..."
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="primary" type="submit" :startIcon="$SearchIcon">Buscar</x-ui.button>
                    <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.modules.index') }}" :startIcon="$ClearIcon">Limpiar</x-ui.link-button>
                </div>
            </form>
            
            <x-ui.link-button
                size="md"
                variant="primary"
                href="#"
                :startIcon="$PlusIcon"
                @click.prevent="$dispatch('open-module-modal')"
            >
                Nuevo Módulo
            </x-ui.link-button>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>Total</span>
                <x-ui.badge size="sm" variant="light" color="info">{{ $modules->total() }}</x-ui.badge>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[880px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Orden</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Nombre</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Icono</p></th>
                            <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Estado</p></th>
                            <th class="px-5 py-3 text-right sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($modules as $module)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6"><span class="font-bold text-gray-700 dark:text-gray-200">#{{ $module->order_num }}</span></td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $module->name }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-8 w-8 items-center justify-center rounded bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                            <span class="w-5 h-5 fill-current">{!! MenuHelper::getIconSvg($module->icon) !!}</span>
                                        </div>
                                        <span class="text-xs text-gray-500">{{ $module->icon }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <x-ui.badge variant="light" color="{{ $module->status ? 'success' : 'error' }}">
                                        {{ $module->status ? 'Activo' : 'Inactivo' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        {{-- Botón Editar --}}
                                        <x-ui.link-button
                                            size="sm"
                                            variant="outline"
                                            href="#"
                                            :startIcon="$EditIcon"
                                            @click.prevent="$dispatch('edit-module-modal', @js($module))"
                                        >
                                            Editar
                                        </x-ui.link-button>

                                        {{-- Botón Eliminar --}}
                                        <form method="POST" action="{{ route('admin.modules.destroy', $module) }}" onsubmit="return confirm('¿Eliminar módulo? Se borrará del menú.')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-transparent px-3 py-2 text-sm font-medium text-error-600 hover:bg-error-50 dark:text-error-400 dark:hover:bg-error-500/10">
                                                <span class="w-4 h-4">{!! $TrashIcon !!}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                    No hay módulos registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="mt-4">
            {{ $modules->links() }}
        </div>
    </x-common.component-card>

    {{-- MODAL CON ALPINE JS --}}
    <x-ui.modal
        x-data="{
            open: @js($errors->any()),
            mode: 'create',
            form: {
                id: null,
                name: '',
                icon: '',
                order_num: '',
                status: 1
            },
            createUrl: @js(route('admin.modules.store')),
            updateBaseUrl: @js(url('/admin/modules')), // Ajusta la URL base si es diferente
            
            init() {
                this.$watch('open', value => document.body.style.overflow = value ? 'hidden' : 'unset');
            },
            openCreate() {
                this.mode = 'create';
                this.form = { id: null, name: '', icon: '', order_num: '', status: 1 };
                this.open = true;
            },
            openEdit(module) {
                this.mode = 'edit';
                this.form = { 
                    id: module.id, 
                    name: module.name, 
                    icon: module.icon, 
                    order_num: module.order_num,
                    status: module.status 
                };
                this.open = true;
            }
        }"
        @open-module-modal.window="openCreate()"
        @edit-module-modal.window="openEdit($event.detail)"
        @close-module-modal.window="open = false"
        :isOpen="false"
        class="max-w-xl"
    >
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="mode === 'create' ? 'Nuevo Módulo' : 'Editar Módulo'"></h3>
                    <p class="text-sm text-gray-500">Configura los detalles del módulo del menú.</p>
                </div>
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                    {!! $ModuleIcon !!}
                </div>
            </div>

            @if ($errors->any())
                <x-ui.alert variant="error" class="mb-4" title="Error" message="Revisa los campos del formulario." />
            @endif

            <form method="POST" :action="mode === 'create' ? createUrl : `${updateBaseUrl}/${form.id}`" class="space-y-5">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT" />
                </template>

                {{-- CAMPO NOMBRE --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nombre</label>
                    <input type="text" name="name" x-model="form.name" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" placeholder="Ej: Ventas" />
                </div>

                {{-- CAMPO ICONO (TEXTO) --}}
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                        Clave del Icono
                        <span class="text-xs text-gray-400 font-normal">(Ej: dashboard, ecommerce, forms)</span>
                    </label>
                    <input type="text" name="icon" x-model="form.icon" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" placeholder="dashboard" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- CAMPO ORDEN --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Orden</label>
                        <input type="number" name="order_num" x-model="form.order_num" required class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
                    </div>

                    {{-- CAMPO ESTADO --}}
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Estado</label>
                        <select name="status" x-model="form.status" class="dark:bg-dark-900 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm focus:border-brand-300 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <x-ui.button type="button" size="md" variant="outline" :startIcon="$ClearIcon" @click="open = false">Cancelar</x-ui.button>
                    <x-ui.button type="submit" size="md" variant="primary" :startIcon="$SaveIcon">
                        <span x-text="mode === 'create' ? 'Guardar' : 'Actualizar'"></span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    </div>
@endsection