<div class="grid w-full min-w-0 grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4" 
     x-data="{
        // Variable que controla si se muestran los campos (True si NO es ingrediente)
        showComplements: '{{ old('type', $product->type ?? 'PRODUCT') }}'.trim() !== 'INGREDENT',
        complementValue: '{{ old('complement', $product->complement ?? 'NO') }}',
        complementMode: '{{ old('complement_mode', $product->complement_mode ?? '') }}',
        classificationValue: '{{ old('classification', $product->classification ?? 'GOOD') }}',
        complements: @json(old('complements', [])),

        addComplement() { this.complements.push({ product: '', qty: 1 }); },
        removeComplement(i) { this.complements.splice(i, 1); },

        handleTypeChange(e) {
            // Verificamos si el valor seleccionado es ingrediente
            const isIngredient = e.target.value.trim() === 'INGREDENT';

            // Si es ingrediente, ocultamos (false). Si no, mostramos (true).
            this.showComplements = !isIngredient;

            // Si se convierte en ingrediente, forzamos los valores a 'NO' y '' (vacío)
            if (isIngredient) {
                this.complementValue = 'NO';
                this.complementMode = '';
                this.classificationValue = 'GOOD';
            }
        }
     }">

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Codigo <span class="text-red-500">*</span></label>
        <input
            type="text"
            name="code"
            value="{{ old('code', $product->code ?? '') }}"
            required
            placeholder="Ingrese el codigo"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nombre <span class="text-red-500">*</span></label>
        <input
            type="text"
            name="description"
            value="{{ old('description', $product->description ?? '') }}"
            required
            placeholder="Ingrese la descripcion"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Abreviatura <span class="text-red-500">*</span></label>
        <input
            type="text"
            name="abbreviation"
            value="{{ old('abbreviation', $product->abbreviation ?? '') }}"
            required
            placeholder="Ingrese la abreviatura"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipo <span class="text-red-500">*</span></label>
        <select
            name="type"
            required
            @change="handleTypeChange($event)"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="PRODUCT" @selected(old('type', $product->type ?? 'PRODUCT') === 'PRODUCT')>Producto final</option>
            <option value="INGREDENT" @selected(old('type', $product->type ?? 'PRODUCT') === 'INGREDENT')>Ingrediente</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Categoria <span class="text-red-500">*</span></label>
        <select
            name="category_id"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione categoria</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id ?? '') == $category->id)>
                    {{ $category->description }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Unidad base <span class="text-red-500">*</span></label>
        <select
            name="base_unit_id"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione unidad</option>
            @foreach ($units as $unit)
                <option value="{{ $unit->id }}" @selected(old('base_unit_id', $product->base_unit_id ?? '') == $unit->id)>
                    {{ $unit->description }}
                </option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kardex <span class="text-red-500">*</span></label>
        <select
            name="kardex"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="N" @selected(old('kardex', $product->kardex ?? 'N') === 'N')>No</option>
            <option value="S" @selected(old('kardex', $product->kardex ?? 'N') === 'S')>Si</option>
        </select>
    </div>

        <div x-show="showComplements" x-transition>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Complemento <span class="text-red-500" x-show="showComplements">*</span></label>
        <select
            name="complement"
            x-model="complementValue"
            x-bind:required="showComplements"
            x-ref="complementSelect"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="NO" @selected(old('complement', $product->complement ?? 'NO') === 'NO')>No</option>
            <option value="HAS" @selected(old('complement', $product->complement ?? 'NO') === 'HAS')>Tiene complementos</option>
            <option value="IS" @selected(old('complement', $product->complement ?? 'NO') === 'IS')>Es complemento</option>
        </select>
    </div>

    <div x-show="showComplements" x-transition>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Modo complemento</label>
        <select
            name="complement_mode"
            x-model="complementMode"
            x-ref="modeSelect"
            x-bind:required="showComplements"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="" @selected(old('complement_mode', $product->complement_mode ?? '') === '')>Sin modo</option>
            <option value="ALL" @selected(old('complement_mode', $product->complement_mode ?? '') === 'ALL')>Todo gratis</option>
            <option value="QUANTITY" @selected(old('complement_mode', $product->complement_mode ?? '') === 'QUANTITY')>Cantidad gratis</option>
        </select>
    </div>

    <div x-show="showComplements" x-transition>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Clasificacion <span class="text-red-500" x-show="showComplements">*</span></label>
        <select
            name="classification"
            x-model="classificationValue"
            x-bind:required="showComplements"
            x-ref="classificationSelect"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="GOOD" x-bind:selected="classificationValue === 'GOOD'">Bien</option>
            <option value="SERVICE" x-bind:selected="classificationValue === 'SERVICE'">Servicio</option>
        </select>
    </div>

    <div class="col-span-full grid grid-cols-1 gap-5 sm:grid-cols-2">
        <div x-data="{ 
                imagePreview: '{{ isset($product) && $product->image ? asset('storage/' . $product->image) : '' }}',
                fileName: '{{ isset($product) && $product->image ? basename($product->image) : '' }}',
                defaultPlaceholder: 'https://placehold.co/100x100?text=Sin+Imagen',
                
                showPreview(event) {
                    const file = event.target.files[0];
                    if (!file) {
                        this.imagePreview = '{{ isset($product) && $product->image ? asset('storage/' . $product->image) : '' }}';
                        this.fileName = '{{ isset($product) && $product->image ? basename($product->image) : '' }}';
                        return;
                    }

                    if (file.size > 2048 * 1024) {
                        alert('El archivo es demasiado grande. Máximo 2MB.');
                        event.target.value = '';
                        return;
                    }

                    this.fileName = file.name;
                    const reader = new FileReader();
                    reader.onload = (e) => { 
                        this.imagePreview = e.target.result; 
                    };
                    reader.readAsDataURL(file);
                },

                removeImage() {
                    this.imagePreview = '';
                    this.fileName = '';
                    document.getElementById('image-input').value = '';
                }
            }">
        
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                Imagen (opcional)
            </label>
            
            <div class="mb-3 flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
                
                <img :src="imagePreview || defaultPlaceholder" alt="Vista previa" 
                    class="h-16 w-16 object-cover rounded border border-gray-300 dark:border-gray-600 shadow-sm bg-gray-200 dark:bg-gray-700">
                
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" 
                    x-text="fileName || 'Sin archivo seleccionado'">
                    </p>
                    
                    <template x-if="imagePreview">
                        <button type="button" @click="removeImage()" 
                            class="text-[10px] text-red-600 hover:text-red-800 font-semibold uppercase tracking-wider">
                            Quitar archivo
                        </button>
                    </template>
                    
                    <template x-if="!imagePreview">
                        <span class="text-[10px] text-gray-400 uppercase tracking-wider">Esperando imagen...</span>
                    </template>
                </div>
            </div>

            <input
                type="file"
                name="image"
                id="image-input"
                accept="image/*"
                @change="showPreview($event)"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/50 dark:file:text-blue-300"
            />

            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                JPG, PNG, GIF, WEBP • Máximo 2MB
            </p>

            @error('image')
                <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Caracteristicas</label>
            <textarea
                name="features"
                rows="4"
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                placeholder="Ingrese las caracteristicas"
            >{{ old('features', $product->features ?? '') }}</textarea>
        </div>
    </div>

    {{-- Datos de sucursal (product_branch) - Precio, stock, etc. para la sucursal actual --}}
    @php
        $currentBranch = $currentBranch ?? null;
        $taxRates = $taxRates ?? collect();
        $productBranch = $productBranch ?? null;
    @endphp
    @if ($currentBranch)
        <div class="col-span-full mt-8">
            <div class="rounded-xl border border-gray-200 bg-gradient-to-br from-slate-50 to-gray-50/80 dark:from-gray-800/50 dark:to-slate-900/50 dark:border-gray-700 overflow-hidden shadow-sm">
                {{-- Header de la sección --}}
                <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-200/80 dark:border-gray-700/80 bg-white/60 dark:bg-gray-800/40">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                        <i class="ri-store-2-line text-xl"></i>
                    </div>
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white/95">Datos en sucursal</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ $currentBranch->legal_name }}</p>
                    </div>
                </div>

                {{-- Contenido: Precio y venta | Inventario --}}
                <div class="p-5 space-y-6">
                    {{-- Bloque: Precio y venta --}}
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Precio y venta</p>
                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div class="sm:col-span-1">
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-money-dollar-circle-line text-gray-400 text-base"></i>
                                    Precio <span class="text-red-500">*</span>
                                </label>
                                <input type="number" step="0.01" name="product_branch_price"
                                    value="{{ old('product_branch_price', $productBranch->price ?? 0) }}"
                                    placeholder="0.00"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                            <div class="sm:col-span-1">
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-percent-line text-gray-400 text-base"></i>
                                    Tasa de impuesto <span class="text-red-500">*</span>
                                </label>
                                <select name="product_branch_tax_rate_id"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90">
                                    <option value="">Seleccione tasa</option>
                                    @foreach ($taxRates as $taxRate)
                                        <option value="{{ $taxRate->id }}" @selected(old('product_branch_tax_rate_id', $productBranch->tax_rate_id ?? '') == $taxRate->id)>
                                            {{ $taxRate->description }} ({{ $taxRate->tax_rate }}%)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Bloque: Inventario --}}
                    <div>
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Inventario</p>
                        <div class="grid gap-4 sm:grid-cols-3">
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-box-3-line text-gray-400 text-base"></i>
                                    Stock actual <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="product_branch_stock"
                                    value="{{ old('product_branch_stock', $productBranch->stock ?? 0) }}"
                                    placeholder="0"
                                    min="0"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30 @error('product_branch_stock') border-red-500 dark:border-red-500 @enderror" />
                                @error('product_branch_stock')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-arrow-down-circle-line text-gray-400 text-base"></i>
                                    Stock mínimo
                                </label>
                                <input type="number" step="0.01" name="product_branch_stock_minimum"
                                    value="{{ old('product_branch_stock_minimum', $productBranch->stock_minimum ?? 0) }}"
                                    placeholder="0"
                                    min="0"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-arrow-up-circle-line text-gray-400 text-base"></i>
                                    Stock máximo
                                </label>
                                <input type="number" step="0.01" name="product_branch_stock_maximum"
                                    value="{{ old('product_branch_stock_maximum', $productBranch->stock_maximum ?? 0) }}"
                                    placeholder="0"
                                    min="0"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-arrow-right-circle-line text-gray-400 text-base"></i>
                                    Stock mínimo de venta
                                </label>
                                <input type="number" step="0.01" name="product_branch_minimum_sell"
                                    value="{{ old('product_branch_minimum_sell', $productBranch->minimum_sell ?? 0) }}"
                                    placeholder="0"
                                    min="0"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                            <div>
                                <label class="mb-1.5 flex items-center gap-1.5 text-sm font-medium text-gray-700 dark:text-gray-400">
                                    <i class="ri-arrow-left-circle-line text-gray-400 text-base"></i>
                                    Stock mínimo de compra
                                </label>
                                <input type="number" step="0.01" name="product_branch_minimum_purchase"
                                    value="{{ old('product_branch_minimum_purchase', $productBranch->minimum_purchase ?? 0) }}"
                                    placeholder="0"
                                    min="0"
                                    class="dark:bg-dark-900 shadow-theme-xs focus:border-blue-400 focus:ring-blue-500/20 dark:focus:border-blue-600 dark:focus:ring-blue-500/30 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-2 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white/90 dark:placeholder:text-white/30" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>