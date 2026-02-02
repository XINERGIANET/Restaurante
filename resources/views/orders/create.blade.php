@extends('layouts.app')

@section('title', 'Punto de Venta')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex items-center gap-2">
            <span class="text-gray-500 dark:text-gray-400"><i class="ri-restaurant-fill"></i></span>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-white/90">
                Salones de Pedidos
            </h2>
        </div>
        <nav>
            <ol class="flex items-center gap-1.5">
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ url('/') }}">
                        Home
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li>
                    <a class="inline-flex items-center gap-1.5 text-sm text-gray-500 dark:text-gray-400" href="{{ route('admin.orders.index') }}">
                        Salones de Pedidos
                        <svg class="stroke-current" width="17" height="16" viewBox="0 0 17 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M6.0765 12.667L10.2432 8.50033L6.0765 4.33366" stroke="" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"></path>
                        </svg>
                    </a>
                </li>
                <li class="text-sm text-gray-800 dark:text-white/90">
                    Mesa {{ str_pad($table->name ?? $table->id, 2, '0', STR_PAD_LEFT) }} | Crear pedido
                </li>
            </ol>
        </nav>
    </div>
    {{-- 
        CAMBIO 1: Contenedor Principal
        - Quitamos 'h-[calc...]' y 'overflow-hidden'.
        - Usamos 'min-h-screen' y 'items-start' para permitir scroll natural.
    --}}
    <div class="flex items-start w-full bg-slate-100 fade-in min-h-screen" style="--brand:#3B82F6;">
        
        {{-- ================= SECCIÓN IZQUIERDA: MENÚ (FLUJO NATURAL) ================= --}}
        {{-- CAMBIO 2: Quitamos 'h-full', 'overflow-y-auto' y 'relative' para que crezca con el contenido --}}
        <main class="flex-1 flex flex-col min-w-0">
            
            {{-- Header (Se mueve con la página) --}}
            <header class="h-20 px-6 flex items-center justify-between bg-white border-b border-gray-200 shadow-sm z-10">
                <div class="flex items-center gap-4">
                    <button onclick="goBack()" class="h-10 w-10 rounded-lg bg-gray-50 border border-gray-200 text-gray-500 hover:text-blue-600 hover:border-blue-600 transition-colors flex items-center justify-center">
                        <i class="fas fa-arrow-left"></i>
                    </button>
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-bold text-slate-800">
                                Mesa <span id="pos-table-name" class="text-blue-600">--</span>
                            </h2>
                            <span id="pos-table-area" class="text-[10px] font-bold px-2 py-0.5 bg-gray-100 text-gray-500 rounded uppercase tracking-wider border border-gray-200">--</span>
                        </div>
                        <div class="flex flex-col text-xs mt-0.5 text-gray-500 font-medium">
                            <div class="flex items-center">
                                <span class="inline-block w-14 text-gray-400">Mozo:</span>
                                <span id="pos-waiter-name" class="text-slate-700 font-semibold truncate max-w-[150px]">--</span>
                            </div>
                            <div class="flex items-center">
                                <span class="inline-block w-14 text-gray-400">Cliente:</span>
                                <span id="pos-client-name" class="text-slate-700 font-semibold truncate max-w-[150px]">--</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="w-64 hidden md:block relative">
                    <input type="text" placeholder="Buscar..." class="w-full pl-9 pr-4 py-2 bg-gray-100 border-transparent focus:bg-white focus:border-blue-500 focus:ring-0 rounded-lg text-sm transition-all">
                    <i class="fas fa-search absolute left-3 top-2.5 text-gray-400 text-xs"></i>
                </div>
            </header>

            {{-- Grid de Productos (Crece hacia abajo infinitamente) --}}
            {{-- CAMBIO 3: Quitamos 'flex-1 overflow-y-auto' --}}
            <div class="p-6 bg-[#F3F4F6]">
                <h3 class="font-bold text-slate-700 mb-4 text-base">Categoría: General</h3>
                <div id="products-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; width: 100%;">
                    {{-- JS llenará esto --}}
                </div>
            </div>
        </main>

        {{-- ================= SECCIÓN DERECHA: CARRITO (STICKY / PEGAJOSO) ================= --}}
        {{-- 
            CAMBIO 4: Configuración Sticky
            - 'sticky top-0': Hace que la barra se pegue al techo cuando haces scroll.
            - 'h-screen': Ocupa todo el alto de la ventana visible.
            - 'overflow-hidden': Para manejar su propio scroll interno si la lista de items es muy larga.
        --}}
        <aside class="w-[600px] bg-white border-l border-gray-300 flex flex-col shadow-2xl z-20 shrink-0 sticky top-0 h-screen">
            
            {{-- Header Carrito --}}
            <div class="h-16 px-6 border-b border-gray-200 bg-white flex justify-between items-center shrink-0">
                <h3 class="text-xl font-bold text-slate-800">Orden Actual</h3>
                <span class="px-3 py-1 bg-blue-50 text-blue-700 rounded-full text-xs font-bold border border-blue-100">En curso</span>
            </div>

            {{-- Lista Items (Scroll interno solo para el carrito) --}}
            <div id="cart-container" class="flex-1 overflow-y-auto p-5 space-y-3 bg-white"></div>

            {{-- Footer Totales --}}
            <div class="p-6 bg-slate-100 border-t border-gray-300 shadow-[0_-5px_25px_rgba(0,0,0,0.05)] shrink-0 z-30">
                <div class="space-y-3 mb-5 text-sm">
                    <div class="flex justify-between text-gray-500 font-medium">
                        <span>Subtotal</span>
                        <span class="text-slate-700" id="ticket-subtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-gray-500 font-medium">
                        <span>Impuestos (10%)</span>
                        <span class="text-slate-700" id="ticket-tax">$0.00</span>
                    </div>
                    <div class="border-t border-dashed border-gray-300 my-2"></div>
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-slate-800">Total a Pagar</span>
                        <span class="text-3xl font-black text-blue-600" id="ticket-total">$0.00</span>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <button onclick="goBack()" class="py-3.5 rounded-xl border border-gray-300 bg-white text-gray-700 font-bold hover:bg-gray-50 shadow-sm transition-all">
                        Guardar
                    </button>
                    <button onclick="sendOrder()" class="py-3.5 rounded-xl bg-blue-600 text-white font-bold shadow-lg shadow-blue-500/30 hover:bg-blue-700 active:scale-95 transition-all flex justify-center items-center gap-2">
                        <span>Cobrar</span> <i class="fas fa-check-circle"></i>
                    </button>
                </div>
            </div>
        </aside>
    </div>

    {{-- SCRIPTS (Sin cambios lógicos, solo visuales ya aplicados) --}}
    <script>
        const productsDB = [
            { id: 1, name: "Burger Clásica", price: 12.00, img: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400" },
            { id: 2, name: "Pizza Pepperoni", price: 22.00, img: "https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400" },
            { id: 3, name: "Coca Cola", price: 3.50, img: "https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400" },
            { id: 4, name: "Cheesecake", price: 8.00, img: "https://images.unsplash.com/photo-1524351199678-941a58a3df26?w=400" },
            { id: 5, name: "Limonada", price: 5.00, img: "https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=400" },
            { id: 6, name: "Pasta Alfredo", price: 18.00, img: "https://images.unsplash.com/photo-1621996346565-e3dbc646d9a9?w=400" }
        ];

        const serverTable = @js([
            'id' => $table->id,
            'name' => $table->name ?? $table->id,
            'area' => $table->area?->name ?? 'Salon',
            'waiter' => 'Sin asignar',
            'clientName' => 'Publico General',
            'status' => $table->situation === 'ocupada' ? 'occupied' : 'free',
            'items' => [],
        ]);
        let db = JSON.parse(localStorage.getItem('restaurantDB'));
        if(!db) db = {};
        let activeKey = `table-{{ $table->id }}`;
        let currentTable = db[activeKey] || serverTable;
function init() {
            document.getElementById('pos-table-name').innerText = currentTable.name || "00";
            document.getElementById('pos-table-area').innerText = currentTable.area || "Salon";
            document.getElementById('pos-waiter-name').innerText = currentTable.waiter || "Sin asignar";
            document.getElementById('pos-client-name').innerText = currentTable.clientName || "Público General";
            renderProducts();
            renderTicket();
        }

        function renderProducts() {
            const grid = document.getElementById('products-grid');
            grid.innerHTML = '';
            productsDB.forEach(prod => {
                const el = document.createElement('div');
                el.className = "bg-white p-3 rounded-xl shadow-sm border border-gray-100 cursor-pointer hover:shadow-md hover:border-blue-300 transition-all group";
                el.onclick = () => addToCart(prod);
                
                // PRECIO COMO PILDORA FLOTANTE
                el.innerHTML = `
                    <div class="relative w-full rounded-lg overflow-hidden mb-2 bg-gray-100" style="height: 120px;">
                        <img src="${prod.img}" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                        <div class="absolute bottom-2 right-2 bg-white px-3 py-1 rounded-full text-xs font-bold text-slate-800 border border-gray-200 shadow-md">
                            $${prod.price.toFixed(2)}
                        </div>
                    </div>
                    <h4 class="font-bold text-slate-700 text-sm leading-tight line-clamp-2">${prod.name}</h4>
                    <span class="text-[10px] text-gray-400">Plato principal</span>
                `;
                grid.appendChild(el);
            });
        }

        function addToCart(prod) {
            if(!currentTable.items) currentTable.items = [];
            const existing = currentTable.items.find(i => i.pId === prod.id);
            if(existing) { existing.qty++; } else {
                currentTable.items.push({ pId: prod.id, qty: 1, price: prod.price, note: "" });
            }
            saveDB(); renderTicket();
        }

        function updateQty(index, change) {
            currentTable.items[index].qty += change;
            if(currentTable.items[index].qty <= 0) currentTable.items.splice(index, 1);
            saveDB(); renderTicket();
        }

        function toggleNoteInput(index) {
            document.getElementById(`note-box-${index}`).classList.toggle('hidden');
        }

        function saveNote(index, val) {
            currentTable.items[index].note = val; saveDB();
        }

        function renderTicket() {
            const container = document.getElementById('cart-container');
            container.innerHTML = '';
            let subtotal = 0;

            if (!currentTable.items || currentTable.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-64 text-gray-300 opacity-60">
                        <i class="fas fa-utensils text-3xl mb-2"></i>
                        <p class="font-medium text-sm">Sin productos</p>
                    </div>`;
            } else {
                currentTable.items.forEach((item, index) => {
                    const prod = productsDB.find(p => p.id === item.pId);
                    subtotal += item.price * item.qty;
                    const hasNote = item.note && item.note.trim() !== "";

                    const row = document.createElement('div');
                    row.className = "bg-white border border-gray-200 rounded-lg p-2.5 shadow-sm relative overflow-hidden group mb-2";
                    row.innerHTML = `
                        <div class="absolute left-0 top-0 bottom-0 w-1 bg-blue-500"></div>
                        <div class="flex gap-3 pl-2">
                            <img src="${prod.img}" class="h-10 w-10 rounded-md object-cover bg-gray-100">
                            <div class="flex-1 min-w-0">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-slate-700 text-xs truncate pr-1">${prod.name}</span>
                                    <span class="font-bold text-slate-800 text-xs">$${(item.price * item.qty).toFixed(2)}</span>
                                </div>
                                <div class="flex justify-between items-center mt-2">
                                    <button onclick="toggleNoteInput(${index})" class="text-[10px] flex items-center gap-1 transition-colors ${hasNote ? 'text-blue-600 bg-blue-50 px-1.5 rounded' : 'text-gray-400 hover:text-blue-500'}">
                                        <i class="fas fa-comment-alt"></i> ${hasNote ? 'Nota' : 'Nota'}
                                    </button>
                                    <div class="flex items-center gap-2 bg-gray-50 rounded border border-gray-100">
                                        <button onclick="updateQty(${index}, -1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-red-500"><i class="fas fa-minus text-[9px]"></i></button>
                                        <span class="text-xs font-bold text-slate-700 w-4 text-center">${item.qty}</span>
                                        <button onclick="updateQty(${index}, 1)" class="w-6 h-5 flex items-center justify-center text-gray-500 hover:text-blue-600"><i class="fas fa-plus text-[9px]"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-2 animate-fadeIn pl-2">
                            <input type="text" value="${item.note}" oninput="saveNote(${index}, this.value)" placeholder="Nota..." class="w-full text-[10px] bg-yellow-50 border border-yellow-200 rounded p-1.5 text-slate-700 focus:outline-none focus:border-yellow-400">
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
            if(db && currentTable) {
                db[activeKey] = currentTable;
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }
        }
        function goBack() { saveDB(); alert("Volviendo..."); }
        function sendOrder() {
            if(confirm("¿Cobrar?")) {
                currentTable.status = 'free'; currentTable.items = []; currentTable.total = 0;
                saveDB(); alert("Cobrado"); renderTicket();
            }
        }
        init();
    </script>
@endsection


