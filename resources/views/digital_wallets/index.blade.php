@extends('layouts.app')
@section('content')
    <x-common.page-breadcrumb pageTitle="{{ 'Billeteras Digitales' }}" />
    <x-common.component-card title="Listado de billeteras digitales" desc="Gestiona las billeteras digitales registradas en el sistema.">
        <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
            <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                <div class="relative flex-1">
                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"> <i class="ri-search-line"></i>
                    </span>
                    <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por descripcion"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-ui.button size="sm" variant="primary" type="submit">Buscar</x-ui.button>
                    <x-ui.button size="sm" variant="outline" class="rounded-xl"
                        @click="window.location.href='{{ route('admin.digital_wallets.index') }}'">Limpiar</x-ui.button>
                </div>
            </form>
            <x-ui.button size="md" variant="create" @click="$dispatch('open-create-digital-wallet-modal')"><i
                    class="ri-add-line"></i> Crear Billetera Digital</x-ui.button>
        </div>
        @if ($digitalWallets->count() > 0)
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
                                    Orden
                                </th>
                                <th class="px-5 py-3 text-center sm:px-6">
                                    Estado
                                </th>
                                <th class="px-5 py-3 text-center sm:px-6">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($digitalWallets as $digitalWallet)
                                <tr
                                    class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                            {{ $digitalWallet->id }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                            {{ $digitalWallet->description }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                            {{ $digitalWallet->order_num }}</p>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <x-ui.badge variant="light" color="{{ $digitalWallet->status ? 'success' : 'error' }}">
                                            {{ $digitalWallet->status ? 'Activo' : 'Inactivo' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-5 py-4 sm:px-6 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <x-ui.link-button size="sm" variant="outline"
                                                x-on:click.prevent="$dispatch('open-edit-digital-wallet-modal', {{ Illuminate\Support\Js::from(['id' => $digitalWallet->id, 'description' => $digitalWallet->description, 'order_num' => $digitalWallet->order_num, 'status' => $digitalWallet->status]) }})"
                                                variant="edit"><i class="ri-pencil-line"></i></x-ui.link-button>

                                            <form action="{{ route('admin.digital_wallets.destroy', $digitalWallet) }}"
                                                method="POST" data-swal-title="Eliminar billetera digital?"
                                                class="relative group js-swal-delete" data-swal-title="Eliminar billetera digital?"
                                                data-swal-text="Se eliminara {{ $digitalWallet->description }}. Esta accion no se puede deshacer."
                                                data-swal-confirm="Si, eliminar" data-swal-cancel="Cancelar"
                                                data-swal-confirm-color="#ef4444" data-swal-cancel-color="#6b7280">
                                                @csrf
                                                @method('DELETE')
                                                <x-ui.button size="sm" variant="eliminate" type="submit"
                                                    style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;">
                                                    <i class="ri-delete-bin-line"></i>
                                                </x-ui.button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="p-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">
                        No hay billeteras digitales disponibles.
                    </p>
                </div>
            </div>
        @endif
        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $digitalWallets->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $digitalWallets->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $digitalWallets->total() }}</span>
            </div>
            <div>
                {{ $digitalWallets->links() }}
            </div>
            <div>
                <form method="GET" action="{{ route('admin.digital_wallets.index') }}">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <select name="per_page" onchange="this.form.submit()"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        @foreach ($allowedPerPage ?? [10, 20, 50, 100] as $size)
                            <option value="{{ $size }}" {{ ($perPage ?? 10) == $size ? 'selected' : '' }}>{{ $size }} / pagina</option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-common.component-card>

    <!--Modal de creacion de billetera digital-->
    <x-ui.modal x-data="{ open: false }" @open-create-digital-wallet-modal.window="open = true"
        @close-create-digital-wallet-modal.window="open = false" :isOpen="false" class="max-w-md">
        <div class="p-6 space-y-4">
            <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Crear Billetera Digital</h3>
            @if ($errors->any())
                <div class="mb-5">
                    <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                </div>
            @endif
            <form id="create-digital-wallet-form" class="space-y-4" action="{{ route('admin.digital_wallets.store') }}"
                method="POST">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Descripcion <span class="text-error-500">*</span></label>
                    <input type="text" name="description" id="description" value="{{ old('description') }}"
                        placeholder="Ingrese la descripcion" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border {{ $errors->has('description') ? 'border-error-500' : 'border-gray-300' }} bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                    @error('description')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Orden <span class="text-error-500">*</span></label>
                    <input type="number" name="order_num" id="order_num" value="{{ old('order_num') }}"
                        placeholder="Ingrese el orden" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border {{ $errors->has('order_num') ? 'border-error-500' : 'border-gray-300' }} bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                    @error('order_num')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex flex-wrap gap-3 justify-end">
                    <x-ui.button type="submit" size="md" variant="primary">Guardar</x-ui.button>
                    <x-ui.button type="button" size="md" variant="outline"
                        @click="open = false">Cancelar</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>

    <!--Modal de edicion de billetera digital-->
    <x-ui.modal x-data="{ open: false, digitalWalletId: null, description: '', orderNum: null, status: '1' }"
        @open-edit-digital-wallet-modal.window="open = true; digitalWalletId = $event.detail.id; description = $event.detail.description; orderNum = $event.detail.order_num; status = $event.detail.status.toString()"
        @close-edit-digital-wallet-modal.window="open = false" :isOpen="false" class="max-w-md">
        <div class="p-6 space-y-4">
            <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Editar Billetera Digital</h3>
            <form id="edit-digital-wallet-form" class="space-y-4"
                x-bind:action="digitalWalletId ? '{{ url('/admin/herramientas/billeteras-digitales') }}/' + digitalWalletId : '#'"
                method="POST">
                @csrf
                @method('PUT')
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Descripcion</label>
                    <input type="text" name="description" id="edit-description" x-model="description"
                        placeholder="Ingrese la descripcion" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Orden</label>
                    <input type="number" name="order_num" id="edit-order_num" x-model="orderNum"
                        placeholder="Ingrese el orden" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Estado</label>
                    <select name="status" id="edit-status" x-model="status" required
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <div class="flex flex-wrap gap-3 justify-end">
                    <x-ui.button type="submit" size="md" variant="primary">Guardar</x-ui.button>
                    <x-ui.button type="button" size="md" variant="outline"
                        @click="open = false">Cancelar</x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.modal>
@endsection
