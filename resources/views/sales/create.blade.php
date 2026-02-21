@extends('layouts.app')

@section('title', 'Punto de Venta - Ventas')

@section('content')
    @php
        $viewId = request('view_id');
        $salesIndexUrl = route('sales.index', $viewId ? ['view_id' => $viewId] : []);
        $salesChargeUrl = route('sales.charge', $viewId ? ['view_id' => $viewId] : []);
    @endphp

    <div class="flex flex-wrap items-center justify-between gap-2 sm:gap-3 mb-4 sm:mb-6">
        <div class="flex items-center gap-2 min-w-0">
            <span class="text-gray-500 dark:text-gray-400 shrink-0"><i class="ri-restaurant-fill"></i></span>
            <div class="flex items-center gap-1.5 sm:gap-2 min-w-0">
                <h2 class="text-base sm:text-xl font-bold text-slate-800 dark:text-white truncate">
                    Punto de Venta
                </h2>
                <span class="text-[10px] font-bold px-1.5 sm:px-2 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded uppercase tracking-wider border border-gray-200 dark:border-gray-600 shrink-0">Ventas</span>
            </div>
        </div>
        <nav class="min-w-0">
            <ol class="flex flex-wrap items-center gap-1 sm:gap-1.5 text-xs sm:text-sm">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ url('/') }}">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="min-w-0">
                    <a class="inline-flex items-center gap-1 sm:gap-1.5 text-gray-500 dark:text-gray-400 truncate max-w-[120px] sm:max-w-none" href="{{ $salesIndexUrl }}">
                        <span class="truncate">Ventas</span>
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="text-gray-800 dark:text-white/90 truncate max-w-[140px] sm:max-w-none">
                    Nueva Venta
                </li>
            </ol>
        </nav>
    </div>

    <div class="rounded-2xl border border-gray-200 dark:border-gray-300 overflow-hidden bg-blue-50 dark:bg-gray-900 fade-in max-w-full">
        <div class="flex flex-col lg:flex-row items-stretch w-full max-w-full bg-white dark:bg-gray-800/50 min-h-[calc(100vh-180px)] sm:min-h-[calc(100vh-12rem)] pb-6 sm:pb-10 min-w-0">
            <main class="w-full lg:flex-1 flex flex-col min-w-0 min-h-0 bg-white dark:bg-gray-900/50 lg:min-h-0">
                <header class="min-h-14 sm:h-20 py-4 px-3 sm:py-0 sm:px-6 flex items-center justify-between gap-2 sm:gap-4 dark:bg-gray-800/50 border-b border-gray-200 shadow-sm z-10 bg-gray-300 flex-wrap sm:flex-nowrap">
                    <div class="flex items-center gap-2 sm:gap-4 min-w-0">
                        <a href="{{ $salesIndexUrl }}" id="back-to-sales-link" class="h-9 sm:h-10 px-2 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-600 transition-colors flex items-center justify-center shadow-sm shrink-0">
                            <i class="ri-arrow-left-line text-lg sm:text-xl"></i>
                            Volver a ventas
                        </a>
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
                            <div id="category-filters" class="flex flex-row flex-wrap gap-1.5 sm:gap-2 overflow-x-auto pb-3"></div>
                        </div>
                        <div class="flex-1 min-h-0 min-w-0 pt-2 sm:pt-3">
                            <h3 class="font-bold text-sm sm:text-base text-slate-800 dark:text-white mb-2 sm:mb-3 px-2 sm:px-4 shrink-0">Productos</h3>
                            <div id="products-grid" class="px-2 sm:px-4 md:px-5 pb-2 grid grid-cols-3 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-5 xl:grid-cols-5 2xl:grid-cols-5 gap-2 sm:gap-4 overflow-y-auto min-h-0 flex-1 content-start"></div>
                        </div>
                    </div>
                </div>
            </main>

            <aside class="flex flex-col shadow-2xl overflow-hidden w-[350px] sm:w-[320px] md:w-[350px] shrink-0 bg-blue-50 dark:bg-gray-900 border-l border-gray-200 dark:border-gray-700 min-h-0" style="min-height: 550px;">
                <div class="h-14 sm:h-16 px-3 sm:px-6 flex items-center justify-between shadow-sm dark:bg-gray-800" style="background: #3B82F6;">
                    <h3 class="sm:text-lg font-bold text-white dark:text-white">Orden Actual</h3>
                    <span id="cart-count-badge" class="inline-block px-2 py-0.5 bg-white/20 text-white rounded-full text-[10px] font-bold">0</span>
                </div>
                <div id="cart-container" class="flex-1 overflow-y-auto p-3 sm:p-5 space-y-2 sm:space-y-3 bg-gray-100 dark:bg-gray-900/50 min-h-0"></div>

                <div class="shrink-0 p-4 sm:p-6 pt-0 bg-white dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                    <div class="space-y-2 sm:space-y-3 mb-4 sm:mb-5 text-xs sm:text-sm">
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span>Subtotal</span>
                            <span class="text-slate-700 dark:text-slate-200" id="ticket-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-500 font-medium">
                            <span>Impuestos</span>
                            <span class="text-slate-700 dark:text-slate-200" id="ticket-tax">$0.00</span>
                        </div>
                        <div class="border-t border-dashed border-gray-300 dark:border-gray-600 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-base sm:text-lg font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                            <span class="text-xl sm:text-3xl font-black text-blue-600 dark:text-blue-400" id="ticket-total">$0.00</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 gap-1.5 sm:gap-2">
                        <button type="button" id="checkout-button" onclick="goToChargeView()"
                            class="py-1.5 px-2 rounded-xl bg-blue-600 text-white font-bold text-xs sm:text-sm shadow-lg hover:bg-blue-700 active:scale-95 transition-all flex justify-center items-center gap-1 sm:gap-2">
                            <span>Cobrar</span> <i class="ri-check-line text-sm sm:text-base"></i>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <div id="notification" class="fixed top-24 right-8 z-50 max-w-sm opacity-0 pointer-events-none transition-opacity duration-300" aria-live="polite"></div>

    {{-- Notificación de stock insuficiente --}}
    <div id="stock-error-notification" class="fixed top-24 right-8 z-50 transform transition-all duration-500 translate-x-[150%] opacity-0 pointer-events-none">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-red-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center"><i class="fas fa-exclamation-triangle text-2xl"></i></div>
            <div class="flex-1">
                <p class="font-bold text-sm">Stock insuficiente</p>
                <p id="stock-error-message" class="text-xs text-red-50 mt-0.5">Solo hay X disponible(s)</p>
            </div>
            <button onclick="hideStockError()" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
    </div>

    {{-- Notificación de producto agregado --}}
    <div id="add-to-cart-notification" class="fixed top-24 right-8 z-50 transform transition-all duration-500 translate-x-[150%] opacity-0">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-green-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center animate-bounce"><i class="fas fa-check text-2xl"></i></div>
            <div class="flex-1">
                <p class="font-bold text-sm">¡Producto agregado!</p>
                <p id="notification-product-name" class="text-xs text-green-50 mt-0.5">Producto</p>
            </div>
            <button onclick="hideNotification()" class="text-white/80 hover:text-white transition-colors"><i class="fas fa-times"></i></button>
        </div>
    </div>

    <style>
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .notification-show { transform: translateX(0) !important; opacity: 1 !important; }
    </style>

    <script>
    (function () {
        const productsRaw = @json($products ?? []);
        const productBranchesRaw = @json($productBranches ?? $productsBranches ?? []);

        const products = Array.isArray(productsRaw) ? productsRaw : Object.values(productsRaw || {});
        const productBranches = Array.isArray(productBranchesRaw) ? productBranchesRaw : Object.values(productBranchesRaw || {});

        const priceByProductId = new Map();
        const taxRateByProductId = new Map();
        const stockByProductId = new Map();
        const defaultTaxPct = 18;
        productBranches.forEach((pb) => {
            const pid = Number(pb.product_id ?? pb.id);
            if (!Number.isNaN(pid)) {
                priceByProductId.set(pid, Number(pb.price ?? 0));
                taxRateByProductId.set(pid, pb.tax_rate != null ? Number(pb.tax_rate) : defaultTaxPct);
                stockByProductId.set(pid, Number(pb.stock ?? 0) || 0);
            }
        });
        let selectedCategory = 'General';
        let searchQuery = '';

        function getProductCategory(prod) {
            const value = (prod && prod.category) ? String(prod.category).trim() : '';
            return value !== '' ? value : 'Sin categoria';
        }

        function getCategories() {
            const unique = new Set();
            products.forEach((prod) => unique.add(getProductCategory(prod)));
            return ['General', ...Array.from(unique).sort((a, b) => a.localeCompare(b))];
        }

        const ACTIVE_SALE_KEY_STORAGE = 'restaurantActiveSaleKey';
        let db = JSON.parse(localStorage.getItem('restaurantDB') || '{}');
        let activeKey = localStorage.getItem(ACTIVE_SALE_KEY_STORAGE);

        if (!activeKey || !db[activeKey] || db[activeKey]?.status === 'completed') {
            activeKey = `sale-${Date.now()}`;
            localStorage.setItem(ACTIVE_SALE_KEY_STORAGE, activeKey);
        }

        let currentSale = db[activeKey] || {
            id: Date.now(),
            clientName: 'Publico General',
            status: 'in_progress',
            items: [],
        };

        db[activeKey] = currentSale;
        localStorage.setItem('restaurantDB', JSON.stringify(db));

        // Al volver a ventas: limpiar la venta actual para que Create quede en blanco la próxima vez
        document.getElementById('back-to-sales-link')?.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem(ACTIVE_SALE_KEY_STORAGE);
            if (activeKey) {
                try {
                    const dbClean = JSON.parse(localStorage.getItem('restaurantDB') || '{}');
                    delete dbClean[activeKey];
                    localStorage.setItem('restaurantDB', JSON.stringify(dbClean));
                } catch (_) {}
            }
            window.location.href = @json($salesIndexUrl);
        });

        function getImageUrl(imgUrl) {
            if (imgUrl && String(imgUrl).trim() !== '') return imgUrl;
            return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIj48cmVjdCBmaWxsPSIjZTdlOWViIiB3aWR0aD0iNDAwIiBoZWlnaHQ9IjQwMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5Y2EzYWYiPlNpbiBpbWFnZW48L3RleHQ+PC9zdmc+';
        }

        function saveDB() {
            db[activeKey] = currentSale;
            localStorage.setItem('restaurantDB', JSON.stringify(db));
        }

        function renderProducts() {
            const grid = document.getElementById('products-grid');
            if (!grid) return;
            grid.innerHTML = '';

            let rendered = 0;

            products.forEach((prod) => {
                const productId = Number(prod.id);
                const price = priceByProductId.get(productId);
                const category = getProductCategory(prod);

                if (typeof price === 'undefined') return;
                if (selectedCategory !== 'General' && category !== selectedCategory) return;
                if (searchQuery) {
                    const q = searchQuery.toLowerCase();
                    const name = (prod.name || '').toLowerCase();
                    if (!name.includes(q)) return;
                }

                const el = document.createElement('div');
                el.className = 'group cursor-pointer transition-transform duration-200 hover:scale-105';
                el.addEventListener('click', function () {
                    addToCart(prod, price);
                });

                const safeName = prod.name || 'Sin nombre';
                const safeCategory = category;

                el.innerHTML = `
                    <div class="rounded-lg overflow-hidden p-3 dark:bg-slate-800/40 shadow-md hover:shadow-xl border border-gray-300 dark:border-slate-700/50 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-blue-500/10 transition-all duration-200 hover:-translate-y-1 backdrop-blur-sm">
                        <div class="relative aspect-square overflow-hidden dark:bg-slate-700/30 rounded-lg border border-gray-300 dark:border-slate-600/30 shadow-sm">
                            <img src="${getImageUrl(prod.img)}" alt="${safeName}" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110" loading="lazy" onerror="this.onerror=null; this.src='${getImageUrl(null)}'">
                            <span class="absolute top-3 right-3 z-10">
                                <span class="px-2.5 py-1 bg-blue-600 dark:bg-blue-500 rounded-lg text-sm font-bold shadow-lg shadow-blue-500/40 dark:shadow-blue-500/20 backdrop-blur-sm border border-blue-400/50 dark:border-blue-400/30 text-white">
                                    $${Number(price).toFixed(2)}
                                </span>
                            </span>
                        </div>
                        <div class="mt-3 flex flex-col gap-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">${safeName}</h4>
                            <h6 class="text-xs text-gray-600 dark:text-gray-400">${safeCategory}</h6>
                        </div>
                    </div>
                `;

                grid.appendChild(el);
                rendered++;
            });

            if (rendered === 0) {
                grid.innerHTML = '<div class="col-span-full text-center text-gray-500 py-8">No hay productos disponibles para esta sucursal.</div>';
            }
        }

        function renderCategoryFilters() {
            const container = document.getElementById('category-filters');
            if (!container) return;

            container.innerHTML = '';
            const categories = getCategories();

            categories.forEach((category) => {
                const button = document.createElement('button');
                button.type = 'button';
                const isActive = selectedCategory === category;
                button.className = 'inline-flex items-center gap-2 px-2.5 py-1.5 rounded-full text-xs sm:text-sm font-semibold border transition-all duration-150 whitespace-nowrap cursor-pointer shrink-0 ' +
                    (isActive ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-200 border-gray-300 dark:border-slate-600 hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400');

                button.textContent = category;
                button.addEventListener('click', () => {
                    selectedCategory = category;
                    renderCategoryFilters();
                    renderProducts();
                });

                container.appendChild(button);
            });
        }

        function addToCart(prod, price) {
            const productId = Number(prod.id);
            if (Number.isNaN(productId)) return;

            if (!Array.isArray(currentSale.items)) currentSale.items = [];

            const stock = stockByProductId.get(productId) ?? 0;
            const existing = currentSale.items.find((i) => Number(i.pId) === productId);
            const qtyToAdd = existing ? existing.qty + 1 : 1;

            if (qtyToAdd > stock) {
                showStockError(prod.name || 'Producto', stock);
                return;
            }

            if (existing) {
                existing.qty += 1;
            } else {
                currentSale.items.push({
                    pId: productId,
                    name: prod.name || '',
                    qty: 1,
                    price: Number(price) || 0,
                    note: '',
                });
            }

            saveDB();
            renderTicket();
            showNotification(prod.name || 'Producto');
        }

        function updateQty(index, delta) {
            if (!currentSale.items[index]) return;
            currentSale.items[index].qty += delta;
            if (currentSale.items[index].qty <= 0) currentSale.items.splice(index, 1);
            saveDB();
            renderTicket();
        }

        function toggleNoteInput(index) {
            const box = document.getElementById('note-box-' + index);
            if (box) box.classList.toggle('hidden');
        }

        function saveNote(index, value) {
            if (!currentSale.items[index]) return;
            currentSale.items[index].note = value;
            saveDB();
        }

        function renderTicket() {
            const container = document.getElementById('cart-container');
            if (!container) return;

            container.innerHTML = '';
            let subtotal = 0;

            if (!currentSale.items || currentSale.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center text-gray-300 dark:text-gray-600 opacity-60 py-12">
                        <i class="fas fa-utensils text-3xl mb-2"></i>
                        <p class="font-medium text-sm">Sin productos</p>
                    </div>`;
            } else {
                currentSale.items.forEach((item, index) => {
                    const prod = products.find((p) => Number(p.id) === Number(item.pId));
                    if (!prod) return;

                    const itemPrice = Number(item.price) || 0;
                    const itemQty = Number(item.qty) || 0;
                    const itemTotal = itemPrice * itemQty;
                    subtotal += itemTotal;

                    const hasNote = !!(item.note && String(item.note).trim() !== '');
                    const noteEscaped = (item.note || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

                    const row = document.createElement('div');
                    row.className = 'bg-white border border-gray-200 rounded-lg p-2.5 shadow-sm relative overflow-hidden group mb-2 dark:bg-slate-800/60 dark:border-slate-700';
                    row.innerHTML = `
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                        <div class="flex gap-3 pl-2">
                            <img src="${getImageUrl(prod.img)}" class="h-10 w-10 rounded-md object-cover bg-gray-100 dark:bg-slate-700" alt="${(prod.name || '').replace(/"/g, '&quot;')}">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-slate-700 dark:text-slate-200 text-xs truncate pr-1">${(prod.name || 'Sin nombre').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</span>
                                    <span class="font-bold text-slate-800 dark:text-white text-xs">$${itemTotal.toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <button type="button" onclick="toggleNoteInput(${index})" class="text-[10px] flex items-center gap-1 transition-colors ${hasNote ? 'text-blue-600 bg-blue-50 dark:bg-blue-900/30 px-1.5 rounded' : 'text-gray-400 hover:text-blue-500 dark:hover:text-blue-400'}">
                                        <i class="fas fa-comment-alt"></i> Nota
                                    </button>
                                    <div class="flex items-center gap-2 bg-gray-50 dark:bg-slate-700 rounded border border-gray-100 dark:border-slate-600">
                                        <button type="button" onclick="updateQty(${index}, -1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-red-500 dark:hover:text-red-400"><i class="ri-subtract-line"></i></button>
                                        <span class="text-xs font-bold text-slate-700 dark:text-slate-200 w-4 text-center">${itemQty}</span>
                                        <button type="button" onclick="updateQty(${index}, 1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-blue-600 dark:hover:text-blue-400"><i class="ri-add-line"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-2 animate-fadeIn pl-2">
                            <input type="text" value="${noteEscaped}" oninput="saveNote(${index}, this.value)" placeholder="Nota..." class="w-full text-[10px] bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded p-1.5 text-slate-700 dark:text-slate-200 focus:outline-none focus:border-yellow-400">
                        </div>
                    `;
                    container.appendChild(row);
                });
            }

            let subtotalBase = 0;
            let tax = 0;
            (currentSale.items || []).forEach((item) => {
                const itemTotal = (Number(item.price) || 0) * (Number(item.qty) || 0);
                const taxPct = taxRateByProductId.get(Number(item.pId)) ?? defaultTaxPct;
                const taxVal = taxPct / 100;
                const itemSubtotal = taxVal > 0 ? itemTotal / (1 + taxVal) : itemTotal;
                subtotalBase += itemSubtotal;
                tax += itemTotal - itemSubtotal;
            });
            const total = subtotalBase + tax;

            document.getElementById('ticket-subtotal').innerText = '$' + subtotalBase.toFixed(2);
            document.getElementById('ticket-tax').innerText = '$' + tax.toFixed(2);
            document.getElementById('ticket-total').innerText = '$' + total.toFixed(2);

            const cartCountBadge = document.getElementById('cart-count-badge');
            const totalItems = (currentSale.items || []).reduce((sum, item) => sum + (Number(item.qty) || 0), 0);
            if (cartCountBadge) cartCountBadge.textContent = String(totalItems);
        }

        function showEmptyCartNotification() {
            const notification = document.getElementById('add-to-cart-notification');
            const productNameEl = document.getElementById('notification-product-name');
            if (!notification || !productNameEl) return;
            productNameEl.textContent = 'Agrega al menos un producto para cobrar.';
            notification.classList.add('notification-show');
            setTimeout(() => notification.classList.remove('notification-show'), 2500);
        }

        function goToChargeView() {
            if (!currentSale.items || currentSale.items.length === 0) {
                showEmptyCartNotification();
                return;
            }
            saveDB();
            sessionStorage.setItem('sales_charge_from_create', '1');
            window.location.href = @json($salesChargeUrl);
        }

        function goBack() {
            if (!currentSale.items || currentSale.items.length === 0) {
                currentSale.items = [];
                currentSale.total = 0;
                saveDB();
                localStorage.removeItem(ACTIVE_SALE_KEY_STORAGE);
                window.location.href = @json($salesIndexUrl);
                return;
            }

            const saleData = {
                items: currentSale.items.map(item => ({
                    pId: item.pId,
                    qty: Number(item.qty),
                    price: Number(item.price),
                    note: item.note || ''
                })),
                notes: 'Venta guardada como borrador - pendiente de pago'
            };

            fetch(@json(route('sales.draft')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(saleData)
            })
            .finally(() => {
                window.location.href = @json($salesIndexUrl);
            });
        }

        function showStockError(productName, stock) {
            const notification = document.getElementById('stock-error-notification');
            const msgEl = document.getElementById('stock-error-message');
            if (!notification || !msgEl) return;
            msgEl.textContent = (productName || 'Producto') + ': solo hay ' + stock + ' disponible(s).';
            notification.classList.add('notification-show');
            notification.classList.remove('pointer-events-none');
            setTimeout(hideStockError, 3500);
        }

        function hideStockError() {
            const notification = document.getElementById('stock-error-notification');
            if (notification) {
                notification.classList.remove('notification-show');
                notification.classList.add('pointer-events-none');
            }
        }

        function showNotification(productName) {
            const notification = document.getElementById('add-to-cart-notification');
            const productNameEl = document.getElementById('notification-product-name');
            if (!notification || !productNameEl) return;
            productNameEl.textContent = productName;
            notification.classList.add('notification-show');
            setTimeout(hideNotification, 1600);
        }

        function hideNotification() {
            const notification = document.getElementById('add-to-cart-notification');
            if (!notification) return;
            notification.classList.remove('notification-show');
        }

        function init() {
            renderCategoryFilters();
            renderProducts();
            renderTicket();
            const searchEl = document.getElementById('search-products');
            if (searchEl) {
                searchEl.addEventListener('input', function(e) {
                    searchQuery = (e.target.value || '').trim();
                    renderProducts();
                });
            }
        }

        init();

        window.goBack = goBack;
        window.getImageUrl = getImageUrl;
        window.updateQty = updateQty;
        window.toggleNoteInput = toggleNoteInput;
        window.saveNote = saveNote;
        window.goToChargeView = goToChargeView;
        window.hideNotification = hideNotification;
    })();
    </script>
@endsection
