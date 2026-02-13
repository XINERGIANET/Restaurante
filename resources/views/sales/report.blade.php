@extends('layouts.app')

@section('content')
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
    <div>
        <x-common.page-breadcrumb pageTitle="Reporte de Ventas" />

        <x-common.component-card title="Reporte de Ventas" desc="Genera un reporte de las ventas registradas.">
            {{-- Barra de filtros y acción principal --}}
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between lg:gap-6">
                <form method="GET" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-3 min-w-0 lg:flex-1 lg:max-w-2xl">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif
                    <div class="flex flex-wrap items-center gap-3 sm:flex-1 sm:flex-nowrap min-w-0">
                        <div class="w-32 shrink-0">
                            <select
                                name="per_page"
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                                onchange="this.form.submit()"
                            >
                                @foreach ([10, 20, 50, 100] as $size)
                                    <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }}/pág</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="relative flex-1 min-w-[140px] max-w-sm">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="ri-search-line"></i></span>
                            <input
                                type="text"
                                name="search"
                                value="{{ $search ?? '' }}"
                                placeholder="Buscar..."
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white py-2 pl-9 pr-3 text-sm text-gray-800 shadow-sm placeholder:text-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                            />
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button type="submit" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="ri-search-line"></i> Buscar
                            </button>
                            <a href="{{ route('sales.report', $viewId ? ['view_id' => $viewId] : []) }}" class="inline-flex h-10 items-center justify-center gap-1.5 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700">
                                <i class="ri-refresh-line"></i> Limpiar
                            </a>
                        </div>
                    </div>
                </form>
                <div class="shrink-0 border-t border-gray-200 pt-4 lg:border-t-0 lg:border-l lg:border-gray-200 lg:pl-6 lg:pt-0">
                    @if ($topOperations->isNotEmpty())
                        @foreach ($topOperations as $operation)
                            @php
                                $topColor = $operation->color ?: '#3B82F6';
                                $topTextColor = str_contains($operation->action ?? '', 'sales.create') ? '#111827' : '#FFFFFF';
                                $topStyle = "background-color: {$topColor}; color: {$topTextColor};";
                                $topActionUrl = $resolveActionUrl($operation->action ?? '', null, $operation);
                            @endphp
                            <a href="{{ $topActionUrl }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg px-4 text-sm font-medium text-white shadow-sm hover:opacity-90" style="{{ $topStyle }}">
                                <i class="{{ $operation->icon }}"></i>{{ $operation->name }}
                            </a>
                        @endforeach
                    @else
                        <a href="{{ route('admin.sales.create', $viewId ? ['view_id' => $viewId] : []) }}" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-green-500 px-4 text-sm font-medium text-gray-900 shadow-sm hover:bg-green-600">
                            <i class="ri-add-line text-lg"></i> Nueva venta
                        </a>
                    @endif
                </div>
            </div>

            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800/50">
                <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-gray-700/80">
                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Comprobante</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Tipo</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Subtotal</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">IGV</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Total</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Cliente</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Fecha</th>
                            <th scope="col" class="px-3 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Usuario</th>
                            <th scope="col" class="px-3 py-3 text-center text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Estado</th>
                            <th scope="col" class="px-3 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-600 dark:text-gray-300">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-800/30">
                        @forelse ($sales as $sale)
                            <tr class="transition-colors hover:bg-gray-50/80 dark:hover:bg-gray-700/30">
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <span class="font-medium text-gray-900 dark:text-white">{{ $sale->number }}</span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">{{ $sale->documentType?->name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm font-medium tabular-nums text-gray-900 dark:text-white">S/ {{ number_format((float) ($sale->salesMovement?->subtotal ?? 0), 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm tabular-nums text-gray-600 dark:text-gray-400">S/ {{ number_format((float) ($sale->salesMovement?->tax ?? 0), 2) }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-right text-sm font-semibold tabular-nums text-gray-900 dark:text-white">S/ {{ number_format((float) ($sale->salesMovement?->total ?? 0), 2) }}</td>
                                <td class="px-4 py-3.5 text-sm text-gray-700 dark:text-gray-300 max-w-[180px] truncate" title="{{ $sale->person_name ?? '-' }}">{{ $sale->person_name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">{{ $sale->moved_at ? \Carbon\Carbon::parse($sale->moved_at)->format('d/m/Y H:i') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-sm text-gray-600 dark:text-gray-400">{{ $sale->user_name ?? '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3.5 text-center">
                                    @php
                                        $status = $sale->status ?? 'A';
                                        $badgeColor = 'success';
                                        $badgeText = 'Activo';
                                        if ($status === 'P') {
                                            $badgeColor = 'warning';
                                            $badgeText = 'Pendiente';
                                        } elseif ($status !== 'A') {
                                            $badgeColor = 'error';
                                            $badgeText = 'Inactivo';
                                        }
                                    @endphp
                                    <x-ui.badge variant="light" color="{{ $badgeColor }}" class="inline-flex text-xs font-medium">
                                        {{ $badgeText }}
                                    </x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($rowOperations->isNotEmpty())
                                            @foreach ($rowOperations as $operation)
                                                @php
                                                    $action = $operation->action ?? '';
                                                    $isDelete = str_contains($action, 'destroy');
                                                    $isCharge = str_contains($action, 'charge');
                                                    if ($isCharge && ($sale->status ?? 'A') !== 'P') {
                                                        continue;
                                                    }

                                                    $actionUrl = $resolveActionUrl($action, $sale, $operation);
                                                    if ($isCharge && $actionUrl !== '#') {
                                                        $separator = str_contains($actionUrl, '?') ? '&' : '?';
                                                        $actionUrl .= $separator . 'movement_id=' . urlencode($sale->id);
                                                    }

                                                    $buttonColor = $operation->color ?: '#3B82F6';
                                                    $buttonTextColor = str_contains($action, 'edit') ? '#111827' : '#FFFFFF';
                                                    $buttonStyle = "background-color: {$buttonColor}; color: {$buttonTextColor};";
                                                    $variant = $isDelete ? 'eliminate' : (str_contains($action, 'edit') ? 'edit' : 'primary');
                                                @endphp

                                                @if ($isDelete)
                                                    <form
                                                        method="POST"
                                                        action="{{ $actionUrl }}"
                                                        class="relative group js-swal-delete"
                                                        data-swal-title="Eliminar venta?"
                                                        data-swal-text="Se eliminara la venta {{ $sale->number }}. Esta accion no se puede deshacer."
                                                        data-swal-confirm="Si, eliminar"
                                                        data-swal-cancel="Cancelar"
                                                        data-swal-confirm-color="#ef4444"
                                                        data-swal-cancel-color="#6b7280"
                                                    >
                                                        @csrf
                                                        @method('DELETE')
                                                        @if ($viewId)
                                                            <input type="hidden" name="view_id" value="{{ $viewId }}">
                                                        @endif
                                                        <x-ui.button size="icon" variant="{{ $variant }}" type="submit" className="rounded-xl" style="{{ $buttonStyle }}" aria-label="{{ $operation->name }}">
                                                            <i class="{{ $operation->icon }}"></i>
                                                        </x-ui.button>
                                                        <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">{{ $operation->name }}</span>
                                                    </form>
                                                @else
                                                    <div class="relative group">
                                                        <x-ui.link-button size="icon" variant="{{ $variant }}" href="{{ $actionUrl }}" className="rounded-xl" style="{{ $buttonStyle }}" aria-label="{{ $operation->name }}">
                                                            <i class="{{ $operation->icon }}"></i>
                                                        </x-ui.link-button>
                                                        <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">{{ $operation->name }}</span>
                                                    </div>
                                                @endif
                                            @endforeach
                                        @else
                                            @if(($sale->status ?? 'A') === 'P')
                                                <div class="relative group">
                                                    <x-ui.link-button
                                                        size="icon"
                                                        variant="primary"
                                                        href="{{ route('admin.sales.charge', array_merge(['movement_id' => $sale->id], $viewId ? ['view_id' => $viewId] : [])) }}"
                                                        className="bg-success-500 text-white hover:bg-success-600 ring-0 rounded-full"
                                                        style="border-radius: 100%; background-color: #10B981; color: #FFFFFF;"
                                                        aria-label="Cobrar"
                                                    >
                                                        <i class="ri-money-dollar-circle-line"></i>
                                                    </x-ui.link-button>
                                                    <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Cobrar</span>
                                                </div>
                                            @endif
                                            <div class="relative group">
                                                <x-ui.link-button
                                                    size="icon"
                                                    variant="edit"
                                                    href="{{ route('admin.sales.edit', array_merge([$sale], $viewId ? ['view_id' => $viewId] : [])) }}"
                                                    className="bg-warning-500 text-white hover:bg-warning-600 ring-0 rounded-full"
                                                    style="border-radius: 100%; background-color: #FBBF24; color: #111827;"
                                                    aria-label="Editar"
                                                >
                                                    <i class="ri-pencil-line"></i>
                                                </x-ui.link-button>
                                                <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Editar</span>
                                            </div>
                                            <form
                                                method="POST"
                                                action="{{ route('admin.sales.destroy', array_merge([$sale], $viewId ? ['view_id' => $viewId] : [])) }}"
                                                class="relative group js-swal-delete"
                                                data-swal-title="Eliminar venta?"
                                                data-swal-text="Se eliminara la venta {{ $sale->number }}. Esta accion no se puede deshacer."
                                                data-swal-confirm="Si, eliminar"
                                                data-swal-cancel="Cancelar"
                                                data-swal-confirm-color="#ef4444"
                                                data-swal-cancel-color="#6b7280"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                @if ($viewId)
                                                    <input type="hidden" name="view_id" value="{{ $viewId }}">
                                                @endif
                                                <x-ui.button
                                                    size="icon"
                                                    variant="eliminate"
                                                    type="submit"
                                                    className="bg-error-500 text-white hover:bg-error-600 ring-0 rounded-full"
                                                    style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;"
                                                    aria-label="Eliminar"
                                                >
                                                    <i class="ri-delete-bin-line"></i>
                                                </x-ui.button>
                                                <span class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50" style="transition-delay: 0.5s;">Eliminar</span>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-6 py-16">
                                    <div class="flex flex-col items-center justify-center gap-4 text-center">
                                        <div class="rounded-full bg-gray-100 p-5 text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                                            <i class="ri-file-list-3-line text-4xl"></i>
                                        </div>
                                        <div>
                                            <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay ventas en este reporte</p>
                                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ajusta los filtros o registra una nueva venta.</p>
                                        </div>
                                        <x-ui.link-button size="sm" variant="primary" href="{{ route('admin.sales.create', $viewId ? ['view_id' => $viewId] : []) }}" class="mt-1">
                                            <i class="ri-add-line mr-1"></i>
                                            Registrar venta
                                        </x-ui.link-button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 rounded-lg border border-gray-200 bg-gray-50/50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/30 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Mostrando <span class="font-medium text-gray-900 dark:text-white">{{ $sales->firstItem() ?? 0 }}</span>
                    a <span class="font-medium text-gray-900 dark:text-white">{{ $sales->lastItem() ?? 0 }}</span>
                    de <span class="font-medium text-gray-900 dark:text-white">{{ $sales->total() }}</span> registros
                </p>
                <div class="flex justify-end">
                    {{ $sales->links() }}
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