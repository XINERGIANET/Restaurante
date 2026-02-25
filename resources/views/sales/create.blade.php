@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    @php
        $viewId = request('view_id');
        $salesIndexUrl = route('sales.index', $viewId ? ['view_id' => $viewId] : []);
        $salesChargeUrl = route('sales.charge', $viewId ? ['view_id' => $viewId] : []);
    @endphp

    {{-- Layout Principal: Altura fija para evitar scroll en el body --}}
    <div class="flex flex-col h-[calc(100vh-80px)] bg-[#F3F4F6] dark:bg-[#0B1120] font-sans overflow-hidden">
        
        {{-- 1. HEADER --}}
            <header class="flex items-center justify-between px-4 sm:px-6 py-3 bg-white dark:bg-[#151C2C] border-b border-gray-200 dark:border-gray-800 shadow-sm z-30 shrink-0 h-16 gap-4 relative">
                        
                {{-- 1. IZQUIERDA: Navegación y Título --}}
                <div class="flex items-center gap-3 sm:gap-5 shrink-0">
                    <a href="#" id="back-to-sales-link" 
                    class="flex items-center justify-center w-9 h-9 rounded-full bg-gray-50 border border-gray-200 hover:bg-gray-100 text-gray-600 transition-all dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700 dark:text-gray-300 shadow-sm">
                        <i class="ri-arrow-left-s-line text-xl"></i>
                    </a>
                    <div class="hidden sm:block h-6 w-px bg-gray-300 dark:bg-gray-700"></div>
                    <div>
                        <h1 class="text-lg font-bold text-gray-900 dark:text-white leading-none tracking-tight">Nueva Venta</h1>
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <span class="relative flex h-2 w-2">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-2 w-2 bg-green-500"></span>
                            </span>
                            <span class="text-[11px] font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider" id="cash-register-display">Caja Principal</span>
                        </div>
                    </div>
                </div>

                {{-- 2. CENTRO: Buscador --}}
                <div class="hidden md:block w-full max-w-md relative group">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none z-10">
                        <i class="ri-search-line text-gray-400 group-focus-within:text-indigo-500 transition-colors"></i>
                    </div>
                    <input type="text" id="search-products" placeholder="Buscar producto..." 
                        class="block w-full pl-10 pr-4 py-2 bg-gray-50/50 dark:bg-gray-900/50 border border-gray-200 dark:border-gray-700 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 focus:bg-white dark:focus:bg-gray-900 transition-all shadow-sm">
                </div>

                {{-- 3. DERECHA: Controles EN FILA (Side-by-Side) --}}
                <div class="flex items-center ml-auto">
                    {{-- Contenedor Cápsula --}}
                    <div class="flex items-center bg-gray-50 dark:bg-gray-900/50 rounded-full border border-gray-200 dark:border-gray-700 shadow-sm px-2 h-10">
                        
                        {{-- Grupo MOZO --}}
                        <div class="flex items-center gap-2 px-3 hover:bg-white dark:hover:bg-gray-800 rounded-full transition-colors h-8 cursor-pointer group relative">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Mozo:</span>
                            <select class="bg-transparent text-xs sm:text-sm font-bold text-gray-800 dark:text-gray-200 outline-none cursor-pointer appearance-none pr-1">
                                <option value="1" selected>ADMIN</option>
                            </select>
                            <i class="ri-arrow-down-s-line text-xs text-gray-400"></i>
                        </div>

                        {{-- Separador Vertical --}}
                        <div class="h-5 w-px bg-gray-300 dark:bg-gray-700 mx-1"></div>

                        {{-- Grupo CLIENTE --}}
                        <div class="flex items-center gap-2 px-3 hover:bg-white dark:hover:bg-gray-800 rounded-full transition-colors h-8 cursor-pointer group relative">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Cliente:</span>
                            <select class="bg-transparent text-xs sm:text-sm font-bold text-gray-800 dark:text-gray-200 outline-none cursor-pointer appearance-none pr-1 max-w-[100px] truncate">
                                <option value="1" selected>Público General</option>
                            </select>
                            <i class="ri-arrow-down-s-line text-xs text-gray-400"></i>
                        </div>

                        {{-- Separador Vertical --}}
                        <div class="h-5 w-px bg-gray-300 dark:bg-gray-700 mx-1"></div>

                        {{-- Grupo PERSONAS --}}
                        <div class="flex items-center gap-2 px-3 hover:bg-white dark:hover:bg-gray-800 rounded-full transition-colors h-8 group">
                            <span class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Pers:</span>
                            <input type="number" value="1" min="1" 
                                class="w-8 text-center text-xs sm:text-sm font-bold bg-transparent border-none p-0 text-gray-800 dark:text-gray-200 focus:ring-0 appearance-none m-0">
                        </div>

                    </div>
                </div>
            </header>

        {{-- 2. CUERPO PRINCIPAL (Split View) --}}
        <div class="flex flex-1 overflow-hidden relative">
            
            {{-- COLUMNA IZQUIERDA: Catálogo --}}
            <main class="flex-1 flex flex-col min-w-0 relative bg-[#F3F4F6] dark:bg-[#0B1120]">
                
                {{-- Filtros Sticky --}}
                <div class="sticky top-0 z-20 px-6 py-4 bg-[#F3F4F6]/95 dark:bg-[#0B1120]/95 backdrop-blur-sm border-b border-gray-200/50 dark:border-gray-800">
                    <div id="category-filters" class="flex gap-2 overflow-x-auto no-scrollbar pb-1">
                        {{-- JS Injected --}}
                    </div>
                </div>

                {{-- Grid Scrollable --}}
                <div class="flex-1 overflow-y-auto px-6 pb-24 pt-2 custom-scrollbar">
                    <div id="products-grid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 2xl:grid-cols-6 gap-4 content-start">
                        {{-- JS Injected --}}
                    </div>
                </div>
                
                {{-- Fade inferior decorativo --}}
                <div class="absolute bottom-0 left-0 right-0 h-12 bg-gradient-to-t from-[#F3F4F6] dark:from-[#0B1120] to-transparent pointer-events-none z-10"></div>
            </main>

            {{-- COLUMNA DERECHA: Carrito (Fixed Layout) --}}
            <aside class="w-[420px] shrink-0 flex flex-col bg-white dark:bg-[#151C2C] border-l border-gray-200 dark:border-gray-800 shadow-2xl z-30 relative h-full">
                
                {{-- Header Carrito (Fijo) --}}
                <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex justify-between items-center bg-white dark:bg-[#151C2C] shrink-0 z-10">
                    <div class="flex items-center gap-2">
                        <h2 class="font-bold text-gray-800 dark:text-white">Orden Actual</h2>
                        <span id="cart-count-badge" class="bg-indigo-600 text-white text-[10px] font-bold px-2 py-0.5 rounded-full">0</span>
                    </div>
                    <button onclick="clearCart()" class="p-2 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all" title="Limpiar carrito">
                        <i class="ri-delete-bin-5-line text-lg"></i>
                    </button>
                </div>

                {{-- Lista de Items (SCROLLABLE - Ocupa el espacio restante) --}}
                <div id="cart-container" class="flex-1 overflow-y-auto p-4 space-y-3 bg-white dark:bg-[#151C2C] custom-scrollbar relative">
                    {{-- Empty State --}}
                    <div class="h-full flex flex-col items-center justify-center text-center p-6">
                        <div class="w-32 h-32 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                            <img src="https://cdn-icons-png.flaticon.com/512/2038/2038854.png" alt="Empty" class="w-16 h-16 opacity-20 grayscale">
                        </div>
                        <h3 class="text-gray-900 dark:text-white font-bold mb-1">Carrito vacío</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 max-w-[200px]">Selecciona productos para comenzar.</p>
                    </div>
                </div>

                {{-- Footer: Totales y Botón (FIJO AL FONDO) --}}
                <div class="shrink-0 p-5 bg-gray-50 dark:bg-[#0f1522] border-t border-gray-200 dark:border-gray-800 shadow-[0_-4px_20px_rgba(0,0,0,0.05)] z-20">
                    
                    {{-- Desglose --}}
                    <div class="space-y-2 mb-4">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Subtotal</span>
                            <span id="ticket-subtotal" class="font-medium text-gray-900 dark:text-white tabular-nums">$0.00</span>
                        </div>
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500 dark:text-gray-400">Impuestos</span>
                            <span id="ticket-tax" class="font-medium text-gray-900 dark:text-white tabular-nums">$0.00</span>
                        </div>
                    </div>

                    <div class="flex justify-between items-end mb-5 pt-3 border-t border-gray-200 dark:border-gray-700">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase font-bold tracking-wider">Total a Pagar</span>
                        </div>
                        <span id="ticket-total" class="text-3xl font-black text-indigo-600 dark:text-indigo-400 tabular-nums leading-none tracking-tight">$0.00</span>
                    </div>

                    <button type="button" id="checkout-button" onclick="goToChargeView()"
                        class="group w-full flex items-center justify-between px-6 h-14 rounded-xl transition-all active:scale-[0.99] shadow-xl
                            !bg-green-600 hover:!bg-green-700 !text-white !border-none
                            dark:!bg-emerald-600 dark:hover:!bg-emerald-500">
                        
                        <span class="text-lg font-bold">Cobrar</span>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-normal opacity-90 group-hover:opacity-100">Procesar</span>
                            <i class="ri-arrow-right-line"></i>
                        </div>
                    </button>
                </div>
            </aside>
        </div>
    </div>

    {{-- NOTIFICACIONES TOAST --}}
    <div id="toast-container" class="fixed top-20 left-1/2 -translate-x-1/2 z-50 pointer-events-none flex flex-col gap-2 w-auto max-w-sm">
        {{-- Stock Error --}}
        <div id="stock-error-notification" class="transform transition-all duration-300 -translate-y-10 opacity-0 pointer-events-none bg-white dark:bg-gray-800 border-l-4 border-red-500 shadow-2xl rounded-r-lg p-4 flex items-center gap-3 min-w-[300px]">
            <div class="text-red-500"><i class="ri-error-warning-fill text-xl"></i></div>
            <div>
                <p class="text-xs font-bold text-gray-900 dark:text-white uppercase">Stock Insuficiente</p>
                <p id="stock-error-message" class="text-sm text-gray-600 dark:text-gray-300">Mensaje de error</p>
            </div>
        </div>

        {{-- Success Add --}}
        <div id="add-to-cart-notification" class="transform transition-all duration-300 -translate-y-10 opacity-0 pointer-events-none bg-slate-800 text-white shadow-2xl rounded-full px-6 py-3 flex items-center gap-3 min-w-[200px]">
            <i class="ri-check-line text-green-400 text-xl"></i>
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400">Agregado</p>
                <p id="notification-product-name" class="text-sm font-bold text-white truncate max-w-[180px]">Producto</p>
            </div>
        </div>
    </div>

    {{-- ESTILOS CSS --}}
    <style>
        /* Ocultar scrollbar estándar */
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Scrollbar personalizada */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #E5E7EB; border-radius: 20px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #D1D5DB; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb { background: #374151; }
        .dark .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #4B5563; }
        
        .notification-show { transform: translateY(0) !important; opacity: 1 !important; }
        .tabular-nums { font-variant-numeric: tabular-nums; }
    </style>

    {{-- LÓGICA JS --}}
    <script>
        (function () {
            // --- 1. CARGA DE DATOS (DATA LOGIC) ---
            // Usamos el operador '??' para evitar errores si la variable viene vacía desde PHP
            const productsRaw = @json($products ?? []);
            const productBranchesRaw = @json($productBranches ?? $productsBranches ?? []);
            const cashRegisters = @json($cashRegisters ?? []);
            
            // AQUÍ ESTABA EL PROBLEMA: Necesitamos pasar las categorías con imagen desde PHP
            const categoriesDB = @json($categories ?? []); 

            const products = Array.isArray(productsRaw) ? productsRaw : Object.values(productsRaw || {});
            const productBranches = Array.isArray(productBranchesRaw) ? productBranchesRaw : Object.values(productBranchesRaw || {});

            const priceByProductId = new Map();
            const taxRateByProductId = new Map();
            const stockByProductId = new Map();
            const defaultTaxPct = 18;
            
            // Mapeo de precios y stock
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

            // --- FUNCIONES AUXILIARES ---
            
            function getProductCategory(prod) {
                const value = (prod && prod.category) ? String(prod.category).trim() : '';
                return value !== '' ? value : 'General';
            }

            // ESTA ES LA FUNCIÓN CORREGIDA QUE CRUZA DATOS
            function getCategories() {
                // 1. Obtener nombres únicos de los productos actuales
                const unique = new Set();
                products.forEach((prod) => unique.add(getProductCategory(prod)));
                
                // 2. Ordenar alfabéticamente (excluyendo General)
                const sortedNames = Array.from(unique)
                    .filter(c => c !== 'General')
                    .sort((a, b) => a.localeCompare(b));

                // 3. Crear objetos {name, img} buscando la imagen en categoriesDB
                const processedCategories = sortedNames.map(catName => {
                    // Buscamos si existe esta categoría en la lista que vino de la BD
                    // Intentamos coincidir por nombre exacto
                    const found = categoriesDB.find(c => c.name === catName);
                    
                    return {
                        name: catName,
                        // Si encontramos la categoría en BD usamos su img, si no, null
                        img: found ? found.img : null 
                    };
                });

                // 4. Retornar lista final con General al principio
                return [
                    { name: 'General', img: null }, 
                    ...processedCategories
                ];
            }

            function getImageUrl(imgUrl) {
                if (imgUrl && String(imgUrl).trim() !== '') return imgUrl;
                // Imagen por defecto (gris) si no hay nada
                return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIyMDAiIGhlaWdodD0iMjAwIj48cmVjdCB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y5ZmFmYiIvPjwvc3ZnPg==';
            }

            // --- STORAGE LOGIC (Carrito persistente) ---
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
                window.location.href = @json($salesIndexUrl ?? '#');
            });

            // --- SESSION CASH REGISTER ---
            fetch('/api/session/cash-register', { method: 'GET', headers: {'X-Requested-With': 'XMLHttpRequest'} })
                .then(res => res.json())
                .then(data => {
                    if(data.success && data.cash_register_id) {
                        const cr = cashRegisters.find(c => c.id == data.cash_register_id);
                        if(cr) {
                            const display = document.getElementById('cash-register-display');
                            if(display) display.innerText = cr.number;
                        }
                    }
                }).catch(()=>{});

            function saveDB() {
                db[activeKey] = currentSale;
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }

            // --- 2. RENDER PRODUCTS ---
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
                    // Filtro por categoría (comparamos Strings)
                    if (selectedCategory !== 'General' && category !== selectedCategory) return;
                    
                    // Filtro por búsqueda
                    if (searchQuery) {
                        const q = searchQuery.toLowerCase();
                        const name = (prod.name || '').toLowerCase();
                        const code = (prod.code || '').toLowerCase(); // Agregado búsqueda por código
                        if (!name.includes(q) && !code.includes(q)) return;
                    }

                    const el = document.createElement('div');
                    el.className = 'group relative flex flex-col bg-white dark:bg-[#1e293b] rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 hover:shadow-lg hover:border-indigo-200 dark:hover:border-indigo-900 transition-all duration-300 cursor-pointer overflow-hidden h-[230px] hover:-translate-y-1';
                    el.addEventListener('click', function () {
                        addToCart(prod, price);
                    });

                    const safeName = prod.name || 'Sin nombre';
                    const safePrice = Number(price).toFixed(2);

                    el.innerHTML = `
                        <div class="h-32 w-full bg-gray-50 dark:bg-gray-800 relative overflow-hidden flex items-center justify-center p-2">
                            <img src="${getImageUrl(prod.img)}" alt="${safeName}" 
                                class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-110 drop-shadow-sm" 
                                loading="lazy" onerror="this.onerror=null; this.src='${getImageUrl(null)}'">
                            
                            <div class="absolute top-2 right-2 bg-white dark:bg-gray-800 shadow-sm border border-gray-100 dark:border-gray-700 px-2 py-1 rounded-md">
                                <span class="text-xs font-extrabold text-gray-900 dark:text-white tabular-nums">S/.${safePrice}</span>
                            </div>
                        </div>
                        
                        <div class="p-3 flex-1 flex flex-col justify-between bg-white dark:bg-[#1e293b]">
                            <div>
                                <p class="text-[10px] text-indigo-500 dark:text-indigo-400 font-bold uppercase tracking-wider truncate mb-1">${category}</p>
                                <h4 class="font-bold text-gray-800 dark:text-gray-100 text-sm leading-snug line-clamp-2">${safeName}</h4>
                            </div>
                            
                            <div class="mt-2 w-full opacity-0 group-hover:opacity-100 transition-opacity duration-200 transform translate-y-2 group-hover:translate-y-0">
                                <span class="block w-full py-1.5 bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-bold text-center rounded-lg">
                                    Agregar +
                                </span>
                            </div>
                        </div>
                    `;

                    grid.appendChild(el);
                    rendered++;
                });

                if (rendered === 0) {
                    grid.innerHTML = `
                        <div class="col-span-full flex flex-col items-center justify-center py-20 text-gray-400">
                            <div class="w-16 h-16 bg-gray-100 dark:bg-gray-800 rounded-full flex items-center justify-center mb-3">
                                <i class="ri-search-line text-2xl opacity-50"></i>
                            </div>
                            <p class="text-sm font-medium">No se encontraron productos</p>
                        </div>
                    `;
                }
            }

            // --- 3. RENDER CATEGORIES (CON IMAGEN) ---
            function renderCategoryFilters() {
                const container = document.getElementById('category-filters');
                if (!container) return;

                container.innerHTML = '';
                
                // Obtenemos lista de objetos {name, img}
                const categories = getCategories();

                categories.forEach((categoryObj) => {
                    const button = document.createElement('button');
                    button.type = 'button';
                    
                    // Comparamos el NOMBRE de la categoría
                    const isActive = selectedCategory === categoryObj.name;
                    
                    // Definir imagen final (con fallback para "General")
                    let imgSrc = categoryObj.img;
                    if (!imgSrc && categoryObj.name === 'General') {
                        // Icono para opción "Todos/General"
                        imgSrc = 'https://cdn-icons-png.flaticon.com/512/556/556690.png'; 
                    }

                    // Estilos del botón (Imagen arriba, texto abajo)
                    button.className = `
                        shrink-0 min-w-[85px] h-[80px] px-2 py-1 rounded-2xl transition-all duration-200 border select-none
                        flex flex-col items-center justify-center gap-1.5 group relative overflow-hidden
                        ${isActive 
                            ? 'bg-gray-900 dark:bg-white border-gray-900 dark:border-white shadow-lg scale-105 z-10' 
                            : 'bg-white dark:bg-[#1e293b] border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800 hover:shadow-md'}
                    `;

                    button.innerHTML = `
                        <div class="w-8 h-8 rounded-full flex items-center justify-center overflow-hidden bg-gray-50 dark:bg-gray-800 p-0.5 transition-transform duration-300 ${isActive ? 'scale-110' : 'group-hover:scale-110'}">
                            <img src="${getImageUrl(imgSrc)}" 
                                alt="${categoryObj.name}" 
                                class="w-full h-full object-contain ${isActive ? '' : 'opacity-80 grayscale group-hover:grayscale-0'} transition-all"
                                onerror="this.src='https://cdn-icons-png.flaticon.com/512/3523/3523063.png'">
                        </div>
                        
                        <span class="text-[10px] font-extrabold uppercase tracking-wide leading-none truncate max-w-full text-center px-1
                            ${isActive ? 'text-white dark:text-gray-900' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-700 dark:group-hover:text-gray-200'}">
                            ${categoryObj.name}
                        </span>
                    `;

                    button.addEventListener('click', () => {
                        selectedCategory = categoryObj.name;
                        renderCategoryFilters();
                        renderProducts();
                    });

                    container.appendChild(button);
                });
            }

            // --- 4. RENDER TICKET ---
            function renderTicket() {
                const container = document.getElementById('cart-container');
                if (!container) return;

                container.innerHTML = '';
                let subtotal = 0;

                if (!currentSale.items || currentSale.items.length === 0) {
                    container.innerHTML = `
                        <div class="h-full flex flex-col items-center justify-center text-gray-400 dark:text-gray-600">
                            <div class="w-20 h-20 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center mb-4">
                                <i class="ri-shopping-bag-3-line text-3xl opacity-50"></i>
                            </div>
                            <p class="text-sm font-bold text-gray-500 dark:text-gray-400">Orden Vacía</p>
                            <p class="text-xs mt-1">Selecciona productos del menú</p>
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
                        const noteEscaped = (item.note || '').replace(/"/g, '&quot;');

                        const row = document.createElement('div');
                        row.className = 'group relative p-3 bg-white dark:bg-[#1e293b] rounded-xl border border-transparent hover:border-gray-200 dark:hover:border-gray-700 hover:shadow-sm transition-all mb-2';
                        
                        row.innerHTML = `
                            <div class="flex gap-3">
                                <div class="w-12 h-12 rounded-lg bg-gray-50 dark:bg-gray-800 overflow-hidden shrink-0 border border-gray-100 dark:border-gray-700">
                                    <img src="${getImageUrl(prod.img)}" class="w-full h-full object-cover mix-blend-multiply dark:mix-blend-normal" alt="img">
                                </div>
                                
                                <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                                    <div class="flex justify-between items-start gap-2">
                                        <h4 class="font-bold text-gray-800 dark:text-gray-200 text-xs leading-tight line-clamp-1">${(prod.name || 'Item').replace(/</g, '&lt;')}</h4>
                                        <span class="font-bold text-gray-900 dark:text-white text-xs tabular-nums">$${itemTotal.toFixed(2)}</span>
                                    </div>
                                    
                                    <div class="flex justify-between items-end mt-1">
                                        <p class="text-[10px] text-gray-400 tabular-nums">$${itemPrice.toFixed(2)} un.</p>
                                        
                                        <div class="flex items-center bg-gray-100 dark:bg-gray-800 rounded-md p-0.5">
                                            <button onclick="updateQty(${index}, -1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-red-500 hover:bg-white dark:hover:bg-gray-700 rounded transition-colors"><i class="ri-subtract-line text-xs"></i></button>
                                            <span class="w-6 text-center text-xs font-bold text-gray-800 dark:text-gray-200 tabular-nums">${itemQty}</span>
                                            <button onclick="updateQty(${index}, 1)" class="w-6 h-6 flex items-center justify-center text-gray-500 hover:text-green-600 hover:bg-white dark:hover:bg-gray-700 rounded transition-colors"><i class="ri-add-line text-xs"></i></button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center mt-2 px-1">
                                <button onclick="toggleNoteInput(${index})" class="text-[10px] font-medium transition-colors flex items-center gap-1 ${hasNote ? 'text-indigo-600 bg-indigo-50 px-1.5 py-0.5 rounded' : 'text-gray-400 hover:text-indigo-500'}">
                                    <i class="ri-chat-1-line"></i> ${hasNote ? 'Editar Nota' : 'Nota'}
                                </button>
                            </div>

                            <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-2">
                                <input type="text" value="${noteEscaped}" oninput="saveNote(${index}, this.value)" 
                                    placeholder="Escribe una instrucción..." 
                                    class="w-full text-xs bg-gray-50 dark:bg-gray-900 border-0 rounded-md py-1.5 px-2 text-gray-700 dark:text-gray-300 focus:ring-1 focus:ring-indigo-500/50">
                            </div>
                        `;
                        container.appendChild(row);
                    });
                }

                // Totales
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
                if (cartCountBadge) cartCountBadge.textContent = totalItems;
            }

            // --- HANDLERS (Expuestos globalmente) ---
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

            function clearCart() {
                if(currentSale.items.length > 0 && confirm('¿Vaciar toda la orden?')) {
                    currentSale.items = [];
                    saveDB();
                    renderTicket();
                }
            }

            function toggleNoteInput(index) {
                const box = document.getElementById('note-box-' + index);
                if (box) {
                    box.classList.toggle('hidden');
                    if(!box.classList.contains('hidden')) {
                        const input = box.querySelector('input');
                        if(input) setTimeout(() => input.focus(), 50);
                    }
                }
            }

            function saveNote(index, value) {
                if (!currentSale.items[index]) return;
                currentSale.items[index].note = value;
                saveDB();
            }

            function goToChargeView() {
                if (!currentSale.items || currentSale.items.length === 0) {
                    const btn = document.getElementById('checkout-button');
                    btn.classList.add('bg-red-600');
                    setTimeout(() => btn.classList.remove('bg-red-600'), 300);
                    return;
                }
                saveDB();
                sessionStorage.setItem('sales_charge_from_create', '1');
                window.location.href = @json($salesChargeUrl ?? '#');
            }

            function showStockError(productName, stock) {
                const notification = document.getElementById('stock-error-notification');
                const msgEl = document.getElementById('stock-error-message');
                if (!notification || !msgEl) return;
                msgEl.textContent = `Solo quedan ${stock} unidades.`;
                notification.classList.add('notification-show');
                setTimeout(hideStockError, 3000);
            }

            function hideStockError() {
                document.getElementById('stock-error-notification')?.classList.remove('notification-show');
            }

            function showNotification(productName) {
                const notification = document.getElementById('add-to-cart-notification');
                const productNameEl = document.getElementById('notification-product-name');
                if (!notification || !productNameEl) return;
                productNameEl.textContent = productName;
                notification.classList.add('notification-show');
                setTimeout(hideNotification, 1200);
            }

            function hideNotification() {
                document.getElementById('add-to-cart-notification')?.classList.remove('notification-show');
            }

            // --- INICIALIZACIÓN ---
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

            // Exponer funciones globales para los onclick="" del HTML
            window.getImageUrl = getImageUrl;
            window.updateQty = updateQty;
            window.clearCart = clearCart;
            window.toggleNoteInput = toggleNoteInput;
            window.saveNote = saveNote;
            window.goToChargeView = goToChargeView;
            window.hideStockError = hideStockError;
            window.hideNotification = hideNotification;

            // Arrancar
            init();

        })();
    </script>
@endsection