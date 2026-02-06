<?php $__env->startSection('content'); ?>
    
    <div class=" flex flex-wrap items-center justify-between gap-3 mb-4">
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
                        href="<?php echo e(route('admin.sales.index')); ?>">
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

    
    <div class="-mx-4 md:-mx-6 -mb-4 md:-mb-6">
        <div class="flex items-start w-full dark:bg-slate-950 fade-in min-h-[calc(100vh-180px)]" style="--brand:#3B82F6;">

            
            <main class="flex-1 p-4 flex flex-col min-w-0">

                
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

                
                <div class="p-6  dark:bg-slate-900 min-h-[calc(100vh-260px)]">
                    <h3 class="font-semibold text-slate-700 dark:text-gray-300 mb-5 text-base flex items-center gap-2">
                        <i class="ri-restaurant-2-line text-blue-600 dark:text-blue-400"></i>
                        Categoría: General
                    </h3>
                    <div id="products-grid"
                        class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5 gap-3 w-full">
                        
                    </div>
                </div>
            </main>

            
            <aside
                class="w-[420px] dark:bg-slate-900 backdrop-blur-sm border-l border-gray-300 dark:border-slate-800 flex flex-col shadow-2xl z-20 shrink-0 sticky top-0 h-screen">

                
                <div
                    class="h-14 px-4 border-b border-gray-200 dark:border-slate-800 dark:bg-slate-900/80 backdrop-blur-sm flex justify-between items-center shrink-0">
                    <div class="flex items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-800 dark:text-white">Orden Actual</h3>
                        <span id="cart-count-badge"
                            class="inline-block px-2 py-0.5 bg-blue-600 dark:bg-blue-500 text-white rounded-full text-[10px] font-bold shadow-lg shadow-blue-500/30">
                            0
                        </span>
                    </div>
                    <span
                        class="px-2.5 py-0.5 bg-blue-50 dark:bg-blue-900/40 text-blue-700 dark:text-blue-400 rounded-full text-[10px] font-bold border border-blue-100 dark:border-blue-800">En
                        curso</span>
                </div>

                
                <div id="cart-container" class="flex-1 overflow-y-auto p-3 dark:bg-slate-900"></div>

                
                <div
                    class="p-4 dark:bg-slate-950/90 backdrop-blur-sm border-t border-gray-300 dark:border-slate-800 shadow-[0_-5px_25px_rgba(0,0,0,0.05)] dark:shadow-[0_-5px_25px_rgba(0,0,0,0.3)] shrink-0 z-30">
                    <div class="space-y-2 mb-4 text-sm">
                        <div class="flex justify-between text-gray-500 dark:text-gray-400 font-medium text-xs">
                            <span>Subtotal</span>
                            <span class="text-slate-800 dark:text-white" id="ticket-subtotal">$0.00</span>
                        </div>
                        <div class="flex justify-between text-gray-500 dark:text-gray-400 font-medium text-xs">
                            <span>Impuestos (10%)</span>
                            <span class="text-slate-800 dark:text-white" id="ticket-tax">$0.00</span>
                        </div>
                        <div class="border-t border-dashed border-gray-300 dark:border-slate-700 my-1.5"></div>
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-slate-800 dark:text-white">Total a Pagar</span>
                            <span class="text-2xl font-black text-blue-600 dark:text-blue-400"
                                id="ticket-total">$0.00</span>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="goBack()"
                            class="py-2.5 rounded-lg border border-gray-300 dark:border-slate-700 dark:bg-slate-800 text-gray-700 dark:text-white text-sm font-bold hover:bg-gray-50 dark:hover:bg-slate-700 shadow-sm transition-all">
                            Guardar
                        </button>
                        <button type="button" id="checkout-button" onclick="openChargeModal()"
                            class="py-2.5 rounded-lg bg-blue-600 text-white text-sm font-bold shadow-lg shadow-blue-500/30 dark:shadow-blue-500/20 hover:bg-blue-700 dark:hover:bg-blue-600 active:scale-95 transition-all flex justify-center items-center gap-2">
                            <span>Cobrar</span> <i class="fas fa-cash-register text-xs"></i>
                        </button>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    
    <div id="add-to-cart-notification" 
        class="fixed top-24 right-8 z-50 transform transition-all duration-500 translate-x-[150%] opacity-0">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-green-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]">
            <div class="w-12 h-12 bg-white/20 rounded-full flex items-center justify-center animate-bounce">
                <i class="fas fa-check text-2xl"></i>
            </div>
            <div class="flex-1">
                <p class="font-bold text-sm">¡Producto agregado!</p>
                <p id="notification-product-name" class="text-xs text-green-50 mt-0.5">Producto</p>
            </div>
            <button onclick="hideNotification()" class="text-white/80 hover:text-white transition-colors">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <style>
        @keyframes slideInFromLeft {
            from {
                transform: translateX(-30px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes pulse-subtle {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.02);
            }
        }

        .cart-item-enter {
            animation: slideInFromLeft 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        .product-click-effect {
            animation: pulse-subtle 0.3s ease-out;
        }

        .shake-animation {
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px) rotate(-2deg); }
            75% { transform: translateX(5px) rotate(2deg); }
        }

        .notification-show {
            transform: translateX(0) !important;
            opacity: 1 !important;
        }

        .qty-badge-pop {
            animation: popScale 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }

        @keyframes popScale {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        .pulse-button {
            animation: pulse-glow 2s infinite;
        }

        @keyframes pulse-glow {
            0%, 100% {
                box-shadow: 0 0 20px rgba(59, 130, 246, 0.3);
            }
            50% {
                box-shadow: 0 0 30px rgba(59, 130, 246, 0.6);
            }
        }

        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
    </style>

    <script>
        const productsDBRaw = <?php echo json_encode($products, 15, 512) ?>;
        const productsBranchesRaw = <?php echo json_encode($productsBranches, 15, 512) ?>;
        const productsDB = Array.isArray(productsDBRaw) ? productsDBRaw : Object.values(productsDBRaw || {});
        const productsBranches = Array.isArray(productsBranchesRaw) ? productsBranchesRaw : Object.values(productsBranchesRaw || {});
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
        let notificationTimeout;

        // Función helper para obtener la URL de la imagen o placeholder
        function getImageUrl(imgUrl) {
            if (imgUrl && imgUrl.trim() !== '') {
                return imgUrl;
            }
            // SVG placeholder simple codificado
            return 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSI0MDAiIGhlaWdodD0iNDAwIj48cmVjdCBmaWxsPSIjZTdlOWViIiB3aWR0aD0iNDAwIiBoZWlnaHQ9IjQwMCIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBkb21pbmFudC1iYXNlbGluZT0ibWlkZGxlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiIGZpbGw9IiM5Y2EzYWYiPlNpbiBpbWFnZW48L3RleHQ+PC9zdmc+';
        }

        function init() {
            const clientNameEl = document.getElementById('pos-client-name');
            if (clientNameEl) {
                clientNameEl.innerText = currentSale.clientName || "Público General";
            }
            
            renderProducts();
            renderTicket();
        }

        function renderProducts() {
            const grid = document.getElementById('products-grid');
            grid.innerHTML = '';
            productsDB.forEach(prod => {
                const productBranch = productsBranches.find(p => p.id === prod.id);
                
                // Si no hay productBranch (producto no está en esta sucursal), no mostrar
                if (!productBranch) {
                    return;
                }
                
                // Debug: verificar URL de imagen
                if (prod.id === 4) {
                    console.log('Producto ID 4:', prod);
                    console.log('URL de imagen:', prod.img);
                }
                
                const el = document.createElement('div');
                el.className = "group cursor-pointer transition-transform duration-200 hover:scale-105";
                el.onclick = function(e) {
                    addToCart(prod, productBranch, e);
                };

                el.innerHTML = `
            <div class="rounded-lg overflow-hidden p-3  dark:bg-slate-800/40 shadow-md hover:shadow-xl border border-gray-300 dark:border-slate-700/50 hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-blue-500/10 transition-all duration-200 hover:-translate-y-1 backdrop-blur-sm">
                <!-- Imagen del producto -->
                <div class="relative aspect-square overflow-hidden  dark:bg-slate-700/30 rounded-lg border border-gray-300 dark:border-slate-600/30 shadow-sm">
                    <img src="${getImageUrl(prod.img)}" 
                        alt="${prod.name}" 
                        class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-110"
                        loading="lazy"
                        onerror="this.onerror=null; this.src=getImageUrl(null)">
                    
                    <!-- Badge de precio flotante -->
                    <span class="absolute top-3 right-3 z-10">
                        <span class="px-2.5 py-1 bg-blue-600 dark:bg-blue-500 rounded-lg text-sm font-bold shadow-lg shadow-blue-500/40 dark:shadow-blue-500/20 backdrop-blur-sm border border-blue-400/50 dark:border-blue-400/30 text-white">
                            $${parseFloat(productBranch.price).toFixed(2)}
                        </span>
                    </span>
                </div>
                
                <!-- Info del producto -->
                <div class="mt-3 flex flex-col gap-1">
                    <h4 class="font-semibold text-gray-900 dark:text-white text-sm line-clamp-2 leading-tight group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        ${prod.name}
                    </h4>
                    <h6 class="text-xs text-gray-600 dark:text-gray-400">
                        ${prod.category || 'Sin categoría'}
                    </h6>
                </div>
            </div>
        `;
                grid.appendChild(el);
            });
        }

        function addToCart(prod, productBranch, event) {
            if (!currentSale.items) currentSale.items = [];
            const existing = currentSale.items.find(i => i.pId === prod.id);
            
            // Efecto visual en el producto clickeado
            if (event && event.currentTarget) {
                event.currentTarget.classList.add('product-click-effect');
                setTimeout(() => {
                    event.currentTarget.classList.remove('product-click-effect');
                }, 300);
            }

            const wasNew = !existing;
            
            if (existing) {
                existing.qty++;
            } else {
                currentSale.items.push({
                    pId: prod.id,
                    qty: 1,
                    price: parseFloat(productBranch.price) || 0,
                    note: ""
                });
            }
            
            saveDB();
            renderTicket(wasNew ? prod.id : null);
            showNotification(prod.name, wasNew);
            
            // Shake effect en el header del carrito
            try {
                const cartHeader = document.querySelector('aside h3');
                if (cartHeader) {
                    cartHeader.classList.add('shake-animation');
                    setTimeout(() => {
                        cartHeader.classList.remove('shake-animation');
                    }, 500);
                }
            } catch (error) {
                console.warn('Could not add shake animation:', error);
            }
        }

        function showNotification(productName, isNew) {
            const notification = document.getElementById('add-to-cart-notification');
            const productNameEl = document.getElementById('notification-product-name');
            
            if (!notification || !productNameEl) {
                console.warn('Notification elements not found');
                return;
            }
            
            if (notificationTimeout) {
                clearTimeout(notificationTimeout);
            }
            
            productNameEl.textContent = isNew ? productName : `${productName} (cantidad actualizada)`;
            notification.classList.add('notification-show');
            
            notificationTimeout = setTimeout(() => {
                hideNotification();
            }, 3000);
        }

        function hideNotification() {
            const notification = document.getElementById('add-to-cart-notification');
            if (notification) {
                notification.classList.remove('notification-show');
            }
        }

        function updateQty(index, change) {
            const item = currentSale.items[index];
            const prod = productsDB.find(p => p.id === item.pId);
            
            currentSale.items[index].qty += change;
            
            if (currentSale.items[index].qty <= 0) {
                // Mostrar notificación de eliminación
                if (prod) {
                    showRemoveNotification(prod.name);
                }
                currentSale.items.splice(index, 1);
            } else if (change > 0 && prod) {
                // Mostrar notificación de incremento
                showNotification(prod.name, false);
            }
            
            saveDB();
            renderTicket();
        }

        function showRemoveNotification(productName) {
            const notification = document.getElementById('add-to-cart-notification');
            const productNameEl = document.getElementById('notification-product-name');
            const notificationDiv = notification.querySelector('div');
            
            if (notificationTimeout) {
                clearTimeout(notificationTimeout);
            }
            
            // Cambiar a estilo de eliminación
            notificationDiv.className = 'bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-red-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]';
            notificationDiv.querySelector('i').className = 'fas fa-trash-alt text-2xl';
            notificationDiv.querySelector('p').textContent = '¡Producto eliminado!';
            productNameEl.textContent = productName;
            
            notification.classList.add('notification-show');
            
            notificationTimeout = setTimeout(() => {
                hideNotification();
                // Restaurar estilo original
                setTimeout(() => {
                    notificationDiv.className = 'bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-green-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]';
                    notificationDiv.querySelector('i').className = 'fas fa-check text-2xl';
                    notificationDiv.querySelector('p').textContent = '¡Producto agregado!';
                }, 500);
            }, 3000);
        }

        function toggleNoteInput(index) {
            document.getElementById(`note-box-${index}`).classList.toggle('hidden');
        }

        function saveNote(index, val) {
            currentSale.items[index].note = val;
            saveDB();
        }

        function renderTicket(newProductId = null) {
            const container = document.getElementById('cart-container');
            if (!container) {
                return;
            }
            
            container.innerHTML = '';
            let subtotal = 0;

            if (!currentSale.items || currentSale.items.length === 0) {
                container.innerHTML = `
                    <div class="flex flex-col items-center justify-center h-48 text-gray-300 dark:text-gray-600 opacity-70">
                        <div class="w-16 h-16 rounded-full dark:bg-slate-800 flex items-center justify-center mb-3">
                            <i class="fas fa-shopping-cart text-2xl dark:text-gray-600"></i>
                        </div>
                        <p class="font-semibold text-sm text-gray-500 dark:text-gray-500">Sin productos</p>
                        <p class="text-xs text-gray-400 dark:text-gray-600 mt-1">Selecciona productos del menú</p>
                     </div>`;
            } else {
                currentSale.items.forEach((item, index) => {
    const prod = productsDB.find(p => p.id === item.pId);
    if (!prod) return; // Skip if product not found
    
    // Buscar el productBranch usando el ID correcto
    const productBranch = productsBranches.find(p => p.id === item.pId);
    if (!productBranch) return; // Skip if not in current branch
    
    // Usar precio de productBranch (convertir a número)
    const itemPrice = parseFloat(productBranch.price) || 0;
    const itemTotal = itemPrice * item.qty;
    subtotal += itemTotal;
    
    const hasNote = item.note && item.note.trim() !== "";
    const isNewItem = newProductId === item.pId;

    const row = document.createElement('div');
    row.className =
        "py-2 px-2 dark:bg-slate-800/60 border border-gray-200 dark:border-slate-700/50 rounded-lg p-1.5 shadow-sm hover:shadow-md relative overflow-hidden group mb-2 transition-all duration-200" +
        (isNewItem ? " cart-item-enter" : "");
    row.innerHTML = `
         <div class="absolute left-0 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-500 to-blue-600 dark:from-blue-400 dark:to-blue-500 rounded-l-lg"></div>
         <div class="flex gap-2 pl-2">
             <div class="relative shrink-0">
                 <img src="${getImageUrl(prod.img)}" 
                      alt="${prod.name}"
                      class="h-12 w-12 rounded-lg object-cover bg-gray-100 dark:bg-slate-700 ring-1 ring-gray-200/50 dark:ring-slate-600/50 shadow-sm"
                      onerror="this.onerror=null; this.src=getImageUrl(null)">
             </div>
             <div class="flex-1 min-w-0 flex flex-col justify-between py-0.5">
                 <div class="flex justify-between items-start gap-2">
                     <div class="flex-1 min-w-0">
                         <h5 class="font-semibold text-slate-900 dark:text-white text-xs truncate leading-tight">${prod.name}</h5>
                         <p class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">$${itemPrice.toFixed(2)} c/u</p>
                     </div>
                     <div class="shrink-0">
                         <span class="font-bold text-blue-600 dark:text-blue-400 text-sm">$${itemTotal.toFixed(2)}</span>
                     </div>
                 </div>
                 <div class="flex justify-between items-center mt-1">
                     <button onclick="toggleNoteInput(${index})" class="text-[10px] flex items-center gap-1 transition-all duration-200 ${hasNote ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 px-1.5 py-0.5 rounded font-medium' : 'text-gray-500 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-50 dark:hover:bg-slate-700/50 px-1.5 py-0.5 rounded'}">
                         <i class="fas fa-sticky-note text-[8px]"></i> ${hasNote ? 'Nota' : '+ Nota'}
                     </button>
                     <div class="flex items-center gap-0.5 dark:bg-slate-700 rounded border border-gray-200 dark:border-slate-600 shadow-sm">
                        <button onclick="updateQty(${index}, -1)" class="w-6 h-6 flex items-center justify-center text-gray-700 dark:text-white hover:bg-red-50 dark:hover:bg-red-900/30 hover:text-red-600 dark:hover:text-red-400 rounded-l transition-all active:scale-95">
                            <i class="ri-subtract-line text-xs"></i>
                        </button>
                         <span class="text-xs font-bold text-slate-900 dark:text-white w-6 text-center ${isNewItem ? 'qty-badge-pop' : ''}" id="qty-${index}">${item.qty}</span>
                         <button onclick="updateQty(${index}, 1)" class="w-6 h-6 flex items-center justify-center text-gray-700 dark:text-white hover:bg-blue-50 dark:hover:bg-blue-900/30 hover:text-blue-600 dark:hover:text-blue-400 rounded-r transition-all active:scale-95">
                             <i class="ri-add-line text-xs"></i>
                        </button>
                     </div>
                 </div>
             </div>
         </div>
         <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-1.5 ml-2 mr-2 animate-fadeIn">
             <div class="bg-gradient-to-r from-amber-50 to-yellow-50 dark:from-amber-900/20 dark:to-yellow-900/20 border-l-2 border-amber-400 dark:border-amber-500 rounded p-1.5">
                 <input type="text" value="${item.note}" oninput="saveNote(${index}, this.value)" placeholder="Ej: Sin cebolla, extra queso..." class="w-full text-[10px] bg-transparent border-none text-slate-700 dark:text-gray-200 focus:outline-none placeholder-gray-400 dark:placeholder-gray-500 font-medium">
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
            
            // Actualizar badge de cantidad
            const cartCountBadge = document.getElementById('cart-count-badge');
            const totalItems = currentSale.items ? currentSale.items.reduce((sum, item) => sum + item.qty, 0) : 0;
            const checkoutButton = document.getElementById('checkout-button');
            
            if (totalItems > 0) {
                if (cartCountBadge) {
                    cartCountBadge.textContent = totalItems;
                    cartCountBadge.classList.remove('hidden');
                    
                    if (newProductId) {
                        cartCountBadge.classList.add('qty-badge-pop');
                        setTimeout(() => {
                            cartCountBadge.classList.remove('qty-badge-pop');
                        }, 300);
                    }
                }
                
                if (checkoutButton) {
                    checkoutButton.classList.add('pulse-button');
                    checkoutButton.disabled = false;
                    checkoutButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }
            } else {
                if (cartCountBadge) {
                    cartCountBadge.classList.add('hidden');
                }
                
                if (checkoutButton) {
                    checkoutButton.classList.remove('pulse-button');
                    checkoutButton.disabled = true;
                    checkoutButton.classList.add('opacity-50', 'cursor-not-allowed');
                }
            }
        }

        function saveDB() {
            if (db && currentSale) {
                db[activeKey] = currentSale;
                localStorage.setItem('restaurantDB', JSON.stringify(db));
            }
        }

        function goBack() {
            saveDB();
            window.location.href = "<?php echo e(route('admin.sales.index')); ?>";
        }

        function sendOrder() {
            if (!currentSale.items || currentSale.items.length === 0) {
                showEmptyCartNotification();
                return;
            }

            // Deshabilitar botón durante el proceso
            const checkoutButton = document.getElementById('checkout-button');
            const originalText = checkoutButton.innerHTML;
            checkoutButton.disabled = true;
            checkoutButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <span>Procesando...</span>';

            // Preparar datos para enviar
            const saleData = {
                items: currentSale.items.map(item => ({
                    pId: item.pId,
                    qty: parseFloat(item.qty),
                    price: parseFloat(item.price),
                    note: item.note || ''
                }))
            };

            // Enviar al servidor
            fetch('<?php echo e(route("admin.sales.process")); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(saleData)
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(err => {
                        throw new Error(err.message || 'Error en el servidor');
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Mostrar notificación de éxito
                    const notification = document.getElementById('add-to-cart-notification');
                    const productNameEl = document.getElementById('notification-product-name');
                    const notificationDiv = notification.querySelector('div');
                    
                    notificationDiv.className = 'bg-gradient-to-r from-emerald-500 to-green-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-emerald-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]';
                    notificationDiv.querySelector('i').className = 'fas fa-check-circle text-2xl';
                    notificationDiv.querySelector('p').textContent = '¡Venta procesada!';
                    productNameEl.textContent = `N° ${data.data.number}`;
                    
                    notification.classList.add('notification-show');
                    
                    // Limpiar carrito
                    currentSale.status = 'completed';
                    currentSale.items = [];
                    currentSale.total = 0;
                    saveDB();
                    
                    // Redirigir después de 2 segundos
                    setTimeout(() => {
                        window.location.href = "<?php echo e(route('admin.sales.index')); ?>";
                    }, 2000);
                } else {
                    // Mostrar error
                    alert('Error: ' + (data.message || 'Error desconocido'));
                    checkoutButton.disabled = false;
                    checkoutButton.innerHTML = originalText;
                }
            })
            .catch(error => {
                console.error('Error completo:', error);
                alert('Error al procesar la venta: ' + error.message);
                checkoutButton.disabled = false;
                checkoutButton.innerHTML = originalText;
            });
        }

        function showEmptyCartNotification() {
            const notification = document.getElementById('add-to-cart-notification');
            const productNameEl = document.getElementById('notification-product-name');
            const notificationDiv = notification.querySelector('div');
            
            notificationDiv.className = 'bg-gradient-to-r from-amber-500 to-orange-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-amber-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]';
            notificationDiv.querySelector('i').className = 'fas fa-exclamation-triangle text-2xl';
            notificationDiv.querySelector('p').textContent = 'Carrito vacío';
            productNameEl.textContent = 'Agrega productos antes de cobrar';
            
            notification.classList.add('notification-show');
            
            setTimeout(() => {
                hideNotification();
                // Restaurar estilo original
                setTimeout(() => {
                    notificationDiv.className = 'bg-gradient-to-r from-green-500 to-emerald-600 text-white px-6 py-4 rounded-xl shadow-2xl border border-green-400/30 backdrop-blur-sm flex items-center gap-4 min-w-[320px]';
                    notificationDiv.querySelector('i').className = 'fas fa-check text-2xl';
                    notificationDiv.querySelector('p').textContent = '¡Producto agregado!';
                }, 500);
            }, 3000);
        }

        function openChargeModal() {
            if (!currentSale.items || currentSale.items.length === 0) {
                showEmptyCartNotification();
                return;
            }

            // Actualizar cliente
            const modalClientName = document.getElementById('modal-client-name');
            if (modalClientName) {
                modalClientName.textContent = currentSale.clientName || 'Público General';
            }
            // Actualizar items
            const container = document.getElementById('modal-items-list');
            if (container) {
                container.innerHTML = '';
                
                currentSale.items.forEach(item => {
                    const prod = productsDB.find(p => p.id === item.pId);
                    if (!prod) return;
                    const productBranch = productsBranches.find(p => p.id === item.pId);
                    if (!productBranch) return;
                    
                    const itemPrice = parseFloat(productBranch.price) || 0;
                    const itemTotal = itemPrice * item.qty;

                    const itemDiv = document.createElement('div');
                    itemDiv.className = 'flex justify-between items-center p-2 rounded-lg bg-white dark:bg-slate-800 border border-gray-200 dark:border-slate-700';
                    itemDiv.innerHTML = `
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <span class="flex items-center justify-center w-6 h-6 text-[10px] font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 rounded-full shrink-0">
                                ${item.qty}x
                            </span>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-gray-900 dark:text-white truncate">${prod.name}</p>
                                <p class="text-[10px] text-gray-500 dark:text-gray-400">$${itemPrice.toFixed(2)} c/u</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold text-gray-900 dark:text-white">$${itemTotal.toFixed(2)}</span>
                    `;
                    container.appendChild(itemDiv);
                });
            }

            // Actualizar totales
            const subtotalEl = document.getElementById('ticket-subtotal');
            const taxEl = document.getElementById('ticket-tax');
            const totalEl = document.getElementById('ticket-total');
            
            if (subtotalEl && taxEl && totalEl) {
                const subtotal = parseFloat(subtotalEl.innerText.replace('$', ''));
                const tax = parseFloat(taxEl.innerText.replace('$', ''));
                const total = parseFloat(totalEl.innerText.replace('$', ''));

                const modalSubtotal = document.getElementById('modal-subtotal');
                const modalTax = document.getElementById('modal-tax');
                const modalTotal = document.getElementById('modal-total');

                if (modalSubtotal) modalSubtotal.textContent = `$${subtotal.toFixed(2)}`;
                if (modalTax) modalTax.textContent = `$${tax.toFixed(2)}`;
                if (modalTotal) modalTotal.textContent = `$${total.toFixed(2)}`;
            }

            // Mostrar modal
            const modal = document.getElementById('charge-modal');
            if (modal) {
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            }
        }

        function closeChargeModal() {
            const modal = document.getElementById('charge-modal');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        }

        init();

        // Configurar eventos del modal después de que todo cargue
        document.addEventListener('DOMContentLoaded', function() {
            // Botón X de cerrar
            const closeBtn = document.getElementById('modal-close-btn');
            if (closeBtn) {
                closeBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeChargeModal();
                };
            }

            // Botón Cancelar
            const cancelBtn = document.getElementById('modal-cancel-btn');
            if (cancelBtn) {
                cancelBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeChargeModal();
                };
            }

            // Botón Confirmar Pago
            const confirmBtn = document.getElementById('modal-confirm-btn');
            if (confirmBtn) {
                confirmBtn.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeChargeModal();
                    sendOrder();
                };
            }

            // Cerrar al hacer clic en el backdrop
            const backdrop = document.getElementById('modal-backdrop');
            if (backdrop) {
                backdrop.onclick = function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    closeChargeModal();
                };
            }

            // Prevenir cierre al hacer clic dentro del modal
            const modalContent = document.querySelector('#charge-modal > div > div');
            if (modalContent) {
                modalContent.onclick = function(e) {
                    e.stopPropagation();
                };
            }
        });
    </script>

    
    <?php echo $__env->make('sales.charge', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\Restaurante\resources\views/sales/create.blade.php ENDPATH**/ ?>