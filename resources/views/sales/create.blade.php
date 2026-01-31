@extends('layouts.app')

@section('content')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        .pos-page { font-family: 'Poppins', sans-serif; }
        .fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        .no-scrollbar::-webkit-scrollbar { display: none; }
    </style>

    <div class="pos-page text-slate-800">
        <x-common.page-breadcrumb
            pageTitle="Ventas"
            :crumbs="[
                ['label' => 'Ventas', 'url' => route('admin.sales.index')],
                ['label' => 'Nueva Venta'],
            ]"
        />

        <x-common.component-card title="Nueva venta" desc="">
            <div class="flex flex-col gap-6">
             

                <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px] fade-in">
                    <main class="min-w-0">
                        <h3 class="font-bold text-slate-700 mb-4">Menu</h3>
                        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5" id="products-grid"></div>
                    </main>

                    <aside class="w-full bg-white border border-gray-200 rounded-2xl flex flex-col h-full shadow-2xl">
                        <div class="p-6 border-b border-gray-100">
                            <div class="flex justify-between items-center">
                                <h3 class="text-lg font-bold text-slate-800">Orden Actual</h3>
                                <span id="status-badge" class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs font-bold">En curso</span>
                            </div>
                        </div>

                        <div id="cart-container" class="flex-1 overflow-y-auto px-5 py-4 space-y-3"></div>

                        <div class="p-6 bg-gray-50 border-t border-gray-200 rounded-t-3xl shadow-[0_-5px_20px_rgba(0,0,0,0.03)]">
                            <div class="space-y-2 mb-4 text-sm">
                                <div class="flex justify-between text-gray-500"><span>Subtotal</span><span class="font-medium text-slate-700" id="ticket-subtotal">$0.00</span></div>
                                <div class="flex justify-between text-gray-500"><span>Impuestos (10%)</span><span class="font-medium text-slate-700" id="ticket-tax">$0.00</span></div>
                                <div class="flex justify-between items-center pt-3 border-t border-dashed border-gray-300 mt-2">
                                    <span class="text-lg font-bold text-slate-800">Total</span>
                                    <span class="text-2xl font-bold text-brand-600" id="ticket-total">$0.00</span>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <button onclick="goBack()" class="py-3 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-100">Guardar</button>
                                <button onclick="sendOrder()" class="py-3 rounded-xl bg-brand-600 text-white font-bold shadow-lg shadow-brand-500/30 hover:bg-brand-700 active:scale-95 transition-all flex justify-center items-center gap-2">
                                    <span>Enviar</span> <i class="fas fa-paper-plane text-xs"></i>
                                </button>
                            </div>
                        </div>
                    </aside>
                </div>
            </div>
        </x-common.component-card>
    </div>

    <script>
        const productsDB = [
            { id: 1, name: "Burger Clasica", price: 12.00, img: "https://images.unsplash.com/photo-1568901346375-23c9450c58cd?w=400" },
            { id: 2, name: "Pizza Pepperoni", price: 22.00, img: "https://images.unsplash.com/photo-1628840042765-356cda07504e?w=400" },
            { id: 3, name: "Coca Cola", price: 3.50, img: "https://images.unsplash.com/photo-1622483767028-3f66f32aef97?w=400" },
            { id: 4, name: "Cheesecake", price: 8.00, img: "https://images.unsplash.com/photo-1524351199678-941a58a3df26?w=400" }
        ];

        let currentTable = {
          
            status: 'occupied',
            items: []
        };

        function init() {

            renderProducts();
            renderTicket();
        }

        function renderProducts() {
            const grid = document.getElementById('products-grid');
            productsDB.forEach(prod => {
                const el = document.createElement('div');
                el.className = "bg-white p-4 rounded-[1.5rem] shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all cursor-pointer group border border-transparent hover:border-brand-100";
                el.onclick = () => addToCart(prod);
                el.innerHTML = `
                    <div class="relative h-28 w-full rounded-2xl overflow-hidden mb-3">
                        <img src="${prod.img}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
                    </div>
                    <h4 class="font-bold text-slate-700 text-sm">${prod.name}</h4>
                    <div class="flex justify-between items-center mt-2">
                        <span class="font-bold text-brand-600">$${prod.price.toFixed(2)}</span>
                        <div class="h-8 w-8 bg-gray-100 rounded-full flex items-center justify-center text-gray-500 group-hover:bg-brand-500 group-hover:text-white transition-colors"><i class="fas fa-plus text-xs"></i></div>
                    </div>`;
                grid.appendChild(el);
            });
        }

        function addToCart(prod) {
            const existing = currentTable.items.find(i => i.pId === prod.id);
            if (existing) {
                existing.qty++;
            } else {
                currentTable.items.push({ pId: prod.id, qty: 1, price: prod.price, note: "" });
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
            container.innerHTML = '';
            let subtotal = 0;

            if (currentTable.items.length === 0) {
                container.innerHTML = `<div class="flex flex-col items-center justify-center h-40 text-gray-300 opacity-60"><i class="fas fa-utensils text-4xl mb-2"></i><p>Vacio</p></div>`;
            }

            currentTable.items.forEach((item, index) => {
                const prod = productsDB.find(p => p.id === item.pId);
                subtotal += item.price * item.qty;
                const hasNote = item.note && item.note.trim() !== "";

                const row = document.createElement('div');
                row.className = "bg-white border border-gray-100 rounded-xl p-3 shadow-sm group";
                row.innerHTML = `
                    <div class="flex gap-3 mb-2">
                        <img src="${prod.img}" class="h-12 w-12 rounded-lg object-cover bg-gray-100">
                        <div class="flex-1">
                            <div class="flex justify-between">
                                <span class="font-bold text-slate-700 text-sm">${prod.name}</span>
                                <span class="font-bold text-slate-800 text-sm">$${(item.price * item.qty).toFixed(2)}</span>
                            </div>
                            <div class="flex justify-between items-center mt-1">
                                <button onclick="toggleNoteInput(${index})" class="text-[10px] flex items-center gap-1 ${hasNote ? 'text-brand-500' : 'text-gray-400 hover:text-brand-500'}">
                                    <i class="fas fa-comment-alt"></i> ${hasNote ? 'Editar nota' : 'Nota'}
                                </button>
                                <div class="flex items-center gap-2 bg-gray-50 border border-gray-100 rounded px-2">
                                    <button onclick="updateQty(${index}, -1)" class="text-gray-400 hover:text-red-500 text-xs px-1">-</button>
                                    <span class="text-xs font-bold text-slate-700 w-3 text-center">${item.qty}</span>
                                    <button onclick="updateQty(${index}, 1)" class="text-gray-400 hover:text-brand-500 text-xs px-1">+</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="note-box-${index}" class="${hasNote ? '' : 'hidden'} mt-1 animate-fadeIn">
                        <input type="text" value="${item.note}" oninput="saveNote(${index}, this.value)" placeholder="Ej: Sin cebolla..." class="w-full text-xs bg-orange-50 border border-brand-100 rounded p-1 text-slate-700 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    </div>
                `;
                container.appendChild(row);
            });

            const tax = subtotal * 0.10;
            document.getElementById('ticket-subtotal').innerText = `$${subtotal.toFixed(2)}`;
            document.getElementById('ticket-tax').innerText = `$${tax.toFixed(2)}`;
            document.getElementById('ticket-total').innerText = `$${(subtotal + tax).toFixed(2)}`;
        }

        function saveDB() {}

        function goBack() {
            saveDB();
            window.location.href = "{{ route('admin.sales.index') }}";
        }

        function sendOrder() {
            if (confirm("Enviar pedido a cocina?")) {
                saveDB();
                window.location.href = "{{ route('admin.sales.index') }}";
            }
        }

        init();
    </script>
@endsection
