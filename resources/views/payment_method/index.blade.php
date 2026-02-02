@extends('layouts.app')
@section('content')
<x-common.page-breadcrumb pageTitle="{{ 'Métodos de pago' }}" />
<x-common.component-card title="Listado de métodos de pago" desc="Gestiona los métodos de pago registrados en el sistema.">
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
                    @click="window.location.href='{{ route('admin.payment_methods.index') }}'">Limpiar</x-ui.button>
            </div>
        </form>
        <x-ui.button size="md" variant="create" @click="$dispatch('open-create-payment-method-modal')"><i
                class="ri-add-line"></i> Crear Método de Pago</x-ui.button>
    </div>
    @if ($paymentMethods->count() > 0)
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
                        @foreach ($paymentMethods as $paymentMethod)
                            <tr
                                class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                        {{ $paymentMethod->id }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                        {{ $paymentMethod->description }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <p class="font-medium text-gray-900 text-theme-sm dark:text-white/90">
                                        {{ $paymentMethod->order_num ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <x-ui.badge variant="light" color="{{ $paymentMethod->status ? 'success' : 'error' }}">
                                        {{ $paymentMethod->status ? 'Activo' : 'Inactivo' }}
                                    </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 sm:px-6 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <x-ui.link-button size="sm" variant="outline"
                                            x-on:click.prevent="$dispatch('open-edit-payment-method-modal', {{ Illuminate\Support\Js::from(['id' => $paymentMethod->id, 'description' => $paymentMethod->description, 'order_num' => $paymentMethod->order_num, 'status' => $paymentMethod->status]) }})"
                                            variant="edit"><i class="ri-pencil-line"></i></x-ui.link-button>

                                        <form action="{{ route('admin.payment_methods.destroy', $paymentMethod) }}"
                                            method="POST"
                                            class="relative group js-swal-delete"
                                            data-swal-title="Eliminar método de pago?"
                                            data-swal-text="Se eliminara {{ $paymentMethod->description }}. Esta accion no se puede deshacer."
                                            data-swal-confirm="Si, eliminar"
                                            data-swal-cancel="Cancelar"
                                            data-swal-confirm-color="#ef4444"
                                            data-swal-cancel-color="#6b7280">
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
                    No hay métodos de pago disponibles.
                </p>
            </div>
        </div>
    @endif
    <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="text-sm text-gray-500">
            Mostrando
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $paymentMethods->firstItem() ?? 0 }}</span>
            -
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $paymentMethods->lastItem() ?? 0 }}</span>
            de
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $paymentMethods->total() }}</span>
        </div>
        <div>
            {{ $paymentMethods->links() }}
        </div>
        <div>
            <form method="GET" action="{{ route('admin.payment_methods.index') }}">
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

<!--Modal de creacion de método de pago-->
<x-ui.modal x-data="{ open: false }" @open-create-payment-method-modal.window="open = true"
    @close-create-payment-method-modal.window="open = false" :isOpen="false" class="max-w-md">
    <div class="p-6 space-y-4">
        <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Crear Método de Pago</h3>
        @if ($errors->any())
            <div class="mb-5">
                <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
            </div>
        @endif
        <form id="create-payment-method-form" class="space-y-4" action="{{ route('admin.payment_methods.store') }}"
            method="POST">
            @csrf
            @include('payment_method._form')
            <div class="flex flex-wrap gap-3 justify-end">
                <x-ui.button type="submit" size="md" variant="primary">Guardar</x-ui.button>
                <x-ui.button type="button" size="md" variant="outline"
                    @click="open = false">Cancelar</x-ui.button>
            </div>
        </form>
    </div>
</x-ui.modal>

@include('payment_method.edit')
@endsection
