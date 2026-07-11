@extends('layouts.app')

@section('content')
    @php
        $orders = $dashboardData['orders'];
        $summary = $dashboardData['summary'];
        $waiterNames = $dashboardData['waiterNames'] ?? collect();
        $formatQty = function ($value) {
            $number = (float) $value;
            return rtrim(rtrim(number_format($number, 2, '.', ''), '0'), '.');
        };
        $statusLabel = function ($status) {
            return match ($status) {
                'FINALIZADO', 'F' => 'Finalizado',
                'CANCELADO', 'C' => 'Cancelado',
                default => 'Pendiente',
            };
        };
        $statusClass = function ($status) {
            return match ($status) {
                'FINALIZADO', 'F' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                'CANCELADO', 'C' => 'bg-red-50 text-red-700 ring-red-200',
                default => 'bg-amber-50 text-amber-700 ring-amber-200',
            };
        };
    @endphp

    <div class="px-4 py-6 md:px-6 2xl:px-10">
        <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-semibold uppercase tracking-widest text-[#C43B25]">Resumen de mozos</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Mesas atendidas</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $dashboardData['waiterName'] ?: 'Todos los mozos' }} -
                    @if($dashboardData['startDate'] === $dashboardData['endDate'])
                        {{ \Carbon\Carbon::parse($dashboardData['startDate'])->format('d/m/Y') }}
                    @else
                        {{ \Carbon\Carbon::parse($dashboardData['startDate'])->format('d/m/Y') }} al
                        {{ \Carbon\Carbon::parse($dashboardData['endDate'])->format('d/m/Y') }}
                    @endif
                </p>
            </div>

            <form method="GET" action="{{ route('dashboard') }}" class="grid grid-cols-1 gap-3 sm:grid-cols-[minmax(0,160px)_minmax(0,160px)_auto] sm:items-end">
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Desde</label>
                    <x-form.date-picker name="start_date" :defaultDate="$dashboardData['startDate']" dateFormat="Y-m-d" :altInput="true" altFormat="d/m/Y" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Hasta</label>
                    <x-form.date-picker name="end_date" :defaultDate="$dashboardData['endDate']" dateFormat="Y-m-d" :altInput="true" altFormat="d/m/Y" />
                </div>
                <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-[#C43B25] px-5 text-sm font-semibold text-white transition hover:bg-[#A83220]">
                    <i class="ri-search-line"></i>
                    Buscar
                </button>
            </form>
        </div>

        <div class="grid grid-cols-2 gap-3 md:grid-cols-5">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Mesas</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['tables'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Pedidos</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['orders'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Productos</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $formatQty($summary['items']) }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Finalizados</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['finished'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Pendientes</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['pending'] }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-gray-900">
                <p class="text-xs text-gray-500">Mozos</p>
                <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $summary['waiters'] }}</p>
            </div>
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Listado del periodo</h3>
            </div>

            @if($waiterNames->isNotEmpty())
                <div class="flex flex-wrap gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                    @foreach($waiterNames as $waiterName)
                        <span class="inline-flex rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-200">
                            {{ $waiterName }}
                        </span>
                    @endforeach
                </div>
            @endif

            @forelse($orders as $order)
                @php
                    $tableName = $order->table?->name ? 'Mesa ' . $order->table->name : 'Mostrador';
                    $areaName = $order->table?->area?->name ?? $order->area?->name;
                    $clientName = trim((string) ($order->movement?->person_name ?? '')) ?: 'Público general';
                    $responsibleName = trim((string) ($order->movement?->responsible_name ?? '')) ?: 'Sin mozo';
                    $detailCount = $order->details->count();
                    $itemQty = $order->details->sum(fn($detail) => (float) ($detail->quantity ?? 0));
                @endphp

                <div x-data="{ open: false }" class="border-b border-gray-100 last:border-b-0 dark:border-gray-800">
                    <div class="grid gap-3 px-4 py-4 md:grid-cols-[1.2fr_1fr_auto] md:items-center">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-semibold text-gray-900 dark:text-white">{{ $tableName }}</p>
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass($order->status) }}">
                                    {{ $statusLabel($order->status) }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                {{ $areaName ?: 'Sin salon' }} - Pedido {{ $order->movement?->number ?? ('#' . $order->id) }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Cliente: {{ $clientName }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">
                                Mozo: {{ $responsibleName }}
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-3 text-sm md:max-w-sm">
                            <div>
                                <p class="text-xs text-gray-500">Hora</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ optional($order->movement?->moved_at ?? $order->updated_at)->format('d/m/Y H:i') }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Productos</p>
                                <p class="font-medium text-gray-800 dark:text-gray-200">{{ $formatQty($itemQty) }} en {{ $detailCount }} linea(s)</p>
                            </div>
                        </div>

                        <button type="button" x-on:click="open = !open" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-200 dark:hover:bg-gray-800">
                            <i class="ri-list-check-2"></i>
                            <span x-text="open ? 'Ocultar' : 'Ver pedido'">Ver pedido</span>
                        </button>
                    </div>

                    <div x-show="open" x-cloak class="bg-gray-50 px-4 pb-4 pt-1 dark:bg-gray-950/40">
                        @if($order->details->isEmpty())
                            <p class="rounded-lg border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-500 dark:border-gray-700 dark:bg-gray-900">
                                Este pedido no tiene productos activos.
                            </p>
                        @else
                            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
                                <table class="min-w-full divide-y divide-gray-100 text-sm dark:divide-gray-800">
                                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3">Producto</th>
                                            <th class="w-28 px-4 py-3">Cantidad</th>
                                            <th class="px-4 py-3">Nota</th>
                                            <th class="w-40 px-4 py-3 text-right">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($order->details as $detail)
                                            @php
                                                $productName = $detail->description ?: ($detail->product?->description ?? 'Producto');
                                                $isDelivered = ($detail->status ?? 'A') === 'E';
                                                $complements = collect($detail->complements ?? [])->map(function ($item) {
                                                    if (is_array($item)) {
                                                        return $item['description'] ?? $item['name'] ?? null;
                                                    }
                                                    return is_string($item) ? $item : null;
                                                })->filter()->values();
                                            @endphp
                                            <tr
                                                x-data="{
                                                    delivered: @js($isDelivered),
                                                    saving: false,
                                                    async markDelivered() {
                                                        if (this.delivered || this.saving) return;
                                                        this.saving = true;
                                                        try {
                                                            const response = await fetch(@js(route('orders.details.deliver', $detail)), {
                                                                method: 'PATCH',
                                                                headers: {
                                                                    'Content-Type': 'application/json',
                                                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '',
                                                                    'Accept': 'application/json',
                                                                },
                                                                body: JSON.stringify({}),
                                                            });
                                                            const data = await response.json();
                                                            if (!response.ok || !data?.success) {
                                                                throw new Error(data?.message || 'No se pudo marcar como entregado.');
                                                            }
                                                            this.delivered = true;
                                                            if (window.Swal) {
                                                                window.Swal.fire({
                                                                    toast: true,
                                                                    position: 'bottom-start',
                                                                    icon: 'success',
                                                                    title: data.message || 'Producto entregado',
                                                                    showConfirmButton: false,
                                                                    timer: 1800,
                                                                    timerProgressBar: true,
                                                                });
                                                            }
                                                        } catch (error) {
                                                            if (window.Swal) {
                                                                window.Swal.fire({
                                                                    toast: true,
                                                                    position: 'bottom-start',
                                                                    icon: 'error',
                                                                    title: error?.message || 'No se pudo actualizar la línea.',
                                                                    showConfirmButton: false,
                                                                    timer: 2400,
                                                                    timerProgressBar: true,
                                                                });
                                                            } else {
                                                                alert(error?.message || 'No se pudo actualizar la línea.');
                                                            }
                                                        } finally {
                                                            this.saving = false;
                                                        }
                                                    }
                                                }"
                                                x-bind:class="delivered ? 'bg-emerald-50/90 ring-1 ring-inset ring-emerald-200' : 'bg-white'"
                                                class="transition-all duration-200"
                                            >
                                                <td class="px-4 py-3 align-top">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <p x-bind:class="delivered ? 'text-emerald-900' : 'text-gray-900'" class="font-medium dark:text-white">{{ $productName }}</p>
                                                        <span x-show="delivered" x-cloak class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                                            <i class="ri-check-line"></i>
                                                            Entregado
                                                        </span>
                                                    </div>
                                                    @if($complements->isNotEmpty())
                                                        <p class="mt-1 text-xs text-gray-500">Complementos: {{ $complements->implode(', ') }}</p>
                                                    @endif
                                                </td>
                                                <td x-bind:class="delivered ? 'text-emerald-800' : 'text-gray-800'" class="px-4 py-3 align-top font-semibold dark:text-gray-200">
                                                    {{ $formatQty($detail->quantity) }}
                                                </td>
                                                <td x-bind:class="delivered ? 'text-emerald-700' : 'text-gray-600'" class="px-4 py-3 align-top dark:text-gray-300">
                                                    {{ $detail->comment ?: '-' }}
                                                </td>
                                                <td class="px-4 py-3 align-top">
                                                    <div class="flex justify-end">
                                                        <button
                                                            type="button"
                                                            x-show="!delivered"
                                                            x-on:click="markDelivered"
                                                            x-bind:disabled="saving"
                                                            class="inline-flex h-9 items-center justify-center gap-2 rounded-full bg-[#C43B25] px-3.5 text-xs font-semibold text-white shadow-sm transition hover:bg-[#A83220] disabled:cursor-not-allowed disabled:opacity-60"
                                                        >
                                                            <i class="ri-check-double-line"></i>
                                                            <span x-text="saving ? 'Marcando...' : 'Entregado'">Entregado</span>
                                                        </button>
                                                        <span
                                                            x-show="delivered"
                                                            x-cloak
                                                            class="inline-flex h-9 items-center justify-center rounded-full bg-emerald-100 px-3 text-xs font-semibold text-emerald-700"
                                                        >
                                                            Listo
                                                        </span>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            @empty
                <div class="px-4 py-12 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-lg bg-gray-100 text-gray-500 dark:bg-gray-800">
                        <i class="ri-restaurant-2-line text-2xl"></i>
                    </div>
                    <h3 class="mt-3 text-base font-semibold text-gray-900 dark:text-white">Sin mesas atendidas</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">No hay pedidos registrados para este periodo.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
