@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    {{-- Breadcrumb fuera del recuadro blanco (fondo gris de página) --}}
    <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3 -mx-4 md:-mx-6 px-4 md:px-6 py-2 mb-4 bg-gray-50 dark:bg-gray-900/80 border-b border-gray-200 dark:border-gray-800">
        <nav class="min-w-0">
            <ol class="flex flex-wrap items-end justify-end gap-1 sm:gap-1.5 text-xs sm:text-sm">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400"
                        href="{{ url('/') }}">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="min-w-0">
                    <a class="inline-flex items-center gap-1 sm:gap-1.5 text-gray-500 dark:text-gray-400 truncate max-w-[120px] sm:max-w-none"
                        href="{{ route('orders.index') }}">
                        <span class="truncate">Salones de Pedidos</span>
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="text-gray-800 dark:text-white/90 truncate max-w-[140px] sm:max-w-none">
                    Mesa {{ str_pad($table->name ?? $table->id, 2, '0', STR_PAD_LEFT) }} | Crear pedido
                </li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-300 overflow-hidden bg-blue-50 dark:bg-gray-900 fade-in max-w-full">
        <main class="w-full lg:flex-2 flex flex-col min-w-0 bg-gray-50 dark:bg-gray-900/50 bg-gray-50 h-[calc(100vh-120px)]">
            <header class="min-h-16 h-auto py-2 px-3 sm:px-5 flex flex-wrap lg:flex-nowrap items-center justify-between gap-4 dark:bg-gray-800/50 border-b border-gray-200 shadow-sm z-10 bg-gray-100 min-w-0 relative">
                <div class="flex items-center gap-3 shrink-0">
                    <button onclick="goBack()" 
                        title="Volver atrás"
                        class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300 transition-all flex items-center justify-center shadow-sm shrink-0">
                        <i class="ri-arrow-left-line text-lg sm:text-xl"></i>
                    </button>
                    <div class="flex items-center gap-2 min-w-0">
                        <div class="flex flex-col justify-center min-w-0">
                            <h2 class="text-sm sm:text-base font-bold text-slate-800 dark:text-white leading-tight truncate">
                                Mesa <span id="pos-table-name">{{ $table->name ?? $table->id }}</span>
                            </h2>
                            <p class="text-[10px] sm:text-xs text-gray-500 dark:text-gray-400 mt-0.5 truncate"><i class="ri-circle-fill" style="color: #00C950;"></i> <span id="pos-table-area">{{ $table->area->name ?? 'Sin área' }}</span></p>
                        </div>
                    </div>
                </div>

                <!-- Opciones (Flex para que fluyan) -->
                <div class="flex items-center gap-3 sm:gap-4 lg:gap-5 text-sm font-medium shrink-0 ml-auto flex-wrap sm:flex-nowrap">
                    <!-- Buscador -->
                    <div class="flex items-center gap-1.5 shrink-0 bg-white p-1 rounded-xl border border-gray-200 shadow-sm">
                        <div class="w-36 sm:w-48 md:w-56 relative">
                            <input type="text" id="search-products" placeholder="Buscar producto..." autocomplete="off"
                                class="w-full pl-8 pr-3 py-1.5 text-xs sm:text-sm bg-transparent border-transparent rounded-lg focus:ring-0 focus:border-transparent outline-none">
                            <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                        </div>
                        <x-ui.button size="xs" variant="outline" onclick="clearProductSearch()" class="!px-2 h-7" id="search-products-clear">
                            <i class="ri-close-line"></i>
                        </x-ui.button>
                    </div>

                    <div class="h-6 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                    <!-- Mozo -->
                    @if(!($isMozo ?? false))
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm">Mozo:</span>
                        <select id="header-waiter-select" onchange="changeWaiter(this)"
                            class="w-24 sm:w-32 py-1.5 px-2 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 outline-none shadow-sm truncate">
                            <option value="{{ $user?->id }}" selected><span id="pos-waiter-name">{{ $user?->name ?? 'Sin asignar' }}</span></option>
                        </select>
                    </div>
                    
                    <div class="h-6 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>
                    @endif
                    
                    <!-- Servicio (automático por área) -->
                    <input type="hidden" id="header-service-type-val" value="{{ $pendingServiceType ?? 'IN_SITU' }}">

                    <div class="h-6 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                    <!-- Cliente -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm">Cliente:</span>
                        <div class="flex items-center gap-1">
                            @php
                                $peopleCollection = $people ?? collect();
                                $clientOptions = $peopleCollection->map(function($p) {
                                    $name = trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? ''));
                                    if ($name === '' && !empty($p->document_number)) {
                                        $name = $p->document_number;
                                    }
                                    return [
                                        'id' => $p->id,
                                        'description' => $name,
                                    ];
                                })->values()->all();
                            @endphp
                            <x-form.select.combobox
                                :options="$clientOptions"
                                x-model="currentTable.person_id"
                                name="header_client_id"
                                placeholder="Seleccionar..."
                                icon=""
                                class="w-32 sm:w-40 md:w-48 !py-1 !px-2 !text-xs sm:!text-sm !font-semibold !text-slate-700 dark:!text-slate-200 !bg-white dark:!bg-slate-700/80 !border !border-gray-200 dark:!border-slate-600 !rounded-lg"
                                x-on:change="
                                    const selected = (@js($clientOptions)).find(o => o.id == currentTable.person_id);
                                    const name = selected ? selected.description : 'Público General';
                                    currentTable.person_id = currentTable.person_id ? parseInt(currentTable.person_id, 10) : null;
                                    currentTable.clientName = name;
                                    saveDB();
                                    const cobroInput = document.getElementById('cobro-client-input');
                                    if (cobroInput) cobroInput.value = name;
                                "
                            />
                            <button type="button"
                                class="inline-flex items-center justify-center h-8 w-8 rounded-lg bg-white border border-gray-200 text-gray-500 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-300 shadow-sm transition-colors"
                                @click="$dispatch('open-person-modal')"
                                title="Nuevo cliente">
                                <i class="ri-user-add-line text-sm sm:text-base"></i>
                            </button>
                        </div>
                    </div>

                    <div class="h-6 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                    <!-- Personas -->
                    <div class="flex items-center gap-2 shrink-0">
                        <div class="flex flex-col text-right">
                            <span class="text-gray-500 dark:text-gray-400 text-xs sm:text-sm leading-none">Personas</span>
                            <span class="text-[9px] sm:text-[10px] text-gray-400">(máx. {{ $table->capacity ?? 1 }})</span>
                        </div>
                        <div class="flex items-center gap-0.5 p-0.5 bg-white dark:bg-slate-700 border border-gray-200 dark:border-slate-600 rounded-lg shadow-sm">
                            <button type="button" onclick="updateDiners(-1)"
                                class="w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-md bg-gray-50 hover:bg-blue-100 text-slate-600 hover:text-blue-600 transition-colors">
                                <i class="ri-subtract-line text-xs"></i>
                            </button>
                            <input type="number"
                                id="diners-input"
                                value="{{ $pendingOrderMovement?->people_count ?? 1 }}"
                                min="1"
                                onchange="updateDiners(0)"
                                class="w-8 sm:w-10 py-1 text-center text-xs sm:text-sm bg-transparent border-none text-slate-700 font-bold focus:ring-0 p-0 m-0 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            <button type="button" onclick="updateDiners(1)"
                                class="w-6 h-6 sm:w-7 sm:h-7 flex items-center justify-center rounded-md bg-gray-50 hover:bg-blue-100 text-slate-600 hover:text-blue-600 transition-colors">
                                <i class="ri-add-line text-xs"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <div class="flex flex-row flex-1 p-4 gap-5 overflow-hidden" style="-webkit-overflow-scrolling: touch;">
                <div class="flex-1 min-w-0 p-3 sm:p-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 flex flex-col min-h-0">                
                    <div class="flex flex-col flex-1 min-h-0 min-w-0 overflow-hidden">
                        <div class="shrink-0 border-gray-300 px-2 sm:px-4 pt-3 pb-4">
                            <div class="flex items-center justify-between">
                            </div>
                            <div id="categories-grid" class="flex flex-row flex-wrap gap-1.5 sm:gap-2 overflow-x-auto pb-3 overscroll-x-contain">
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto pt-2 sm:pt-3">
                            <div id="products-grid" class="px-2 sm:px-4 md:px-5 p-3 grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-2 sm:gap-4 content-start pb-6">
                            </div>
                        </div>
                    </div>
                </div>
                <aside class="flex flex-col rounded-2xl shadow-sm border border-gray-200 dark:border-gray-800 overflow-hidden w-[400px] shrink-0 bg-white dark:bg-gray-900 min-h-0">
                    {{-- Tabs Resumen | Cobro (Cobro oculto para Mozo) --}}
                    <div class="flex shrink-0 border-b border-gray-200 dark:border-gray-700">
                        <button type="button" id="tab-resumen" onclick="switchAsideTab('resumen')"
                            class="flex-1 py-3 px-4 text-sm font-bold transition-colors rounded-tl-2xl bg-brand-500 text-white">
                            Resumen
                        </button>
                        @if($canCharge ?? true)
                        <button type="button" id="tab-cobro" onclick="switchAsideTab('cobro')"
                            class="flex-1 py-3 px-4 text-sm font-bold transition-colors bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-orange-100 dark:hover:bg-orange-900/30 hover:text-orange-600 dark:hover:text-orange-400">
                            Cobro
                        </button>
                        @endif
                    </div>

                    {{-- Contenido Resumen --}}
                    <div id="aside-resumen" class="flex flex-col flex-1 min-h-0 overflow-hidden">
                        {{-- Datos Delivery --}}
                        <div id="delivery-info-container" class="hidden p-3 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-100 dark:border-blue-800 space-y-2">
                            <div class="flex flex-col gap-2">
                                <div class="flex-1">
                                    <label class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Dirección de Entrega</label>
                                    <input type="text" id="delivery-address" oninput="updateDeliveryInfo()" placeholder="Av. Siempre Viva 123" class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                                </div>
                                <div class="flex gap-2">
                                    <div class="flex-1">
                                        <label class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Teléfono Contacto</label>
                                        <input type="text" id="delivery-phone" oninput="updateDeliveryInfo()" placeholder="999..." class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                                    </div>
                                    <div class="w-24">
                                        <label class="block text-[10px] font-bold uppercase text-blue-600 dark:text-blue-400 mb-1">Costo Delivery</label>
                                        <input type="number" step="0.5" id="delivery-amount" oninput="updateDeliveryInfo()" placeholder="0.00" class="w-full py-1.5 px-2 text-xs rounded border border-blue-200 focus:ring-1 focus:ring-blue-400 outline-none">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="cart-container" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2 sm:space-y-3 bg-white dark:bg-gray-900 min-h-0 overscroll-contain" style="-webkit-overflow-scrolling: touch;"></div>
                        <div id="cancelled-platos-container" class="shrink-0 hidden border-t border-gray-200 dark:border-gray-700 bg-amber-50 dark:bg-amber-900/20 p-3 sm:p-4 max-h-40 overflow-y-auto">
                            <p class="text-xs font-semibold text-amber-800 dark:text-amber-200 mb-2 flex items-center gap-1"><i class="ri-error-warning-line"></i> Platos anulados</p>
                            <div id="cancelled-platos-list" class="space-y-1.5 text-xs"></div>
                        </div>
                        <div class="shrink-0 p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700">
                            <div class="space-y-2 sm:space-y-3 text-xs sm:text-sm">
                                <div class="flex justify-between text-gray-500 font-medium">
                                    <span>Subtotal</span>
                                    <span class="text-slate-700 dark:text-slate-300" id="ticket-subtotal">$0.00</span>
                                </div>
                                <div class="flex justify-between text-gray-500 font-medium">
                                    <span>Impuestos</span>
                                    <span class="text-slate-700 dark:text-slate-300" id="ticket-tax">$0.00</span>
                                </div>
                                <div class="border-t border-dashed border-gray-300 dark:border-gray-600 my-2"></div>
                                <div class="flex justify-between items-center">
                                    <span class="text-base sm:text-lg font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                                    <span class="text-xl sm:text-2xl font-black text-blue-600 dark:text-blue-400" id="ticket-total">$0.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Contenido Cobro (oculto para Mozo) --}}
                    @if($canCharge ?? true)
                    <div id="aside-cobro" class="hidden flex-col flex-1 min-h-0 overflow-y-auto p-4 sm:p-5">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Cliente</label>
                                <div class="relative">
                                    <input type="text" id="cobro-client-input" readonly
                                        value="{{ $person?->name ?? 'Público General' }}"
                                        class="w-full pl-3 pr-8 py-2.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                    <button type="button" onclick="clearCobroClient()" class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                        <i class="ri-close-line text-lg"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Documento</label>
                                    <select id="cobro-document-type" class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($documentTypes ?? []) as $dt)
                                            <option value="{{ optional($dt)->id }}">{{ optional($dt)->name ?? '' }}</option>
                                        @empty
                                            <option value="">Sin documentos</option>
                                        @endforelse
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">Caja</label>
                                    <select id="cobro-cash-register" class="w-full py-2.5 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-slate-700 dark:text-slate-200 text-sm">
                                        @forelse(($cashRegisters ?? []) as $cr)
                                            <option value="{{ optional($cr)->id }}">{{ optional($cr)->number ?? 'Caja ' . optional($cr)->id }}</option>
                                        @empty
                                            <option value="">Sin cajas</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400">Métodos de pago</label>
                                    <button type="button" onclick="addCobroPaymentMethod()"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-brand-500 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-brand-600 active:scale-95 transition-colors shrink-0">
                                        <i class="ri-add-line text-sm"></i> Agregar
                                    </button>
                                </div>
                                <div id="cobro-payment-methods-list" class="space-y-3 max-h-48 overflow-y-auto pr-1"></div>
                                <div class="mt-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-100 dark:bg-gray-800/80 px-3 py-2.5">
                                    <div class="flex justify-between items-center">
                                        <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">Total pagado</span>
                                        <span class="text-base font-bold text-slate-800 dark:text-white tabular-nums" id="cobro-total-paid">S/ 0.00</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Botones Guardar / Cobrar: visibles según pestaña activa --}}
                    <div class="shrink-0 p-4 sm:p-5 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
                        {{-- Footer Resumen: solo Guardar y Precuenta --}}
                        <div id="footer-resumen" class="flex justify-between">
                            <x-ui.button id="btn-precuenta" type="button" variant="secondary" size="sm">
                                <i class="ri-save-line text-base"></i>
                                <span>Precuenta</span>
                            </x-ui.button>
                            <button type="button" id="btn-guardar" onclick="processOrder()"
                                class="py-2.5 px-4 rounded-xl bg-gray-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-gray-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                                <i class="ri-save-line text-base"></i>
                                <span>Guardar</span>
                            </button>
                        </div>
                        {{-- Footer Cobro: solo Cobrar (oculto para Mozo) --}}
                        @if($canCharge ?? true)
                        <div id="footer-cobro" class="hidden justify-end">
                            <button type="button" onclick="processOrderPayment()"
                                class="py-2.5 px-4 rounded-xl bg-brand-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-brand-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                                <i class="ri-bank-card-line text-base"></i>
                                <span>Cobrar</span>
                            </button>
                        </div>
                        @endif
                    </div>
                </aside>
            </div>

            {{-- Modal para crear/editar cliente rápido --}}
            <x-ui.modal 
                x-data="{ open: false }" 
                @open-person-modal.window="open = true" 
                @close-person-modal.window="open = false" 
                :isOpen="false" 
                :showCloseButton="false"
                class="max-w-4xl z-[100]">
                <div class="p-6 sm:p-8 bg-white dark:bg-gray-800">
                    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400">
                                <i class="ri-user-add-line text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Registrar / Editar Cliente</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ingresa DNI y nombre de la persona.</p>
                            </div>
                        </div>
                        <button type="button"
                            @click="open = false"
                            class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 transition-colors">
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>

                    <form method="POST"
                        action="{{ route('admin.companies.branches.people.store', [$branch->company_id ?? '0', $branch->id ?? '0']) }}"
                        class="space-y-6">
                        @csrf
                        <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">
                        <input type="hidden" name="location_id" value="{{ $branch->location_id ?? '' }}">
                        <input type="hidden" name="from_pos" value="1">
                        <input type="hidden" name="document_number" value="00000000">
                        @include('branches.people._form', ['person' => null, 'hidePinAndRoles' => true])

                        <div class="flex flex-wrap gap-3 justify-end pt-4 border-t border-gray-100 dark:border-gray-700">
                            <button type="button" @click="open = false"
                                class="px-5 py-2.5 rounded-xl border border-gray-300 text-gray-700 font-semibold hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors">
                                Cancelar
                            </button>
                            <button type="submit"
                                class="px-5 py-2.5 rounded-xl bg-blue-600 text-white font-semibold hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all">
                                <i class="ri-save-line mr-1"></i> Guardar Cliente
                            </button>
                        </div>
                    </form>
                </div>
            </x-ui.modal>
        </main>
    </div>

    <div id="notification" class="fixed top-24 right-8 z-50 max-w-sm opacity-0 pointer-events-none transition-opacity duration-300" aria-live="polite"></div>

    {{-- Toast: producto agregado (igual que en ventas) --}}
    <div id="toast-container" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 pointer-events-none flex flex-col gap-2 w-auto max-w-sm">
        <div id="add-to-cart-notification" class="transform transition-all duration-300 -translate-y-10 opacity-0 pointer-events-none bg-slate-800 text-white shadow-2xl rounded-full px-6 py-3 flex items-center gap-3 min-w-[200px]">
            <i class="ri-check-line text-green-400 text-xl"></i>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400">Agregado</p>
                <p id="notification-product-name" class="text-sm font-bold text-white truncate max-w-[180px]">Producto</p>
            </div>
        </div>
    </div>

    {{-- removeQuantityModal debe existir en window antes de que Alpine procese el modal --}}
    <script>
        window.removeQuantityModal = function() {
            return {
                open: false,
                indexToRemove: null,
                quantityToRemove: 1,
                maxQty: 1,
                productName: '',
                reasonToRemove: '',
                isComandado: false
            };
        };
    </script>
    {{-- Modal eliminar por cantidad (se abre al presionar la basurita en una línea del pedido) --}}
    <x-ui.modal x-data="removeQuantityModal()"
        @open-remove-quantity-modal.window="
            if ($event.detail && $event.detail.maxQty >= 1) {
                open = true;
                indexToRemove = $event.detail.index ?? null;
                maxQty = Math.max(1, $event.detail.maxQty ?? 1);
                productName = $event.detail.productName || 'Producto';
                quantityToRemove = 1;
                reasonToRemove = '';
                isComandado = !!$event.detail.isComandado;
                $nextTick(() => { if ($refs.qtyInput) $refs.qtyInput.value = 1; });
            }
        "
        @close-remove-quantity-modal.window="open = false; indexToRemove = null; quantityToRemove = 1; maxQty = 1; productName = ''; reasonToRemove = ''; isComandado = false"
        :showCloseButton="false"
        class="max-w-4xl z-[100]">
        <div class="p-6 sm:p-8 bg-white dark:bg-gray-800">
            <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-red-50 text-red-600 dark:bg-red-900/20 dark:text-red-400">
                        <i class="ri-delete-bin-line text-2xl"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Eliminar cantidad del pedido</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-0.5" x-text="productName ? (productName + ' · Cantidad actual: ' + maxQty) : ''"></p>
                    </div>
                </div>
                <button type="button"
                    @click="$dispatch('close-remove-quantity-modal')"
                    class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 hover:bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:hover:bg-gray-600 transition-colors">
                    <i class="ri-close-line text-xl"></i>
                </button>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad a eliminar</label>
                <input type="number"
                    x-ref="qtyInput"
                    @input="
                        let val = $event.target.value;
                        if (val === '' || val === null) { return; }
                        let v = parseInt(val, 10);
                        quantityToRemove = (isNaN(v) || v < 1) ? 1 : Math.min(maxQty, Math.max(1, v));
                        $event.target.value = quantityToRemove;
                    "
                    @blur="
                        if ($event.target.value === '' || isNaN(parseInt($event.target.value, 10))) {
                            quantityToRemove = 1;
                            $event.target.value = 1;
                        }
                    "
                    min="1"
                    :max="maxQty"
                    class="w-24 text-center text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500">
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1" x-text="'Entre 1 y ' + maxQty + (maxQty === 1 ? ' unidad' : ' unidades')"></p>
                <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mt-2" x-show="maxQty >= 1">
                    Quedarán: <span x-text="Math.max(0, maxQty - quantityToRemove)"></span> <span x-text="(maxQty - quantityToRemove) === 1 ? 'unidad' : 'unidades'"></span>
                </p>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Razón <span x-show="isComandado" class="text-red-600">(requerida)</span>
                </label>
                <textarea x-model="reasonToRemove"
                    rows="2"
                    placeholder="Ej: pedido equivocado, cliente canceló..."
                    class="w-full text-sm border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 bg-white dark:bg-gray-800 focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-600">
                <button type="button" @click="$dispatch('close-remove-quantity-modal')"
                    class="px-4 py-2 rounded-xl border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
                <button type="button"
                    @click="
                        if (indexToRemove != null && quantityToRemove >= 1 && (!isComandado || reasonToRemove.trim())) {
                            var q = Math.min(quantityToRemove, maxQty);
                            window.applyRemoveQuantity(indexToRemove, q, reasonToRemove.trim());
                            $dispatch('close-remove-quantity-modal');
                        }
                    "
                    :disabled="isComandado && !reasonToRemove.trim()"
                    :class="isComandado && !reasonToRemove.trim() ? 'opacity-50 cursor-not-allowed' : ''"
                    class="px-4 py-2 rounded-xl bg-red-600 text-white hover:bg-red-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                    Eliminar <span x-text="quantityToRemove > 1 ? quantityToRemove + ' unidades' : '1 unidad'"></span>
                </button>
            </div>
        </div>
    </x-ui.modal>
    <style>.notification-show { transform: translateY(0) !important; opacity: 1 !important; }</style>

    <script>
        document.addEventListener('alpine:init', function() {
            window.Alpine.data('removeQuantityModal', function() {
                return {
                    open: false,
                    indexToRemove: null,
                    quantityToRemove: 1,
                    maxQty: 1,
                    productName: '',
                    reasonToRemove: '',
                    isComandado: false
                };
            });
        });
    </script>
    <script>
        (function() {
            @php
                $serverTableData = [
                    'id' => $table->id,
                    'table_id' => $table->id,
                    'area_id' => $table->area_id ?? ($area->id ?? null),
                    'name' => $table->name ?? $table->id,
                    'waiter' => $user?->name ?? 'Sin asignar',
                    // Si hay pedido pendiente, usar su cliente; si no, usar el de la sesión (si existe)
                    'person_id' => $pendingClientId ?? null,
                    'clientName' => $pendingClientName ?? ($person?->name ?? 'Sin cliente'),
                    'status' => $table->situation ?? 'libre',
                    'items' => [],
                    'people_count' => (int) ($pendingPeopleCount ?? ($table->capacity ?? 1)),
                    'service_type' => $pendingServiceType ?? 'IN_SITU',
                    'delivery_address' => $pendingDeliveryAddress ?? '',
                    'contact_phone' => $pendingContactPhone ?? '',
                    'delivery_amount' => (float) ($pendingDeliveryAmount ?? 0),
                    'original_area_id' => $area->id ?? null,
                    'original_area_name' => $area->name ?? 'Sin área',
                    'delivery_area_id' => $deliveryAreaId ?? null,
                ];
            @endphp
            const serverTable = @json($serverTableData);
            const startFresh = @json($startFresh ?? false);
            // IDs del pedido pendiente que viene directo del servidor (fuente de verdad)
            const serverOrderMovementId = @json($pendingOrderMovementId ?? null);
            const serverMovementId = @json($pendingMovementId ?? null);
            const serverPendingItems = @json($pendingItems ?? []);
            const serverPendingCancelledDetails = @json($pendingCancelledDetails ?? []);
            const waiterPinEnabled = @json($waiterPinEnabled ?? false);
            const validateWaiterPinUrl = @json(route('orders.validateWaiterPin'));
            const waiterPinBranchId = @json((int) session('branch_id'));
            const cobroPaymentMethods = @json($paymentMethods ?? []);
            const cobroPaymentGateways = @json($paymentGateways ?? []);
            const cobroCards = @json($cards ?? []);
            const cobroDigitalWallets = @json($digitalWallets ?? []);
            const cobroBanks = @json($banks ?? []);

            let db = JSON.parse(localStorage.getItem('restaurantDB'));
            if (!db) db = {};
            let activeKey = `table-{{ $table->id }}`;
            let autoSaveTimer = null;

            // Si la mesa no tiene pedido pendiente (startFresh) o está libre: pedido nuevo, borrar borrador
            const tableIsFree = (serverTable.status || '').toLowerCase() === 'libre';
            const useFreshOrder = startFresh || tableIsFree;
            if (useFreshOrder && db[activeKey]) {
                delete db[activeKey];
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }

            let currentTable = (useFreshOrder || !db[activeKey]) ? serverTable : db[activeKey];
            // Exponer currentTable en window para que Alpine (combobox cliente) pueda accederlo en expresiones
            window.currentTable = currentTable;
            // Inicializar people_count si no existe en el estado guardado (default: capacity de mesa)
            if (!currentTable.people_count) currentTable.people_count = {{ $pendingPeopleCount ?? 1 }};
            // Siempre sincronizar order_movement_id y movement_id con el servidor para evitar duplicados
            if (serverOrderMovementId) {
                // El servidor es la fuente de verdad cuando hay pedido pendiente:
                // siempre rehidratamos los ítems desde serverPendingItems para evitar duplicados.
                currentTable = { ...currentTable, ...serverTable };
                window.currentTable = currentTable;
                currentTable.order_movement_id = serverOrderMovementId;
                currentTable.movement_id = serverMovementId;
                currentTable.service_type = serverTable.service_type;
                currentTable.delivery_address = serverTable.delivery_address;
                currentTable.contact_phone = serverTable.contact_phone;
                currentTable.delivery_amount = serverTable.delivery_amount;
                currentTable.items = Array.isArray(serverPendingItems) ? serverPendingItems : [];
                currentTable.items.forEach(function(it) {
                    it.savedQty = parseFloat(it.qty) ?? parseFloat(it.quantity) ?? 0;
                });
                currentTable.cancellations = [];
                db[activeKey] = currentTable;
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            } else {
                // No hay pedido pendiente en servidor: asegurar que no usamos un ID viejo del localStorage
                currentTable.order_movement_id = null;
                currentTable.movement_id = null;
                currentTable.items = currentTable.items || [];
            }
            // Inicializar estructura de cancelaciones por plato
            if (!currentTable.cancellations) currentTable.cancellations = [];

            function getStoredWaiter() {
                try {
                    const key = `waiterPin:${waiterPinBranchId}`;
                    const raw = sessionStorage.getItem(key);
                    if (!raw) return null;
                    const data = JSON.parse(raw);
                    if (!data || !data.person_id) return null;
                    const ts = Number(data.ts || 0);
                    if (!ts || (Date.now() - ts) > (12 * 60 * 60 * 1000)) {
                        sessionStorage.removeItem(key);
                        return null;
                    }
                    return data;
                } catch (e) {
                    return null;
                }
            }

            async function ensureWaiterPin() {
                if (!waiterPinEnabled) return true;
                const existing = getStoredWaiter();
                if (existing) {
                    // Mostrar mozo real en la UI
                    currentTable.waiter = existing.name || currentTable.waiter;
                    const el = document.getElementById('pos-waiter-name');
                    if (el && existing.name) el.innerText = existing.name;
                    return true;
                }
                if (!window.Swal) return false;

                while (true) {
                    const result = await Swal.fire({
                        title: 'PIN de mozo',
                        input: 'password',
                        inputLabel: 'Ingrese su PIN para tomar pedidos',
                        inputPlaceholder: 'PIN',
                        inputAttributes: { autocomplete: 'off' },
                        showCancelButton: true,
                        confirmButtonText: 'Ingresar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                        inputValidator: (value) => {
                            if (!value || !String(value).trim()) {
                                return 'Ingrese el PIN.';
                            }
                            return null;
                        }
                    });
                    if (!result.isConfirmed) {
                        return false;
                    }
                    try {
                        const pin = String(result.value || '').trim();
                        const res = await fetch(validateWaiterPinUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: JSON.stringify({ pin })
                        });
                        const data = await res.json().catch(() => null);
                        if (data && data.success && data.waiter && data.waiter.person_id) {
                            const key = `waiterPin:${waiterPinBranchId}`;
                            const payload = { ...data.waiter, ts: Date.now() };
                            sessionStorage.setItem(key, JSON.stringify(payload));
                            currentTable.waiter = payload.name || currentTable.waiter;
                            const el = document.getElementById('pos-waiter-name');
                            if (el && payload.name) el.innerText = payload.name;
                            saveDB();
                            return true;
                        }
                        await Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'error',
                            title: data?.message || 'PIN inválido.',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    } catch (e) {
                        await Swal.fire({
                            toast: true,
                            position: 'bottom-end',
                            icon: 'error',
                            title: 'No se pudo validar el PIN.',
                            showConfirmButton: false,
                            timer: 2500
                        });
                    }
                }
            }

            function init() {
                // Si requiere PIN, pedirlo al abrir la mesa
                if (waiterPinEnabled) {
                    ensureWaiterPin();
                }
                // Marcar la mesa como ocupada al abrir la vista
                const tableId = currentTable.table_id ?? currentTable.id ?? {{ $table->id }};
                fetch('{{ route('orders.openTable') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ table_id: tableId })
                }).catch(() => {});

                // Inicializar datos de la mesa
                if (document.getElementById('pos-table-name')) {
                    document.getElementById('pos-table-name').innerText = currentTable.name ||
                        "{{ str_pad($table->name ?? $table->id, 2, '0', STR_PAD_LEFT) }}";
                }
                if (document.getElementById('pos-table-area')) {
                    document.getElementById('pos-table-area').innerText = currentTable.area ||
                        "{{ $table->area?->name ?? ($area?->name ?? 'Sin área') }}";
                }
                if (document.getElementById('pos-waiter-name')) {
                    document.getElementById('pos-waiter-name').innerText = currentTable.waiter ||
                        "{{ $user?->name ?? 'Sin asignar' }}";
                }
                if (document.getElementById('pos-client-name')) {
                    document.getElementById('pos-client-name').innerText = currentTable.clientName ||
                        "{{ $person?->name ?? 'Sin cliente' }}";
                }
                const cobroClientInput = document.getElementById('cobro-client-input');
                if (cobroClientInput) {
                    cobroClientInput.value = currentTable.clientName || "{{ $person?->name ?? 'Público General' }}";
                }
                const dinersInput = document.getElementById('diners-input');
                if (dinersInput && currentTable.people_count) {
                    dinersInput.value = currentTable.people_count;
                }

                // Inicializar datos de servicio y delivery (automático por área)
                if (currentTable.area_id == serverTable.delivery_area_id) {
                    currentTable.service_type = 'DELIVERY';
                } else {
                    currentTable.service_type = 'IN_SITU';
                }
                
                // Actualizar visibilidad de datos delivery
                if (typeof changeServiceType === 'function') {
                    changeServiceType({ value: currentTable.service_type });
                }

                const addrInput = document.getElementById('delivery-address');
                const phoneInput = document.getElementById('delivery-phone');
                const amountInput = document.getElementById('delivery-amount');
                if (addrInput) addrInput.value = currentTable.delivery_address || '';
                if (phoneInput) phoneInput.value = currentTable.contact_phone || '';
                if (amountInput) amountInput.value = currentTable.delivery_amount || '';
                refreshCartPricesFromServer();
                renderCategories();
                renderProducts();
                renderTicket();
                renderCancelledSection();
                fixScrollLayout();
                function updateSearchClearVisibility() {
                    const inp = document.getElementById('search-products');
                    const btn = document.getElementById('search-products-clear');
                    if (btn) btn.classList.toggle('hidden', !inp || !inp.value.trim());
                }
                window.clearProductSearch = function() {
                    const inp = document.getElementById('search-products');
                    if (inp) {
                        inp.value = '';
                        productSearchQuery = '';
                        inp.focus();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                };
                document.addEventListener('input', function(e) {
                    if (e.target && e.target.id === 'search-products') {
                        productSearchQuery = (e.target.value || '').trim();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                });
                document.addEventListener('keyup', function(e) {
                    if (e.target && e.target.id === 'search-products') {
                        productSearchQuery = (e.target.value || '').trim();
                        updateSearchClearVisibility();
                        renderProducts();
                    }
                });
                document.addEventListener('keydown', function(e) {
                    if (e.target && e.target.id === 'search-products' && e.key === 'Escape') clearProductSearch();
                });
                updateSearchClearVisibility();
                if (currentTable.items && currentTable.items.length > 0) {
                    // setTimeout(scheduleAutoSave, 800);
                }
                if (typeof addCobroPaymentMethod === 'function' && document.getElementById('cobro-payment-methods-list')?.children.length === 0) {
                    addCobroPaymentMethod();
                }
                // Si viene con cobro=1 (desde botón Cobrar en mesas), abrir pestaña Cobro
                if (new URLSearchParams(window.location.search).get('cobro') === '1' && typeof switchAsideTab === 'function') {
                    setTimeout(() => switchAsideTab('cobro'), 100);
                }
            }

            function fixScrollLayout() {
                function run() {
                    const grid = document.getElementById('products-grid');
                    const cart = document.getElementById('cart-container');
                    if (grid) {
                        grid.scrollTop = 0;
                        void grid.offsetHeight;
                    }
                    if (cart) {
                        cart.scrollTop = 0;
                        void cart.offsetHeight;
                    }
                    window.scrollTo(0, 0);
                }
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        run();
                        setTimeout(run, 100);
                    });
                });
            }

            // Función para escapar HTML y prevenir XSS
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            const PLACEHOLDER_IMG = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23e5e7eb" width="200" height="200"/%3E%3Ctext fill="%239ca3af" font-family="sans-serif" font-size="14" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3ESin imagen%3C/text%3E%3C/svg%3E';

            // Función para obtener la URL de la imagen
            function getImageUrl(imagePath) {
                if (!imagePath || imagePath === 'null' || imagePath === null || imagePath === '') {
                    return PLACEHOLDER_IMG;
                }
                // Si ya es una URL completa, retornarla
                if (imagePath.startsWith('http://') || imagePath.startsWith('https://') || imagePath.startsWith(
                    'data:')) {
                    return imagePath;
                }
                // Si es una ruta relativa que empieza con /, retornarla tal cual
                if (imagePath.startsWith('/')) {
                    return imagePath;
                }
                // Si es una ruta de storage, ya viene con asset() desde el servidor
                return imagePath;
            }
            window.getImageUrl = getImageUrl;

            // Datos de productos, categorías y productBranches desde el servidor.
            const serverProductBranches = @json($productBranches ?? []);
            const serverCategories = @json(collect($categories ?? [])->map(fn($c) => ['id' => $c->id, 'name' => $c->description ?? '', 'img' => $c->image ? asset('storage/' . $c->image) : null])->values()->all());
            const serverProductsRaw = @json($products ?? []);
            const categoryIdsInBranch = (serverCategories || []).map(c => Number(c.id));
            // Solo productos que tienen productBranch en esta sucursal Y cuya categoría está en category_branch (está en serverCategories).
            const serverProducts = (serverProductsRaw || []).filter(p =>
                serverProductBranches.some(pb => Number(pb.product_id) === Number(p.id)) &&
                categoryIdsInBranch.includes(Number(p.category_id))
            );

            function getItemTaxRatePercent(item) {
                const rate = parseFloat(item?.tax_rate);
                return !isNaN(rate) && rate >= 0 ? rate : 10;
            }

            // Los precios del POS incluyen IGV.
            function calculateTotalsFromItems(items) {
                let subtotal = 0;
                let tax = 0;
                let total = 0;

                (items || []).forEach(item => {
                    const qty = parseFloat(item.qty) || 0;
                    const price = parseFloat(item.price) || 0;
                    const courtesyQty = parseInt(item.courtesyQty) || 0;
                    const paidQty = Math.max(0, qty - courtesyQty);
                    const lineTotal = paidQty * price;
                    const rate = getItemTaxRatePercent(item) / 100;
                    const lineSubtotal = rate > 0 ? (lineTotal / (1 + rate)) : lineTotal;
                    const lineTax = lineTotal - lineSubtotal;
                    subtotal += lineSubtotal;
                    tax += lineTax;
                    total += lineTotal;
                });

                return {
                    subtotal: Math.round(subtotal * 100) / 100,
                    tax: Math.round(tax * 100) / 100,
                    total: Math.round(total * 100) / 100,
                };
            }

            // Actualizar precios del carrito con los precios actuales del servidor
            function refreshCartPricesFromServer() {
                if (!currentTable?.items || !serverProductBranches?.length) return;
                let updated = false;
                currentTable.items.forEach(item => {
                    const pId = parseInt(item.pId || item.product_id, 10);
                    const pb = serverProductBranches.find(p => p.product_id === pId || parseInt(p.product_id, 10) === pId);
                    if (pb) {
                        const newPrice = parseFloat(pb.price);
                        if (!isNaN(newPrice) && newPrice >= 0 && newPrice !== parseFloat(item.price)) {
                            item.price = newPrice;
                            updated = true;
                        }
                        const newTaxRate = parseFloat(pb.tax_rate);
                        if (!isNaN(newTaxRate) && newTaxRate >= 0 && newTaxRate !== parseFloat(item.tax_rate)) {
                            item.tax_rate = newTaxRate;
                            updated = true;
                        }
                    }
                });
                if (updated) saveDB();
            }

            const CATEGORY_ALL_ID = '__all__';
            let selectedCategoryId = CATEGORY_ALL_ID;
            let productSearchQuery = '';

            function renderCategories() {
                const grid = document.getElementById('categories-grid');
                if (!grid) return; 
                
                grid.innerHTML = '';

                // Botón "Todos" por defecto (lista todos los productos de la sucursal con categoría)
                const allBtn = document.createElement('button');
                allBtn.type = 'button';
                const isAllActive = selectedCategoryId === CATEGORY_ALL_ID;
                allBtn.className = [
                    'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold',
                    'border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0',
                    isAllActive
                        ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                        : 'bg-white dark:bg-slate-800 text-gray-700 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400'
                ].join(' ');
                allBtn.onclick = function(e) {
                    e.preventDefault();
                    selectedCategoryId = CATEGORY_ALL_ID;
                    renderCategories();
                    renderProducts();
                };
                allBtn.innerHTML = `<i class="ri-apps-line text-lg"></i><span>Todos</span>`;
                grid.appendChild(allBtn);

                if (!serverCategories || serverCategories.length === 0) {
                    renderProducts();
                    return;
                }

                serverCategories.forEach(cat => {
                    const el = document.createElement('button');
                    const categoryName = escapeHtml(cat.name || 'Sin nombre');
                    const imageUrl = getImageUrl(cat.img);
                    const isActive = selectedCategoryId === cat.id;

                    el.type = 'button';
                    el.className = [
                        'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold',
                        'border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0',
                        isActive
                            ? 'bg-blue-600 text-white border-blue-600 shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400'
                    ].join(' ');

                    el.onclick = function(e) {
                        e.preventDefault();
                        selectedCategoryId = cat.id;
                        renderCategories();
                        renderProducts();
                    };

                    el.innerHTML = `
                        <img src="${imageUrl}" alt="${categoryName}"
                            class="w-6 h-6 rounded-full object-cover shrink-0 border ${isActive ? 'border-blue-300' : 'border-gray-200 dark:border-slate-600'}"
                            onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22200%22 height=%22200%22%3E%3Crect fill=%22%23e5e7eb%22 width=%22200%22 height=%22200%22/%3E%3C/svg%3E'">
                        <span>${categoryName}</span>
                    `;
                    grid.appendChild(el);
                });
            }
            
            function renderProducts() {
                const grid = document.getElementById('products-grid');
                if (!grid) return;
                grid.innerHTML = '';

                if (!serverProducts || serverProducts.length === 0) {
                    grid.innerHTML =
                        '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles</div>';
                    return;
                }

                // Filtrar por categoría seleccionada ("Todos" = todos los productos de la sucursal con categoría)
                let productsToShow = selectedCategoryId === CATEGORY_ALL_ID
                    ? serverProducts
                    : serverProducts.filter(p => p.category_id == selectedCategoryId);

                // Filtrar por texto de búsqueda: leer siempre del input para filtrar desde la primera letra
                const searchInput = document.getElementById('search-products');
                const q = (searchInput && searchInput.value !== undefined)
                    ? String(searchInput.value || '').trim().toLowerCase()
                    : String(productSearchQuery || '').trim().toLowerCase();
                if (q.length > 0) {
                    productsToShow = productsToShow.filter(p => {
                        const name = String(p.name || '').toLowerCase();
                        const category = String(p.category || '').toLowerCase();
                        return name.includes(q) || category.includes(q);
                    });
                }

                let productsRendered = 0;
                productsToShow.forEach(prod => {
                    const productBranch = serverProductBranches.find(p => p.product_id === prod.id || p.id === prod.id);
                    if (!productBranch) return;

                    const el = document.createElement('div');
                    el.className = "group cursor-pointer transition-transform duration-200 hover:scale-105 h-full flex";

                    // Prevenir múltiples clics rápidos
                    let isAdding = false;
                    el.onclick = function(e) {
                        e.preventDefault();

                        isAdding = true;
                        addToCart(prod, productBranch);

                        // Permitir agregar de nuevo después de un breve delay
                        setTimeout(() => {
                            isAdding = false;
                        }, 500);
                    };

                    const productName = escapeHtml(prod.name || 'Sin nombre');
                    const imageUrl = getImageUrl(prod.img);
                    const priceFormatted = 'S/ ' + parseFloat(productBranch.price).toFixed(2);
                    const hasImg = prod.img && String(prod.img).trim() !== '';
                    const stockVal = Number(productBranch.stock);
                    const stockText = !isNaN(stockVal) ? stockVal.toFixed(2) : '0.00';

                    el.innerHTML = `
                        <div class="rounded-2xl overflow-hidden p-4 sm:p-5 bg-white dark:bg-slate-800/60 border-2 border-blue-200 dark:border-blue-500/40 hover:border-blue-400 dark:hover:border-blue-400 transition-all duration-200 hover:-translate-y-0.5 flex flex-col items-center text-center h-full w-full">
                            <div class="w-20 h-16 sm:w-20 sm:h-20 rounded-full bg-blue-500 flex items-center justify-center shrink-0 overflow-hidden mb-3">
                                ${hasImg
                                    ? `<img src="${imageUrl}" alt="${productName}" class="w-full h-full object-contain rounded-full object-cover object-center" loading="lazy" onerror="this.parentElement.innerHTML='<i class=\\'ri-restaurant-2-line text-2xl sm:text-3xl text-white\\'></i>'">`
                                    : `<i class="ri-restaurant-2-line text-2xl sm:text-3xl text-white"></i>`
                                }
                            </div>
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm sm:text-base line-clamp-2 leading-tight mb-1 min-h-[2.5rem]">
                                ${productName}
                            </h4>
                            <span class="text-base sm:text-lg font-bold text-blue-600 dark:text-blue-400">
                                ${priceFormatted}
                            </span>
                            <span class="mt-1 text-xs font-medium text-gray-500 dark:text-gray-400">Stock: ` + stockText + `</span>
                        </div>
                    `;
                    grid.appendChild(el);
                    productsRendered++;
                });

                if (productsRendered === 0) {
                    if (q.length > 0) {
                        grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">No se encontraron productos para "' + escapeHtml(productSearchQuery) + '"</div>';
                    } else {
                        grid.innerHTML = selectedCategoryId === CATEGORY_ALL_ID
                            ? '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles para esta sucursal</div>'
                            : '<div class="col-span-full text-center text-gray-500 py-8">No hay productos en esta categoría</div>';
                    }
                }

            }

            function addToCart(prod, productBranch) {
                if (!currentTable.items) currentTable.items = [];

                if (!productBranch || !productBranch.price) {

                    return;
                }

                const price = parseFloat(productBranch.price);
                if (isNaN(price) || price <= 0) {
                    return;
                }

                const stock = parseFloat(productBranch.stock ?? 0) || 0;

                // Asegurar que el ID del producto sea un número entero para la comparación
                const productId = parseInt(prod.id, 10);
                if (isNaN(productId) || productId <= 0) {

                    return;
                }

                // Limpiar items inválidos antes de buscar
                currentTable.items = currentTable.items.filter(i => {
                    const itemPId = parseInt(i.pId, 10);
                    return !isNaN(itemPId) && itemPId > 0;
                });

                // Buscar si el producto ya existe en el carrito
                const existing = currentTable.items.find(i => {
                    const itemPId = parseInt(i.pId, 10);
                    return !isNaN(itemPId) && itemPId === productId;
                });

                const qtyToAdd = existing ? existing.qty + 1 : 1;
                if (qtyToAdd > stock) {
                    showNotification('Stock insuficiente', (prod.name || 'Producto') + ': solo hay ' + stock + ' disponible(s).', 'error');
                    return;
                }

                if (existing) {
                    // Si existe, solo aumentar la cantidad
                    existing.qty++;
                    if (existing.tax_rate === undefined || existing.tax_rate === null) {
                        existing.tax_rate = parseFloat(productBranch.tax_rate ?? 10);
                    }
                } else {
                    // Si no existe, agregarlo como nuevo item
                    currentTable.items.push({
                        pId: productId,
                        name: prod.name || 'Sin nombre',
                        qty: 1,
                        price: price,
                        tax_rate: parseFloat(productBranch.tax_rate ?? 10),
                        note: "",
                        delivered: false,
                        courtesyQty: 0,
                        isTakeAway: false
                    });
                }
                saveDB();
                renderTicket();
                showAddToCartNotification(prod.name || 'Producto');
            }

            async function updateQty(index, change) {
                const item = currentTable.items[index];
                const oldQty = item.qty;
                let newQty = oldQty + change;

                // Aumentar cantidad: no requiere razón
                if (change > 0) {
                    item.qty = newQty;
                    saveDB();
                    renderTicket();
                    return;
                }

                // Disminuir cantidad (si pedido guardado, no bajar de savedQty)
                const savedQty = Number.isFinite(parseFloat(item.savedQty)) ? parseFloat(item.savedQty) : 0;
                if (currentTable.order_movement_id && savedQty > 0) {
                    newQty = Math.max(savedQty, newQty);
                }
                item.qty = newQty;
                if (item.qty <= 0) currentTable.items.splice(index, 1);
                saveDB();
                renderTicket();
            }

            async function setQtyFromInput(index, inputEl) {
                const item = currentTable.items[index];
                if (!item) return;
                const raw = parseInt(inputEl.value, 10);
                const newQty = isNaN(raw) || raw < 1 ? 1 : raw;
                const oldQty = item.qty;
                if (newQty === oldQty) {
                    inputEl.value = newQty;
                    return;
                }
                if (newQty > oldQty) {
                    item.qty = newQty;
                    saveDB();
                    renderTicket();
                    return;
                }
                const savedQty = parseFloat(item.savedQty) ?? 0;
                if (currentTable.order_movement_id && savedQty > 0 && newQty < savedQty) {
                    inputEl.value = savedQty;
                    return;
                }
                await updateQty(index, newQty - oldQty);
            }

            function toggleNoteInput(index) {
                const box = document.getElementById(`note-box-${index}`);
                if (box) box.classList.toggle('hidden');
            }

            function toggleCourtesyInput(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                if (item.courtesyQty > 0) {
                    item.courtesyQty = 0;
                } else {
                    item.courtesyQty = 1;
                }
                saveDB();
                renderTicket();
            }

            async function confirmRemoveLine(index) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const qty = parseInt(item.qty, 10) || 1;
                const prod = serverProducts.find(p => p.id === item.pId);
                const name = (prod && prod.name) ? prod.name : 'este producto';
                const msg = qty === 1
                    ? `¿Desea eliminar 1 unidad de **${escapeHtml(name)}** del pedido?`
                    : `¿Desea eliminar **${qty}** unidades de **${escapeHtml(name)}** del pedido?`;
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Eliminar del pedido',
                        html: msg.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>'),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#dc2626'
                    });
                    if (!result.isConfirmed) return;
                } else if (!window.confirm(msg.replace(/\*\*/g, ''))) {
                    return;
                }
                await removeFromCart(index);
            }

            async function removeFromCart(index) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const qty = parseInt(item.qty, 10) || 1;
                await updateQty(index, -qty);
            }

            function applyRemoveQuantity(index, qtyToRemove, reason) {
                if (!currentTable.items || index < 0 || index >= currentTable.items.length) return;
                const item = currentTable.items[index];
                const prod = serverProducts.find(p => p.id === item.pId);
                const qty = parseFloat(item.qty) || 0;
                const toCancel = Math.min(qtyToRemove, qty);
                if (toCancel <= 0) return;
                if (currentTable.order_movement_id) {
                    currentTable.cancellations = currentTable.cancellations || [];
                    currentTable.cancellations.push({
                        pId: item.pId,
                        name: item.name || (prod && prod.name),
                        qtyCanceled: toCancel,
                        price: item.price,
                        note: item.note || null,
                        cancel_reason: reason || null,
                        product_snapshot: prod ? { ...prod } : null
                    });
                }
                item.qty = qty - toCancel;
                if (item.qty <= 0) currentTable.items.splice(index, 1);
                saveDB();
                renderTicket();
            }

            function saveNote(index, val) {
                currentTable.items[index].note = val;
                saveDB();
            }

            function toggleDelivered(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                currentTable.items[index].delivered = !currentTable.items[index].delivered;
                saveDB();
                renderTicket();
            }

            function setCourtesyQty(index, inputEl) {
                if (!currentTable.items || !currentTable.items[index]) return;
                let val = parseFloat(inputEl.value);
                if (isNaN(val) || val < 0) val = 0;
                const maxQty = parseFloat(currentTable.items[index].qty) || 0;
                if (val > maxQty) val = maxQty;
                currentTable.items[index].courtesyQty = val;
                inputEl.value = val;
                saveDB();
                renderTicket();
            }

            function changeCourtesyQty(index, delta) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const item = currentTable.items[index];
                const maxQty = parseFloat(item.qty) || 0;
                let v = (parseFloat(item.courtesyQty) || 0) + delta;
                v = Math.max(0, Math.min(maxQty, v));
                item.courtesyQty = v;
                saveDB();
                renderTicket();
            }
            window.setCourtesyQty = setCourtesyQty;
            window.changeCourtesyQty = changeCourtesyQty;

            function toggleTakeAway(index) {
                if (!currentTable.items || !currentTable.items[index]) return;
                const it = currentTable.items[index];
                it.isTakeAway = !it.isTakeAway;
                const tag = "(PARA LLEVAR)";
                if (it.isTakeAway) {
                    if (!(it.note || '').includes(tag)) {
                        it.note = tag + " " + (it.note || '');
                    }
                } else {
                    it.note = (it.note || '').replace(tag, "").trim();
                }
                saveDB();
                renderTicket();
            }

            function renderTicket() {
                const container = document.getElementById('cart-container');
                if (!container) {
                    return;
                }
                container.innerHTML = '';
                let subtotal = 0;

                if (!currentTable.items || currentTable.items.length === 0) {
                    container.innerHTML = `
                    <div class="flex flex-col items-center justify-center  text-gray-300 opacity-60">
                        <i class="fas fa-utensils text-3xl mb-2"></i>
                        <p class="font-medium text-sm">Sin productos</p>
                    </div>`;
                } else {
                    const isComandado = !!currentTable.order_movement_id;
                    currentTable.items.forEach((item, index) => {
                        const prod = serverProducts.find(p => p.id === item.pId);
    if (!prod) return;
    const itemPrice = parseFloat(item.price) || 0;
    const itemQty = parseInt(item.qty) || 0;
    const courtesyQty = Math.min(parseFloat(item.courtesyQty) || 0, itemQty);
    const paidQty = Math.max(itemQty - courtesyQty, 0);
    const lineBase = itemPrice * paidQty;
    const lineTotal = lineBase;
    // si quieres que el subtotal ignore las cortesías:
    subtotal += lineBase;
    const noteTime = item.commandTime || "";
    const noteText = typeof item.note === "string" ? item.note.trim() : "";
    const hasNote = noteText !== "";
    const row = document.createElement('div');
                        row.className = "bg-[#363636] dark:bg-[#363636] rounded-xl p-4 mb-2 border border-gray-600";

                        const productName = escapeHtml(prod.name || 'Sin nombre');
                        const itemNote = escapeHtml(noteText || '');
                        const isDelivered = !!item.delivered;
                        const statusLabel = isDelivered ? 'Entregado' : (isComandado ? 'Comandado' : 'Pendiente');
                        const statusClass = isDelivered ? 'bg-green-200/90 text-green-900' : (isComandado ? 'bg-amber-200/90 text-amber-900' : 'bg-gray-400/80 text-gray-800');
                        const rawSaved = item.savedQty != null && item.savedQty !== '' ? parseFloat(item.savedQty) : NaN;
                        const savedQtyItem = Number.isFinite(rawSaved) ? rawSaved : (isComandado ? (parseFloat(item.qty) || 0) : 0);
                        const canReduce = !isComandado || (parseFloat(item.qty) || 0) > savedQtyItem;
                        const qtyMinusDisabled = canReduce ? '' : ' disabled';
                        const qtyMinusClass = canReduce ? ' hover:bg-gray-500 transition-colors' : ' opacity-40 cursor-not-allowed';
                        const qtyMinusOnclick = canReduce ? `onclick="updateQty(${index}, -1)"` : '';
                        const trashOnclick = `onclick="window.dispatchEvent(new CustomEvent('open-remove-quantity-modal', { detail: { index: ${index}, maxQty: ${itemQty}, productName: '${String(prod.name || 'Producto').replace(/\\\\/g, '\\\\').replace(/'/g, "\\\\'")}', isComandado: ${isComandado} } }))"`;
                        const hasCourtesy = item.courtesyQty > 0;
                        row.innerHTML = `
                        <div class="flex flex-col gap-3">
                            <div class="flex justify-between items-start gap-2">
                                <div class="min-w-0 flex-1">
                                    <div class="font-bold text-base truncate">${productName}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">${noteTime ? noteTime + ' - ' : ''}S/ ${parseFloat(item.price).toFixed(2)} c/u</div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <span class="px-2.5 badge badge-sm py-0.5 rounded-md text-xs font-medium ${statusClass}">${statusLabel}</span>
                                    <button type="button" ${trashOnclick} class="p-1.5 text-gray-400 hover:text-red-400 transition-colors" title="Eliminar cantidad (registrar anulación)">
                                        <i class="ri-delete-bin-line text-sm"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="flex flex-nowrap items-center gap-3">
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="text-[9px] font-medium text-gray-400 uppercase tracking-wider">Cantidad</span>
                                    <div class="inline-flex items-center rounded-lg overflow-hidden border border-gray-500">
                                        <button type="button" ${qtyMinusOnclick} class="w-8 h-8 flex items-center justify-center ${qtyMinusClass}"${qtyMinusDisabled}>
                                            <i class="ri-subtract-line text-sm"></i>
                                        </button>
                                        <input type="number" value="${item.qty}" min="1" onchange="setQtyFromInput(${index}, this)" class="w-10 h-8 text-center text-sm font-bold bg-transparent border-none [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none focus:ring-0 focus:outline-none" ${canReduce ? '' : 'readonly'}>
                                        <button type="button" onclick="updateQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center hover:bg-gray-500 transition-colors">
                                            <i class="ri-add-line text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0 ${hasCourtesy ? '' : 'hidden'}">
                                    <span class="text-[9px] font-medium text-gray-400 uppercase tracking-wider">Cortesía</span>
                                    <div class="inline-flex items-center rounded-lg overflow-hidden border border-gray-500">
                                        <button type="button" onclick="changeCourtesyQty(${index}, -1)" class="w-8 h-8 flex items-center justify-center hover:bg-gray-500 transition-colors">
                                            <i class="ri-subtract-line text-sm"></i>
                                        </button>
                                        <span class="w-10 h-8 flex items-center justify-center text-sm font-bold">${courtesyQty}</span>
                                        <button type="button" onclick="changeCourtesyQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center hover:bg-gray-500 transition-colors">
                                            <i class="ri-add-line text-sm"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <button type="button" onclick="toggleTakeAway(${index})" class="flex items-center gap-1.5 text-[11px] ${item.isTakeAway ? 'text-orange-400' : 'text-gray-400'} hover:text-orange-400 transition-colors mt-1">
                                    <i class="ri-shopping-bag-3-line text-[10px]"></i> Para Llevar
                                </button>
                                <button type="button" onclick="toggleNoteInput(${index})" class="flex items-center gap-1.5 text-[11px] text-gray-400 hover:text-amber-400 transition-colors mt-1">
                                    ${hasNote ? '<i class="fas fa-comment-alt text-[10px]"></i> Nota' : '+ Nota'}
                                </button>
                                <button type="button" onclick="toggleCourtesyInput(${index})" class="flex items-center gap-1.5 text-[11px] text-gray-400 hover:text-amber-400 transition-colors mt-1">
                                    ${hasCourtesy ? '<i class="fas fa-utensils text-[10px]"></i> Cortesía' : '+ Cortesía'}
                                </button>
                            </div>
                            
                            <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'}">
                                <input type="text" value="${itemNote}" onblur="saveNote(${index}, this.value)" placeholder="Escribe una nota..." class="w-full text-xs bg-gray-600 border border-gray-500 rounded-lg px-2.5 py-2 text-gray-200 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-amber-400">
                            </div>
                            <div class="flex justify-between items-center pt-1 border-t border-gray-600">
                                <button type="button" onclick="toggleDelivered(${index})" class="text-xs flex items-center gap-1.5 text-gray-400 hover:text-gray-300 transition-colors">
                                    <span class="inline-flex w-3 h-3 rounded-full border ${isDelivered ? 'border-green-400 bg-green-500' : 'border-gray-500'}"></span>
                                    <span>${isDelivered ? 'Entregado' : 'Entregado'}</span>
                                </button>
                                <span class="font text-base">S/ ${lineTotal.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                        container.appendChild(row);
                    });
                }
                const totals = calculateTotalsFromItems(currentTable.items || []);
                const tax = totals.tax;
                const total = totals.total;
                subtotal = totals.subtotal;

                const subtotalEl = document.getElementById('ticket-subtotal');
                const taxEl = document.getElementById('ticket-tax');
                const totalEl = document.getElementById('ticket-total');

                if (subtotalEl) subtotalEl.innerText = `$${subtotal.toFixed(2)}`;
                if (taxEl) taxEl.innerText = `$${tax.toFixed(2)}`;
                if (totalEl) totalEl.innerText = `$${total.toFixed(2)}`;

                syncCobroAmountsWithCart(total);
                renderCancelledSection();
            }

            function syncCobroAmountsWithCart(orderTotal) {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return;
                const rows = list.querySelectorAll('.cobro-pm-row');
                if (rows.length === 0) return;
                if (rows.length === 1) {
                    const input = rows[0].querySelector('.cobro-pm-amount');
                    if (input) input.value = orderTotal.toFixed(2);
                } else {
                    const totalPaid = Array.from(rows).slice(1).reduce((s, row) => {
                        const inp = row.querySelector('.cobro-pm-amount');
                        return s + (parseFloat(inp?.value || 0) || 0);
                    }, 0);
                    const firstInput = rows[0].querySelector('.cobro-pm-amount');
                    if (firstInput) firstInput.value = Math.max(0, orderTotal - totalPaid).toFixed(2);
                }
                if (typeof updateCobroTotalPaid === 'function') updateCobroTotalPaid();
            }

            function renderCancelledSection() {
                const container = document.getElementById('cancelled-platos-container');
                const listEl = document.getElementById('cancelled-platos-list');
                if (!container || !listEl) return;

                const hasSavedOrder = !!currentTable.order_movement_id;
                const isCurrentPendingOrder = hasSavedOrder && (currentTable.order_movement_id === serverOrderMovementId);
                const serverCancelled = (isCurrentPendingOrder && serverPendingCancelledDetails && serverPendingCancelledDetails.length) ? serverPendingCancelledDetails : [];
                const clientCancelled = currentTable.cancellations || [];
                const hasAny = serverCancelled.length > 0 || clientCancelled.length > 0;

                if (!hasSavedOrder || !hasAny) {
                    container.classList.add('hidden');
                    listEl.innerHTML = '';
                    return;
                }

                container.classList.remove('hidden');
                let html = '';
                serverCancelled.forEach(function (d) {
                    const desc = escapeHtml(d.description || 'Producto');
                    const qty = d.quantity != null ? d.quantity : 1;
                    const reason = escapeHtml((d.comment || '').trim() || '—');
                    html += `<div class="rounded border border-amber-200 dark:border-amber-700/50 p-1.5 bg-white/60 dark:bg-gray-800/60"><span class="font-medium text-slate-700 dark:text-slate-200">${desc}</span> <span class="text-amber-700 dark:text-amber-300">×${qty}</span><br><span class="text-amber-800 dark:text-amber-200 italic">${reason}</span></div>`;
                });
                clientCancelled.forEach(function (c) {
                    const name = escapeHtml(c.name || 'Producto');
                    const qty = c.qtyCanceled != null ? c.qtyCanceled : 1;
                    const reason = escapeHtml((c.cancel_reason || '').trim() || '—');
                    html += `<div class="rounded border border-amber-200 dark:border-amber-700/50 p-1.5 bg-white/60 dark:bg-gray-800/60"><span class="font-medium text-slate-700 dark:text-slate-200">${name}</span> <span class="text-amber-700 dark:text-amber-300">×${qty}</span><br><span class="text-amber-800 dark:text-amber-200 italic">${reason}</span></div>`;
                });
                listEl.innerHTML = html;
            }

            /** Devuelve los ítems del pedido agrupados por producto (mismo pId → un ítem con qty sumada) para enviar al servidor. */
            function getItemsGroupedByProduct() {
                const items = currentTable.items || [];
                const byPid = {};
                items.forEach((it) => {
                    const id = parseInt(it.pId, 10) || 0;
                    if (!id) return;
                    if (!byPid[id]) {
                        byPid[id] = {
                            pId: it.pId,
                            name: it.name,
                            price: parseFloat(it.price) || 0,
                            tax_rate: it.tax_rate,
                            note: it.note || '',
                            commandTime: it.commandTime || null,
                            delivered: !!it.delivered,
                            courtesyQty: 0,
                            qty: 0
                        };
                    }
                    byPid[id].qty = (byPid[id].qty || 0) + (parseInt(it.qty, 10) || 1);
                    byPid[id].courtesyQty = (byPid[id].courtesyQty || 0) + (parseInt(it.courtesyQty) || 0);
                    if (it.delivered) byPid[id].delivered = true;
                });
                return Object.values(byPid);
            }

            function saveDB() {
                if (db && currentTable) {
                    // Agregar timestamp para saber cuándo se guardó
                    currentTable.lastUpdated = new Date().toISOString();
                    currentTable.isActive = true;
                    db[activeKey] = currentTable;
                    localStorage.setItem('restaurantDB', JSON.stringify(db));
                    const hasItems = currentTable.items && currentTable.items.length > 0;
                    const hasCancels = currentTable.cancellations && currentTable.cancellations.length > 0;
                    if (hasItems || hasCancels) {
                        // scheduleAutoSave(); // Deshabilitado auto-guardado automático al servidor
                    }
                }
            }

            function goToIndexWithTurbo() {
                const base = "{{ route('orders.index') }}";
                const params = new URLSearchParams(window.location.search);
                const viewId = params.get('view_id');
                let url = base + '?_=' + Date.now();
                if (viewId) url += '&view_id=' + encodeURIComponent(viewId);
                const win = (typeof window.top !== 'undefined' ? window.top : window);
                try {
                    win.location.replace(url);
                } catch (e1) {
                    try {
                        win.location.href = url;
                    } catch (e2) {
                        const a = document.createElement('a');
                        a.href = url;
                        a.target = '_top';
                        document.body.appendChild(a);
                        a.click();
                        document.body.removeChild(a);
                    }
                }
            }

            function isMesaYaCobradaMessage(msg) {
                if (!msg || typeof msg !== 'string') return false;
                const m = msg.toLowerCase();
                return m.indexOf('ya fue cobrada') !== -1 || m.indexOf('ya fue cobrado') !== -1;
            }

            function isNoActiveShiftMessage(msg) {
                if (!msg || typeof msg !== 'string') return false;
                const m = msg.toLowerCase();
                return m.indexOf('no hay un turno activo') !== -1
                    || m.indexOf('apertura de caja') !== -1
                    || m.indexOf('apertura de caja primero') !== -1;
            }

            // Limpiar auto-guardado al navegar con Turbo para no dispararlo en otra página
            document.addEventListener('turbo:before-visit', function() {
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }
            });

            function scheduleAutoSave() {
                if (autoSaveTimer) clearTimeout(autoSaveTimer);
                autoSaveTimer = setTimeout(autoSaveToServer, 1500);
            }

            function autoSaveToServer() {
                autoSaveTimer = null;
                const items = currentTable.items || [];
                if (items.length === 0 && (!currentTable.cancellations || currentTable.cancellations.length === 0)) return;
                // No auto-guardar si hay cancelaciones pendientes de razón (se pide al hacer Guardar / Cobrar)
                const cancels = currentTable.cancellations || [];
                if (cancels.some(c => !(c.cancel_reason && String(c.cancel_reason).trim()))) return;
                const itemsToSend = getItemsGroupedByProduct();
                const totals = calculateTotalsFromItems(itemsToSend);
                const order = {
                    items: itemsToSend,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: totals.subtotal,
                    tax: totals.tax,
                    total: totals.total,
                    people_count: currentTable.people_count ?? 0,
                    client_id: currentTable.person_id ?? null,
                    client_name: currentTable.clientName || null,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };
                fetch('{{ route('orders.process') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(order)
                })
                .then(res => res.headers.get('content-type')?.includes('application/json') ? res.json() : Promise.reject(new Error('Respuesta inválida')))
                    .then(data => {
                        if (data && data.success) {
                            currentTable.cancellations = [];
                            saveDB();
                        } else if (data && isMesaYaCobradaMessage(data.message)) {
                        if (typeof showNotification === 'function') {
                            showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                        }
                    }
                })
                .catch(() => {});
            }

            /** Pide una sola razón de anulación y la asigna a todas las cancelaciones que no la tengan. Devuelve false si el usuario cancela. */
            async function ensureCancellationReasons() {
                const cancels = currentTable.cancellations || [];
                const pending = cancels.filter(c => !(c.cancel_reason && String(c.cancel_reason).trim()));
                if (pending.length === 0) return true;

                let reason = null;
                if (window.Swal) {
                    const result = await Swal.fire({
                        title: 'Razón de anulación',
                        html: `Hay <strong>${pending.length}</strong> ítem(s) anulados. Indica la razón (aplica a todos).`,
                        input: 'textarea',
                        inputPlaceholder: 'Escribe la razón de la anulación...',
                        showCancelButton: true,
                        confirmButtonText: 'Continuar',
                        cancelButtonText: 'Cancelar',
                        inputValidator: (value) => {
                            if (!value || !value.trim()) return 'Debes ingresar una razón';
                            return null;
                        }
                    });
                    if (!result.isConfirmed || !result.value) return false;
                    reason = result.value.trim();
                } else {
                    const p = window.prompt('Razón de anulación (aplica a todos los ítems anulados):');
                    if (!p || !p.trim()) return false;
                    reason = p.trim();
                }

                cancels.forEach(c => {
                    if (!(c.cancel_reason && String(c.cancel_reason).trim())) c.cancel_reason = reason;
                });
                return true;
            }

            async function processOrder() {
                if (waiterPinEnabled) {
                    const ok = await ensureWaiterPin();
                    if (!ok) return;
                }
                const okReason = await ensureCancellationReasons();
                if (!okReason) return;
                const btnGuardar = document.getElementById('btn-guardar');
                if (btnGuardar) { btnGuardar.disabled = true; }
                let items = getItemsGroupedByProduct();

                // Hora de comanda: solo se fija la primera vez; la nota va siempre solo como texto.
                const now = new Date();
                const timeString = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: false });
                items.forEach((it) => {
                    if (!it) return;
                    if (!it.commandTime) it.commandTime = timeString;
                });

                const totals = calculateTotalsFromItems(items);
                const subtotal = totals.subtotal;
                const tax = totals.tax;
                const total = totals.total;

                const order = {
                    items: items,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: subtotal,
                    tax: tax,
                    total: total,
                    people_count: currentTable.people_count ?? 0,
                    client_id: currentTable.person_id ?? null,
                    client_name: currentTable.clientName || null,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };
                fetch('{{ route('orders.process') }}', {
                        method: 'POST',
                        cache: 'no-store',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                    'content') ||
                                '',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(order)
                    })
                    .then(async (response) => {
                        const ct = response.headers.get('content-type');
                        if (ct && ct.includes('application/json')) {
                            return response.json();
                        }
                        throw new Error(response.status === 419 ? 'Sesión expirada. Recarga la página.' : (response.status === 401 ? 'Debes iniciar sesión.' : 'Error del servidor. Intenta de nuevo.'));
                    })
                    .then(async data => {
                        if (data && data.success) {
                            // Limpiar cancelaciones ya persistidas
                            currentTable.cancellations = [];
                            saveDB();
                            sessionStorage.setItem('flash_success_message', data.message);
                            goToIndexWithTurbo();
                        } else if (data && isMesaYaCobradaMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                            } else {
                                alert(data.message || 'Esta mesa ya fue cobrada.');
                            }
                        } else if (data && data.message && String(data.message).indexOf('PIN') !== -1) {
                            sessionStorage.removeItem(`waiterPin:${waiterPinBranchId}`);
                            if (typeof showNotification === 'function') {
                                showNotification('PIN requerido', data.message || 'Ingrese el PIN del mozo e intente guardar de nuevo.', 'error');
                            } else {
                                sessionStorage.setItem('flash_error_message', data.message || 'Ingrese el PIN del mozo e intente guardar de nuevo.');
                            }
                        } else if (data && isNoActiveShiftMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Caja cerrada', data.message || 'No hay un turno activo. Realice una Apertura de Caja primero.', 'error');
                            } else {
                                sessionStorage.setItem('flash_error_message', data.message || 'No hay un turno activo. Realice una Apertura de Caja primero.');
                            }
                        } else {
                            console.error('Error al guardar:', data);
                            sessionStorage.setItem('flash_error_message', data?.message || 'Error al guardar.');
                        }
                        if (btnGuardar) { btnGuardar.disabled = false; }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sessionStorage.setItem('flash_error_message', 'Error al guardar el pedido. Revisa la consola.');
                        if (btnGuardar) { btnGuardar.disabled = false; }
                    });

            }

            function getCobroPaymentMethodsFromForm() {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return [];
                const result = [];
                const rows = list.querySelectorAll('.cobro-pm-row');
                rows.forEach(row => {
                    const methodSelect = row.querySelector('.cobro-pm-method');
                    const input = row.querySelector('.cobro-pm-amount');
                    if (!methodSelect || !input) return;
                    const pmId = parseInt(methodSelect.value, 10);
                    const amount = parseFloat(String(input.value || 0).replace(',', '.')) || 0;
                    if (!pmId || amount <= 0) return;
                    const obj = { payment_method_id: pmId, amount };
                    const desc = (methodSelect.options[methodSelect.selectedIndex]?.text || '').toLowerCase();
                    const isCard = (desc.includes('tarjeta') || desc.includes('card')) && !desc.includes('billetera');
                    const isWallet = desc.includes('billetera');
                    const isTransfer = desc.includes('transferencia') || desc.includes('transfer') || desc.includes('deposito') || desc.includes('depósito');
                    if (isCard) {
                        const gw = row.querySelector('.cobro-pm-gateway');
                        const card = row.querySelector('.cobro-pm-card');
                        if (gw?.value) obj.payment_gateway_id = parseInt(gw.value, 10);
                        if (card?.value) obj.card_id = parseInt(card.value, 10);
                    }
                    if (isWallet) {
                        const wallet = row.querySelector('.cobro-pm-wallet');
                        if (wallet?.value) obj.digital_wallet_id = parseInt(wallet.value, 10);
                    }
                    if (isTransfer) {
                        const bank = row.querySelector('.cobro-pm-bank');
                        if (bank?.value) obj.bank_id = parseInt(bank.value, 10);
                    }
                    result.push(obj);
                });
                return result;
            }

            function getCobroTotalPaid() {
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let total = 0;
                inputs.forEach(inp => {
                    total += parseFloat(String(inp.value || 0).replace(',', '.')) || 0;
                });
                return total;
            }

            async function processOrderPayment() {
                if (waiterPinEnabled) {
                    const ok = await ensureWaiterPin();
                    if (!ok) return;
                }
                const items = currentTable.items || [];
                if (items.length === 0) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'Agrega productos a la orden antes de cobrar.', 'error');
                    } else {
                        sessionStorage.setItem('flash_error_message', 'Agrega productos a la orden antes de cobrar.');
                    }
                    return;
                }
                const totals = calculateTotalsFromItems(items);
                const total = totals.total;
                const paymentMethodsData = getCobroPaymentMethodsFromForm();
                const totalPaid = paymentMethodsData.reduce((s, p) => s + (parseFloat(p.amount) || 0), 0);

                if (paymentMethodsData.length === 0) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'Agrega al menos un método de pago.', 'error');
                    } else {
                        alert('Agrega al menos un método de pago.');
                    }
                    return;
                }
                if (Math.abs(totalPaid - total) > 0.01) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'La suma de los métodos de pago debe ser igual al total (S/ ' + total.toFixed(2) + ').', 'error');
                    } else {
                        alert('La suma de los métodos de pago debe ser igual al total (S/ ' + total.toFixed(2) + ').');
                    }
                    return;
                }

                if (window.Swal) {
                    const confirmPayment = await Swal.fire({
                        title: 'Confirmar Cobro',
                        text: `Total a cobrar: S/ ${total.toFixed(2)}. ¿Deseas proceder?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Sí, cobrar',
                        cancelButtonText: 'Cancelar'
                    });
                    if (!confirmPayment.isConfirmed) return;
                }

                const okReason = await ensureCancellationReasons();
                if (!okReason) return;
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

                const itemsToSend = getItemsGroupedByProduct();
                const processPayload = {
                    items: itemsToSend,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: totals.subtotal,
                    tax: totals.tax,
                    total: totals.total,
                    people_count: currentTable.people_count ?? 0,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    service_type: currentTable.service_type ?? 'IN_SITU',
                    order_movement_id: currentTable.order_movement_id ?? null,
                    cancellations: currentTable.cancellations || [],
                };

                let btn = document.querySelector('button[onclick*="processOrderPayment"]');
                if (btn) { btn.disabled = true; btn.innerHTML = '<i class="ri-loader-4-line animate-spin text-base"></i><span>Procesando...</span>'; }

                try {
                    let movementId = currentTable.movement_id;

                    if (!movementId) {
                        const processRes = await fetch('{{ route('orders.process') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(processPayload)
                        });
                        const ct = processRes.headers.get('content-type');
                        let processData = null;
                        if (ct && ct.includes('application/json')) {
                            processData = await processRes.json();
                        } else {
                            throw new Error(processRes.status === 419 ? 'Sesión expirada. Recarga la página.' : (processRes.status === 401 ? 'Debes iniciar sesión.' : (processRes.status === 500 ? 'Error al procesar. Intenta de nuevo.' : 'Error del servidor.')));
                        }
                        if (!processData || !processData.success || !processData.movement_id) {
                            throw new Error(processData?.message || 'No se pudo guardar el pedido. Intenta de nuevo.');
                        }
                        movementId = processData.movement_id;
                        currentTable.cancellations = [];
                        currentTable.order_movement_id = processData.order_movement_id;
                        currentTable.movement_id = movementId;
                        saveDB();
                    }

                    const docTypeEl = document.getElementById('cobro-document-type');
                    const cashRegEl = document.getElementById('cobro-cash-register');
                    const paymentPayload = {
                        movement_id: movementId,
                        table_id: currentTable.table_id ?? currentTable.id,
                        document_type_id: docTypeEl?.value ? parseInt(docTypeEl.value, 10) : null,
                        cash_register_id: cashRegEl?.value ? parseInt(cashRegEl.value, 10) : null,
                        client_id: currentTable.person_id ?? null,
                        client_name: currentTable.clientName || null,
                        payment_methods: paymentMethodsData,
                        notes: '',
                    };

                    const payRes = await fetch('{{ route('orders.processOrderPayment') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(paymentPayload)
                    });

                    const payData = payRes.headers.get('content-type')?.includes('application/json') ? await payRes.json() : null;
                    if (!payRes.ok) {
                        throw new Error(payData?.message || payData?.error || 'Error al procesar el cobro.');
                    }
                    if (!payData?.success) {
                        throw new Error(payData?.message || payData?.error || 'Error al procesar el cobro.');
                    }

                    if (db && activeKey && db[activeKey]) {
                        delete db[activeKey];
                        localStorage.setItem('restaurantDB', JSON.stringify(db));
                    }
                    sessionStorage.setItem('flash_success_message', payData.message || 'Cobro de pedido procesado correctamente');
                    const indexUrl = "{{ route('orders.index') }}";
                    if (window.Turbo && typeof window.Turbo.visit === 'function') {
                        window.Turbo.visit(indexUrl, { action: 'replace' });
                    } else {
                        window.location.href = indexUrl;
                    }
                } catch (error) {
                    console.error('Error:', error);
                    if (String(error?.message || '').indexOf('PIN') !== -1) {
                        sessionStorage.removeItem(`waiterPin:${waiterPinBranchId}`);
                    }
                    if (typeof showNotification === 'function') {
                        showNotification('Error', error?.message || 'Error al procesar.', 'error');
                    } else {
                        alert(error?.message || 'Error al procesar.');
                    }
                } finally {
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="ri-bank-card-line text-base"></i><span>Cobrar</span>'; }
                }
            }

            function releaseTableAndGoBack() {
                const tableId = currentTable?.table_id ?? currentTable?.id ?? {{ $table->id }};
                const url = "{{ route('orders.cancelOrder') }}";
                const indexUrl = "{{ route('orders.index') }}";
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

                fetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrf,
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ table_id: tableId }),
                    })
                    .then(async (r) => {
                        if (r.headers.get('content-type')?.includes('application/json')) {
                            return r.json();
                        }
                        return null;
                    })
                    .then((data) => {
                        // Limpiar borrador local de esta mesa para que no reaparezca como guardada
                        if (db && activeKey && db[activeKey]) {
                            delete db[activeKey];
                            localStorage.setItem('restaurantDB', JSON.stringify(db));
                        }
                        if (currentTable) {
                            currentTable.items = [];
                            currentTable.order_movement_id = null;
                            currentTable.movement_id = null;
                            currentTable.isActive = false;
                        }
                    })
                    .catch(() => {
                        /* seguir igual, redirigir */ })
                    .finally(() => {
                        if (window.Turbo && typeof window.Turbo.visit === 'function') {
                            window.Turbo.visit(indexUrl, {
                                action: 'replace'
                            });
                        } else {
                            window.location.href = indexUrl;
                        }
                    });
            }

            function goBack() {
                const items = currentTable?.items || [];
                const cancels = currentTable?.cancellations || [];
                // Si no hay productos en la mesa, liberar la mesa y volver sin guardar nada
                if (!items.length && !cancels.length) {
                    releaseTableAndGoBack();
                    return;
                }
                // Si hay productos, guardar el pedido y volver al listado
                processOrder();
            }

            // Inicializar cuando el DOM esté listo
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', init);
            } else {
                init();
            }

            function showAddToCartNotification(productName) {
                const notification = document.getElementById('add-to-cart-notification');
                const productNameEl = document.getElementById('notification-product-name');
                if (!notification || !productNameEl) return;
                productNameEl.textContent = productName;
                notification.classList.add('notification-show');
                setTimeout(() => notification.classList.remove('notification-show'), 1200);
            }

            function showNotification(title, message, type = 'info') {
                const notification = document.getElementById('notification');
                if (!notification) return;
                const isError = type === 'error';
                notification.innerHTML = `
                    <div class="rounded-xl border p-4 shadow-lg ${isError ? 'bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800' : 'bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800'}">
                        <div class="flex items-start gap-3">
                            <div class="${isError ? 'text-red-500' : 'text-green-500'}"><i class="fas fa-${isError ? 'exclamation-circle' : 'check-circle'} text-xl"></i></div>
                            <div>
                                <h3 class="font-semibold ${isError ? 'text-red-800 dark:text-red-200' : 'text-green-800 dark:text-green-200'}">${title}</h3>
                                <p class="text-sm mt-1 ${isError ? 'text-red-700 dark:text-red-300' : 'text-green-700 dark:text-green-300'}">${message}</p>
                            </div>
                        </div>
                    </div>
                `;
                notification.classList.remove('opacity-0', 'pointer-events-none');
                setTimeout(() => {
                    notification.classList.add('opacity-0', 'pointer-events-none');
                }, 3500);
            }

            function updateDiners(delta) {
                const input = document.getElementById('diners-input');
                if (!input) return;
                let value = delta === 0
                    ? parseInt(input.value, 10)
                    : parseInt(input.value, 10) + delta;
                if (isNaN(value) || value < 1) value = 1;
                input.value = value;
                if (currentTable) {
                    currentTable.people_count = value;
                    if (db && activeKey) {
                        db[activeKey] = currentTable;
                        localStorage.setItem('restaurantDB', JSON.stringify(db));
                    }
                }
            }

            function switchAsideTab(tab) {
                const resumen = document.getElementById('aside-resumen');
                const cobro = document.getElementById('aside-cobro');
                const btnResumen = document.getElementById('tab-resumen');
                const btnCobro = document.getElementById('tab-cobro');
                const footerResumen = document.getElementById('footer-resumen');
                const footerCobro = document.getElementById('footer-cobro');
                const productsGrid = document.getElementById('products-grid');
                const categoriesGrid = document.getElementById('categories-grid');
                const searchInput = document.getElementById('search-products');
                if (tab === 'cobro') {
                    resumen?.classList.add('hidden');
                    cobro?.classList.remove('hidden');
                    cobro?.classList.add('flex');
                    footerResumen?.classList.add('hidden');
                    footerCobro?.classList.remove('hidden');
                    btnResumen?.classList.remove('bg-brand-500', 'text-white');
                    btnResumen?.classList.add('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnCobro?.classList.remove('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnCobro?.classList.add('bg-brand-500', 'text-white');
                    // Deshabilitar agregar/modificar productos mientras se está en Cobro
                    if (productsGrid) {
                        productsGrid.classList.add('pointer-events-none', 'opacity-60');
                    }
                    if (categoriesGrid) {
                        categoriesGrid.classList.add('pointer-events-none', 'opacity-60');
                    }
                    if (searchInput) {
                        searchInput.setAttribute('disabled', 'disabled');
                        searchInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                    }
                } else {
                    cobro?.classList.add('hidden');
                    cobro?.classList.remove('flex');
                    resumen?.classList.remove('hidden');
                    footerCobro?.classList.add('hidden');
                    footerResumen?.classList.remove('hidden');
                    btnCobro?.classList.remove('bg-brand-500', 'text-white');
                    btnCobro?.classList.add('bg-gray-100', 'dark:bg-gray-800', 'text-gray-500', 'dark:text-gray-400');
                    btnResumen?.classList.remove('bg-gray-200', 'dark:bg-gray-700', 'text-gray-700', 'dark:text-gray-300');
                    btnResumen?.classList.add('bg-brand-500', 'text-white');
                    // Volver a habilitar productos al regresar a Resumen
                    if (productsGrid) {
                        productsGrid.classList.remove('pointer-events-none', 'opacity-60');
                    }
                    if (categoriesGrid) {
                        categoriesGrid.classList.remove('pointer-events-none', 'opacity-60');
                    }
                    if (searchInput) {
                        searchInput.removeAttribute('disabled');
                        searchInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    }
                }
            }

            function clearCobroClient() {
                const input = document.getElementById('cobro-client-input');
                if (input) input.value = 'Público General';
                if (currentTable) {
                    currentTable.clientName = 'Público General';
                    currentTable.person_id = null;
                    saveDB();
                }
                const headerSelect = document.getElementById('header-client-select');
                if (headerSelect) {
                    for (let i = 0; i < headerSelect.options.length; i++) {
                        if (headerSelect.options[i].text === 'Público General') {
                            headerSelect.selectedIndex = i;
                            break;
                        }
                    }
                }
            }

            function getCobroOrderTotal() {
                const totals = calculateTotalsFromItems(currentTable?.items || []);
                return totals.total || 0;
            }

            function getCobroRemainingAmount(excludeInput) {
                const orderTotal = getCobroOrderTotal();
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let paid = 0;
                inputs.forEach(inp => {
                    if (inp !== excludeInput) {
                        paid += parseFloat(inp.value || 0) || 0;
                    }
                });
                return Math.max(0, orderTotal - paid);
            }

            function autocompleteCobroAmount(inputEl) {
                if (!inputEl) return;
                const val = parseFloat(inputEl.value || 0) || 0;
                if (val > 0) {
                    inputEl.select();
                    return;
                }
                const remaining = getCobroRemainingAmount(inputEl);
                if (remaining > 0) {
                    inputEl.value = remaining.toFixed(2);
                    inputEl.select();
                    updateCobroTotalPaid();
                }
            }

            function isCobroMethodCard(desc) {
                const d = (desc || '').toLowerCase();
                return (d.includes('tarjeta') || d.includes('card')) && !d.includes('billetera');
            }
            function isCobroMethodWallet(desc) {
                return ('' + (desc || '')).toLowerCase().includes('billetera');
            }
            function isCobroMethodTransfer(desc) {
                const d = ('' + (desc || '')).toLowerCase();
                return d.includes('transferencia') || d.includes('transfer') || d.includes('deposito') || d.includes('depósito');
            }

            function buildCobroGatewayOptions() {
                const gws = cobroPaymentGateways || [];
                const opts = gws.map(g => `<option value="${g.id}">${escapeHtml(g.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar pasarela</option>' + opts : '<option value="">Sin pasarelas</option>';
            }
            function buildCobroCardOptions() {
                const cards = cobroCards || [];
                const credit = cards.filter(c => (c.type || '').toUpperCase() === 'C');
                const debit = cards.filter(c => (c.type || '').toUpperCase() === 'D');
                let html = '<option value="">Seleccionar tarjeta</option>';
                if (credit.length) {
                    html += '<optgroup label="Crédito">' + credit.map(c => `<option value="${c.id}">${escapeHtml(c.description || '')}</option>`).join('') + '</optgroup>';
                }
                if (debit.length) {
                    html += '<optgroup label="Débito">' + debit.map(c => `<option value="${c.id}">${escapeHtml(c.description || '')}</option>`).join('') + '</optgroup>';
                }
                return html || '<option value="">Sin tarjetas</option>';
            }
            function buildCobroWalletOptions() {
                const wls = cobroDigitalWallets || [];
                const opts = wls.map(w => `<option value="${w.id}">${escapeHtml(w.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar billetera</option>' + opts : '<option value="">Sin billeteras</option>';
            }
            function buildCobroBankOptions() {
                const banks = cobroBanks || [];
                const opts = banks.map(b => `<option value="${b.id}">${escapeHtml(b.description || '')}</option>`).join('');
                return opts ? '<option value="">Seleccionar banco</option>' + opts : '<option value="">Sin bancos</option>';
            }

            function toggleCobroExtraFields(row) {
                const methodSelect = row.querySelector('.cobro-pm-method');
                const cardGroup = row.querySelector('.cobro-pm-card-group');
                const walletGroup = row.querySelector('.cobro-pm-wallet-group');
                const bankGroup = row.querySelector('.cobro-pm-bank-group');
                if (!methodSelect || !cardGroup || !walletGroup) return;
                const sel = methodSelect.options[methodSelect.selectedIndex];
                const desc = sel ? sel.text : '';
                const isCard = isCobroMethodCard(desc);
                const isWallet = isCobroMethodWallet(desc);
                const isTransfer = isCobroMethodTransfer(desc);
                cardGroup.classList.toggle('hidden', !isCard);
                walletGroup.classList.toggle('hidden', !isWallet);
                const gw = row.querySelector('.cobro-pm-gateway');
                const card = row.querySelector('.cobro-pm-card');
                const wallet = row.querySelector('.cobro-pm-wallet');
                if (!isCard && gw) gw.value = '';
                if (!isCard && card) card.value = '';
                if (!isWallet && wallet) wallet.value = '';
                if (bankGroup) {
                    if (isTransfer) {
                        bankGroup.classList.remove('hidden');
                        bankGroup.classList.add('flex');
                    } else {
                        bankGroup.classList.add('hidden');
                        bankGroup.classList.remove('flex');
                        const bank = row.querySelector('.cobro-pm-bank');
                        if (bank) bank.value = '';
                    }
                }
            }

            function addCobroPaymentMethod() {
                const list = document.getElementById('cobro-payment-methods-list');
                if (!list) return;
                const methods = cobroPaymentMethods || [];
                const opts = methods.map(pm =>
                    `<option value="${pm.id}">${escapeHtml(pm.description || '')}</option>`
                ).join('');
                const autoAmount = getCobroRemainingAmount();
                const row = document.createElement('div');
                row.className = 'cobro-pm-row rounded-lg border border-gray-200 dark:border-gray-600 bg-gray-50 dark:bg-gray-800/50 p-3 space-y-2';
                row.innerHTML = `
                    <div class="flex gap-2 items-end flex-wrap">
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Método</label>
                            <select class="cobro-pm-method w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-orange-400 focus:border-orange-400" onchange="toggleCobroExtraFields(this.closest('.cobro-pm-row'))">
                                ${opts}
                            </select>
                        </div>
                        <div class="w-24 shrink-0">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Monto</label>
                            <input type="number" step="0.01" min="0" value="${autoAmount > 0 ? autoAmount.toFixed(2) : '0.00'}" placeholder="0.00"
                                class="w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm tabular-nums cobro-pm-amount"
                                oninput="updateCobroTotalPaid()" onfocus="autocompleteCobroAmount(this)">
                        </div>
                        <button type="button" onclick="this.closest('.cobro-pm-row').remove(); updateCobroTotalPaid();" class="p-2 h-9 flex items-center justify-center text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors shrink-0" title="Eliminar">
                            <i class="ri-delete-bin-line text-lg"></i>
                        </button>
                    </div>
                    <div class="cobro-pm-card-group hidden flex gap-2 items-end flex-wrap">
                        <div class="flex-1 min-w-[100px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Pasarela</label>
                            <select class="cobro-pm-gateway w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                ${buildCobroGatewayOptions()}
                            </select>
                        </div>
                        <div class="flex-1 min-w-[100px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Tarjeta</label>
                            <select class="cobro-pm-card w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                ${buildCobroCardOptions()}
                            </select>
                        </div>
                    </div>
                    <div class="cobro-pm-wallet-group hidden flex gap-2 items-end flex-wrap">
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Billetera (Yape, Plin...)</label>
                            <select class="cobro-pm-wallet w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                ${buildCobroWalletOptions()}
                            </select>
                        </div>
                    </div>
                    <div class="cobro-pm-bank-group hidden flex gap-2 items-end flex-wrap">
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1">Banco destino</label>
                            <select class="cobro-pm-bank w-full py-2 px-3 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm">
                                ${buildCobroBankOptions()}
                            </select>
                        </div>
                    </div>
                `;
                list.appendChild(row);
                toggleCobroExtraFields(row);
                updateCobroTotalPaid();
            }

            function updateCobroTotalPaid() {
                const inputs = document.querySelectorAll('.cobro-pm-amount');
                let total = 0;
                inputs.forEach(inp => {
                    total += parseFloat(inp.value || 0) || 0;
                });
                const el = document.getElementById('cobro-total-paid');
                if (el) el.textContent = 'S/ ' + total.toFixed(2);
            }

            function changeClient(selectEl) {
                if (!selectEl || !currentTable) return;
                const name = selectEl.options[selectEl.selectedIndex]?.text || 'Público General';
                const personId = selectEl.value ? parseInt(selectEl.value, 10) : null;
                currentTable.clientName = name;
                currentTable.person_id = personId;
                saveDB();
                const cobroInput = document.getElementById('cobro-client-input');
                if (cobroInput) cobroInput.value = name;
            }

            function changeWaiter(selectEl) {
                if (!selectEl || !currentTable) return;
                const name = selectEl.options[selectEl.selectedIndex]?.text || 'Sin asignar';
                currentTable.waiter = name;
                saveDB();
            }

            function setCourtesyQty(index, inputEl) {
            if (!currentTable.items || !currentTable.items[index]) return;
            let val = parseFloat(inputEl.value);
            if (isNaN(val) || val < 0) val = 0;
            const maxQty = parseFloat(currentTable.items[index].qty) || 0;
            if (val > maxQty) val = maxQty;
            currentTable.items[index].courtesyQty = val;
            inputEl.value = val;
            saveDB();
            renderTicket();
            }

            function changeServiceType(valOrEl) {
                if (!currentTable) return;
                const val = (valOrEl && typeof valOrEl === 'object') ? valOrEl.value : valOrEl;
                currentTable.service_type = val;
                
                // Mostrar/ocultar panel de delivery
                const deliveryContainer = document.getElementById('delivery-info-container');
                if (deliveryContainer) {
                    if (val === 'DELIVERY') {
                        deliveryContainer.classList.remove('hidden');
                    } else {
                        deliveryContainer.classList.add('hidden');
                    }
                }
                
                // Nota: El área ya está definida por la mesa abierta, no se cambia aquí
                saveDB();
                renderTicket();
            }

            function updateDeliveryInfo() {
                if (!currentTable) return;
                const addr = document.getElementById('delivery-address');
                const phone = document.getElementById('delivery-phone');
                const amount = document.getElementById('delivery-amount');
                
                if (addr) currentTable.delivery_address = addr.value;
                if (phone) currentTable.contact_phone = phone.value;
                if (amount) currentTable.delivery_amount = parseFloat(amount.value) || 0;
                
                saveDB();
                renderTicket();
            }

            // Exponer funciones usadas desde onclick en el HTML (mismo ámbito tras re-render)
            window.toggleCourtesyInput = toggleCourtesyInput;
            window.toggleNoteInput = toggleNoteInput;
            window.updateQty = updateQty;
            window.setQtyFromInput = setQtyFromInput;
            window.confirmRemoveLine = confirmRemoveLine;
            window.removeFromCart = removeFromCart;
            window.applyRemoveQuantity = applyRemoveQuantity;
            window.saveNote = saveNote;
            window.toggleDelivered = toggleDelivered;
            window.getImageUrl = getImageUrl;
            window.goBack = goBack;
            window.processOrder = processOrder;
            window.processOrderPayment = processOrderPayment;
            window.updateDiners = updateDiners;
            window.switchAsideTab = switchAsideTab;
            window.clearCobroClient = clearCobroClient;
            window.addCobroPaymentMethod = addCobroPaymentMethod;
            window.updateCobroTotalPaid = updateCobroTotalPaid;
            window.autocompleteCobroAmount = autocompleteCobroAmount;
            window.toggleCobroExtraFields = toggleCobroExtraFields;
            window.changeClient = changeClient;
            window.changeWaiter = changeWaiter;
            window.changeServiceType = changeServiceType;
            window.updateDeliveryInfo = updateDeliveryInfo;
            window.toggleTakeAway = toggleTakeAway;
            
        })();
    </script>
@endsection
