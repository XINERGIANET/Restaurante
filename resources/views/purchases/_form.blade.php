@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="Nueva Compra" />

    <x-common.component-card title="Compras | Nuevo" desc="Registra una compra con su detalle, totales, impacto de stock/caja y pagos.">
        
        @if($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-600">
                <div class="font-bold flex items-center gap-2 mb-2">
                    <i class="ri-error-warning-line text-lg"></i> Por favor corrige los siguientes errores:
                </div>
                <ul class="list-disc pl-6 space-y-1">
                    @foreach($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @php
            $purchaseMovement = optional($purchase ?? null)->purchaseMovement;
            $existingItems = collect($purchaseMovement?->details ?? [])->map(function ($detail) {
                return [
                    'product_id' => (int) ($detail->product_id ?? 0),
                    'unit_id' => (int) ($detail->unit_id ?? 0),
                    'description' => (string) ($detail->description ?? ''),
                    'quantity' => (float) ($detail->quantity ?? 1),
                    'amount' => (float) ($detail->amount ?? 0),
                    'comment' => (string) ($detail->comment ?? ''),
                    'product_query' => '',
                    'product_open' => false,
                    'product_cursor' => 0,
                ];
            })->values();
        @endphp

        <form 
            method="POST" 
            action="{{ route('purchase.store', !empty($viewId) ? ['view_id' => $viewId] : []) }}" 
            class="space-y-6" 
            enctype="multipart/form-data"
            x-data="purchaseForm({
                products: @js($products->map(fn($p) => [
                    'id' => (int) $p->id,
                    'code' => (string) ($p->code ?? ''),
                    'name' => (string) ($p->description ?? ''),
                    'unit_id' => (int) ($p->unit_sale ?? 0),
                    'unit_name' => (string) ($p->unit_name ?? ''),
                    'cost' => (float) ($p->price ?? 0),
                ])->values()),
                units: @js($units),
                initialProviderId: @js((int) old('person_id', $purchase?->person_id ?? 0)),
                initialItems: @js(old('items', $existingItems->all())),
                taxRate: @js((float) old('tax_rate_percent', $defaultTaxRate ?? 18)),
                includesTax: @js((string) old('includes_tax', $purchaseMovement?->includes_tax ?? 'N')),
                initialCurrency: @js((string) old('currency', $purchaseMovement?->currency ?? 'PEN')),
                initialBranchId: @js((int) old('branch_id', $purchaseMovement?->branch_id ?? $branchId ?? 0)),
                initialPaymentType: @js((string) old('payment_type', $purchaseMovement?->payment_type ?? 'CONTADO'))
            })"
            x-init="initForm()"
            @submit.prevent="
                let firstInvalid = null;
                const elements = $el.querySelectorAll('input[name]:not([type=hidden]), select[name], textarea[name]');
                for (let el of elements) {
                    if (!el.checkValidity()) {
                        firstInvalid = el;
                        break;
                    }
                }
                if (firstInvalid) {
                    firstInvalid.reportValidity();
                } else {
                    isSubmitting = true;
                    $el.submit();
                }
            "
        >
            @csrf
            
            <div class="space-y-5">
                
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-gray-700">Cabecera de compra</h3>
                    
                    <div class="grid grid-cols-1 gap-4 md:grid-cols-12 md:items-end"> 
                        <div class="md:col-span-3">
                            <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-500 tracking-wider">Sucursal de Destino</label>
                            <select name="branch_id" x-model.number="selectedBranchId" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:outline-none focus:ring-1 focus:ring-[#244BB3] bg-white" required>
                                <option value="">Selecciona una sucursal</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}">{{ $branch->legal_name ?? $branch->name ?? 'Sucursal ' . $branch->id }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <x-form.date-picker name="moved_at" label="Fecha" placeholder="dd/mm/yyyy hh:mm" :defaultDate="old('moved_at', optional($purchase?->moved_at ?? now())->format('Y-m-d H:i'))" dateFormat="Y-m-d H:i" :enableTime="true" :time24hr="true" :altInput="true" altFormat="d/m/Y H:i" locale="es" :compact="true" />
                        </div>

                        <div class="md:col-span-3">
                            <x-form.select.combobox 
                                label="Proveedor"
                                name="person_id"
                                x-model="selectedProviderId"
                                :options="$people->map(fn($person) => [
                                    'id' => $person->id,
                                    'description' => trim(($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) . ' - ' . ($person->document_number ?? '')
                                ])->values()->all()"
                                :required="true"
                                placeholder="Buscar proveedor..."
                                icon="ri-user-shared-line"
                                iconClickEvent="open-modal-proveedor"
                            />
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-500 tracking-wider">Documento</label>
                            <select name="document_type_id" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:outline-none focus:ring-1 focus:ring-[#244BB3] bg-white" required>
                                <option value="">Documento...</option>
                                @foreach($documentTypes as $documentType)
                                    <option value="{{ $documentType->id }}" @selected((int) old('document_type_id', $purchase?->document_type_id ?? 0) === (int) $documentType->id)>{{ str_replace(' de compra', '', $documentType->name) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="mb-1.5 block text-xs font-semibold uppercase text-gray-500 tracking-wider">Serie</label>
                            <input type="text" name="series" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:outline-none focus:ring-1 focus:ring-[#244BB3]" value="{{ old('series', $purchaseMovement?->series ?? '001') }}" placeholder="001" required>
                        </div>
                    </div>
                </div>  

                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                    <div class="mb-4 flex items-center justify-between">
                        <h3 class="text-sm font-bold uppercase tracking-wide text-gray-700">Detalle de compra</h3>
                        <button type="button" @click="addItem()" class="inline-flex items-center rounded-lg bg-[#244BB3] px-3.5 py-2 text-xs font-semibold text-white hover:bg-[#1f3f98] transition-colors">
                            <i class="ri-add-line mr-1 text-base"></i>Agregar item
                        </button>
                    </div>

                    <div class="overflow-visible rounded-lg border border-gray-200">
                        <table class="w-full table-fixed">
                            <colgroup>
                                <col style="width:36%">
                                <col style="width:20%">
                                <col style="width:6%">
                                <col style="width:6%">
                                <col style="width:25%">
                                <col style="width:4%">
                                <col style="width:3%">
                            </colgroup>
                            <thead style="background-color: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-bold uppercase text-gray-600 tracking-wider">Codigo / Producto</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold uppercase text-gray-600 tracking-wider">Unidad</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase text-gray-600 tracking-wider">Cant.</th>
                                    <th class="px-3 py-3 text-right text-xs font-bold uppercase text-gray-600 tracking-wider">P. Unit.</th>
                                    <th class="px-3 py-3 text-left text-xs font-bold uppercase text-gray-600 tracking-wider">Notas</th>
                                    <th class="px-3 py-3 text-right text-xs font-bold uppercase text-gray-600 tracking-wider">Importe</th>
                                    <th class="px-3 py-3 text-center text-xs font-bold uppercase text-gray-600 tracking-wider"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in items" :key="idx">
                                    <tr class="border-b border-gray-100 last:border-b-0 hover:bg-gray-50/50 transition-colors">
                                        
                                        <td class="relative overflow-visible px-2 py-2">
                                            <input type="hidden" :name="`items[${idx}][product_id]`" :value="item.product_id">
                                            <input type="hidden" :name="`items[${idx}][description]`" :value="item.description">
                                            
                                            <div class="relative z-20" @click.outside="item.product_open = false">
                                                <input type="text" x-model="item.product_query" @focus="item.product_open = true" @input="item.product_open = true" @keydown.arrow-down.prevent="moveProductCursor(idx, 1)" @keydown.arrow-up.prevent="moveProductCursor(idx, -1)" @keydown.enter.prevent="selectProductByCursor(idx)" class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" placeholder="Buscar producto..." autocomplete="off" required>
                                                <button type="button" x-show="item.product_id" @click="clearProduct(idx)" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-700" title="Limpiar producto"><i class="ri-close-line"></i></button>
                                                
                                                <div x-show="item.product_open" x-cloak class="absolute left-0 top-full z-[999] mt-1 max-h-56 min-w-[22rem] max-w-[30rem] overflow-y-auto overflow-x-hidden rounded-lg border border-gray-200 bg-white shadow-xl">
                                                    <template x-if="filteredProducts(item).length === 0"><p class="px-3 py-2 text-xs text-gray-500">Sin resultados</p></template>
                                                    <template x-for="(product, pIndex) in filteredProducts(item)" :key="product.id">
                                                        <button type="button" @click="selectProduct(idx, product)" @mouseenter="item.product_cursor = pIndex" class="flex w-full items-center justify-between px-3 py-2.5 text-left text-sm hover:bg-gray-50" :class="item.product_cursor === pIndex ? 'bg-gray-100' : ''">
                                                            <span class="font-medium text-gray-800" x-text="`${product.code || 'SIN'} - ${product.name}`"></span>
                                                            <span class="text-xs text-gray-500" x-text="product.unit_name || ''"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-2 py-2">
                                            <input type="hidden" :name="`items[${idx}][unit_id]`" :value="item.unit_id">
                                            <x-form.select.combobox x-model.number="item.unit_id" :options="$units" :required="true" placeholder="Unidad..." />
                                        </td>

                                        <td class="px-2 py-2">
                                            <input :name="`items[${idx}][quantity]`" type="number" step="1" min="1" x-model.number="item.quantity" class="h-9 w-20 max-w-full rounded-md border border-gray-300 px-2 text-center text-sm font-semibold focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" required>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input :name="`items[${idx}][amount]`" type="number" step="0.01" min="0" x-model.number="item.amount" class="h-9 w-20 max-w-full rounded-md border border-gray-300 px-2 text-right text-sm font-semibold focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" required>
                                        </td>
                                        <td class="px-2 py-2">
                                            <input :name="`items[${idx}][comment]`" x-model="item.comment" class="h-9 w-full rounded-md border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" placeholder="Opcional">
                                        </td>
                                        <td class="px-2 py-2 text-right">
                                            <p class="text-sm font-bold text-gray-800" x-text="money((item.quantity || 0) * (item.amount || 0))"></p>
                                        </td>
                                        <td class="px-2 py-2 text-center">
                                            <button type="button" @click="removeItem(idx)" class="inline-flex h-8 w-8 items-center justify-center rounded text-gray-400 hover:text-red-500 hover:bg-red-50" title="Eliminar item">
                                                <i class="ri-delete-bin-line text-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-5 lg:p-6 shadow-sm">
                    <div class="flex flex-col lg:flex-row gap-8">

                        <div class="w-full lg:w-[65%] xl:w-[70%]">
                            <h3 class="mb-5 text-sm font-bold uppercase tracking-wide text-gray-700 border-b border-gray-100 pb-2">Datos de compra</h3>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Incluye IGV</label>
                                    <select name="includes_tax" x-model="includesTax" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] bg-white">
                                        <option value="N">No</option>
                                        <option value="S">Si</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">IGV %</label>
                                    <input type="number" step="0.01" min="0" max="100" name="tax_rate_percent" x-model.number="taxRate" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" required>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Tipo pago</label>
                                    <select name="payment_type" x-model="payment_type" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] bg-white">
                                        @foreach(['CONTADO','CREDITO'] as $option)
                                            <option value="{{ $option }}">{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Moneda</label>
                                    <select name="currency" x-model="currency" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] bg-white" required>
                                        <option value="PEN">PEN - Soles</option>
                                        <option value="USD">USD - Dólares</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Tipo de Cambio</label>
                                    <input type="number" step="0.001" min="0.001" name="exchange_rate" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" value="{{ old('exchange_rate', $purchaseMovement?->exchange_rate ?? 3.5) }}" required>
                                </div>
                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Afecta caja</label>
                                    <select name="affects_cash" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] bg-white">
                                        <option value="N" @selected(old('affects_cash', $purchaseMovement?->affects_cash ?? 'N') === 'N')>No</option>
                                        <option value="S" @selected(old('affects_cash', $purchaseMovement?->affects_cash ?? 'N') === 'S')>Si</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Afecta kardex</label>
                                    <select name="affects_kardex" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] bg-white">
                                        <option value="S" @selected(old('affects_kardex', $purchaseMovement?->affects_kardex ?? 'S') === 'S')>Si</option>
                                        <option value="N" @selected(old('affects_kardex', $purchaseMovement?->affects_kardex ?? 'S') === 'N')>No</option>
                                    </select>
                                </div>
                                <div class="lg:col-span-2">
                                    <label class="mb-1.5 block text-[11px] font-bold uppercase text-gray-500 tracking-wider">Notas de compra</label>
                                    <input type="text" name="comment" class="h-10 w-full rounded-lg border border-gray-300 px-3 text-sm focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3]" placeholder="Comentario opcional..." value="{{ old('comment', $purchase?->comment ?? '') }}">
                                </div>
                            </div>
                        </div>

                        <div class="w-full lg:w-[35%] xl:w-[30%] min-w-[280px]">
                            <div class="h-full rounded-xl border border-gray-200 bg-[#f8f9fa] p-5 flex flex-col">
                                <h3 class="mb-5 text-sm font-bold uppercase tracking-wide text-gray-700 border-b border-gray-200 pb-2">Resumen</h3>
                                
                                <div class="space-y-3 flex-grow">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 font-medium">Subtotal</span>
                                        <span class="font-bold text-gray-900" x-text="money(summary.subtotal)"></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 font-medium">IGV (<span x-text="taxRate"></span>%)</span>
                                        <span class="font-bold text-gray-900" x-text="money(summary.tax)"></span>
                                    </div>
                                </div>

                                <div class="mt-6 flex items-end justify-between border-t border-gray-300 pt-5">
                                    <span class="text-[13px] font-bold uppercase tracking-wider text-gray-700">Total</span>
                                    <span class="text-3xl font-black text-[#1a2a5d]" x-text="money(summary.total)"></span>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="flex flex-col lg:flex-row gap-6" x-show="payment_type === 'CONTADO'" x-cloak>
                    
                    <div class="w-full lg:w-[75%] xl:w-[80%]">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 lg:p-6 shadow-sm">
                            
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between mb-4 pb-2 border-b border-gray-100">
                                <div>
                                    <h3 class="text-sm font-semibold uppercase tracking-wide text-gray-700 flex items-center gap-1 mb-0">
                                        <i class="ri-wallet-3-line text-[#244BB3] text-lg"></i> Pagos
                                    </h3>
                                    <p class="text-xs text-gray-500">Añade métodos para cubrir el total.</p>
                                </div>

                                <div class="mt-2 sm:mt-0 flex flex-col items-end px-3 py-1.5 rounded-lg border border-gray-200 bg-white shadow-sm text-xs">
                                    <span class="block text-[10px] font-bold uppercase tracking-wider text-gray-500 mb-0.5">
                                        Faltante por Pagar
                                    </span>
                                    <span class="block text-2xl font-black transition-colors"
                                          :class="paymentDifference > 0 ? 'text-[#2ecc71]' : 'text-gray-400'">
                                        <span x-text="currency === 'USD' ? '$' : 'S/'"></span> 
                                        <span x-text="Number(paymentDifference).toFixed(2)"></span>
                                    </span>
                                </div>
                            </div>

                            <fieldset :disabled="payment_type !== 'CONTADO'" class="w-full border-0 p-0 m-0">
                                
                                <div class="hidden md:flex items-center px-4 pb-2 border-b border-gray-100 text-[11px] font-bold uppercase tracking-wider text-gray-500 mb-2">
                                    <div class="w-[35%] pl-2">Método</div>
                                    <div class="w-[20%]">Monto</div>
                                    <div class="w-[40%] pl-6"></div> 
                                    <div class="w-[5%]"></div>
                                </div>

                                <div class="space-y-1">
                                    <template x-for="(row, index) in paymentRows" :key="row.id">
                                        <div class="flex flex-col md:flex-row md:items-center px-4 py-3 bg-white border border-gray-100 md:border-transparent md:border-b md:border-b-gray-100 rounded-xl md:rounded-none hover:bg-gray-50 transition-colors group">
                                            
                                            <div class="w-full md:w-[35%] flex items-center relative">
                                                <span class="md:hidden text-[11px] font-bold uppercase text-gray-500 mb-1 w-full block absolute -top-5">Método</span>
                                                <i class="text-[18px] mr-2"
                                                    :class="{
                                                        'ri-money-dollar-circle-line text-emerald-500': row.methodId == '1',
                                                        'ri-bank-card-line text-blue-500': row.methodId == '2',
                                                        'ri-bank-line text-indigo-500': row.methodId == '3',
                                                        'ri-smartphone-line text-purple-500': row.methodId == '5',
                                                        'ri-wallet-3-line text-gray-400': row.methodId == ''
                                                    }"></i>
                                                <select x-model="row.methodId" @change="row.methodName = $event.target.options[$event.target.selectedIndex].text" :name="`payments[${index}][payment_method_id]`" required class="w-full bg-transparent border-0 focus:ring-0 text-[14px] font-medium text-gray-800 px-0 cursor-pointer appearance-none">
                                                    <option value="1">Efectivo</option>
                                                    <option value="2">Tarjeta de Crédito / Débito</option>
                                                    <option value="3">Transferencia Bancaria</option>
                                                    <option value="5">Billetera Digital</option>
                                                </select>
                                                <i class="ri-arrow-down-s-line text-gray-400 pointer-events-none -ml-4"></i>
                                                <input type="hidden" :name="`payments[${index}][payment_method]`" :value="row.methodName">
                                            </div>

                                            <div class="w-full md:w-[20%] flex items-center mt-6 md:mt-0 relative">
                                                <span class="md:hidden text-[11px] font-bold uppercase text-gray-500 mb-1 block absolute -top-5">Monto</span>
                                                <span class="text-gray-400 font-medium text-[15px] mr-1" x-text="currency === 'USD' ? '$' : 'S/.'"></span>
                                                <input type="number" step="0.01" min="0.00" x-model.number="row.amount" :name="`payments[${index}][amount]`" required class="w-full bg-transparent border-0 focus:ring-0 text-[15px] font-bold text-gray-800 px-1 p-0 placeholder-gray-300" placeholder="0.00">
                                            </div>

                                            <div class="hidden md:block w-px h-8 bg-gray-200 mx-4"></div>

                                            <div class="w-full md:w-[40%] flex items-center mt-6 md:mt-0 relative min-h-[36px]">
                                                
                                                <template x-if="['1', ''].includes(row.methodId)">
                                                    <div class="w-full"></div> 
                                                </template>

                                                <template x-if="row.methodId == '2'">
                                                    <div class="flex gap-4 w-full">
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">Tipo Tarjeta</label>
                                                            <select :name="`payments[${index}][card_id]`" required class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] appearance-none">
                                                                <option value="">Seleccionar v</option>
                                                                @foreach ($cards ?? [] as $card)
                                                                    <option value="{{ $card->id }}">{{ $card->description }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">N° Lote (Op.)</label>
                                                            <input type="text" :name="`payments[${index}][number]`" placeholder="Ej: 001234" class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] placeholder-gray-300">
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="row.methodId == '3'">
                                                    <div class="flex gap-4 w-full">
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">Banco Destino</label>
                                                            <select :name="`payments[${index}][bank_id]`" required class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] appearance-none">
                                                                <option value="">Seleccionar v</option>
                                                                @foreach ($banks ?? [] as $bank)
                                                                    <option value="{{ $bank->id }}">{{ $bank->description }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">N° Operación</label>
                                                            <input type="text" :name="`payments[${index}][number]`" required placeholder="Ej: 987654" class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] placeholder-gray-300">
                                                        </div>
                                                    </div>
                                                </template>

                                                <template x-if="row.methodId == '5'">
                                                    <div class="flex gap-4 w-full">
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">Aplicación</label>
                                                            <select :name="`payments[${index}][digital_wallet_id]`" required class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] appearance-none">
                                                                <option value="">Seleccionar v</option>
                                                                @foreach ($digitalWallets ?? [] as $dw)
                                                                    <option value="{{ $dw->id }}">{{ $dw->description }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="w-1/2 relative">
                                                            <label class="block text-[10px] text-gray-400 font-semibold mb-0.5 absolute -top-4 left-0 uppercase">N° Celular / Ref.</label>
                                                            <input type="text" :name="`payments[${index}][number]`" required placeholder="Ej: 999..." class="w-full border-0 border-b border-gray-200 bg-transparent px-0 py-1 text-sm font-medium text-gray-700 focus:ring-0 focus:border-[#244BB3] placeholder-gray-300">
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="w-full md:w-[5%] flex justify-end mt-4 md:mt-0">
                                                <template x-if="paymentRows.length > 1">
                                                    <button type="button" @click="removePaymentRow(index)" class="text-gray-300 hover:text-red-500 opacity-100 md:opacity-0 md:group-hover:opacity-100 transition-opacity p-1" title="Eliminar pago">
                                                        <i class="ri-delete-bin-line text-lg"></i>
                                                    </button>
                                                </template>
                                            </div>

                                        </div>
                                    </template>
                                </div>
                            </fieldset>

                            <div class="mt-4 pt-3 border-t border-gray-50">
                                <button type="button" @click="addPaymentRow()" :disabled="payment_type !== 'CONTADO'" class="inline-flex items-center gap-1 text-[12px] font-bold text-gray-600 hover:text-gray-900 transition-colors py-1.5 px-3 rounded border border-gray-300 hover:bg-gray-50 bg-white disabled:opacity-50 disabled:cursor-not-allowed">
                                    <i class="ri-add-circle-line text-base"></i>
                                    <span>Agregar otro método de pago</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="w-full lg:w-[25%] xl:w-[20%] min-w-[280px]">
                        <div class="rounded-xl border border-gray-200 bg-white p-5 h-full flex flex-col shadow-sm">
                            <h3 class="text-xs font-bold uppercase tracking-wide text-gray-700 mb-3 flex items-center gap-1.5">
                                <i class="ri-image-add-line text-lg text-[#244BB3]"></i> Adjuntar comprobante
                            </h3>
                            <div class="flex-grow border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:bg-gray-50 transition-colors relative cursor-pointer flex flex-col items-center justify-center">
                                <input type="file" name="payment_image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                                <i class="ri-upload-cloud-2-line text-3xl text-gray-400 mb-1 block"></i>
                                <span class="text-[13px] text-gray-600 font-medium block">Haz clic para subir imagen</span>
                                <span class="text-[11px] text-gray-400">JPG, PNG o PDF</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5 mt-5">
                <a href="{{ route('purchase.index', !empty($viewId) ? ['view_id' => $viewId] : []) }}" 
                   class="inline-flex items-center rounded-lg border border-gray-300 px-6 py-2.5 text-sm font-bold text-gray-700 hover:bg-gray-50 transition-colors shadow-sm"
                   :class="{'opacity-50 pointer-events-none': isSubmitting}">
                    <i class="ri-close-line mr-2 text-lg"></i>Cancelar
                </a>
                
                <button type="submit" 
                        class="inline-flex items-center rounded-lg bg-[#1a2a5d] px-7 py-2.5 text-sm font-bold text-white shadow-md transition-colors"
                        :class="isSubmitting ? 'opacity-75 cursor-wait pointer-events-none' : 'hover:bg-[#121d3f]'">
                    <i class="ri-save-line mr-2 text-lg" x-show="!isSubmitting"></i>
                    <i class="ri-loader-4-line mr-2 text-lg animate-spin" x-show="isSubmitting" style="display: none;"></i>
                    <span x-text="isSubmitting ? 'Guardando...' : 'Guardar compra'">Guardar compra</span>
                </button>
            </div>
        </form>
    </x-common.component-card>

    @once
        @push('scripts')
            <script>
                function purchaseForm({ products, units, initialProviderId, initialItems, taxRate, includesTax, initialCurrency, initialBranchId, initialPaymentType }) {
                    return {
                        isSubmitting: false, 
                        
                        products,
                        units,
                        selectedProviderId: initialProviderId || '',
                        items: (initialItems && initialItems.length) 
                            ? initialItems 
                            : [{ product_id: 0, unit_id: '', description: '', quantity: 1, amount: 0, comment: '', product_query: '', product_open: false, product_cursor: 0 }],
                        taxRate: Number(taxRate || 18),
                        includesTax: includesTax || 'N',
                        currency: initialCurrency || 'PEN',
                        selectedBranchId: Number(initialBranchId || 0),
                        payment_type: initialPaymentType || 'CONTADO',
                        
                        paymentRows: [{ id: Date.now(), methodId: '1', methodName: 'Efectivo', amount: '' }],
                        _previousTotal: 0,
                        
                        initForm() {
                            this.$watch('summary.total', (value) => {
                                if (this.paymentRows.length === 1 && (this.paymentRows[0].amount === '' || this.paymentRows[0].amount == this._previousTotal)) {
                                    this.paymentRows[0].amount = Number(value).toFixed(2);
                                }
                                this._previousTotal = value;
                            });
                        },

                        get summary() {
                            const lineTotal = this.items.reduce((sum, i) => sum + ((Number(i.quantity) || 0) * (Number(i.amount) || 0)), 0);
                            const r = (Number(this.taxRate) || 0) / 100;
                            if (this.includesTax === 'S') {
                                const subtotal = r > 0 ? (lineTotal / (1 + r)) : lineTotal;
                                const tax = lineTotal - subtotal;
                                return { subtotal, tax, total: lineTotal };
                            }
                            const subtotal = lineTotal;
                            const tax = subtotal * r;
                            return { subtotal, tax, total: subtotal + tax };
                        },

                        addPaymentRow() {
                            const faltante = this.paymentDifference;
                            this.paymentRows.push({ 
                                id: Date.now(), methodId: '1', methodName: 'Efectivo', amount: faltante > 0 ? faltante.toFixed(2) : '' 
                            });
                        },

                        removePaymentRow(index) {
                            if (this.paymentRows.length > 1) this.paymentRows.splice(index, 1);
                        },

                        get totalPaid() {
                            return this.paymentRows.reduce((sum, row) => sum + (Number(row.amount) || 0), 0);
                        },

                        get paymentDifference() {
                            const diff = this.summary.total - this.totalPaid;
                            return diff > 0 ? diff : 0;
                        },

                        addItem() {
                            this.items.push({ product_id: 0, unit_id: '', description: '', quantity: 1, amount: 0, comment: '', product_query: '', product_open: false, product_cursor: 0 });
                        },
                        removeItem(idx) {
                            this.items.splice(idx, 1);
                            if (!this.items.length) this.addItem();
                        },
                        filteredProducts(item) {
                            const term = String(item.product_query || '').toLowerCase().trim();
                            const list = term === '' ? this.products : this.products.filter(p => String(p.code || '').toLowerCase().includes(term) || String(p.name || '').toLowerCase().includes(term));
                            if (item.product_cursor >= list.length) item.product_cursor = 0;
                            return list.slice(0, 40);
                        },
                        selectProduct(idx, product) {
                            this.items[idx].product_id = Number(product.id);
                            this.items[idx].product_query = `${product.code || 'SIN'} - ${product.name}`;
                            this.items[idx].description = product.name || '';
                            this.items[idx].product_open = false;
                            this.setProductMeta(idx);
                        },
                        clearProduct(idx) {
                            const c = this.items[idx];
                            c.product_id = 0; c.product_query = ''; c.description = ''; c.unit_id = ''; c.amount = 0; c.product_open = true; c.product_cursor = 0;
                        },
                        moveProductCursor(idx, step) {
                            const current = this.items[idx];
                            const list = this.filteredProducts(current);
                            if (!list.length) return;
                            const max = list.length - 1;
                            const next = current.product_cursor + step;
                            if (next < 0) current.product_cursor = max;
                            else if (next > max) current.product_cursor = 0;
                            else current.product_cursor = next;
                        },
                        selectProductByCursor(idx) {
                            const current = this.items[idx];
                            const list = this.filteredProducts(current);
                            if (!list.length) return;
                            this.selectProduct(idx, list[current.product_cursor] || list[0]);
                        },
                        setProductMeta(idx) {
                            const product = this.products.find(p => Number(p.id) === Number(this.items[idx].product_id));
                            if (!product) return;
                            if (!this.items[idx].product_query) this.items[idx].product_query = `${product.code || 'SIN'} - ${product.name}`;
                            this.items[idx].description = product.name || '';
                            if (!this.items[idx].unit_id && product.unit_id) this.items[idx].unit_id = Number(product.unit_id);
                            if (!this.items[idx].amount && product.cost) this.items[idx].amount = Number(product.cost);
                        },
                        money(v) {
                            return `${this.currency === 'USD' ? '$' : 'S/'} ${Number(v || 0).toFixed(2)}`;
                        }
                    }
                }
            </script>
        @endpush
    @endonce
@endsection