@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3 mb-4 sm:mb-6">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-gray-500 dark:text-gray-400 shrink-0"><i class="ri-restaurant-fill"></i></span>
            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                <h2 class="text-base sm:text-xl font-bold text-slate-800 dark:text-white truncate">
                    Mesa <span id="pos-table-name">{{ $table->name ?? $table->id }}</span>
                </h2>
                <span id="pos-table-area"
                    class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded uppercase tracking-wider border border-gray-200 dark:border-gray-600 shrink-0">--</span>
            </div>
        </div>
        <nav class="min-w-0">
            <ol class="flex flex-wrap items-center gap-1 sm:gap-1.5 text-xs sm:text-sm">
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
        <div class="flex flex-col lg:flex-row items-stretch w-full max-w-full bg-white dark:bg-gray-800/50 min-h-[calc(100vh-180px)] sm:min-h-[calc(100vh-12rem)] pb-6 sm:pb-10 min-w-0">
        <main class="w-full lg:flex-1 flex flex-col min-w-0 min-h-0 bg-white dark:bg-gray-900/50 lg:min-h-0">
            <header class="min-h-14 sm:h-20 py-4 px-3 sm:py-0 sm:px-6 flex items-center justify-between gap-2 sm:gap-4 dark:bg-gray-800/50 border-b border-gray-200 shadow-sm z-10 bg-gray-300 flex-wrap sm:flex-nowrap">
                <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                    <button onclick="goBack()" 
                        title="Volver atrás"
                        class="h-9 sm:h-10 px-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-600 transition-colors flex items-center justify-center shadow-sm shrink-0">
                        <i class="ri-arrow-left-line text-lg sm:text-xl"></i>
                        Volver a mesa
                    </button>

                    <div class="flex items-center gap-2 sm:gap-4 md:gap-6 text-xs sm:text-sm text-gray-500 font-medium min-w-0">
                        <div class="flex items-center gap-1 sm:gap-2 group min-w-0">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">Mozo:</span>
                            <div class="relative flex items-center min-w-0">
                                <select onchange="changeWaiter(this.value)"
                                    class="min-w-0 w-16 sm:w-24 md:min-w-[100px] md:max-w-[140px] py-1 px-2 sm:px-3 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1rem] sm:bg-[length:1.25rem] bg-[right_0.2rem_center] sm:bg-[right_0.25rem_center] bg-no-repeat truncate">
                                    <option value="{{ $user?->id }}" selected>{{ $user?->name ?? 'Sin asignar' }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="h-3 sm:h-4 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                        <div class="flex items-center gap-1 sm:gap-2 group min-w-0">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">Cliente:</span>
                            <div class="relative flex items-center min-w-0">
                                <select onchange="changeClient(this.value)"
                                    class="min-w-0 w-20 sm:w-28 md:min-w-[110px] md:max-w-[180px] py-1 px-2 sm:px-3 bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold text-xs sm:text-sm cursor-pointer focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm appearance-none bg-[url('data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%226b7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22M6%208l4%204%204-4%22%2F%3E%3C%2Fsvg%3E')] bg-[length:1rem] sm:bg-[length:1.25rem] bg-[right_0.2rem_center] sm:bg-[right_0.25rem_center] bg-no-repeat truncate">
                                    <option value="{{ $person?->id }}" selected>{{ $person?->name ?? 'Público General' }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="h-3 sm:h-4 w-px bg-gray-300 dark:bg-slate-600 shrink-0"></div>

                        <div class="flex items-center gap-1 sm:gap-2 group min-w-0">
                            <span class="text-gray-500 dark:text-gray-400 shrink-0">Personas:</span>
                            <div class="relative flex items-center">
                                <input type="number" 
                                    value="{{ $diners ?? 1 }}" 
                                    min="1" 
                                    onchange="updateDiners(this.value)"
                                    class="w-8 sm:w-10 py-1 px-1 text-center text-xs sm:text-sm bg-white dark:bg-slate-700/80 border border-gray-200 dark:border-slate-600 rounded-lg text-slate-700 dark:text-slate-200 font-semibold focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-500/40 focus:border-blue-400 outline-none shadow-sm [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none">
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="p-3 sm:p-5 md:p-6 w-full bg-white flex flex-col flex-1 min-h-0">                
                <div class="flex flex-col flex-1 min-h-0 min-w-0">
                    <div class="shrink-0 border-gray-300 px-2 sm:px-4 pt-3 pb-4">
                        <div class="flex items-center justify-between">
                        <h3 class="font-bold text-sm sm:text-base text-slate-800 dark:text-white mb-2 shrink-0">Categoría</h3>
                        <div class="flex items-center justify-end mb-3 sm:mb-4 shrink-0">
                            <div class="w-40 sm:w-56 md:w-64 relative">
                                <input type="text" id="search-products" placeholder="Buscar..."
                                    class="w-full pl-8 pr-3 py-2 text-sm bg-white border border-gray-200 dark:border-slate-600 rounded-lg focus:border-blue-500 focus:ring-1 focus:ring-blue-200 transition-all">
                                <i class="fas fa-search absolute left-2.5 sm:left-3 top-2.5 text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        </div>
                        <div id="categories-grid" class="flex flex-row flex-wrap gap-1.5 sm:gap-2 overflow-x-auto pb-3">
                        </div>
                    </div>
                    <div class="flex-1 min-h-0 min-w-0 pt-2 sm:pt-3">
                        <h3 class="font-bold text-sm sm:text-base text-slate-800 dark:text-white mb-2 sm:mb-3 px-2 sm:px-4 shrink-0">Productos</h3>
                        <div id="products-grid" class="px-2 sm:px-4 md:px-5 pb-2 grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-2 sm:gap-4 overflow-y-auto min-h-0 flex-1 content-start">
                        </div>
                    </div>
                </div>
            </div>
        </main>

        <aside
            class="flex flex-col shadow-2xl overflow-hidden w-[350px] sm:w-[320px] md:w-[350px] shrink-0 bg-blue-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 min-h-0" style="min-height: 550px;">
            <div class="h-14 sm:h-16 px-3 sm:px-6 flex items-center justify-between shadow-sm dark:bg-gray-800" style="background: #3B82F6;">
                <h3 class=" sm:text-lg font-bold text-white dark:text-white">Orden Actual</h3>
            </div>
            <div id="cart-container" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2 sm:space-y-3 bg-gray-100 dark:bg-gray-900/50 min-h-0"></div>

            <div class="shrink-0 p-4 sm:p-6 pt-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-5 text-xs sm:text-sm">
                    <div class="flex justify-between text-gray-500 font-medium">
                        <span>Subtotal</span>
                        <span class="text-slate-700" id="ticket-subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-500 font-medium">
                        <span>Impuestos</span>
                        <span class="text-slate-700" id="ticket-tax">$0.00</span>
                    </div>
                    <div class="border-t border-dashed border-gray-300 dark:border-gray-600 my-2"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-base sm:text-lg font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                        <span class="text-xl sm:text-3xl font-black text-blue-600" id="ticket-total">$0.00</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-1.5 sm:gap-2">
                    <button onclick="processOrder()"
                        class="py-1.5 px-2 rounded-xl bg-gray-500 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-gray-600 active:scale-95 transition-all flex justify-center items-center gap-1 sm:gap-2">
                        <span>Guardar</span> <i class="ri-save-line text-sm sm:text-base"></i>
                    </button>
                    <button onclick="processOrderPayment()"
                        class="py-1.5 px-2 rounded-xl bg-blue-600 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-blue-700 active:scale-95 transition-all flex justify-center items-center gap-1 sm:gap-2">
                        <span>Cobrar</span> <i class="ri-check-line text-sm sm:text-base"></i>
                    </button>
                </div>
            </div>
        </aside>
        </div>
    </div>

    <div id="notification" class="fixed top-24 right-8 z-50 max-w-sm opacity-0 pointer-events-none transition-opacity duration-300" aria-live="polite"></div>

    <script>
        (function() {
            @php
                $serverTableData = [
                    'id' => $table->id,
                    'table_id' => $table->id,
                    'area_id' => $table->area_id ?? ($area->id ?? null),
                    'name' => $table->name ?? $table->id,
                    'waiter' => $user?->name ?? 'Sin asignar',
                    'clientName' => $person?->name ?? 'Sin cliente',
                    'status' => $table->situation ?? 'libre',
                    'items' => [],
                ];
            @endphp
            const serverTable = @json($serverTableData);
            const startFresh = @json($startFresh ?? false);
            // IDs del pedido pendiente que viene directo del servidor (fuente de verdad)
            const serverOrderMovementId = @json($pendingOrderMovementId ?? null);
            const serverMovementId = @json($pendingMovementId ?? null);

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
            // Siempre sincronizar order_movement_id y movement_id con el servidor para evitar duplicados
            if (serverOrderMovementId) {
                currentTable.order_movement_id = serverOrderMovementId;
                currentTable.movement_id = serverMovementId;
            } else {
                // No hay pedido pendiente en servidor: asegurar que no usamos un ID viejo del localStorage
                currentTable.order_movement_id = null;
                currentTable.movement_id = null;
            }

            function init() {
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
                refreshCartPricesFromServer();
                renderCategories();
                renderProducts();
                renderTicket();
                if (currentTable.items && currentTable.items.length > 0) {
                    setTimeout(scheduleAutoSave, 800);
                }
            }

            // Función para escapar HTML y prevenir XSS
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Función para obtener la URL de la imagen
            function getImageUrl(imagePath) {
                if (!imagePath || imagePath === 'null' || imagePath === null || imagePath === '') {
                    // Retornar una imagen placeholder o una imagen por defecto
                    return 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="200" height="200"%3E%3Crect fill="%23e5e7eb" width="200" height="200"/%3E%3Ctext fill="%239ca3af" font-family="sans-serif" font-size="14" dy="10.5" font-weight="bold" x="50%25" y="50%25" text-anchor="middle"%3ESin imagen%3C/text%3E%3C/svg%3E';
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

            // Datos de productos, categorías y productBranches desde el servidor
            const serverProducts = @json($products ?? []);
            const serverProductBranches = @json($productBranches ?? []);
            const serverCategories = @json(collect($categories ?? [])->map(fn($c) => ['id' => $c->id, 'name' => $c->description ?? '', 'img' => $c->image ? asset('storage/' . $c->image) : null])->values()->all());

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
                    const lineTotal = qty * price;
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

            let selectedCategoryId = null;

            function renderCategories() {
                const grid = document.getElementById('categories-grid');
                if (!grid) return; 
                
                grid.innerHTML = '';

                if (!serverCategories || serverCategories.length === 0) {
                    grid.innerHTML = '<div class="text-center text-gray-500 py-2 text-sm w-full">No hay categorías</div>';
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
                            onerror="this.onerror=null; this.src=getImageUrl(null)">
                        <span>${categoryName}</span>
                    `;
                    grid.appendChild(el);
                });
            }
            
            function renderProducts() {
                const grid = document.getElementById('products-grid');
                grid.innerHTML = '';

                if (!serverProducts || serverProducts.length === 0) {
                    grid.innerHTML =
                        '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles</div>';
                    return;
                }

                // Filtrar por categoría seleccionada (si hay una)
                const productsToShow = selectedCategoryId == null
                    ? serverProducts
                    : serverProducts.filter(p => p.category_id == selectedCategoryId);

                let productsRendered = 0;
                productsToShow.forEach(prod => {
                    const productBranch = serverProductBranches.find(p => p.product_id === prod.id || p.id === prod.id);
                    if (!productBranch) return;

                    const el = document.createElement('div');
                    el.className = "group cursor-pointer transition-transform duration-200 hover:scale-105";

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
                    const productCategory = escapeHtml(prod.category || 'Sin categoría');
                    const imageUrl = getImageUrl(prod.img);

                    el.innerHTML = `
                        <div class="rounded-lg overflow-hidden p-3  dark:bg-slate-800/40 shadow-md hover:shadow-xl border border-gray-300 dark:border-slate-700/50 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-blue-500/10 transition-all duration-200 hover:-translate-y-1 backdrop-blur-sm">
                            <div class="relative aspect-square overflow-hidden  dark:bg-slate-700/30 rounded-lg border border-gray-300 dark:border-slate-600/30 shadow-sm">
                                <img src="${imageUrl}" 
                                    alt="${productName}" 
                                    class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                                    loading="lazy"
                                    onerror="this.onerror=null; this.src=getImageUrl(null)">
                                
                                <span class="absolute top-3 right-3 z-10">
                                    <span class="px-2.5 py-1 bg-blue-600 dark:bg-blue-500 rounded-lg text-sm font-bold shadow-lg shadow-blue-500/40 dark:shadow-blue-500/20 backdrop-blur-sm border border-blue-400/50 dark:border-blue-400/30 text-white">
                                        $${parseFloat(productBranch.price).toFixed(2)}
                                    </span>
                                </span>
                            </div>
                            
                            <div class="mt-3 flex flex-col gap-1">
                                <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                                    ${productName}
                                </h4>
                                <h6 class="text-xs text-gray-600 dark:text-gray-400">
                                    ${productCategory}
                                </h6>
                            </div>
                        </div>
                    `;
                    grid.appendChild(el);
                    productsRendered++;
                });

                if (productsRendered === 0) {
                    grid.innerHTML = selectedCategoryId != null
                        ? '<div class="col-span-full text-center text-gray-500 py-8">No hay productos en esta categoría</div>'
                        : '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles para esta sucursal</div>';
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
                        pId: productId, // Guardar como número entero
                        name: prod.name || 'Sin nombre',
                        qty: 1,
                        price: price,
                        tax_rate: parseFloat(productBranch.tax_rate ?? 10),
                        note: ""
                    });
                }
                saveDB();
                renderTicket();
            }

            function updateQty(index, change) {
                currentTable.items[index].qty += change;
                if (currentTable.items[index].qty <= 0) currentTable.items.splice(index, 1);
                saveDB();
                renderTicket();
            }

            function toggleNoteInput(index) {
                document.getElementById(`note-box-${index}`).classList.toggle('hidden');
            }

            function saveNote(index, val) {
                currentTable.items[index].note = val;
                saveDB();
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
                    currentTable.items.forEach((item, index) => {
                        const prod = serverProducts.find(p => p.id === item.pId);
                        if (!prod) {
                            return;
                        }

                        const itemPrice = parseFloat(item.price) || 0;
                        const itemQty = parseInt(item.qty) || 0;
                        subtotal += itemPrice * itemQty;
                        const hasNote = item.note && item.note.trim() !== "";

                        const row = document.createElement('div');
                        row.className =
                            "bg-white border border-gray-200 rounded-lg p-2.5 shadow-sm relative overflow-hidden group mb-2";

                        const productName = escapeHtml(prod.name || 'Sin nombre');
                        const productImage = getImageUrl(prod.img || null);
                        const itemNote = escapeHtml(item.note || '');

                        row.innerHTML = `
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                        <div class="flex gap-3 pl-2">
                            <img src="${productImage}" class="h-10 w-10 rounded-md object-cover bg-gray-100" alt="${productName}">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-slate-700 text-xs truncate pr-1">${productName}</span>
                                    <span class="font-bold text-slate-800 text-xs">$${(item.price * item.qty).toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <button onclick="toggleNoteInput(${index})" class="text-[10px] flex items-center gap-1 transition-colors ${hasNote ? 'text-blue-600 bg-blue-50 px-1.5 rounded' : 'text-gray-400 hover:text-blue-500'}">
                                        <i class="fas fa-comment-alt"></i> ${hasNote ? 'Nota' : 'Nota'}
                                    </button>
                                    <div class="flex items-center gap-2 bg-gray-50 rounded border border-gray-100">
                                        <button onclick="updateQty(${index}, -1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-red-500"><i class="ri-subtract-line"></i></button>
                                        <span class="text-xs font-bold text-slate-700 w-4 text-center">${item.qty}</span>
                                        <button onclick="updateQty(${index}, 1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-blue-600"><i class="ri-add-line"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-2 animate-fadeIn pl-2">
                            <input type="text" value="${itemNote}" oninput="saveNote(${index}, this.value)" placeholder="Nota..." class="w-full text-[10px] bg-yellow-50 border border-yellow-200 rounded p-1.5 text-slate-700 focus:outline-none focus:border-yellow-400">
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

            }

            function saveDB() {
                if (db && currentTable) {
                    // Agregar timestamp para saber cuándo se guardó
                    currentTable.lastUpdated = new Date().toISOString();
                    currentTable.isActive = true;
                    db[activeKey] = currentTable;
                    localStorage.setItem('restaurantDB', JSON.stringify(db));
                    if (currentTable.items && currentTable.items.length > 0) {
                        scheduleAutoSave();
                    }
                }
            }

            function goToIndexWithTurbo() {
                const url = "{{ route('orders.index') }}?_=" + Date.now();
                if (window.Turbo && typeof window.Turbo.visit === 'function') {
                    window.Turbo.visit(url, { action: 'replace' });
                } else {
                    window.location.href = url;
                }
            }

            function isMesaYaCobradaMessage(msg) {
                if (!msg || typeof msg !== 'string') return false;
                const m = msg.toLowerCase();
                return m.indexOf('ya fue cobrada') !== -1 || m.indexOf('ya fue cobrado') !== -1;
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
                if (items.length === 0) return;
                const totals = calculateTotalsFromItems(items);
                const order = {
                    items: items,
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
                    order_movement_id: currentTable.order_movement_id ?? null,
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
                        if (data.order_movement_id) currentTable.order_movement_id = data.order_movement_id;
                        if (data.movement_id) currentTable.movement_id = data.movement_id;
                        saveDB();
                    } else if (data && isMesaYaCobradaMessage(data.message)) {
                        if (typeof showNotification === 'function') {
                            showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                        }
                    }
                })
                .catch(() => {});
            }

            function processOrder() {
                const items = currentTable.items || [];
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
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    order_movement_id: currentTable.order_movement_id ?? null,
                };
                fetch('{{ route('orders.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                    'content') ||
                                '',
                            'Accept': 'application/json'
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
                    .then(data => {
                        if (data && data.success) {
                            sessionStorage.setItem('flash_success_message', data.message);
                            goToIndexWithTurbo();
                        } else if (data && isMesaYaCobradaMessage(data.message)) {
                            if (typeof showNotification === 'function') {
                                showNotification('Aviso', data.message || 'Esta mesa ya fue cobrada.', 'info');
                            } else {
                                alert(data.message || 'Esta mesa ya fue cobrada.');
                            }
                        } else {
                            console.error('Error al guardar:', data);
                            sessionStorage.setItem('flash_error_message', data?.message || 'Error al guardar.');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        sessionStorage.setItem('flash_error_message', 'Error al guardar el pedido. Revisa la consola.');
                    });

            }

            function processOrderPayment() {
                const items = currentTable.items || [];
                if (items.length === 0) {
                    if (typeof showNotification === 'function') {
                        showNotification('Error', 'Agrega productos a la orden antes de cobrar.', 'error');
                    } else {
                        sessionStorage.setItem('flash_error_message', 'Agrega productos a la orden antes de cobrar.');
                    }
                    return;
                }
                // Cancelar auto-guardado pendiente para evitar que dispare después de navegar y cree duplicados
                if (autoSaveTimer) { clearTimeout(autoSaveTimer); autoSaveTimer = null; }

                const totals = calculateTotalsFromItems(items);
                const subtotal = totals.subtotal;
                const tax = totals.tax;
                const total = totals.total;
                const payload = {
                    items: items,
                    table_id: currentTable.table_id ?? currentTable.id,
                    area_id: currentTable.area_id ?? null,
                    subtotal: subtotal,
                    tax: tax,
                    total: total,
                    people_count: currentTable.people_count ?? 0,
                    contact_phone: currentTable.contact_phone ?? null,
                    delivery_address: currentTable.delivery_address ?? null,
                    delivery_time: currentTable.delivery_time ?? null,
                    delivery_amount: currentTable.delivery_amount ?? 0,
                    order_movement_id: currentTable.order_movement_id ?? null,
                };
                // Guardar el pedido (solo persiste, NO finaliza) y navegar a cobrar
                fetch('{{ route('orders.process') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                                'content') || '',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    })

                    .then(async (response) => {
                        const ct = response.headers.get('content-type');
                        if (ct && ct.includes('application/json')) {
                            return response.json();
                        }
                        throw new Error(response.status === 419 ? 'Sesión expirada. Recarga la página.' : (response.status === 401 ? 'Debes iniciar sesión.' : (response.status === 500 ? 'Error al procesar el cobro de pedido. Intenta de nuevo.' : 'Error del servidor. Intenta de nuevo.' + response.statusText)));
                    })
                    .then(data => {
                        if (data && data.success && data.movement_id) {
                            const url = new URL("{{ route('orders.charge') }}", window.location.origin);
                            url.searchParams.set('movement_id', data.movement_id);
                            url.searchParams.set('_t', Date.now());
                            if (window.Turbo && typeof window.Turbo.visit === 'function') {
                                window.Turbo.visit(url.toString(), { action: 'advance' });
                            } else {
                                window.location.href = url.toString();
                            }
                        } else {
                            if (typeof showNotification === 'function') {
                                showNotification('Error', data?.message || 'No se pudo procesar. Intenta de nuevo.',
                                    'error');
                            } else {
                                alert(data?.message || 'No se pudo procesar. Intenta de nuevo.');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        if (typeof showNotification === 'function') {
                            showNotification('Error', 'Error al procesar el cobro de pedido.', 'error');
                        } else {
                            alert('Error al procesar el cobro de pedido.');
                        }
                    });
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
                // Si no hay productos en la mesa, liberar la mesa y volver sin guardar nada
                if (!items.length) {
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

            // Exponer funciones usadas desde onclick en el HTML (mismo ámbito tras re-render)
            window.toggleNoteInput = toggleNoteInput;
            window.updateQty = updateQty;
            window.saveNote = saveNote;
            window.getImageUrl = getImageUrl;
            window.goBack = goBack;
            window.processOrder = processOrder;
            window.processOrderPayment = processOrderPayment;
        })();
    </script>
@endsection
