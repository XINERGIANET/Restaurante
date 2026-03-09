@extends('layouts.app')

@section('content')
    <div x-data="{}">
        @php
            use Illuminate\Support\Facades\Route;

            $viewId = request('view_id');
            $operacionesCollection = collect($operaciones ?? []);
            $topOperations = $operacionesCollection->where('type', 'T');
            $rowOperations = $operacionesCollection->where('type', 'R');

            $resolveActionUrl = function ($action, $model = null, $operation = null) use ($viewId) {
                if (!$action) {
                    return '#';
                }

                if (str_starts_with($action, '/') || str_starts_with($action, 'http')) {
                    $url = $action;
                } else {
                    $routeCandidates = [$action];
                    if (!str_starts_with($action, 'admin.')) {
                        $routeCandidates[] = 'admin.' . $action;
                    }
                    $routeCandidates = array_merge(
                        $routeCandidates,
                        array_map(fn($name) => $name . '.index', $routeCandidates),
                    );

                    $routeName = null;
                    foreach ($routeCandidates as $candidate) {
                        if (Route::has($candidate)) {
                            $routeName = $candidate;
                            break;
                        }
                    }

                    if ($routeName) {
                        try {
                            $url = $model ? route($routeName, $model) : route($routeName);
                        } catch (\Exception $e) {
                            $url = '#';
                        }
                    } else {
                        $url = '#';
                    }
                }

                $targetViewId = $viewId;
                if ($operation && !empty($operation->view_id_action)) {
                    $targetViewId = $operation->view_id_action;
                }

                if ($targetViewId && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'view_id=' . urlencode($targetViewId);
                }

                return $url;
            };
        @endphp

        <x-common.page-breadcrumb pageTitle="Pedidos" />

        <x-common.component-card title="Listado de pedidos" desc="Gestiona los pedidos registrados.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif
                    <div class="w-full sm:w-24">
                        <select name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} /
                                    pagina</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por numero, persona o usuario"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="md" variant="primary" type="submit"
                            class="flex-1 sm:flex-none h-11 px-6 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95"
                            style="background-color: #244BB3; border-color: #244BB3;">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline"
                            href="{{ route('orders.list', $viewId ? ['view_id' => $viewId] : []) }}"
                            class="flex-1 sm:flex-none h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
            </div>
            <div class="flex flex-wrap items-end justify-between gap-3 w-full">
                <div class="flex flex-wrap items-end gap-3">
                    <div class="w-[155px] shrink-0 [&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-600 dark:[&_label]:text-gray-400">
                        <x-form.date-picker name="date_from" label="Desde" :defaultDate="$dateFrom" dateFormat="Y-m-d" />
                    </div>
                    <div class="w-[155px] shrink-0 [&_label]:mb-1 [&_label]:text-xs [&_label]:font-medium [&_label]:text-gray-600 dark:[&_label]:text-gray-400">
                        <x-form.date-picker name="date_to" label="Hasta" :defaultDate="$dateTo" dateFormat="Y-m-d" />
                    </div>
                    <div class="w-[155px] shrink-0">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Método de pago</label>
                        <select name="payment_method_id" onchange="this.form.submit()"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                            <option value="">Todos</option>
                            @foreach ($paymentMethods ?? [] as $pm)
                                <option value="{{ $pm->id }}" @selected(($paymentMethodId ?? '') == $pm->id)>{{ $pm->description ?? $pm->id }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-[155px] shrink-0">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipo de documento</label>
                        <select name="document_type_id" onchange="this.form.submit()"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                            <option value="">Todos</option>
                            @foreach ($documentTypes ?? [] as $dt)
                                <option value="{{ $dt->id }}" @selected(($documentTypeId ?? '') == $dt->id)>{{ $dt->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-[100px] shrink-0">
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Caja</label>
                        <select name="cash_register_id" onchange="this.form.submit()"
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-5 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                            <option value="">Todas</option>
                            @foreach ($cashRegisters ?? [] as $cr)
                                <option value="{{ $cr->id }}" @selected(($cashRegisterId ?? '') == $cr->id)>{{ $cr->number ?? $cr->id }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="shrink-0">
                    <button type="button"
                        class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
                        onclick="window.location.href='{{ route('orders.pdf', array_filter([
                            'date_from' => $dateFrom,
                            'date_to' => $dateTo,
                            'search' => $search,
                            'document_type_id' => $documentTypeId ?? null,
                            'payment_method_id' => $paymentMethodId ?? null,
                            'cash_register_id' => $cashRegisterId ?? null,
                        ])) }}'">
                        <i class="ri-file-pdf-line text-base"></i>
                        <span>Descargar PDF</span>
                    </button>
                </div>
            </div>
            </form>
    </div>
    <div x-data="{ openRow: null }"
        class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <table class="w-full table-fixed text-sm">
            <thead class="bg-[#63B7EC] text-white">
                <tr>
                    <th class="w-14 px-2 py-2 text-center first:rounded-tl-xl text-xs font-bold uppercase tracking-wider">
                        #
                    </th>
                    <th class="w-20 px-2 py-2 text-left text-xs font-bold uppercase tracking-wider">
                        Número
                    </th>
                    <th class="w-20 px-2 py-2 text-center text-xs font-bold uppercase tracking-wider">
                        Total
                    </th>
                    <th class="w-24 px-2 py-2 text-center text-xs font-bold uppercase tracking-wider">
                        Fecha
                    </th>
                    <th class="w-24 px-2 py-2 text-left text-xs font-bold uppercase tracking-wider">
                        Persona
                    </th>
                    <th class="w-24 px-2 py-2 text-left text-xs font-bold uppercase tracking-wider">
                        Responsable
                    </th>
                    <th class="w-24 px-2 py-2 text-center last:rounded-tr-xl text-xs font-bold uppercase tracking-wider">
                        Estado
                    </th>
                </tr>
            </thead>
            <tbody>
                @forelse ($orders as $order)
                    @php
                        $rowStatus = strtoupper((string) ($order->status ?? 'PENDIENTE'));
                        $situationStatus = strtoupper((string) ($order->movement?->status ?? 'A'));
                        $rowStatusColor = in_array($rowStatus, ['FINALIZADO', 'F'], true)
                            ? 'success'
                            : (in_array($rowStatus, ['CANCELADO', 'I'], true)
                                ? 'error'
                                : 'warning');
                        $rowStatusText = in_array($rowStatus, ['FINALIZADO', 'F'], true)
                            ? 'Finalizado'
                            : (in_array($rowStatus, ['CANCELADO', 'I'], true)
                                ? 'Cancelado'
                                : 'Pendiente');
                        $situationColor = in_array($situationStatus, ['A', '1'], true) ? 'success' : 'error';
                        $situationText = in_array($situationStatus, ['A', '1'], true) ? 'Activado' : 'Inactivo';
                    @endphp
                    <tr
                        class="border-b border-gray-100 transition hover:bg-gray-100/50 dark:border-gray-800 dark:hover:bg-white/5 {{ $loop->iteration % 2 === 0 ? 'bg-gray-50/60 dark:bg-gray-800/30' : 'bg-white dark:bg-transparent' }}">
                        <td class="px-2 py-2 text-center">
                            <div class="flex items-center justify-center gap-1">
                                <button type="button"
                                    @click="openRow === {{ $order->id }} ? openRow = null : openRow = {{ $order->id }}"
                                    class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#63B7EC] text-white transition hover:opacity-90">
                                    <i class="ri-add-line text-sm" x-show="openRow !== {{ $order->id }}"></i>
                                    <i class="ri-subtract-line text-sm" x-show="openRow === {{ $order->id }}"></i>
                                </button>
                            </div>
                        </td>
                        <td class="px-2 py-2">
                            @php
                                $num = $order->movement?->number ?? null;
                                $numDisplay = $num
                                    ? (ctype_digit((string) $num)
                                        ? str_pad($num, 8, '0', STR_PAD_LEFT)
                                        : $num)
                                    : '-';
                            @endphp
                            <span class="text-gray-700 text-xs dark:text-gray-300 truncate block max-w-full" title="{{ $numDisplay }}">{{ $numDisplay }}</span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <span class="text-gray-700 text-xs font-medium dark:text-gray-300 tabular-nums">{{ number_format((float) ($order->total ?? 0), 2) }}</span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <span class="text-gray-600 text-xs dark:text-gray-400 tabular-nums">{{ $order->movement?->moved_at?->format('d/m/Y H:i') ?? '-' }}</span>
                        </td>
                        <td class="px-2 py-2 overflow-hidden">
                            <span class="text-gray-600 text-xs dark:text-gray-400 truncate block max-w-full" title="{{ $order->movement?->person_name ?? '-' }}">{{ $order->movement?->person_name ?? '-' }}</span>
                        </td>
                        <td class="px-2 py-2 overflow-hidden">
                            <span class="text-gray-600 text-xs dark:text-gray-400 truncate block max-w-full" title="{{ $order->movement?->responsible_name ?? '-' }}">{{ $order->movement?->responsible_name ?? '-' }}</span>
                        </td>
                        <td class="px-2 py-2 text-center">
                            <x-ui.badge variant="light" color="{{ $rowStatusColor }}"
                                class="inline-flex text-[11px] font-medium px-2 py-0.5">
                                {{ $rowStatusText }}
                            </x-ui.badge>
                        </td>
                    </tr>
                    {{-- Acordeón: Detalle del pedido (estilo compras) --}}
                    <tr x-show="openRow === {{ $order->id }}" x-cloak x-transition
                        class="bg-slate-50 dark:bg-slate-800/40 border-b border-gray-200 dark:border-gray-800">
                        <td colspan="7" class="px-6 py-5">
                            <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900 overflow-hidden shadow-sm">
                                <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80 flex items-center gap-2">
                                    <i class="ri-file-list-3-line text-brand-500"></i>
                                    <h4 class="text-sm font-bold text-gray-700 dark:text-gray-200">Detalle del pedido #{{ $order->movement?->number ?? $order->id }}</h4>
                                </div>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left">
                                        <thead class="text-xs text-gray-500 uppercase bg-white dark:bg-gray-900 dark:text-gray-400 border-b border-gray-100 dark:border-gray-800">
                                            <tr>
                                                <th class="px-4 py-3 font-semibold">Subtotal</th>
                                                <th class="px-4 py-3 font-semibold">IGV</th>
                                                <th class="px-4 py-3 font-semibold text-right">Total</th>
                                                <th class="px-4 py-3 font-semibold">Moneda</th>
                                                <th class="px-4 py-3 font-semibold">Área</th>
                                                <th class="px-4 py-3 font-semibold">Mesa</th>
                                                <th class="px-4 py-3 font-semibold">Tipo doc.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-white/5 transition-colors">
                                                <td class="px-4 py-3 font-medium text-gray-700 dark:text-gray-300 tabular-nums">{{ number_format((float) ($order->subtotal ?? 0), 2) }}</td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400 tabular-nums">{{ number_format((float) ($order->tax ?? 0), 2) }}</td>
                                                <td class="px-4 py-3 text-right font-bold text-gray-700 dark:text-gray-300 tabular-nums">{{ number_format((float) ($order->total ?? 0), 2) }}</td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->currency ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->area?->name ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->table?->name ?? '-' }}</td>
                                                <td class="px-4 py-3 text-gray-600 dark:text-gray-400">{{ $order->movement?->documentType?->name ?? '-' }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12">
                            <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                <div
                                    class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                    <i class="ri-restaurant-2-line"></i>
                                </div>
                                <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay pedidos
                                    registrados.</p>
                                <p class="text-gray-500">Crea el primer pedido desde Salones de pedidos.</p>
                                <x-ui.link-button size="sm" variant="primary"
                                    href="{{ route('orders.index', $viewId ? ['view_id' => $viewId] : []) }}">
                                    <i class="ri-add-line"></i>
                                    <span>Ir a pedidos</span>
                                </x-ui.link-button>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>



        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="text-sm text-gray-500">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $orders->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $orders->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $orders->total() }}</span>
            </div>
            <div>
                {{ $orders->links() }}
            </div>
        </div>
        </x-common.component-card>
    </div>
@endsection
