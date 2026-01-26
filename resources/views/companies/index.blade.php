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

    $SaveIcon = new HtmlString('
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M5 13L9 17L19 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
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

    $BuildingIcon = new HtmlString('
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 21V5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M9 21V15H15V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M8 7H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M14 7H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M8 11H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M14 11H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
    ');
@endphp

@section('content')
    <div x-data="{}">
    <x-common.page-breadcrumb pageTitle="Empresas" />

    @if (session('status'))
        <div class="mb-5">
            <x-ui.alert variant="success" title="Listo" :message="session('status')" />
        </div>
    @endif

    <x-common.component-card title="Listado de empresas" desc="Gestiona las empresas registradas en el sistema.">
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
                        placeholder="Buscar por razon social, RUC o direccion"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                    />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="primary" type="submit" :startIcon="$SearchIcon">Buscar</x-ui.button>
                    <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.companies.index') }}" :startIcon="$ClearIcon">Limpiar</x-ui.link-button>
                </div>
            </form>
            <x-ui.link-button
                size="md"
                variant="primary"
                href="{{ route('admin.companies.create') }}"
                :startIcon="$PlusIcon"
                @click.prevent="$dispatch('open-company-modal')"
            >
                Nueva empresa
            </x-ui.link-button>
        </div>

        <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-2 text-sm text-gray-500">
                <span>Total</span>
                <x-ui.badge size="sm" variant="light" color="info">{{ $companies->total() }}</x-ui.badge>
                <span class="text-gray-400">empresas</span>
            </div>
            <div class="text-sm text-gray-500">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $companies->total() }}</span>
            </div>
        </div>

        <div class="mt-4 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <div class="max-w-full overflow-x-auto custom-scrollbar">
                <table class="w-full min-w-[880px]">
                    <thead>
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Razon social</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">RUC</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Direccion</p>
                            </th>
                            <th class="px-5 py-3 text-right sm:px-6">
                                <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($companies as $company)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="space-y-1">
                                        <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $company->legal_name }}</p>
                                        <p class="text-gray-500 text-theme-xs dark:text-gray-400">ID: {{ $company->id }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-700 text-theme-sm dark:text-gray-200">{{ $company->tax_id }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $company->address }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
                                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.companies.show', $company) }}" :startIcon="$ViewIcon">
                                            Ver
                                        </x-ui.link-button>
                                        <x-ui.link-button
                                            size="sm"
                                            variant="outline"
                                            href="{{ route('admin.companies.edit', $company) }}"
                                            :startIcon="$EditIcon"
                                            @click.prevent="$dispatch('edit-company-modal', @js(['id' => $company->id, 'tax_id' => $company->tax_id, 'legal_name' => $company->legal_name, 'address' => $company->address]))"
                                        >
                                            Editar
                                        </x-ui.link-button>
                                        <form method="POST" action="{{ route('admin.companies.destroy', $company) }}" onsubmit="return confirm('Eliminar esta empresa?')">
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
                                <td colspan="4" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            {!! $BuildingIcon !!}
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay empresas registradas.</p>
                                        <p class="text-gray-500">Crea tu primera empresa para comenzar.</p>
                                        <x-ui.link-button
                                            size="sm"
                                            variant="primary"
                                            href="{{ route('admin.companies.create') }}"
                                            :startIcon="$PlusIcon"
                                            @click.prevent="$dispatch('open-company-modal')"
                                        >
                                            Registrar empresa
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
            <p class="text-xs text-gray-500">
                Pagina {{ $companies->currentPage() }} de {{ $companies->lastPage() }}
            </p>
            <div>
                {{ $companies->links() }}
            </div>
        </div>
    </x-common.component-card>

    <x-ui.modal
        x-data="{
            open: @js($errors->any()),
            mode: @js(old('form_mode', 'create')),
            form: {
                id: @js(old('company_id')),
                tax_id: @js(old('tax_id', '')),
                legal_name: @js(old('legal_name', '')),
                address: @js(old('address', '')),
            },
            createUrl: @js(route('admin.companies.store')),
            updateBaseUrl: @js(url('/admin/empresas')),
            init() {
                this.$watch('open', value => {
                    document.body.style.overflow = value ? 'hidden' : 'unset';
                });
            },
            openCreate() {
                this.mode = 'create';
                this.form = { id: null, tax_id: '', legal_name: '', address: '' };
                this.open = true;
            },
            openEdit(company) {
                this.mode = 'edit';
                this.form = {
                    id: company.id,
                    tax_id: company.tax_id || '',
                    legal_name: company.legal_name || '',
                    address: company.address || ''
                };
                this.open = true;
            }
        }"
        @open-company-modal.window="openCreate()"
        @edit-company-modal.window="openEdit($event.detail)"
        @close-company-modal.window="open = false"
        :isOpen="false"
        class="max-w-3xl"
    >
        <div class="p-6 sm:p-8">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-gray-400">Administracion</p>
                    <h3 class="mt-2 text-lg font-semibold text-gray-800 dark:text-white/90" x-text="mode === 'create' ? 'Registrar empresa' : 'Editar empresa'"></h3>
                    <p class="mt-1 text-sm text-gray-500" x-text="mode === 'create' ? 'Ingresa la informacion principal de la empresa.' : 'Actualiza la informacion principal de la empresa.'"></p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                    {!! $BuildingIcon !!}
                </div>
            </div>

            @if ($errors->any())
                <div class="mb-5">
                    <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                </div>
            @endif

            <form method="POST" :action="mode === 'create' ? createUrl : `${updateBaseUrl}/${form.id}`" class="space-y-6">
                @csrf
                <template x-if="mode === 'edit'">
                    <input type="hidden" name="_method" value="PUT" />
                </template>
                <input type="hidden" name="form_mode" x-model="mode" />
                <input type="hidden" name="company_id" x-model="form.id" />

                @include('companies._form', ['company' => null, 'useAlpine' => true])

                <div class="flex flex-wrap gap-3">
                    <x-ui.button type="submit" size="md" variant="primary" :startIcon="$SaveIcon">
                        <span x-text="mode === 'create' ? 'Guardar' : 'Actualizar'"></span>
                    </x-ui.button>
                    <x-ui.button type="button" size="md" variant="outline" :startIcon="$ClearIcon" @click="open = false">
                        Cancelar
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
    </div>
@endsection
