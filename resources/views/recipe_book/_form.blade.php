@php
    // Definimos la variable readonly por si no viene seteada
    $isReadonly = $readonly ?? false;
@endphp

<div x-data="{
        ingredients: {{ \Illuminate\Support\Js::from(old('ingredients', data_get($recipe, 'ingredients') ?? [])) }},
        product_list: {{ \Illuminate\Support\Js::from($ingredientsList ?? []) }}, // Usamos ?? [] por seguridad

        addIngredient() {
            this.ingredients.push({
                product_id: '',
                unit_id: '',
                quantity: 1,
                notes: '',
                unit_cost: 0,
                order: this.ingredients.length
            });
        },

        removeIngredient(index) {
            this.ingredients.splice(index, 1);
        },

        calculateTotalCost() {
            return this.ingredients.reduce((total, item) => {
                return total + (parseFloat(item.quantity || 0) * parseFloat(item.unit_cost || 0));
            }, 0).toFixed(2);
        },

        isProductSelected(productId) {
            return this.ingredients.some(i => i.product_id == productId);
        }
    }" class="w-full max-w-5xl mx-auto space-y-6 pb-10">

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="border-b border-gray-100 px-6 py-4 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300">
                    <i class="ri-file-list-3-fill"></i>
                </span>
                Información de la Receta
            </h3>
        </div>

        @if (session('error'))
            <div class="mb-5 p-4 rounded-lg bg-red-50 border border-red-200 m-6">
                <div class="flex items-center mb-2">
                    <i class="ri-error-warning-fill text-red-500 mr-2 text-xl"></i>
                    <span class="font-bold text-red-800">Error:</span>
                </div>
                <div class="text-sm text-red-700">
                    {{ session('error') }}
                </div>
            </div>
        @endif
        
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                <div x-data='{ 
                        open: false, 
                        search: "", 
                        selectedId: "{{ old("product_id", data_get($recipe, "product_id")) }}",
                        products: @json($productsForRecipe ?? []), // Usamos ?? [] por seguridad

                        init() {
                            if (this.selectedId) {
                                const found = this.products.find(p => p.id == this.selectedId);
                                if (found) this.search = found.description;
                            }
                        },

                        get filtered() {
                            if (this.search === "") return this.products;
                            return this.products.filter(p => 
                                p.description.toLowerCase().includes(this.search.toLowerCase()) ||
                                p.code.toLowerCase().includes(this.search.toLowerCase())
                            );
                        },

                        select(product) {
                            this.selectedId = product.id;
                            this.search = product.description;
                            this.open = false;
                        }
                    }'
                    @mousedown.outside="open = false"
                    @keydown.escape.window="open = false"
                    >
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">
                        Producto a Producir <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">
                        <input 
                            type="text" 
                            x-model="search"
                            @if(!$isReadonly)
                                @focus="open = true"
                                @mousedown="open = true"
                            @endif
                            placeholder="Seleccionar producto..."
                            class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white transition-all {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                            autocomplete="off"
                            @disabled($isReadonly)
                        >
                        
                        @if(!$isReadonly)
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                            <i class="ri-arrow-down-s-line transition-transform duration-200" :class="{'rotate-180': open}"></i>
                        </div>

                        <ul 
                            x-show="open" 
                            x-transition.opacity
                            class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:border-gray-700"
                            style="display: none;"
                        >
                            <li x-show="filtered.length === 0" class="px-4 py-3 text-sm text-gray-500">
                                No se encontraron productos.
                            </li>

                            <template x-for="p in filtered" :key="p.id">
                                <li 
                                    @mousedown.prevent="select(p)"
                                    class="cursor-pointer select-none px-4 py-2.5 text-sm text-gray-800 hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-gray-700 transition-colors flex justify-between items-center"
                                >
                                    <span x-text="p.description" :class="{'font-bold text-blue-600': selectedId == p.id}"></span>
                                    <span class="text-xs text-gray-400 font-mono" x-text="p.code"></span>
                                </li>
                            </template>
                        </ul>
                        @endif
                    </div>

                    <input type="hidden" name="product_id" x-model="selectedId">
                </div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Estado</label>
                        <select name="status" @disabled($isReadonly) class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white transition-all {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}">
                            <option value="A" @selected(old('status', data_get($recipe, 'status', 'A')) === 'A')>Activo</option>
                            <option value="I" @selected(old('status', data_get($recipe, 'status')) === 'I')>Inactivo</option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Tiempo (min)</label>
                        <div class="relative">
                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                <i class="ri-time-line text-gray-400"></i>
                            </div>
                            <input type="number" name="preparation_time" value="{{ old('preparation_time', data_get($recipe, 'preparation_time')) }}" @disabled($isReadonly) class="h-11 w-full rounded-lg border border-gray-300 bg-white pl-10 px-4 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white transition-all {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}" placeholder="0">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Rendimiento Final</label>
                    
                    <div class="flex items-center" 
                        x-data='{ 
                            open: false, 
                            search: "", 
                            selectedUnitId: "{{ old("yield_unit_id", data_get($recipe, "yield_unit_id")) }}",
                            units: @json($units ?? []),

                            init() {
                                if (this.selectedUnitId) {
                                    const found = this.units.find(u => u.id == this.selectedUnitId);
                                    if (found) this.search = found.description;
                                }
                            },

                            get filtered() {
                                if (this.search === "") return this.units;
                                return this.units.filter(u => 
                                    u.description.toLowerCase().includes(this.search.toLowerCase())
                                );
                            },

                            select(unit) {
                                this.selectedUnitId = unit.id;
                                this.search = unit.description;
                                this.open = false;
                            }
                        }'
                        @mousedown.outside="open = false"
                        @keydown.escape.window="open = false"
                        >
                        <input type="number" 
                            name="yield_quantity" 
                            value="{{ old('yield_quantity', data_get($recipe, 'yield_quantity', 1)) }}" 
                            @disabled($isReadonly)
                            class="h-11 w-24 rounded-l-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white z-10 {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                            placeholder="1">

                        <div class="relative flex-1">
                            <input 
                                type="text" 
                                x-model="search"
                                @if(!$isReadonly)
                                    @focus="open = true"
                                    @mousedown="open = true"
                                @endif
                                placeholder="Unidad..."
                                @disabled($isReadonly)
                                class="h-11 w-full rounded-r-lg border border-l-0 border-gray-300 bg-white px-4 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white transition-all {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                autocomplete="off"
                            >
                            
                            @if(!$isReadonly)
                            <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                <i class="ri-arrow-down-s-line transition-transform duration-200" :class="{'rotate-180': open}"></i>
                            </div>

                            <ul 
                                x-show="open" 
                                x-transition.opacity
                                class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-xl border border-gray-200 bg-white py-1 shadow-xl ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:border-gray-700"
                                style="display: none;"
                            >
                                <template x-for="u in filtered" :key="u.id">
                                    <li 
                                        @mousedown.prevent="select(u)"
                                        class="cursor-pointer select-none px-4 py-2.5 text-sm text-gray-800 hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-gray-700 transition-colors"
                                        :class="{'bg-blue-50 text-blue-700 font-bold': selectedUnitId == u.id}"
                                    >
                                        <span x-text="u.description"></span>
                                    </li>
                                </template>
                            </ul>
                            @endif
                        </div>

                        <input type="hidden" name="yield_unit_id" x-model="selectedUnitId">
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Método</label>
                    <select name="preparation_method" @disabled($isReadonly) class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white transition-all {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}">
                        <option value="">Seleccione...</option>
                        <option value="wok" @selected(old('preparation_method', data_get($recipe, 'preparation_method')) === 'wok')>🔥 Wok</option>
                        <option value="horno" @selected(old('preparation_method', data_get($recipe, 'preparation_method')) === 'horno')>🥯 Horno</option>
                        <option value="freidora" @selected(old('preparation_method', data_get($recipe, 'preparation_method')) === 'freidora')>🍟 Freidora</option>
                        <option value="frio" @selected(old('preparation_method', data_get($recipe, 'preparation_method')) === 'frio')>🥗 Frío</option>
                        <option value="manual" @selected(old('preparation_method', data_get($recipe, 'preparation_method')) === 'manual')>🔪 Manual</option>
                    </select>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300">Descripción / Historia</label>
                    <textarea name="description" rows="3" @disabled($isReadonly) class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white resize-none {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}" placeholder="Ej: Versión especial de la casa...">{{ old('description', data_get($recipe, 'description')) }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800 overflow-hidden">
        
        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800">
            <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span class="flex items-center justify-center w-8 h-8 rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-300">
                    <i class="ri-restaurant-2-fill"></i>
                </span>
                Ingredientes y Costos
            </h3>
            
            @if(!$isReadonly)
            <button type="button" @click="addIngredient()" class="h-9 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-gray-800 transition-all dark:bg-blue-600 dark:hover:bg-blue-500">
                <i class="ri-add-line"></i> Agregar
            </button>
            @endif
        </div>

        <div class="p-6">
            <div class="hidden md:grid md:grid-cols-12 gap-4 mb-3 px-2 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                <div class="col-span-5">Insumo / Producto</div>
                <div class="col-span-2">Cantidad</div>
                <div class="col-span-2">Costo Unit.</div>
                <div class="col-span-2">Total</div>
                @if(!$isReadonly) <div class="col-span-1 text-center">Borrar</div> @endif
            </div>

            <div class="space-y-3">
                <template x-for="(ingredient, index) in ingredients" :key="index">
                    <div class="group grid grid-cols-1 md:grid-cols-12 gap-4 items-center rounded-xl border border-gray-200 bg-white p-3 shadow-sm hover:border-blue-400 hover:shadow-md transition-all dark:border-gray-700 dark:bg-gray-900">
                        
                        <div class="col-span-1 md:col-span-5 relative"
                            x-data="{ 
                                open: false, 
                                search: '', 
                                
                                init() {
                                    if (ingredient.product_id) {
                                        const found = product_list.find(p => p.id == ingredient.product_id);
                                        if (found) this.search = found.description;
                                    }
                                },

                                get filteredProducts() {
                                    if (this.search === '') {
                                        return product_list.filter(p => !isProductSelected(p.id) || p.id == ingredient.product_id);
                                    }
                                    return product_list.filter(product => {
                                        const matchesSearch = product.description.toLowerCase().includes(this.search.toLowerCase());
                                        const isAlreadySelected = isProductSelected(product.id) && product.id != ingredient.product_id;
                                        return matchesSearch && !isAlreadySelected;
                                    });
                                },

                                selectProduct(product) {
                                    ingredient.product_id = product.id;
                                    this.search = product.description;
                                    ingredient.unit_cost = parseFloat(product.current_price || 0);
                                    this.open = false; 
                                },

                                closeDropdown() {
                                    this.open = false;
                                }
                            }"                            
                            @mousedown.outside="closeDropdown()"
                            @keydown.escape.window="closeDropdown()"
                            >
                            <label class="md:hidden text-xs font-bold text-gray-500 mb-1 block">Insumo</label>
                            
                            <div class="relative">
                                <input 
                                    type="text" 
                                    x-model="search"
                                    @if(!$isReadonly)
                                        @focus="open = true" 
                                        @click="open = true"
                                    @endif
                                    placeholder="Buscar insumo..."
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}"
                                    autocomplete="off"
                                    @disabled($isReadonly)
                                >
                                @if(!$isReadonly)
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-gray-400">
                                    <i class="ri-arrow-down-s-line transition-transform duration-200" :class="{'rotate-180': open}"></i>
                                </div>

                                <ul 
                                    x-show="open" 
                                    x-transition.opacity.duration.100ms
                                    class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-gray-700 border border-gray-100 dark:border-gray-600"
                                    style="display: none;"
                                >
                                    <li x-show="filteredProducts.length === 0" class="px-4 py-2 text-xs text-gray-500 cursor-default select-none">
                                        No se encontraron coincidencias.
                                    </li>

                                    <template x-for="product in filteredProducts" :key="product.id">
                                        <li 
                                            @mousedown.prevent="selectProduct(product)"
                                            class="cursor-pointer select-none px-4 py-2 text-sm text-gray-900 hover:bg-blue-50 hover:text-blue-700 dark:text-white dark:hover:bg-gray-700 transition-colors border-b border-gray-50 last:border-0 dark:border-gray-700"
                                        >
                                            <div class="flex justify-between items-center">
                                                <span x-text="product.description" class="font-medium"></span>
                                                <span class="text-xs text-gray-400 bg-gray-100 px-1.5 py-0.5 rounded dark:bg-gray-700">
                                                    S/ <span x-text="parseFloat(product.current_price).toFixed(2)"></span>
                                                </span>
                                            </div>
                                        </li>
                                    </template>
                                </ul>
                                @endif
                            </div>
                            
                            <input type="hidden" :name="`ingredients[${index}][product_id]`" x-model="ingredient.product_id">
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label class="md:hidden text-xs font-bold text-gray-500 mb-1 block">Cantidad</label>
                            <input 
                                type="number" 
                                :name="`ingredients[${index}][quantity]`" 
                                x-model="ingredient.quantity" 
                                step="0.01" 
                                class="h-10 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm text-center text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                placeholder="1"
                                @disabled($isReadonly)
                            >
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label class="md:hidden text-xs font-bold text-gray-500 mb-1 block">Costo Unit.</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-400 text-xs font-bold">S/</span>
                                <input 
                                    type="number" 
                                    :name="`ingredients[${index}][unit_cost]`" 
                                    x-model="ingredient.unit_cost" 
                                    step="0.01" 
                                    class="h-10 w-full rounded-lg border border-gray-300 bg-white pl-9 px-3 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}" 
                                    placeholder="0.00"
                                    @disabled($isReadonly)
                                >
                            </div>
                        </div>

                        <div class="col-span-1 md:col-span-2">
                            <label class="md:hidden text-xs font-bold text-gray-500 mb-1 block">Total</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-gray-400 text-xs font-bold">S/</span>
                                <input 
                                    type="text" 
                                    readonly
                                    :value="( (parseFloat(ingredient.quantity)||0) * (parseFloat(ingredient.unit_cost)||0) ).toFixed(2)"
                                    class="h-10 w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 px-3 text-sm font-bold text-gray-700 focus:outline-none cursor-default dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                >
                            </div>
                        </div>

                        @if(!$isReadonly)
                        <div class="col-span-1 md:col-span-1 flex justify-center">
                            <button type="button" @click="removeIngredient(index)" class="flex items-center justify-center w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition-colors" title="Eliminar fila">
                                <i class="ri-delete-bin-line text-lg"></i>
                            </button>
                        </div>
                        @endif

                    </div>
                </template>

                <div x-show="ingredients.length === 0" class="flex flex-col items-center justify-center py-12 rounded-xl border-2 border-dashed border-gray-300 bg-gray-50/50 dark:border-gray-700 dark:bg-gray-900/20">
                    <div class="p-4 bg-white rounded-full shadow-sm mb-3 dark:bg-gray-800">
                        <i class="ri-shopping-basket-2-line text-3xl text-blue-500"></i>
                    </div>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">Aún no hay ingredientes</p>
                    @if(!$isReadonly)
                    <button type="button" @click="addIngredient()" class="mt-2 text-sm font-semibold text-blue-600 hover:underline">
                        + Agregar primer ítem
                    </button>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200 dark:bg-gray-900 dark:border-gray-700">
            <div class="flex justify-end items-center gap-4">
                <span class="text-sm font-medium text-gray-500 uppercase tracking-wide">Costo Total Receta</span>
                <div class="flex items-baseline gap-1 text-gray-900 dark:text-white">
                    <span class="text-lg font-semibold text-gray-400">S/</span>
                    <span class="text-3xl font-bold" x-text="calculateTotalCost()"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <label class="mb-2 block text-sm font-semibold text-gray-700 dark:text-gray-300"><i class="ri-sticky-note-fill text-yellow-500"></i> Notas</label>
        <textarea name="notes" rows="3" @disabled($isReadonly) class="w-full rounded-lg border border-gray-300 bg-yellow-50/30 px-4 py-3 text-sm text-gray-800 focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none dark:border-gray-600 dark:bg-gray-900 dark:text-white resize-none {{ $isReadonly ? 'bg-gray-100 cursor-not-allowed' : '' }}">{{ old('notes', data_get($recipe, 'notes')) }}</textarea>
    </div>
</div>