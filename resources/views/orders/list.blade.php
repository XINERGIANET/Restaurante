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
                        array_map(fn ($name) => $name . '.index', $routeCandidates)
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
                        <select
                            name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()"
                        >
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} / pagina</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="Buscar por numero, persona o usuario"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                        />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="md" variant="primary" type="submit" class="flex-1 sm:flex-none h-11 px-6 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95" style="background-color: #244BB3; border-color: #244BB3;">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline" href="{{ route('orders.list', $viewId ? ['view_id' => $viewId] : []) }}" class="flex-1 sm:flex-none h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-orange-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                        <i class="ri-file-pdf-line"></i> Descargar PDF
                    </button>
                </div>
            </div>

            <div x-data="{ openRow: null }" class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[1480px]">
                    <thead style="background-color: #63B7EC; color: #FFFFFF;">
                        <tr class="bg-[#63B7EC] text-white">
                            <th class="w-12 px-3 py-3 text-center first:rounded-tl-xl">
                                <span class="font-semibold text-theme-xs uppercase">#</span>
                            </th>
                            <th class="min-w-[100px] px-5 py-3 text-left sm:px-6">
                                <span class="font-semibold text-theme-xs uppercase">Número</span>
                            </th>
                            <th class="min-w-[90px] px-5 py-3 text-right sm:px-6">
                                <span class="font-semibold text-theme-xs uppercase">Total</span>
                            </th>
                            <th class="min-w-[150px] px-5 py-3 text-left sm:px-6">
                                <span class="font-semibold text-theme-xs uppercase">Fecha</span>
                            </th>
                            <th class="min-w-[120px] px-5 py-3 text-left sm:px-6">
                                <span class="font-semibold text-theme-xs uppercase">Persona</span>
                            </th>
                            <th class="min-w-[120px] px-5 py-3 text-left sm:px-6">
                                <span class="font-semibold text-theme-xs uppercase">Responsable</span>
                            </th>
                            <th class="min-w-[100px] px-5 py-3 text-left sm:px-6 last:rounded-tr-xl">
                                <span class="font-semibold text-theme-xs uppercase">Estado</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            @php
                                $rowStatus = strtoupper((string) ($order->status ?? 'PENDIENTE'));
                                $situationStatus = strtoupper((string) ($order->movement?->status ?? 'A'));
                                $rowStatusColor = in_array($rowStatus, ['FINALIZADO', 'F'], true) ? 'success' : (in_array($rowStatus, ['CANCELADO', 'I'], true) ? 'error' : 'warning');
                                $rowStatusText = in_array($rowStatus, ['FINALIZADO', 'F'], true) ? 'Finalizado' : (in_array($rowStatus, ['CANCELADO', 'I'], true) ? 'Cancelado' : 'Pendiente');
                                $situationColor = in_array($situationStatus, ['A', '1'], true) ? 'success' : 'error';
                                $situationText = in_array($situationStatus, ['A', '1'], true) ? 'Activado' : 'Inactivo';
                            @endphp
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-4 py-3.5 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button type="button"
                                            @click="openRow === {{ $order->id }} ? openRow = null : openRow = {{ $order->id }}"
                                            class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600">
                                            <i class="ri-add-line text-sm" x-show="openRow !== {{ $order->id }}"></i>
                                            <i class="ri-subtract-line text-sm" x-show="openRow === {{ $order->id }}"></i>
                                        </button>
                                        <span class="text-gray-700 text-theme-sm dark:text-gray-300">{{ $order->id }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    @php
                                        $num = $order->movement?->number ?? null;
                                        $numDisplay = $num ? (ctype_digit((string) $num) ? str_pad($num, 8, '0', STR_PAD_LEFT) : $num) : '-';
                                    @endphp
                                    <span class="font-medium text-gray-800 text-theme-sm dark:text-white/90">{{ $numDisplay }}</span>
                                </td>
                                <td class="px-5 py-3.5 text-right sm:px-6">
                                    <span class="font-semibold text-gray-800 text-theme-sm dark:text-white/90">{{ number_format((float) ($order->total ?? 0), 2) }}</span>
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    <span class="text-gray-600 text-theme-sm dark:text-gray-400">{{ $order->movement?->moved_at?->format('Y-m-d H:i') ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    <span class="text-gray-600 text-theme-sm dark:text-gray-400">{{ $order->movement?->person_name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    <span class="text-gray-600 text-theme-sm dark:text-gray-400">{{ $order->movement?->responsible_name ?? '-' }}</span>
                                </td>
                                <td class="px-5 py-3.5 sm:px-6">
                                    <x-ui.badge variant="light" color="{{ $rowStatusColor }}" class="inline-flex text-xs font-medium">
                                        {{ $rowStatusText }}
                                    </x-ui.badge>
                                </td>
                            </tr>
                            <tr x-cloak  x-show="openRow === {{ $order->id }}" class="bg-gray-50/70 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800">
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Subtotal</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ number_format((float) ($order->subtotal ?? 0), 2) }}</div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">IGV</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ number_format((float) ($order->tax ?? 0), 2) }}</div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Total</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ number_format((float) ($order->total ?? 0), 2) }}</div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Moneda</div>
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                        {{ $order->currency ?? '-' }}
                                    </div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Área</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $order->area?->name ?? '-' }}</div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Mesa</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $order->table?->name ?? '-' }}</div>
                                </td>
                                <td colspan="1" class="px-5 py-3.5 sm:px-6">
                                    <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Tipo de documento</div>
                                    <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $order->movement?->documentType?->name ?? '-' }}</div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-restaurant-2-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay pedidos registrados.</p>
                                        <p class="text-gray-500">Crea el primer pedido desde Salones de pedidos.</p>
                                        <x-ui.link-button size="sm" variant="primary" href="{{ route('admin.orders.index', $viewId ? ['view_id' => $viewId] : []) }}">
                                            <i class="ri-add-line"></i>
                                            <span>Ir a pedidos</span>
                                        </x-ui.link-button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

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
