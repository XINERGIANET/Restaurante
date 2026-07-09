@extends('layouts.app')

@section('content')
    @php
        $viewId = request('view_id');
        $statusOptions = [
            '' => 'Todos',
            'printed' => 'Impresas',
            'pending' => 'Pendientes',
            'printing' => 'Procesando',
            'error' => 'Con error',
            'dismissed' => 'Descartadas',
        ];
        $statusMeta = function ($job) {
            $status = (string) ($job->status ?? '');
            $hasError = trim((string) ($job->last_error ?? '')) !== '';

            if ($status === 'printed') {
                return ['Impreso', 'bg-emerald-50 text-emerald-700 ring-emerald-200'];
            }
            if ($status === 'printing') {
                return ['Procesando', 'bg-blue-50 text-blue-700 ring-blue-200'];
            }
            if ($status === 'dismissed') {
                return ['Descartado', 'bg-gray-100 text-gray-700 ring-gray-200'];
            }
            if ($hasError) {
                return ['Con error', 'bg-red-50 text-red-700 ring-red-200'];
            }

            return ['Pendiente', 'bg-amber-50 text-amber-700 ring-amber-200'];
        };
        $personName = function ($user) {
            if (!$user) {
                return '-';
            }
            $name = trim((string) (($user->person->first_name ?? '') . ' ' . ($user->person->last_name ?? '')));
            return $name !== '' ? $name : ($user->name ?? '-');
        };
    @endphp

    <div
        x-data="{
            modalOpen: false,
            modalTitle: '',
            modalText: '',
            openTicket(title, text) {
                this.modalTitle = title || 'Comanda';
                this.modalText = text || '';
                this.modalOpen = true;
            }
        }"
    >
        <x-common.page-breadcrumb pageTitle="Comandas" />

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600">
                        <i class="ri-checkbox-circle-line text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Impresas</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ (int) ($stats['printed'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <i class="ri-time-line text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Pendientes</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ (int) ($stats['pending'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                        <i class="ri-loader-4-line text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Procesando</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ (int) ($stats['printing'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-theme-sm dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-center gap-4">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-red-50 text-red-600">
                        <i class="ri-error-warning-line text-2xl"></i>
                    </span>
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Con error</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900 dark:text-white">{{ (int) ($stats['error'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <x-common.component-card title="" class="mt-6">
            @if (!$schemaReady)
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    Falta ejecutar la migracion de impresiones termicas para poder listar comandas.
                </div>
            @endif

            <form method="GET" class="grid grid-cols-1 gap-4 lg:grid-cols-12 lg:items-end">
                @if ($viewId)
                    <input type="hidden" name="view_id" value="{{ $viewId }}">
                @endif
                <div class="lg:col-span-4">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Impresora</label>
                    <input
                        type="text"
                        name="printer"
                        value="{{ $printerSearch }}"
                        placeholder="Buscar impresora"
                        class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                    >
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Estado</label>
                    <select name="status" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Desde</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Hasta</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 shadow-theme-xs focus:border-brand-300 focus:outline-hidden focus:ring-2 focus:ring-brand-500/10 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                </div>
                <div class="flex gap-2 lg:col-span-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-[#0F4A33] px-4 text-sm font-medium text-white transition hover:bg-[#0B3A28]">
                        <i class="ri-search-line"></i>
                        Buscar
                    </button>
                    <a href="{{ route('orders.commands.index', $viewId ? ['view_id' => $viewId] : []) }}" class="inline-flex h-11 w-12 items-center justify-center rounded-lg border border-gray-300 text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                        <i class="ri-refresh-line"></i>
                    </a>
                </div>
            </form>

            <div class="mt-6 overflow-hidden rounded-xl border border-gray-200 dark:border-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-[#0F4A33] text-white">
                            <tr>
                                <th class="px-5 py-4 text-left font-semibold">Fecha</th>
                                <th class="px-5 py-4 text-left font-semibold">Impresora</th>
                                <th class="px-5 py-4 text-left font-semibold">Pedido</th>
                                <th class="px-5 py-4 text-left font-semibold">Estado</th>
                                <th class="px-5 py-4 text-center font-semibold">Intentos</th>
                                <th class="px-5 py-4 text-left font-semibold">Error</th>
                                <th class="px-5 py-4 text-right font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white dark:divide-gray-800 dark:bg-gray-900">
                            @forelse ($jobs as $job)
                                @php
                                    [$statusLabel, $statusClass] = $statusMeta($job);
                                    $order = $job->movement?->orderMovement;
                                    $tableName = $order?->table?->name;
                                    $ticketText = (string) ($job->ticket_text ?? '');
                                    $contentSummary = (string) ($job->content_summary ?? '');
                                    $useExistingJob = in_array((string) $job->status, ['pending', 'printing'], true);
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-4 text-gray-700 dark:text-gray-200">
                                        <div class="font-medium">{{ optional($job->created_at)->format('d/m/Y H:i') }}</div>
                                        <div class="text-xs text-gray-500">ID {{ $job->id }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-200">
                                        <div class="font-medium">{{ $job->printer_name ?: 'Sin asignar' }}</div>
                                        <div class="text-xs text-gray-500">Solicito: {{ $personName($job->requestedBy) }}</div>
                                    </td>
                                    <td class="px-5 py-4 text-gray-700 dark:text-gray-200">
                                        <div class="font-medium">Pedido #{{ $job->movement?->number ?? $job->movement_id }}</div>
                                        <div class="text-xs text-gray-500">{{ $tableName ? 'Mesa ' . $tableName : 'Mesa -' }}</div>
                                        @if ($contentSummary !== '')
                                            <div class="mt-1 max-w-xs truncate text-xs text-gray-500">{{ $contentSummary }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ring-1 {{ $statusClass }}">
                                            {{ $statusLabel }}
                                        </span>
                                        @if ($job->printed_at)
                                            <div class="mt-1 text-xs text-gray-500">Imp: {{ $job->printed_at->format('d/m/Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-5 py-4 text-center font-semibold text-gray-800 dark:text-gray-100">{{ (int) $job->attempts }}</td>
                                    <td class="px-5 py-4">
                                        <div class="max-w-xs truncate text-xs text-red-600" title="{{ $job->last_error }}">{{ $job->last_error ?: '-' }}</div>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex justify-end gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-200 text-gray-600 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800"
                                                title="Ver comanda"
                                                @click="openTicket('Comanda #{{ $job->id }}', window.decodeCommandTicket('{{ base64_encode($ticketText) }}'))"
                                            >
                                                <i class="ri-file-text-line"></i>
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex h-9 items-center justify-center gap-2 rounded-lg bg-[#0F4A33] px-3 text-xs font-semibold text-white transition hover:bg-[#0B3A28]"
                                                title="Reimprimir"
                                                data-reprint-command
                                                data-job-id="{{ $useExistingJob ? $job->id : '' }}"
                                                data-movement-id="{{ $job->movement_id }}"
                                                data-printer-name="{{ e($job->printer_name) }}"
                                                data-ticket-b64="{{ base64_encode($ticketText) }}"
                                                data-summary="{{ e($contentSummary) }}"
                                            >
                                                <i class="ri-printer-line"></i>
                                                Reimprimir
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-5 py-16 text-center text-gray-500">
                                        <i class="ri-file-list-3-line mb-3 block text-4xl text-gray-300"></i>
                                        No hay comandas para los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if ($jobs instanceof \Illuminate\Contracts\Pagination\Paginator && $jobs->hasPages())
                <div class="mt-4">
                    {{ $jobs->links() }}
                </div>
            @endif
        </x-common.component-card>

        <div
            x-show="modalOpen"
            x-cloak
            class="fixed inset-0 z-99999 flex items-center justify-center bg-black/50 p-4"
            @keydown.escape.window="modalOpen = false"
        >
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-xl dark:bg-gray-900" @click.outside="modalOpen = false">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white" x-text="modalTitle"></h3>
                    <button type="button" class="text-gray-400 hover:text-gray-700 dark:hover:text-gray-200" @click="modalOpen = false">
                        <i class="ri-close-line text-2xl"></i>
                    </button>
                </div>
                <pre class="max-h-[70vh] overflow-auto whitespace-pre-wrap px-5 py-4 font-mono text-sm leading-6 text-gray-800 dark:text-gray-100" x-text="modalText"></pre>
            </div>
        </div>
    </div>

    <script>
        window.decodeCommandTicket = function (value) {
            try {
                const binary = atob(String(value || ''));
                const bytes = Uint8Array.from(binary, function (char) {
                    return char.charCodeAt(0);
                });
                return new TextDecoder('utf-8').decode(bytes);
            } catch (e) {
                try {
                    return atob(String(value || ''));
                } catch (ignored) {
                    return '';
                }
            }
        };

        document.addEventListener('click', async function (event) {
            const button = event.target.closest('[data-reprint-command]');
            if (!button) return;

            const originalHtml = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Enviando';

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                const ticketText = window.decodeCommandTicket(button.getAttribute('data-ticket-b64') || '');
                const jobId = button.getAttribute('data-job-id') || '';
                const body = {
                    movement_id: parseInt(button.getAttribute('data-movement-id') || '0', 10) || null,
                    printer_name: button.getAttribute('data-printer-name') || null,
                    ticket_text: ticketText,
                    content_summary: button.getAttribute('data-summary') || null,
                    retry_attempt: true
                };
                if (jobId) {
                    body.print_job_id = parseInt(jobId, 10);
                }

                const response = await fetch(@json(route('orders.print.kitchen.thermal')), {
                    method: 'POST',
                    cache: 'no-store',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify(body)
                });
                const data = response.headers.get('content-type')?.includes('application/json') ? await response.json() : null;
                if (!response.ok || !data?.success) {
                    throw new Error(data?.message || 'No se pudo reimprimir la comanda.');
                }

                if (window.Swal) {
                    window.Swal.fire({ icon: 'success', title: 'Comanda enviada', text: data.message || 'Reimpresion enviada.', timer: 2200, showConfirmButton: false });
                } else {
                    alert(data.message || 'Comanda enviada.');
                }
                window.setTimeout(() => window.location.reload(), 900);
            } catch (error) {
                if (window.Swal) {
                    window.Swal.fire({ icon: 'error', title: 'No se pudo reimprimir', text: error?.message || 'Intenta nuevamente.' });
                } else {
                    alert(error?.message || 'No se pudo reimprimir.');
                }
            } finally {
                button.disabled = false;
                button.innerHTML = originalHtml;
            }
        });
    </script>
@endsection
