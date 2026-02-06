{{-- Modal para procesar el pago de la venta --}}
<div id="charge-modal" 
     class="fixed inset-0 z-50 overflow-y-auto" 
     style="display: none;"
     aria-labelledby="modal-title" 
     role="dialog" 
     aria-modal="true">
    
    {{-- Overlay --}}
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 backdrop-blur-sm transition-opacity" id="modal-backdrop"></div>

    {{-- Contenedor del modal --}}
    <div class="flex min-h-full items-center justify-center p-2">
        <div class="relative transform overflow-hidden rounded-lg bg-white dark:bg-slate-900 text-left shadow-xl transition-all w-full max-w-md">
            
            {{-- Header --}}
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 px-3 py-2">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-white/20 backdrop-blur-sm">
                            <i class="fas fa-cash-register text-sm text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-white" id="modal-title">
                                Procesar Pago
                            </h3>
                        </div>
                    </div>
                    <button type="button" 
                            id="modal-close-btn"
                            class="rounded-md text-white transition-colors hover:text-blue-100">
                        <i class="fas fa-times text-lg"></i>
                    </button>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-3 py-3 space-y-3 max-h-[60vh] overflow-y-auto">
                
                {{-- Cliente --}}
                <div class="rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-slate-700 dark:bg-slate-800/50">
                    <div class="flex items-center gap-1.5">
                        <div class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-100 dark:bg-blue-900/30">
                            <i class="fas fa-user text-[10px] text-blue-600 dark:text-blue-400"></i>
                        </div>
                        <div>
                            <p class="text-[10px] text-gray-500 dark:text-gray-400">Cliente</p>
                            <p class="text-xs font-semibold text-gray-900 dark:text-white" id="modal-client-name">Público General</p>
                        </div>
                    </div>
                </div>

                {{-- Resumen de productos --}}
                <div>
                    <h4 class="mb-1.5 flex items-center gap-1.5 text-[10px] font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-shopping-bag text-[10px] text-blue-600 dark:text-blue-400"></i>
                        Resumen de la Orden
                    </h4>
                    <div class="space-y-1 max-h-24 overflow-y-auto pr-1" id="modal-items-list">
                        {{-- Items se llenarán con JavaScript --}}
                    </div>
                </div>

                {{-- Totales --}}
                <div class="space-y-1 rounded-lg border border-blue-200 bg-gradient-to-br from-blue-50 to-indigo-50 p-2 dark:border-slate-700 dark:from-slate-800 dark:to-slate-800/50">
                    <div class="flex justify-between text-[10px]">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="font-semibold text-gray-900 dark:text-white" id="modal-subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-[10px]">
                        <span class="text-gray-600 dark:text-gray-400">Impuestos (10%)</span>
                        <span class="font-semibold text-gray-900 dark:text-white" id="modal-tax">$0.00</span>
                    </div>
                    <div class="border-t border-blue-300 pt-1 dark:border-slate-600">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-bold text-gray-900 dark:text-white">Total a Pagar</span>
                            <span class="text-xl font-black text-blue-600 dark:text-blue-400" id="modal-total">$0.00</span>
                        </div>
                    </div>
                </div>

                {{-- Método de pago --}}
                <div>
                    <label class="mb-1.5 block text-[10px] font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-credit-card mr-1 text-[10px] text-blue-600 dark:text-blue-400"></i>
                        Método de Pago
                    </label>
                    <div class="grid grid-cols-3 gap-1.5">
                        <button type="button" 
                                class="payment-method-btn active flex flex-col items-center gap-0.5 rounded-lg border-2 p-2 transition-all hover:scale-105" 
                                data-method="cash">
                            <i class="fas fa-money-bill-wave text-sm"></i>
                            <span class="text-[9px] font-semibold">Efectivo</span>
                        </button>
                        <button type="button" 
                                class="payment-method-btn flex flex-col items-center gap-0.5 rounded-lg border-2 p-2 transition-all hover:scale-105" 
                                data-method="card">
                            <i class="fas fa-credit-card text-sm"></i>
                            <span class="text-[9px] font-semibold">Tarjeta</span>
                        </button>
                        <button type="button" 
                                class="payment-method-btn flex flex-col items-center gap-0.5 rounded-lg border-2 p-2 transition-all hover:scale-105" 
                                data-method="transfer">
                            <i class="fas fa-exchange-alt text-sm"></i>
                            <span class="text-[9px] font-semibold">Transferencia</span>
                        </button>
                    </div>
                </div>

                {{-- Monto recibido (solo para efectivo) --}}
                <div id="cash-received-section" class="space-y-1.5">
                    <label for="amount-received" class="block text-[10px] font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-hand-holding-usd mr-1 text-[10px] text-green-600 dark:text-green-400"></i>
                        Monto Recibido
                    </label>
                    <div class="relative">
                        <span class="absolute left-2 top-1/2 -translate-y-1/2 text-xs font-bold text-gray-500 dark:text-gray-400">$</span>
                        <input type="number" 
                               id="amount-received" 
                               class="h-8 w-full rounded-lg border-2 border-gray-300 bg-white pl-6 pr-2 text-sm font-semibold text-gray-900 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400"
                               placeholder="0.00" 
                               step="0.01"
                               min="0">
                    </div>
                </div>

                {{-- Notas adicionales --}}
                <div>
                    <label for="sale-notes" class="mb-1 block text-[10px] font-semibold text-gray-700 dark:text-gray-300">
                        <i class="fas fa-sticky-note mr-1 text-[10px] text-amber-600 dark:text-amber-400"></i>
                        Notas Adicionales (Opcional)
                    </label>
                    <textarea id="sale-notes" 
                              rows="1" 
                              class="w-full resize-none rounded-lg border-2 border-gray-300 bg-white px-2 py-1.5 text-[10px] text-gray-900 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800 dark:text-white dark:focus:border-blue-400 dark:focus:ring-blue-400"
                              placeholder="Agregar notas..."></textarea>
                </div>

            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 bg-gray-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800/50">
                <div class="flex gap-1.5">
                    <button type="button" 
                            id="modal-cancel-btn"
                            class="flex-1 rounded-lg border-2 border-gray-300 bg-white px-2 py-1.5 text-[10px] font-bold text-gray-700 transition-all hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                        <i class="fas fa-times mr-1 text-[9px]"></i>
                        Cancelar
                    </button>
                    <button type="button" 
                            id="modal-confirm-btn"
                            class="flex-1 rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-2 py-1.5 text-[10px] font-bold text-white shadow-lg shadow-blue-500/30 transition-all hover:from-blue-700 hover:to-blue-800 active:scale-95 dark:from-blue-700 dark:to-blue-800 dark:shadow-blue-500/20 dark:hover:from-blue-800 dark:hover:to-blue-900">
                        <i class="fas fa-check-circle mr-1 text-[9px]"></i>
                        Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .payment-method-btn {
        border-color: #e5e7eb;
        color: #6b7280;
        background-color: #ffffff;
    }

    .dark .payment-method-btn {
        border-color: #475569;
        color: #94a3b8;
        background-color: #1e293b;
    }

    .payment-method-btn.active {
        border-color: #3b82f6;
        color: #3b82f6;
        background-color: #eff6ff;
    }

    .dark .payment-method-btn.active {
        border-color: #60a5fa;
        color: #60a5fa;
        background-color: #1e3a8a;
    }
</style>
