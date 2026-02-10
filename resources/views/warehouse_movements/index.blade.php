@extends('layouts.app')

@section('content')
    <div x-data="{}">
        @php
            use Illuminate\Support\Facades\Route;

            $viewId = request('view_id');
            $operacionesCollection = collect($operaciones ?? []);
            $topOperations = $operacionesCollection->where('type', 'T');
            $rowOperations = $operacionesCollection->where('type', 'R');

            $resolveActionUrl = function ($action, $warehouseMovement = null, $operation = null) use ($viewId) {
                if (!$action) {
                    return '#';
                }

                if (str_starts_with($action, '/') || str_starts_with($action, 'http')) {
                    $url = $action;
                } else {
                    // Normalizar guiones a guiones bajos para coincidir con nombres de rutas Laravel
                    $normalizedAction = str_replace('-', '_', $action);
                    
                    $routeCandidates = [$action, $normalizedAction];
                    if (!str_starts_with($action, 'admin.')) {
                        $routeCandidates[] = 'admin.' . $action;
                        $routeCandidates[] = 'admin.' . $normalizedAction;
                    }
                    // Agregar variantes con .index solo si no tiene ya un método específico
                    if (!str_contains($action, '.') || str_ends_with($action, '.index')) {
                        $routeCandidates = array_merge(
                            $routeCandidates,
                            array_map(fn ($name) => $name . '.index', array_filter($routeCandidates, fn($n) => !str_contains($n, '.')))
                        );
                    }

                    $routeName = null;
                    foreach ($routeCandidates as $candidate) {
                        if (Route::has($candidate)) {
                            $routeName = $candidate;
                            break;
                        }
                    }

                    if ($routeName) {
                        try {
                            $url = $warehouseMovement ? route($routeName, $warehouseMovement) : route($routeName);
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

            $resolveTextColor = function ($operation) {
                $action = $operation->action ?? '';
                if ($action === 'warehouse-movements.create') {
                    return '#111827';
                }
                return '#FFFFFF';
            };
        @endphp

        <x-common.page-breadcrumb pageTitle="{{ $title ?? 'Movimientos de Almacén' }}" />

        <x-common.component-card title="Listado de movimientos de almacén" desc="Gestiona los movimientos de almacén registrados en el sistema.">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between mb-6">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center min-w-0">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif
                    <div class="w-auto flex-none">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 sm:hidden">Por página</label>
                        <select name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} /
                                    página</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex-1 min-w-0">
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 sm:hidden">Buscar</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                <i class="ri-search-line"></i>
                            </span>
                            <input type="text" name="search" value="{{ $search }}"
                                placeholder="Buscar por número, persona, usuario..."
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-none">
                        <x-ui.button size="md" variant="primary" type="submit" class="flex-1 sm:flex-none h-11 px-4 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95" style="background-color: #63B7EC; border-color: #63B7EC;">
                            <i class="ri-search-line text-gray-100"></i>
                            <span class="font-medium text-gray-100">Buscar</span>
                        </x-ui.button>

                        <x-ui.link-button size="md" variant="outline" href="{{ request()->url() }}" class="flex-1 sm:flex-none h-11 px-4 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                            <i class="ri-refresh-line"></i>
                            <span class="font-medium">Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

            </div>
            <div class="flex items-center gap-3 border-t border-gray-100 pt-4 lg:border-0 lg:pt-0 flex-none ml-auto">
                @foreach ($topOperations as $operation)
                    @php
                        $topTextColor = $resolveTextColor($operation);
                        $topColor = $operation->color ?: '#3B82F6';
                        $topStyle = "background-color: {$topColor}; color: {$topTextColor};";
                        $topActionUrl = $resolveActionUrl($operation->action ?? '', null, $operation);
                    @endphp
                    <x-ui.link-button size="md" variant="primary"
                        class="w-full sm:w-auto h-11 px-6 shadow-sm"
                        style="{{ $topStyle }}"
                        href="{{ $topActionUrl }}">
                        <i class="{{ $operation->icon }} text-lg"></i>
                        <span>{{ $operation->name }}</span>
                    </x-ui.link-button>
                @endforeach
                @if($topOperations->isEmpty())
                    <x-ui.link-button size="md" variant="primary" 
                        href="{{ route('warehouse_movements.input', $viewId ? ['view_id' => $viewId] : []) }}"
                        class="w-full sm:w-auto h-11 px-6 shadow-sm" 
                        style="background-color: #00A389; color: #FFFFFF;">
                        <i class="ri-archive-line text-lg"></i>
                        <span>Entrada</span>
                    </x-ui.link-button>
                    <x-ui.link-button size="md" variant="primary" 
                        href="{{ route('warehouse_movements.output', $viewId ? ['view_id' => $viewId] : []) }}"
                        class="w-full sm:w-auto h-11 px-6 shadow-sm" 
                        style="background-color: #EF4444; color: #FFFFFF;">
                        <i class="ri-archive-line text-lg"></i>
                        <span>Salida</span>
                    </x-ui.link-button>
                @endif
            </div>
            <div
                class="rounded-xl border border-gray-200 bg-white overflow-hidden dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead style="background-color: #63B7EC; color: #FFFFFF;">
                            <tr>
                                <th  class="px-3 py-4 text-center whitespace-nowrap first:rounded-tl-xl sticky left-0 z-20 w-32 max-w-[128px] sm:w-auto sm:max-w-none">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider truncate">Número</p>
                                </th>
                                <th class="px-5 py-4 text-center whitespace-nowrap">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Tipo de Movimiento</p>
                                </th>
                                    <th  class="px-5 py-4 text-center whitespace-nowrap">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Persona</p>
                                </th>
                                <th  class="px-5 py-4 text-center whitespace-nowrap">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Comentario</p>
                                </th>
                                <th  class="px-5 py-4 text-center whitespace-nowrap">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Estado</p>
                                </th>
                                <th  class="px-5 py-4 text-center whitespace-nowrap">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Fecha</p>
                                </th>
                                <th  class="px-5 py-4 text-center whitespace-nowrap last:rounded-tr-xl">
                                    <p class="font-bold text-gray-100 text-xs uppercase tracking-wider">Acciones</p>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($warehouseMovements as $warehouseMovement)
                                @php
                                    $movement = $warehouseMovement->movement;
                                    $statusColors = [
                                        'PENDING' => 'warning',
                                        'SENT' => 'info',
                                        'FINALIZED' => 'success',
                                        'REJECTED' => 'error',
                                    ];
                                    $statusColor = $statusColors[$warehouseMovement->status] ?? 'info';
                                @endphp
                                <tr class="group/row transition hover:bg-gray-50/80 dark:hover:bg-white/5">
                                    <td class="px-3 py-4 whitespace-nowrap sticky left-0 z-10 bg-white dark:bg-[#121212] group-hover/row:bg-gray-50 dark:group-hover/row:bg-gray-800 w-32 max-w-[128px] sm:w-auto sm:max-w-none">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-50 text-brand-500 dark:bg-brand-500/10 shrink-0">
                                                <i class="ri-archive-line text-xs"></i>
                                            </div>
                                            <p class="font-semibold text-gray-800 text-theme-sm dark:text-white/90 truncate" title="{{ $movement->number ?? '-' }}">
                                                {{ $movement->number ?? '-' }}
                                            </p>
                                        </div>
                                    </td>
                                    <td class="px-5 py-4 text-center">
                                        <x-ui.badge variant="light" color="info">
                                            {{ $movement->movementType->description ?? '-' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-5 py-4 min-w-[200px]">
                                        <p class="text-gray-600 text-theme-sm dark:text-gray-400">
                                            {{ $movement->person_name ?? $movement->user_name ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 min-w-[200px]">
                                        <p class="text-gray-600 text-theme-sm dark:text-gray-400 line-clamp-1" title="{{ $movement->comment ?? '-' }}">
                                            {{ $movement->comment ?? '-' }}
                                        </p>
                                    </td>
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        <x-ui.badge variant="light" color="{{ $statusColor }}">
                                            {{ $warehouseMovement->status ?? 'FINALIZED' }}
                                        </x-ui.badge>
                                    </td>
                                    <td class="px-5 py-4 text-center whitespace-nowrap">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $movement->moved_at ? $movement->moved_at->format('d/m/Y H:i') : '-' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-2">
                                            <div class="relative group"> <a
                                                    href="{{ route('warehouse_movements.show', ['warehouseMovement' => $warehouseMovement->id]) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-info-500 text-white hover:bg-info-600 transition-colors shadow-sm"
                                                    style="background-color: #63B7EC; color: #FFFFFF;"
                                                    aria-label="Ver Registro">
                                                    <i class="ri-eye-line"></i>
                                                </a>
                                            </div>
                                            <div class="relative group"> <a
                                                    href="{{ route('warehouse_movements.edit', ['warehouseMovement' => $warehouseMovement->id]) }}"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-warning-500 text-white hover:bg-warning-600 transition-colors shadow-sm"
                                                    style="background-color: #FBBF24; color: #111827;"
                                                    aria-label="Editar Registro">
                                                    <i class="ri-pencil-line"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-16">
                                        <div class="flex flex-col items-center gap-4 text-center">
                                            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gray-50 text-gray-400 dark:bg-gray-800/50 dark:text-gray-600">
                                                <i class="ri-archive-line text-3xl"></i>
                                            </div>
                                            <div class="space-y-1">
                                                <p class="text-base font-semibold text-gray-800 dark:text-white/90">No hay movimientos de almacén registrados</p>
                                                <p class="text-sm text-gray-500">Comienza registrando tu primer movimiento de almacén.</p>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </x-common.component-card>

        <div class="mt-5 mb-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between px-4 sm:px-6">
            <div class="text-sm text-gray-500 dark:text-gray-400">
                Mostrando
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $warehouseMovements->firstItem() ?? 0 }}</span>
                -
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $warehouseMovements->lastItem() ?? 0 }}</span>
                de
                <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $warehouseMovements->total() }}</span>
            </div>
            <div class="flex-none pagination-simple">
                {{ $warehouseMovements->links() }}
            </div>
        </div>
    </div>
@endsection
