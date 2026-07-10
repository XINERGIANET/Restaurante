@extends('layouts.app')

@section('content')
    @php
        $orders = $dashboardData['orders'];
        $summary = $dashboardData['summary'];
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
                <p class="text-xs font-semibold uppercase tracking-widest text-[#C43B25]">Resumen de mozo</p>
                <h2 class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">Mesas atendidas</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $dashboardData['waiterName'] ?: 'Mozo' }} -
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
        </div>

        <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-gray-900">
            <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-800">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Listado del periodo</h3>
            </div>

            @forelse($orders as $order)
                @php
                    $tableName = $order->table?->name ? 'Mesa ' . $order->table->name : 'Mostrador';
                    $areaName = $order->table?->area?->name ?? $order->area?->name;
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
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                        @foreach($order->details as $detail)
                                            @php
                                                $productName = $detail->description ?: ($detail->product?->description ?? 'Producto');
                                                $unitName = $detail->unit?->name ?? $detail->unit?->abbreviation ?? '';
                                                $complements = collect($detail->complements ?? [])->map(function ($item) {
                                                    if (is_array($item)) {
                                                        return $item['description'] ?? $item['name'] ?? null;
                                                    }
                                                    return is_string($item) ? $item : null;
                                                })->filter()->values();
                                            @endphp
                                            <tr>
                                                <td class="px-4 py-3 align-top">
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $productName }}</p>
                                                    @if($complements->isNotEmpty())
                                                        <p class="mt-1 text-xs text-gray-500">Complementos: {{ $complements->implode(', ') }}</p>
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 align-top font-semibold text-gray-800 dark:text-gray-200">
                                                    {{ $formatQty($detail->quantity) }} {{ $unitName }}
                                                </td>
                                                <td class="px-4 py-3 align-top text-gray-600 dark:text-gray-300">
                                                    {{ $detail->comment ?: '-' }}
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
