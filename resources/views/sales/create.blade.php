@extends('layouts.app')

@section('content')
    {{-- Breadcrumb --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div class="flex items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400"><i class="ri-restaurant-fill"></i></span>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                Punto de Venta
            </h2>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300"
                        href="{{ route('admin.sales.index') }}">
                        Ventas
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none"
                            xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2"
                                stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">
                    Nueva Venta
                </li>
            </ol>
        </nav>
    </div>

    {{-- Contenedor Principal - Full Width con fondo --}}
    <div class="-mx-4 md:-mx-6 -mb-4 md:-mb-6">
        <div class="flex items-start w-full dark:bg-slate-950 fade-in min-h-[calc(100vh-180px)]" style="--brand:#3B82F6;">

            {{-- ================= SECCIÓN IZQUIERDA: MENÚ ================= --}}
            <main class="flex-1 p-4 flex flex-col min-w-0">

                {{-- Header --}}
                <header
                    class="h-20 px-6 flex items-center justify-between dark:bg-slate-900/80 backdrop-blur-sm border-b border-gray-200 dark:border-slate-800 shadow-sm z-10">
                    <div class="flex items-center gap-4">
                        <button onclick="goBack()"
                            class="h-10 w-10 rounded-lg bg-gray-50 dark:bg-slate-800 border border-gray-200 dark:border-slate-700 text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:border-blue-600 dark:hover:border-blue-500 transition-colors flex items-center justify-center">
                            <i class="fas fa-arrow-left"></i>
                        </button>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-xl font-bold text-slate-800 dark:text-white">
                                    Nueva Venta
                                </h2>
                            </div>
                            <div class="flex flex-col text-xs mt-0.5 text-gray-500 dark:text-gray-400 font-medium">
                                <div class="flex items-center">
                                    <span class="inline-block w-14 text-gray-400 dark:text-gray-500">Cliente:</span>
                                    <span id="pos-client-name"
                                        class="text-slate-700 dark:text-gray-300 font-semibold truncate max-w-[150px]">Público
                                        General</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="w-64 hidden md:block relative">
                        <input type="text" placeholder="Buscar..."
                            class="w-full dark:bg-slate-600 border border-gray-200 dark:border-slate-700 focus:bg-white dark:focus:bg-slate-700 focus:border-blue-500 dark:focus:border-blue-400 focus:ring-1 focus:ring-blue-500 dark:focus:ring-blue-400 rounded-lg text-sm transition-all text-slate-800 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 pl-9 pr-4 py-2">
                        <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 dark:text-gray-500 text-xs"></i>
                    </div>
                </header>

                {{-- Grid de Productos --}}
                <div class="p-6  dark:bg-slate-900 min-h-[calc(100vh-260px)]">
                    <h3 class="font-semibold text-slate-700 dark:text-gray-300 mb-5 text-base flex items-center gap-2">
                        <i class="ri-restaurant-2-line text-blue-600 dark:text-blue-400"></i>
                        Categoría: General
                    </h3>
                    <div id="products-grid"
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3 w-full">
                        {{-- JS llenará esto --}}
                    </div>
                </div>
            </main>

            {{-- ================= SECCIÓN DERECHA: CARRITO (STICKY) ================= --}}
            <aside
                class="w-[600px] dark:bg-slate-900 backdrop-blur-sm border-l border-gray-300 dark:border-slate-800 flex flex-col shadow-2xl z-20 shrink-0 sticky top-0 h-screen">

                {{-- Header Carrito --}}
                <div
                    class="h-16 px-6 border-b border-gray-200 dark:border-slate-800 dark:bg-slate-900/80 backdrop-blur-sm flex justify-between items-center shrink-0">
                    <h3 class="text-xl font-bold text-slate-800 dark:text-white">Orden Actual</h3>
                    <span
                        class="px-3 py-1 bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 rounded-full text-xs font-bold border border-blue-100 dark:border-blue-800">En
                        curso</span>
                </div>

                {{-- Lista Items --}}
                <div id="cart-container" class="flex-1 overflow-y-auto p-5 space-y-3 dark:bg-slate-900"></div>

                {{-- Footer Totales --}}
                <div
                    class="p-6 dark:bg-slate-950/90 backdrop-blur-sm border-t border-gray-300 dark:border-slate-800 shadow-[0_-5px_25px_rgba(0,0,0,0.05)] dark:shadow-[0_-5px_25px_rgba(0,0,0,0.3)] shrink-0 z-30">
                    <div class="space-y-3 mb-5 text-sm">
                        <div class="flex justify-between text-gray-500 dark:text-gray-400 font-medium">
                            <span>Subtotal</span>
                            <span class="text-slate-800 dark:text-white" id="ticket-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-500 dark:text-gray-400 font-medium">
                            <span>Impuestos (10%)</span>
                            <span class="text-slate-800 dark:text-white" id="ticket-tax">$0.00</span>
                        </div>
                        <div class="border-t border-dashed border-gray-300 dark:border-slate-700 my-2"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                            <span class="text-3xl font-black text-blue-600 dark:text-blue-400"
                                id="ticket-total">$0.00</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <button onclick="goBack()"
                            class="py-3.5 rounded-xl border border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-700 dark:text-white font-bold hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm transition-all">
                            Guardar
                        </button>
                        <button onclick="sendOrder()"
                            class="py-3.5 rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30 dark:shadow-blue-500/20 hover:bg-blue-700 dark:hover:bg-blue-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                            <span>Cobrar</span> <i class="fas fa-check-circle"></i>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
    </div>

    <script>
        const productsDB = @json($products);

        const serverSale = {
            id: Date.now(),
            clientName: 'Público General',
            status: 'in_progress',
            items: [],
        };
        let db = JSON.parse(localStorage.getItem('restaurantDB'));
        if (!db) db = {};
        let activeKey = `sale-${Date.now()}`;
        let currentSale = db[activeKey] || serverSale;

        function init() {
            document.getElementById('pos-client-name').innerText = currentSale.clientName || "Público General";
            renderProducts();
            renderTicket();
        }

        function renderProducts() {
            const grid = document.getElementById('products-grid');
            grid.innerHTML = '';
            productsDB.forEach(prod => {
                const el = document.createElement('div');
                el.className = "group cursor-pointer";
                el.onclick = () => addToCart(prod);

                el.innerHTML = `
                    <div class="dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 overflow-hidden shadow-sm hover:shadow-md transition-all duration-200 hover:-translate-y-1">
                        <!-- Imagen del producto -->
                        <div class="relative aspect-square overflow-hidden bg-gray-100 dark:bg-slate-700">
                            <img src="${prod.img}" 
                                alt="${prod.name}" 
                                class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
                                loading="lazy"
                                onerror="this.src='{{ asset('images/no-image.png') }}'">
                            
                            <!-- Badge de precio -->
                            <div class="absolute bottom-2 right-2">
                                <span class="px-2.5 py-1 dark:bg-slate-900 rounded-md text-xs font-bold text-blue-600 dark:text-blue-400 shadow-md">
                                    $${parseFloat(prod.price).toFixed(2)}
                                </span>
                            </div>
                        </div>
                        
                        <!-- Info del producto -->
                        <div class="p-3">
                            <h4 class="font-semibold text-slate-800 dark:text-white text-sm mb-1 line-clamp-2 leading-tight">
                                ${prod.name}
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                ${prod.category}
                            </p>
                        </div>
                    </div>
                `;
                grid.appendChild(el);
            });
        }

        function addToCart(prod) {
            if (!currentSale.items) currentSale.items = [];
            const existing = currentSale.items.find(i => i.pId === prod.id);
            if (existing) {
                existing.qty++;
            } else {
                currentSale.items.push({
                    pId: prod.id,
                    qty: 1,
                    price: prod.price,
                    note: ""
                });
            }
            saveDB();
            renderTicket();
        }

        function updateQty(index, change) {
            currentSale.items[index].qty += change;
            if (currentSale.items[index].qty <= 0) currentSale.items.splice(index, 1);
            saveDB();
            renderTicket();
        }

        function toggleNoteInput(index) {
            document.getElementById(`note-box-${index}`).classList.toggle('hidden');
        }

        function saveNote(index, val) {
            currentSale.items[index].note = val;
            saveDB();
        }

        function renderTicket() {
            const container = document.getElementById('cart-container');
            container.innerHTML = '';
            let subtotal = 0;

            if (!currentSale.items || currentSale.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-64 text-gray-300 dark:text-gray-600 opacity-70">
                        <div class="w-20 h-20 rounded-full dark:bg-slate-800 flex items-center justify-center mb-4">
                            <i class="fas fa-shopping-cart text-3xl dark:text-gray-600"></i>
                        </div>
                        <p class="font-semibold text-base text-gray-500 dark:text-gray-500">Sin productos</p>
                        <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">Selecciona productos del menú</p>
                     </div>`;
            } else {
                currentSale.items.forEach((item, index) => {
                const prod = productsDB.find(p => p.id === item.pId);
                if (!prod) return; // Skip if product not found
                subtotal += item.price * item.qty;
                const hasNote = item.note && item.note.trim() !== "";

                const row = document.createElement('div');
                    row.className =
                        "dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/50 rounded-xl p-2 shadow-sm hover:shadow-md relative overflow-hidden group mb-3 transition-all duration-200";
                row.innerHTML = `
                         <div class="absolute left-0 top-0 bottom-0 w-1 bg-gradient-to-b from-blue-500 to-blue-600 dark:from-blue-400 dark:to-blue-500 rounded-l-xl"></div>
                         <div class="flex gap-4 pl-3">
                             <div class="relative shrink-0">
                                 <img src="${prod.img}" class="h-16 w-16 rounded-xl object-cover bg-gray-100 dark:bg-slate-700 ring-2 ring-gray-200/50 dark:ring-slate-600/50 shadow-sm">
                             </div>
                             <div class="flex-1 min-w-0 flex flex-col justify-between">
                                 <div class="flex justify-between items-start mb-2">
                                     <div class="flex-1 min-w-0 pr-2">
                                         <h5 class="font-semibold text-slate-900 dark:text-white text-sm truncate mb-1">${prod.name}</h5>
                                         <p class="text-xs text-gray-500 dark:text-gray-400">$${item.price.toFixed(2)} c/u</p>
                                     </div>
                                     <div class="shrink-0">
                                         <span class="font-bold text-blue-600 dark:text-blue-400 text-base">$${(item.price * item.qty).toFixed(2)}</span>
                                     </div>
                            </div>
                                 <div class="flex justify-between items-center">
                                     <button onclick="toggleNoteInput(${index})" class="text-xs flex items-center gap-1.5 transition-all duration-200 ${hasNote ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-2.5 py-1 rounded-lg font-medium' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-slate-700/50 px-2.5 py-1 rounded-lg'}">
                                         <i class="fas fa-sticky-note text-[10px]"></i> ${hasNote ? 'Nota' : 'Agregar nota'}
                                     </button>
                                     <div class="flex items-center gap-1 dark:bg-slate-700 rounded-lg border border-gray-200 dark:border-slate-600 shadow-sm">
                                        <button onclick="updateQty(${index}, -1)" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-white hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 rounded-l-lg transition-all active:scale-95">
                                            <i class="ri-subtract-line text-sm"></i>
                                        </button>
                                         <span class="text-sm font-bold text-slate-900 dark:text-white w-8 text-center">${item.qty}</span>
                                         <button onclick="updateQty(${index}, 1)" class="w-8 h-8 flex items-center justify-center text-gray-700 dark:text-white hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:text-blue-600 dark:hover:text-blue-400 rounded-r-lg transition-all active:scale-95">
                                             <i class="ri-add-line text-sm"></i>
                                </button>
                                </div>
                            </div>
                        </div>
                    </div>
                         <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-3 ml-3 animate-fadeIn">
                             <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border-l-3 border-amber-400 dark:border-amber-500 rounded-lg p-2.5">
                                 <input type="text" value="${item.note}" oninput="saveNote(${index}, this.value)" placeholder="Ej: Sin cebolla, extra queso..." class="w-full text-xs bg-transparent border-none text-slate-700 dark:text-gray-200 focus:outline-none placeholder-gray-400 dark:placeholder-gray-500 font-medium">
                             </div>
                    </div>
                `;
                container.appendChild(row);
            });
            }
            const tax = subtotal * 0.10;
            document.getElementById('ticket-subtotal').innerText = `$${subtotal.toFixed(2)}`;
            document.getElementById('ticket-tax').innerText = `$${tax.toFixed(2)}`;
            document.getElementById('ticket-total').innerText = `$${(subtotal + tax).toFixed(2)}`;
        }

        function saveDB() {
            if (db && currentSale) {
                db[activeKey] = currentSale;
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }
        }

        function goBack() {
            saveDB();
            window.location.href = "{{ route('admin.sales.index') }}";
        }

        function sendOrder() {
            if (confirm("¿Cobrar?")) {
                currentSale.status = 'completed';
                currentSale.items = [];
                currentSale.total = 0;
                saveDB();
                alert("Cobrado");
                renderTicket();
                window.location.href = "{{ route('admin.sales.index') }}";
            }
        }
        init();
    </script>
@endsection
