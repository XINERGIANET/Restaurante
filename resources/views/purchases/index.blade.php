@extends('layouts.app')

@section('content')
    @php
        $viewId = request()->query('view_id');
    @endphp
<div>
    <x-common.page-breadcrumb pageTitle="Compras" />

    <x-common.component-card title="Listado de compras" desc="Gestiona las compras registradas.">
        {{-- filtros y búsqueda similares a ventas pero adaptados --}}
        <div class="flex flex-col gap-4">
            <form method="GET" class="w-full flex flex-col gap-4">
                <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center flex-wrap">
                        <x-ui.per-page-selector :per-page="$perPage ?? 10" />

                        <div class="relative flex-1 min-w-[200px]">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Buscar por proveedor, serie, documento..."
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                        </div>

                        <div class="flex gap-2">
                            <x-ui.button size="md" variant="primary" type="submit"
                                class="h-11 px-6 shadow-sm hover:shadow-md transition-all duration- 0 active:scale-95"
                                style="background-color: #244BB3; border-color: #244BB3;">
                                <i class="ri-search-line text-gray-100"></i>
                                <span class="font-medium text-gray-100 hidden sm:inline">Buscar</span>
                            </x-ui.button>

                            <x-ui.link-button size="md" variant="outline"
                                href="{{ route('purchase.index') }}"
                                class="h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                                <i class="ri-refresh-line"></i>
                                <span class="font-medium hidden sm:inline">Limpiar</span>
                            </x-ui.link-button>
                        </div>
                    </div>

                    <div class="flex-shrink-0">
                        <a type="button" href="{{ route('purchase.create', ['view_id' => $viewId]) }}" class="h-11 inline-flex items-center gap-2 px-4 py-2 rounded-lg" style="background-color: #12f00e; color: #111827;">
                            <i class="ri-add-line"></i>
                            <span>Nueva Compra</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        {{-- tabla --}}
        <div x-data="{ openRow: null }" class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
            <table class="w-full min-w-[1100px]">
                <thead>
                    <tr class="text-white" style="background-color: #63B7EC; color: #FFFFFF;">
                        <th class="px-5 py-3 text-left sm:px-6 first:rounded-tl-xl sticky-left-header">
                            <p class="font-semibold text-white text-theme-xs uppercase">#</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Comprobante</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Subtotal</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">IGV</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Total</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Persona</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Moneda</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-semibold text-white text-theme-xs uppercase">Fecha</p>
                        </th>
                        <th class="px-5 py-3 text-right sm:px-6 last:rounded-tr-xl">
                            <p class="font-semibold text-white text-theme-xs uppercase">Acciones</p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($purchases as $purchase)
                        @php
                            $persona = json_decode($purchase->json_persona ?? '{}');
                            $nombreProveedor = $persona->legal_name ?? $persona->name ?? 'Proveedor Desconocido';
                            $docIdentidad = $persona->tax_id ?? $persona->document_number ?? '-';
                            $iniciales = strtoupper(substr($nombreProveedor, 0, 2));
                        @endphp

                        <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                            <td class="px-4 py-4 sm:px-6 sticky-left">
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                        class="h-6 w-6 flex items-center justify-center rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 transition"
                                        @click="openRow === {{ $purchase->id }} ? openRow = null : openRow = {{ $purchase->id }}">
                                        <i class="ri-add-line"></i>
                                    </button>
                                    <p class="font-bold text-gray-800 text-center text-theme-sm dark:text-white/90">{{ $purchase->id }}</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div>
                                    <p class="font-bold text-gray-800 text-theme-sm dark:text-white/90">{{ $purchase->serie }}-{{ $purchase->anio }}</p>
                                    <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-medium">FACTURA DE COMPRA</p>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">S/ {{ number_format($purchase->subtotal, 2) }}</td>
                            <td class="px-5 py-4 sm:px-6">S/ {{ number_format($purchase->igv, 2) }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="font-bold text-brand-600 text-theme-sm dark:text-brand-400">S/ {{ number_format($purchase->total, 2) }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-800 text-theme-sm dark:text-white/90 truncate max-w-[150px]" title="{{ $nombreProveedor }}">{{ $nombreProveedor }}</p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">{{ $purchase->moneda ?? 'PEN' }}</td>
                            <td class="px-5 py-4 sm:px-6">{{ $purchase->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center justify-end gap-2">
                                    <button class="p-2 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors" title="Ver">
                                        <i class="ri-eye-line"></i>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-amber-600 dark:hover:text-amber-400 transition-colors" title="Editar">
                                        <i class="ri-pencil-line"></i>
                                    </button>
                                    <button class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-colors" title="Eliminar">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr x-show="openRow === {{ $purchase->id }}" x-cloak class="bg-gray-50/70 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800">
                            <td colspan="9" class="px-5 py-3 sm:px-6">
                                {{-- aquí puedes insertar detalles si los necesitas --}}
                                <div>Detalle de la compra #{{ $purchase->id }}</div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12">
                                <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                    <i class="ri-inbox-line text-3xl"></i>
                                    <p>No hay compras registradas</p>
                                    <a href="{{ route('purchase.create') }}" class="mt-2 inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-bold hover:bg-indigo-700 transition-colors">
                                        <i class="ri-add-line"></i>Crear primera compra
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                Mostrando <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $purchases->firstItem() ?? 0 }}</span> - <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $purchases->lastItem() ?? 0 }}</span> de <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $purchases->total() }}</span>
            </div>
            <div>
                {{ $purchases->links() }}
            </div>
        </div>
    </x-common.component-card>
</div>

@push('scripts')
<script>
(function() {
    function showFlashToast() {
        const msg = sessionStorage.getItem('flash_success_message');
        if (!msg) return;
        sessionStorage.removeItem('flash_success_message');
        if (window.Swal) {
            Swal.fire({
                toast: true,
                position: 'bottom-end',
                icon: 'success',
                title: msg,
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true
            });
        }
    }
    showFlashToast();
    document.addEventListener('turbo:load', showFlashToast);
})();
</script>
@endpush
@endsection