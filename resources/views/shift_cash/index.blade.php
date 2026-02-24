@extends('layouts.app')

@section('content')
    <div x-data="{}">
        @php
            use Illuminate\Support\Facades\Route;

            $viewId = request('view_id');
            $operacionesCollection = collect($operaciones ?? []);
            $topOperations = $operacionesCollection->where('type', 'T');
            $rowOperations = $operacionesCollection->where('type', 'R');

            // --- Helpers ---
            $resolveActionUrl = function ($action, $shift = null, $operation = null) use ($viewId) {
                if (!$action) return '#';
                if (str_starts_with($action, '/') || str_starts_with($action, 'http')) {
                    $url = $action;
                } else {
                    $routeCandidates = [$action];
                    if (!str_starts_with($action, 'admin.')) $routeCandidates[] = 'admin.' . $action;
                    $routeCandidates = array_merge($routeCandidates, array_map(fn($name) => $name . '.index', $routeCandidates));
                    $routeName = null;
                    foreach ($routeCandidates as $candidate) {
                        if (Route::has($candidate)) { $routeName = $candidate; break; }
                    }
                    if ($routeName) {
                        try { $url = $shift ? route($routeName, $shift) : route($routeName); } catch (\Exception $e) { $url = '#'; }
                    } else { $url = '#'; }
                }
                $targetViewId = $viewId;
                if ($operation && !empty($operation->view_id_action)) $targetViewId = $operation->view_id_action;
                if ($targetViewId && $url !== '#') {
                    $separator = str_contains($url, '?') ? '&' : '?';
                    $url .= $separator . 'view_id=' . urlencode($targetViewId);
                }
                return $url;
            };

            $resolveTextColor = function ($operation) {
                $action = $operation->action ?? '';
                if (str_contains($action, 'shift-cash.create')) return '#111827';
                return '#FFFFFF';
            };
        @endphp

        <x-common.page-breadcrumb pageTitle="Turno por caja " />

        <x-common.component-card title="Gestión de Turnos" desc="Resumen detallado de ingresos y egresos por turno.">
            
            {{-- BUSCADOR Y BOTONES --}}
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between mb-4">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    @if ($viewId) <input type="hidden" name="view_id" value="{{ $viewId }}"> @endif
                    <x-ui.per-page-selector :per-page="$perPage" />
                    <div class="relative flex-1">
                        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"><i class="ri-search-line"></i></span>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por N° apertura..." class="dark:bg-dark-900 shadow-theme-xs h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-10 text-sm focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90" />
                    </div>
                    <div class="relative flex w-full sm:w-auto min-w-[200px]">
                        <select 
                            onchange="
                                let baseUrl = '{{ url('/caja/turno-caja') }}/' + this.value;
                                let params = new URLSearchParams(window.location.search);                               
                                params.delete('cash_register_id'); 
                                window.location.href = baseUrl + (params.toString() ? '?' + params.toString() : '');
                            "
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            @if (isset($cashRegisters))
                                @foreach ($cashRegisters as $register)
                                    <option value="{{ $register->id }}" {{ $selectedBoxId == $register->id ? 'selected' : '' }}>
                                        {{ $register->number }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-ui.button size="md" variant="primary" type="submit" class="h-11 px-4" style="background-color: #244BB3;">
                            <i class="ri-search-line text-gray-100"></i> <span class="text-gray-100">Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="md" variant="outline" href="{{ route('shift-cash.redirect') }}{{ $viewId ? '?view_id=' . urlencode($viewId) : '' }}" class="h-11 px-4">
                            <i class="ri-refresh-line"></i> <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <div class="flex items-center gap-2">
                    @foreach ($topOperations as $operation)
                        @php
                             $topStyle = "background-color: " . ($operation->color ?: '#3B82F6') . "; color: " . $resolveTextColor($operation) . ";";
                             $url = $resolveActionUrl($operation->action, null, $operation);
                        @endphp
                         <x-ui.link-button size="md" variant="primary" style="{{ $topStyle }}" href="{{ $url }}">
                            <i class="{{ $operation->icon }}"></i> <span>{{ $operation->name }}</span>
                        </x-ui.link-button>
                    @endforeach
                     @if ($topOperations->isEmpty())
                        <x-ui.button size="md" variant="primary" type="button" style="background-color: #12f00e; color: #111827;" @click="$dispatch('open-shift-modal')">
                            <i class="ri-add-line"></i> <span>Nuevo turno</span>
                        </x-ui.button>
                    @endif
                </div>
            </div>

            {{-- TABLA --}}
            {{-- TABLA PRINCIPAL --}}
            <div class="table-responsive mt-4 overflow-x-auto max-w-full rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full min-w-[1200px]">
                    <thead style="background-color: #63B7EC; color: #FFFFFF;">
                        <tr class="text-white">
                            <th class="px-5 py-3 text-center sm:px-6 sticky-left-header first:rounded-tl-xl">
                                <p class="font-medium text-theme-xs uppercase">Orden</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">Fecha Inicio</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">Fecha Cierre</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">N° Apertura</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">Detalle (Ingresos/Egresos)</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">N° Cierre</p>
                            </th>
                            <th class="px-5 py-3 text-left sm:px-6">
                                <p class="font-semibold text-theme-xs uppercase">Estado</p>
                            </th>
                            <th class="px-5 py-3 text-right sm:px-6 last:rounded-tr-xl">
                                <p class="font-semibold text-theme-xs uppercase">Operaciones</p>
                            </th>
                        </tr>
                    </thead>
                    
                    @forelse ($shift_cash as $shift)
                        @php
                            $ingresosTotal = 0;
                            $egresosTotal = 0;
                            $desgloseIngresos = [];
                            $desgloseEgresos = [];

                            if($shift->movements) {
                                foreach($shift->movements as $mov) {
                                    if($mov->id == $shift->cash_movement_start_id || $mov->id == $shift->cash_movement_end_id) {
                                        continue; 
                                    }

                                    if ($mov->paymentConcept && $mov->details) {
                                        $tipo = $mov->paymentConcept->type; 
                                        foreach($mov->details as $detail) {
                                            $metodo = $detail->paymentMethod->name ?? ($detail->payment_method ?? 'Otros');
                                            $monto = $detail->amount;

                                            if ($tipo == 'I') {
                                                $ingresosTotal += $monto;
                                                if (!isset($desgloseIngresos[$metodo])) $desgloseIngresos[$metodo] = 0;
                                                $desgloseIngresos[$metodo] += $monto;
                                            } elseif ($tipo == 'E') {
                                                $egresosTotal += $monto;
                                                if (!isset($desgloseEgresos[$metodo])) $desgloseEgresos[$metodo] = 0;
                                                $desgloseEgresos[$metodo] += $monto;
                                            }
                                        }
                                    }
                                }
                            }
                            $neto = $ingresosTotal - $egresosTotal;
                        @endphp

                        <tbody x-data="{ expanded: false }" class="divide-y divide-gray-100 dark:divide-gray-800">
                            <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5 align-top">
                                
                                {{-- 0. ORDEN (BOTÓN DESPLEGABLE) --}}
                                <td class="px-3 py-4 text-center sticky-left">
                                    <button type="button" @click="expanded = !expanded"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600 dark:bg-brand-500 dark:text-white">
                                        <i class="ri-add-line" x-show="!expanded"></i>
                                        <i class="ri-subtract-line" x-show="expanded" x-cloak></i>
                                    </button>
                                </td>

                                {{-- 1. FECHA INICIO --}}
                                <td class="px-5 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-800 dark:text-white">{{ \Carbon\Carbon::parse($shift->started_at)->format('d/m/Y') }}</span>
                                        <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($shift->started_at)->format('H:i:s A') }}</span>
                                    </div>
                                </td>

                                {{-- NUEVO: FECHA CIERRE --}}
                                <td class="px-5 py-4">
                                    @if($shift->ended_at)
                                        <div class="flex flex-col">
                                            <span class="font-bold text-gray-800 dark:text-white">{{ \Carbon\Carbon::parse($shift->ended_at)->format('d/m/Y') }}</span>
                                            <span class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($shift->ended_at)->format('H:i:s A') }}</span>
                                        </div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">-- En curso --</span>
                                    @endif
                                </td>

                                {{-- 2. N° APERTURA --}}
                                <td class="px-5 py-4">
                                    <x-ui.badge variant="light" color="info">{{ $shift->cashMovementStart?->movement?->number ?? '---' }}</x-ui.badge>
                                    @if($shift->cashMovementStart?->total)
                                        <div class="text-xs font-bold text-gray-600 mt-1">$ {{ number_format($shift->cashMovementStart->total, 2) }}</div>
                                    @endif
                                </td>

                                {{-- 3. DETALLE (INGRESOS/EGRESOS) --}}
                                <td class="px-5 py-4">
                                    <div class="flex flex-col gap-2 w-64 text-xs">
                                        @if($ingresosTotal > 0)
                                            <div>
                                                <div class="flex justify-between font-bold text-emerald-600 mb-1 border-b border-emerald-100 pb-0.5">
                                                    <span><i class="ri-arrow-up-line"></i> Ingresos:</span>
                                                    <span>$ {{ number_format($ingresosTotal, 2) }}</span>
                                                </div>
                                                <div class="pl-2 space-y-0.5">
                                                    @foreach($desgloseIngresos as $metodo => $monto)
                                                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                                            <span>{{ $metodo }}:</span>
                                                            <span>{{ number_format($monto, 2) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if($egresosTotal > 0)
                                            <div>
                                                <div class="flex justify-between font-bold text-red-500 mb-1 border-b border-red-100 pb-0.5">
                                                    <span><i class="ri-arrow-down-line"></i> Egresos:</span>
                                                    <span>$ {{ number_format($egresosTotal, 2) }}</span>
                                                </div>
                                                <div class="pl-2 space-y-0.5">
                                                    @foreach($desgloseEgresos as $metodo => $monto)
                                                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                                                            <span>{{ $metodo }}:</span>
                                                            <span>{{ number_format($monto, 2) }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif

                                        @if($ingresosTotal == 0 && $egresosTotal == 0)
                                            <span class="text-gray-400 italic">Sin movimientos operativos</span>
                                        @else
                                            <div class="border-t border-dashed border-gray-300 pt-1 mt-1">
                                                <div class="flex justify-between font-bold text-gray-700 dark:text-gray-200">
                                                    <span>Balance:</span>
                                                    <span class="{{ $neto >= 0 ? 'text-emerald-600' : 'text-red-500' }}">$ {{ number_format($neto, 2) }}</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                {{-- 4. N° CIERRE --}}
                                <td class="px-5 py-4">
                                    @if($shift->cashMovementEnd)
                                        <x-ui.badge variant="light" color="warning">{{ $shift->cashMovementEnd->movement->number ?? '---' }}</x-ui.badge>
                                        <div class="text-xs font-bold text-gray-600 mt-1">$ {{ number_format($shift->cashMovementEnd->total, 2) }}</div>
                                    @else
                                        <span class="text-xs text-gray-400 italic">Pendiente</span>
                                    @endif
                                </td>

                                {{-- 5. ESTADO --}}
                                <td class="px-5 py-4">
                                    @if(!$shift->ended_at)
                                        <x-ui.badge variant="solid" color="success" class="animate-pulse">EN CURSO</x-ui.badge>
                                    @else
                                        <x-ui.badge variant="solid" color="secondary">CERRADO</x-ui.badge>
                                    @endif
                                </td>

                                {{-- 6. ACCIONES --}}
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        @if($rowOperations->isNotEmpty())
                                            @foreach ($rowOperations as $op)
                                                 @php $url = $resolveActionUrl($op->action, $shift, $op); @endphp
                                                 <x-ui.link-button size="icon" href="{{ $url }}" style="background-color: {{ $op->color }}; color: white;" className="rounded-lg">
                                                     <i class="{{ $op->icon }}"></i>
                                                 </x-ui.link-button>
                                            @endforeach
                                        @else
                                            <x-ui.link-button size="icon" variant="edit" href="{{ route('shift-cash.edit', ['cash_register_id' => $selectedBoxId, 'shiftCash' => $shift->id]) }}" style="background-color: #fbbf24; color: #1f2937;" className="rounded-lg"><i class="ri-pencil-line"></i></x-ui.link-button>
                                            <form action="{{ route('shift-cash.destroy', ['cash_register_id' => $selectedBoxId, 'shiftCash' => $shift->id]) }}" method="POST" class="inline js-swal-delete">
                                                @csrf @method('DELETE')
                                                @if ($viewId) <input type="hidden" name="view_id" value="{{ $viewId }}"> @endif
                                                <x-ui.button size="icon" type="submit" style="background-color: #ef4444; color: white;" className="rounded-lg"><i class="ri-delete-bin-line"></i></x-ui.button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>

                            {{-- PANEL DESPLEGABLE CON TU FORMATO EXACTO (Apertura y Cierre) --}}
                            <tr x-show="expanded" x-cloak class="border-b border-gray-100 bg-gray-50 dark:border-gray-800 dark:bg-gray-900/20">
                                <td colspan="8" class="px-5 py-6 sm:px-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 w-full max-w-5xl mx-auto">
                                        
                                        {{-- DATOS APERTURA --}}
                                        @php $movApertura = $shift->cashMovementStart?->movement; @endphp
                                        <div class="mx-auto w-full max-w-xl space-y-1 text-center text-gray-800 dark:text-gray-200">
                                            <p class="font-bold text-brand-500 uppercase text-xs mb-2">Información de Apertura</p>
                                            <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                <span class="font-semibold">Persona</span>
                                                <span>{{ $movApertura?->person_name ?: '-' }}</span>
                                            </div>
                                            <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                <span class="font-semibold">Responsable</span>
                                                <span>{{ $movApertura?->responsible_name ?: '-' }}</span>
                                            </div>
                                            <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                <span class="font-semibold">Origen</span>
                                                <span>{{ $movApertura?->movementType?->description ?? '-' }} - {{ strtoupper(substr($movApertura?->documentType?->name ?? '', 0, 1)) }}{{ $movApertura?->salesMovement?->series ?? '' }}-{{ $movApertura?->number ?? '-' }}</span>
                                            </div>
                                        </div>

                                        {{-- DATOS CIERRE --}}
                                        @php $movCierre = $shift->cashMovementEnd?->movement; @endphp
                                        <div class="mx-auto w-full max-w-xl space-y-1 text-center text-gray-800 dark:text-gray-200">
                                            <p class="font-bold text-red-500 uppercase text-xs mb-2">Información de Cierre</p>
                                            @if($movCierre)
                                                <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                    <span class="font-semibold">Persona</span>
                                                    <span>{{ $movCierre->person_name ?: '-' }}</span>
                                                </div>
                                                <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                    <span class="font-semibold">Responsable</span>
                                                    <span>{{ $movCierre->responsible_name ?: '-' }}</span>
                                                </div>
                                                <div class="grid grid-cols-2 border-b border-gray-200 py-2 dark:border-gray-700">
                                                    <span class="font-semibold">Origen</span>
                                                    <span>{{ $movCierre->movementType?->description ?? '-' }} - {{ strtoupper(substr($movCierre->documentType?->name ?? '', 0, 1)) }}{{ $movCierre->salesMovement?->series ?? '' }}-{{ $movCierre->number ?? '-' }}</span>
                                                </div>
                                            @else
                                                <div class="py-10 text-gray-400 italic">El turno aún no ha sido cerrado.</div>
                                            @endif
                                        </div>

                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    @empty
                        <tbody>
                            <tr><td colspan="8" class="text-center py-8 text-gray-500">No hay turnos registrados</td></tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            {{-- PAGINACION --}}
            <div class="mt-4">
                {{ $shift_cash->links() }}
            </div>
        </x-common.component-card>

        {{-- MODAL --}}
      
    </div>
@endsection