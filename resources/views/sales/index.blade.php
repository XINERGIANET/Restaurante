@extends('layouts.app')

@section('content')
    <div>
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

        <x-common.page-breadcrumb pageTitle="Ventas" />

        <x-common.component-card title="Listado de ventas" desc="Gestiona las ventas registradas.">
            <div class="flex flex-col gap-4">
                <form method="GET" class="w-full flex flex-col gap-4">
                    @if ($viewId)
                        <input type="hidden" name="view_id" value="{{ $viewId }}">
                    @endif

                    <div class="flex flex-col xl:flex-row xl:items-start xl:justify-between gap-4">
                        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center flex-wrap">

                            <x-ui.per-page-selector :per-page="$perPage" />

                            <div class="relative flex-1 min-w-[200px]">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                                    <i class="ri-search-line"></i>
                                </span>
                                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar..."
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                            </div>
                            <div class="flex gap-2">
                                <x-ui.button size="md" variant="primary" type="submit"
                                    class="h-11 px-6 shadow-sm hover:shadow-md transition-all duration-200 active:scale-95"
                                    style="background-color: #244BB3; border-color: #244BB3;">
                                    <i class="ri-search-line text-gray-100"></i>
                                    <span class="font-medium text-gray-100 hidden sm:inline">Buscar</span>
                                </x-ui.button>
                                <x-ui.link-button size="md" variant="outline"
                                    href="{{ route('sales.index', $viewId ? ['view_id' => $viewId] : []) }}"
                                    class="h-11 px-6 border-gray-200 text-gray-600 hover:bg-gray-50 hover:text-gray-900 transition-all duration-200">
                                    <i class="ri-refresh-line"></i>
                                    <span class="font-medium hidden sm:inline">Limpiar</span>
                                </x-ui.link-button>
                            </div>
                        </div>

                        <div class="flex-shrink-0">
                            @if ($topOperations->isNotEmpty())
                                @foreach ($topOperations as $operation)
                                    @php
                                        $topColor = $operation->color ?: '#3B82F6';
                                        $topTextColor = str_contains($operation->action ?? '', 'sales.create')
                                            ? '#111827'
                                            : '#FFFFFF';
                                        $topStyle = "background-color: {$topColor}; color: {$topTextColor};";
                                        $topActionUrl = $resolveActionUrl($operation->action ?? '', null, $operation);
                                    @endphp
                                    <x-ui.link-button size="md" variant="primary" style="{{ $topStyle }}"
                                        href="{{ $topActionUrl }}" class="h-11">
                                        <i class="{{ $operation->icon }}"></i>
                                        <span>{{ $operation->name }}</span>
                                    </x-ui.link-button>
                                @endforeach
                            @else
                                <x-ui.link-button size="md" variant="primary" style="background-color: #12f00e; color: #111827;"
                                    href="{{ route('sales.create', $viewId ? ['view_id' => $viewId] : []) }}" class="h-11">
                                    <i class="ri-add-line"></i>
                                    <span>Nueva Venta</span>
                                </x-ui.link-button>
                            @endif
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
                                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-600 dark:bg-gray-900 dark:text-white/90 dark:focus:border-brand-800">
                                    <option value="">Todas</option>
                                    @foreach ($cashRegisters ?? [] as $cr)
                                        <option value="{{ $cr->id }}" @selected(($cashRegisterId ?? '') == $cr->id)>{{ $cr->number ?? $cr->id }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="shrink-0">
                            <button type="button"
                                onclick="descargarPdf()"
                                data-pdf-url="{{ route('sales.pdf', array_filter([
                                    'date_from'         => $dateFrom,
                                    'date_to'           => $dateTo,
                                    'search'            => $search,
                                    'document_type_id'  => $documentTypeId ?? null,
                                    'payment_method_id' => $paymentMethodId ?? null,
                                    'cash_register_id'  => $cashRegisterId ?? null,
                                ])) }}"
                                class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-orange-600 px-4 text-sm font-medium text-white shadow-sm transition hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2">
                                <i class="ri-file-pdf-line text-base"></i>
                                <span>Descargar PDF</span>
                            </button>
                        </div>
                    </div>
                </form>                    
            </div>
            
            <div x-data="{ openRow: null }" class="table-responsive mt-4 rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[1100px]">
                    <thead>
                        <tr class="text-white">
                            <th style="background-color: #63B7EC; color: #FFFFFF;" class="px-5 py-3 text-left sm:px-6 first:rounded-tl-xl sticky-left-header">
                                <p class="font-semibold text-white text-center text-theme-xs uppercase">#</p>
                            </th>
                            <th style="background-color: #63B7EC; color: #FFFFFF;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Comprobante</p>
                            </th>
                            <th style="background-color: #63B7EC; color: #FFFFFF;" class= "px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Subtotal</p>
                            </th>
                            <th  style="background-color: #63B7EC; color: #FFFFFF;" class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">IGV</p>
                            </th>
                            <th  style="background-color: #63B7EC; color: #FFFFFF;"  class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Total</p>
                            </th>
                            <th  style="background-color: #63B7EC; color: #FFFFFF;"  class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Persona</p>
                            </th>
                            <th  style="background-color: #63B7EC; color: #FFFFFF;"  class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-white text-theme-xs uppercase">Situación</p>
                            </th>
                            <th  style="background-color: #63B7EC; color: #FFFFFF;"  class="px-5 py-3 text-right sm:px-6 last:rounded-tr-xl">
                                <p class="font-semibold text-white text-theme-xs uppercase">Acciones</p>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-4 py-4 sm:px-6 sticky-left">
                                    <div class="flex items-center gap-2">
                                        <button type="button"
                                            @click="openRow === {{ $sale->id }} ? openRow = null : openRow = {{ $sale->id }}"
                                            class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600">
                                            <i class="ri-add-line" x-show="openRow !== {{ $sale->id }}"></i>
                                            <i class="ri-subtract-line" x-show="openRow === {{ $sale->id }}"></i>
                                        </button>
                                        <p class="font-bold text-gray-800 text-center text-theme-sm dark:text-white/90">{{ $sale->id }}</p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div>
                                        <p class="font-bold text-gray-800 text-theme-sm dark:text-white/90">
                                            {{ strtoupper(substr($sale->documentType->name, 0, 1)) }}{{ $sale->salesMovement->series }}-{{ $sale->number }}
                                        </p>
                                        <p class="text-[11px] text-gray-500 dark:text-gray-400 uppercase font-medium">
                                            {{ $sale->documentType?->name ?? '-' }}
                                        </p>
                                    </div>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-800 text-theme-sm dark:text-white/90">S/ {{ number_format((float) ($sale->salesMovement?->subtotal ?? 0), 2) }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-800 text-theme-sm dark:text-white/90">S/ {{ number_format((float) ($sale->salesMovement?->tax ?? 0), 2) }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-bold text-brand-600 text-theme-sm dark:text-brand-400">S/ {{ number_format((float) ($sale->salesMovement?->total ?? 0), 2) }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-800 text-theme-sm dark:text-white/90 truncate max-w-[150px]" title="{{ $sale->person_name ?? 'Público General' }}">
                                        {{ $sale->person_name ?? 'Público General' }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
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
                                    <x-ui.badge variant="light" color="{{ $badgeColor }}">
                                        {{ $badgeText }}
                                        </x-ui.badge>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">
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
                                                        href="{{ route('sales.charge', array_merge(['movement_id' => $sale->id], $viewId ? ['view_id' => $viewId] : [])) }}"
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
                                                    href="{{ route('sales.edit', array_merge([$sale], $viewId ? ['view_id' => $viewId] : [])) }}"
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
                                                action="{{ route('sales.destroy', array_merge([$sale], $viewId ? ['view_id' => $viewId] : [])) }}"
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
                            <tr x-show="openRow === {{ $sale->id }}" x-cloak class="bg-gray-50/70 dark:bg-gray-800/40 border-b border-gray-100 dark:border-gray-800">
                                <td colspan="8" class="px-5 py-3 sm:px-6">
                                    <div class="grid w-full grid-cols-5 gap-x-6 gap-y-3">
                                        {{-- Fila 1 --}}
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Fecha</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90 whitespace-nowrap">{{ $sale->moved_at ? $sale->moved_at->format('d/m/Y H:i') : '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Usuario</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $sale->user_name ?: '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Responsable</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $sale->responsible_name ?: '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Moneda</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $sale->salesMovement?->currency ?? 'PEN' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">T. cambio</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ number_format((float) ($sale->salesMovement?->exchange_rate ?? 1), 3) }}</div>
                                        </div>
                                        {{-- Fila 2 --}}
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Tipo de pago</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $sale->salesMovement?->payment_type ?? '-' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Por consumo</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ ($sale->salesMovement?->consumption ?? 'N') === 'Y' ? 'Sí' : 'No' }}</div>
                                        </div>
                                        <div>
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Comentario</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ Str::limit($sale->comment ?? '-', 50) }}</div>
                                        </div>
                                        <div class="col-span-2">
                                            <div class="text-xs font-medium uppercase tracking-wide text-gray-400 dark:text-gray-500">Origen</div>
                                            <div class="mt-0.5 text-sm text-gray-800 dark:text-white/90">{{ $sale->movementType?->description ?? 'Venta' }} – {{ strtoupper(substr($sale->documentType?->name ?? '', 0, 1)) }}{{ $sale->salesMovement?->series }}-{{ $sale->number }}</div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-shopping-bag-3-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay ventas registradas.</p>
                                        <p class="text-gray-500">Crea la primera venta para comenzar.</p>
                                        <x-ui.link-button size="sm" variant="primary" href="{{ route('sales.create', $viewId ? ['view_id' => $viewId] : []) }}">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar venta</span>
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
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $sales->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $sales->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $sales->total() }}</span>
                </div>
                <div>
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

    function descargarPdf() {
        const btn     = document.querySelector('[data-pdf-url]');
        const baseUrl = btn ? btn.dataset.pdfUrl : "{{ route('sales.pdf') }}";

        // Sobreescribir fechas con lo que tenga el picker en este momento
        const url    = new URL(baseUrl, window.location.origin);
        const dfVal  = document.querySelector('[name="date_from"]')?.value;
        const dtVal  = document.querySelector('[name="date_to"]')?.value;
        if (dfVal) url.searchParams.set('date_from', dfVal);
        if (dtVal) url.searchParams.set('date_to', dtVal);

        window.open(url.toString(), '_blank');
    }
    </script>
    @endpush
@endsection
