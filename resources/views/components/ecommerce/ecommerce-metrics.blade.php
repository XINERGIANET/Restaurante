@php
    use Illuminate\Support\Str;

    $accountBreakdowns = $accountBreakdowns ?? [];
    $metricsComponentId = 'dashboard-metrics-'.uniqid();
@endphp

<div id="{{ $metricsComponentId }}" data-dashboard-metrics-root>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 md:gap-6">
        @foreach ($accounts as $key => $account)
            @php
                $theme = match ($key) {
                    'Ventas' => ['color' => '#2979ff', 'icon' => 'sales'],
                    'Compras' => ['color' => '#FE0000', 'icon' => 'purchases'],
                    'Entradas' => ['color' => '#03B430', 'icon' => 'entries'],
                    'Salidas' => ['color' => '#FFA500', 'icon' => 'expenses'],
                    default => ['color' => '#4b5563', 'icon' => Str::slug($key)],
                };
                $accountKey = $theme['icon'];
                $breakdown = $accountBreakdowns[$accountKey]
                    ?? $accountBreakdowns[$key]
                    ?? $accountBreakdowns[Str::slug($key)]
                    ?? null;
            @endphp
            <button
                type="button"
                class="dashboard-metric-card group w-full rounded-2xl p-5 md:p-6 shadow-lg hover:shadow-xl transition-all duration-300 relative overflow-hidden text-left focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-black/20"
                style="background-color: {{ $theme['color'] }}"
                data-account-key="{{ $accountKey }}"
                data-account-title="{{ $key }}"
                data-account='@json($breakdown)'
            >
                <div class="absolute -right-4 -top-4 w-24 h-24 bg-white/10 rounded-full blur-2xl group-hover:bg-white/20 transition-all"></div>

                <div class="flex items-start justify-between relative z-10">
                    <div class="flex items-center justify-center w-10 h-10 bg-white/20 backdrop-blur-md rounded-xl shadow-inner">
                        @if ($key == 'Ventas')
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4a1 1 0 0 1-1 1H4a2 2 0 0 0 0 4h15a1 1 0 0 0 1-1v-4"></path><path d="M19 11v12"></path></svg>
                        @elseif($key == 'Compras')
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="10" width="18" height="11" rx="2"></rect><path d="M3 10L12 3L21 10"></path><path d="M6 10V21"></path><path d="M10 10V21"></path><path d="M14 10V21"></path><path d="M18 10V21"></path></svg>
                        @elseif($key == 'Entradas')
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"></rect><line x1="2" y1="10" x2="22" y2="10"></line></svg>
                        @elseif($key == 'Salidas')
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"></rect><line x1="12" y1="18" x2="12.01" y2="18"></line></svg>
                        @else
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                        @endif
                    </div>

                    <span class="flex items-center gap-1 rounded-lg bg-white/20 text-white py-1 px-2.5 text-[10px] font-black uppercase tracking-widest backdrop-blur-sm">
                        <svg width="8" height="8" viewBox="0 0 10 10" fill="none" xmlns="http://www.w3.org/2000/svg" class="{{ ($account['diff'] ?? 0) < 0 ? 'rotate-180' : '' }}">
                            <path d="M5 2L8 5L2 5L5 2Z" fill="currentColor"/>
                        </svg>
                        {{ abs((float) ($account['diff'] ?? 0)) }}%
                    </span>
                </div>

                <div class="mt-6 relative z-10">
                    <span class="text-[10px] font-black text-white/70 uppercase tracking-[0.2em]">
                        {{ match($key) { 'Ventas' => 'VENTAS', 'Compras' => 'COMPRAS', 'Entradas' => 'ENTRADAS', 'Salidas' => 'SALIDAS', default => strtoupper($key) } }}
                    </span>
                    <h4 class="mt-1 text-3xl font-black text-white tracking-tight">
                        S/{{ number_format((float) ($account['total'] ?? 0), 2) }}
                    </h4>
                </div>

                <div class="mt-6 flex items-center justify-between border-t border-white/10 pt-4 relative z-10">
                    <span class="text-[10px] font-bold text-white/50 uppercase tracking-widest">Transacciones</span>
                    <span class="text-xs font-black text-white bg-white/10 px-2 py-0.5 rounded-md">
                        {{ number_format((int) ($account['transactions'] ?? 0)) }}
                    </span>
                </div>

                <div class="mt-3 text-left text-[11px] font-semibold uppercase tracking-[0.2em] text-white/70 relative z-10">
                    Click para ver detalle
                </div>
            </button>
        @endforeach
    </div>

    <div id="{{ $metricsComponentId }}-modal" class="fixed inset-0 z-[99999] hidden items-center justify-center overflow-y-auto p-4 sm:p-6">
        <div data-dashboard-modal-backdrop class="fixed inset-0 h-full w-full bg-gray-400/30 backdrop-blur-[32px]"></div>
        <div class="relative flex w-full max-w-6xl flex-col rounded-3xl bg-[#F4F6FA] dark:bg-gray-900 max-h-[90vh] overflow-hidden">
            <div class="min-h-0 flex-1 overflow-y-auto p-5 sm:p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 pb-4 dark:border-gray-700">
                    <div>
                        <p class="text-[11px] font-bold uppercase tracking-[0.25em] text-gray-400">Desglose detallado</p>
                        <h3 id="{{ $metricsComponentId }}-title" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">Detalle de tarjeta</h3>
                        <p id="{{ $metricsComponentId }}-subtitle" class="mt-2 text-sm text-gray-500 dark:text-gray-400"></p>
                    </div>
                    <button type="button" data-dashboard-modal-close class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white" aria-label="Cerrar detalle">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Total</p>
                        <p id="{{ $metricsComponentId }}-total" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">S/0.00</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Transacciones</p>
                        <p id="{{ $metricsComponentId }}-transactions" class="mt-2 text-2xl font-black text-gray-900 dark:text-white">0</p>
                    </div>
                    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900/70">
                        <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Formula</p>
                        <p id="{{ $metricsComponentId }}-formula" class="mt-2 text-sm font-medium text-gray-700 dark:text-gray-300">Suma de registros</p>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border border-dashed border-gray-200 bg-gray-50 p-4 text-sm text-gray-600 dark:border-gray-700 dark:bg-gray-900/50 dark:text-gray-300">
                    Este detalle muestra cada movimiento que alimenta la tarjeta y sus lineas internas para corroborar el monto.
                </div>

                <div id="{{ $metricsComponentId }}-content" class="mt-6 space-y-4"></div>
                <div id="{{ $metricsComponentId }}-empty" class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900/70 dark:text-gray-300 hidden">
                    No hay movimientos para mostrar en este rango.
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const rootId = @json($metricsComponentId);
        const logPrefix = '[dashboard-metrics]';

        function fmtMoney(value) {
            const num = Number(value || 0);
            return 'S/' + num.toFixed(2);
        }

        function renderMovement(movement) {
            const lines = Array.isArray(movement.lines) ? movement.lines : [];
            const rows = lines.map((line) => {
                const complements = Array.isArray(line.complements) && line.complements.length > 0
                    ? `<div class="mt-1 text-xs text-gray-500 dark:text-gray-400">Complementos: ${line.complements.join(', ')}</div>`
                    : '';
                const courtesy = Number(line.courtesy_qty || 0) > 0
                    ? `<div class="mt-1 text-xs text-amber-600 dark:text-amber-400">Cortesia: ${Number(line.courtesy_qty || 0).toFixed(2)}</div>`
                    : '';
                const amount = line.line_total ?? line.amount ?? 0;
                const unitAmount = line.unit_amount ?? line.amount ?? 0;
                return `
                    <tr class="align-top">
                        <td class="py-3 pr-4">
                            <div class="font-semibold text-gray-800 dark:text-gray-200">${line.name || 'Item'}</div>
                            ${complements}
                            ${courtesy}
                        </td>
                        <td class="py-3 px-4 text-right tabular-nums text-gray-600 dark:text-gray-300">${Number(line.qty || 0).toFixed(2)}</td>
                        <td class="py-3 px-4 text-right tabular-nums text-gray-600 dark:text-gray-300">${fmtMoney(unitAmount)}</td>
                        <td class="py-3 px-4 text-right tabular-nums font-bold text-gray-900 dark:text-white">${fmtMoney(amount)}</td>
                        <td class="py-3 pl-4"><span class="text-gray-600 dark:text-gray-400">${line.comment || '—'}</span></td>
                    </tr>
                `;
            }).join('');

            return `
                <details class="group rounded-3xl border border-gray-200 bg-white shadow-sm open:shadow-md dark:border-gray-700 dark:bg-gray-900">
                    <summary class="cursor-pointer list-none px-5 py-4 sm:px-6">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h4 class="text-base font-extrabold text-gray-900 dark:text-white">${movement.label || 'Movimiento'}</h4>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-[11px] font-bold uppercase tracking-[0.2em] text-gray-500 dark:bg-gray-800 dark:text-gray-300">${movement.date || '-'}</span>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">${movement.customer || movement.supplier || movement.concept || 'Sin referencia'}</p>
                                <p class="mt-1 text-xs font-medium text-gray-400 dark:text-gray-500">${movement.seller ? 'Usuario: ' + movement.seller : ''}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-black text-gray-900 dark:text-white">${fmtMoney(movement.total)}</p>
                                <p class="mt-1 text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">${Number(movement.detail_count || 0)} lineas</p>
                            </div>
                        </div>
                    </summary>
                    <div class="border-t border-gray-200 px-5 py-5 sm:px-6 dark:border-gray-700">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Registro</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">${movement.number || movement.label || 'Movimiento'}</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Total registrado</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">${fmtMoney(movement.total)}</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Suma de lineas</p>
                                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-gray-200">${fmtMoney(movement.lines_total)}</p>
                            </div>
                            <div class="rounded-2xl bg-gray-50 p-4 dark:bg-gray-800/70">
                                <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-gray-400">Diferencia</p>
                                <p class="mt-1 text-sm font-semibold ${Math.abs(Number(movement.difference || 0)) > 0.01 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400'}">${fmtMoney(movement.difference)}</p>
                            </div>
                        </div>
                        <div class="mt-5 overflow-x-auto">
                            <table class="min-w-full text-left text-sm">
                                <thead class="text-[11px] uppercase tracking-[0.2em] text-gray-400">
                                    <tr>
                                        <th class="py-3 pr-4 font-bold">Item</th>
                                        <th class="py-3 px-4 font-bold text-right">Cantidad</th>
                                        <th class="py-3 px-4 font-bold text-right">Unitario</th>
                                        <th class="py-3 px-4 font-bold text-right">Total</th>
                                        <th class="py-3 pl-4 font-bold">Comentario</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">${rows}</tbody>
                            </table>
                        </div>
                    </div>
                </details>
            `;
        }

        function initDashboardMetrics() {
            const root = document.getElementById(rootId);
            if (!root) {
                console.warn(logPrefix, 'root not found', rootId);
                return;
            }

            if (root.dataset.metricsBound === '1') {
                console.info(logPrefix, 'already initialized', rootId);
                return;
            }
            root.dataset.metricsBound = '1';

            const modal = document.getElementById(rootId + '-modal');
            const title = document.getElementById(rootId + '-title');
            const subtitle = document.getElementById(rootId + '-subtitle');
            const total = document.getElementById(rootId + '-total');
            const transactions = document.getElementById(rootId + '-transactions');
            const formula = document.getElementById(rootId + '-formula');
            const content = document.getElementById(rootId + '-content');
            const empty = document.getElementById(rootId + '-empty');
            const closeButtons = root.querySelectorAll('[data-dashboard-modal-close], [data-dashboard-modal-backdrop]');
            const cards = root.querySelectorAll('.dashboard-metric-card');
            const globalBreakdowns = window.dashboardData && window.dashboardData.accountBreakdowns
                ? window.dashboardData.accountBreakdowns
                : {};

            console.info(logPrefix, 'init', {
                rootId,
                cards: cards.length,
                modalFound: !!modal,
            });

            function closeModal(reason) {
                console.info(logPrefix, 'closeModal', { reason });
                if (!modal) {
                    console.error(logPrefix, 'modal not found on close');
                    return;
                }
                modal.classList.add('hidden');
                modal.classList.remove('flex');
                document.body.style.removeProperty('overflow');
                document.documentElement.style.removeProperty('overflow');
            }

            function openModal(account, meta) {
                console.info(logPrefix, 'openModal called', {
                    meta,
                    hasAccount: !!account,
                    account,
                });

                if (!modal) {
                    console.error(logPrefix, 'modal not found');
                    return;
                }

                if (!account || typeof account !== 'object') {
                    console.error(logPrefix, 'invalid account payload', { account, meta });
                    empty.classList.remove('hidden');
                    content.innerHTML = '';
                    title.textContent = 'Detalle no disponible';
                    subtitle.textContent = 'No llego payload para esta tarjeta.';
                    total.textContent = fmtMoney(0);
                    transactions.textContent = '0';
                    formula.textContent = 'Sin datos';
                } else {
                    title.textContent = account.title || meta.title || 'Detalle de tarjeta';
                    subtitle.textContent = account.subtitle || '';
                    total.textContent = fmtMoney(account.total || 0);
                    transactions.textContent = Number(account.transactions || 0).toLocaleString('es-PE');
                    formula.textContent = account.formula || 'Suma de registros';

                    const items = Array.isArray(account.items) ? account.items : [];
                    console.info(logPrefix, 'render items', {
                        count: items.length,
                        firstItem: items[0] || null,
                    });

                    if (items.length === 0) {
                        empty.classList.remove('hidden');
                        content.innerHTML = '';
                    } else {
                        empty.classList.add('hidden');
                        content.innerHTML = items.map(renderMovement).join('');
                    }
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.style.setProperty('overflow', 'hidden');
                document.documentElement.style.setProperty('overflow', 'hidden');
                console.info(logPrefix, 'modal open state applied', {
                    hiddenClass: modal.classList.contains('hidden'),
                    flexClass: modal.classList.contains('flex'),
                });
            }

            cards.forEach((card, index) => {
                card.addEventListener('click', function () {
                    const raw = card.getAttribute('data-account');
                    const accountKey = card.getAttribute('data-account-key') || '';
                    const fallbackPayload = globalBreakdowns[accountKey]
                        || globalBreakdowns[String(accountKey).toLowerCase()]
                        || null;
                    const meta = {
                        index,
                        key: accountKey,
                        title: card.getAttribute('data-account-title'),
                        raw,
                        fallbackPayload,
                    };

                    console.info(logPrefix, 'card click', meta);

                    let parsed = null;
                    try {
                        parsed = raw ? JSON.parse(raw) : null;
                        console.info(logPrefix, 'payload parsed', parsed);
                    } catch (error) {
                        console.error(logPrefix, 'JSON parse failed', {
                            meta,
                            error: error?.message || error,
                        });
                    }

                    if (!parsed && fallbackPayload) {
                        parsed = fallbackPayload;
                        console.info(logPrefix, 'using fallback payload from window.dashboardData.accountBreakdowns', {
                            key: accountKey,
                            parsed,
                        });
                    }

                    openModal(parsed, meta);
                });
            });

            closeButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    closeModal('button');
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal && !modal.classList.contains('hidden')) {
                    closeModal('escape');
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initDashboardMetrics, { once: true });
        } else {
            initDashboardMetrics();
        }
        document.addEventListener('turbo:load', initDashboardMetrics);
        document.addEventListener('turbo:render', initDashboardMetrics);
    })();
</script>
