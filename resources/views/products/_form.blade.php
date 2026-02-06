<div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Codigo</label>
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
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Descripcion</label>
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
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Abreviatura</label>
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
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipo</label>
        <select
            name="type"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="PRODUCT" @selected(old('type', $product->type ?? 'PRODUCT') === 'PRODUCT')>Producto</option>
            <option value="COMPONENT" @selected(old('type', $product->type ?? 'PRODUCT') === 'COMPONENT')>Componente</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Categoria</label>
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
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Unidad base</label>
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
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Kardex</label>
        <select
            name="kardex"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="N" @selected(old('kardex', $product->kardex ?? 'N') === 'N')>No</option>
            <option value="S" @selected(old('kardex', $product->kardex ?? 'N') === 'S')>Si</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Compuesto</label>
        <select
            name="is_compound"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="N" @selected(old('is_compound', $product->is_compound ?? 'N') === 'N')>No</option>
            <option value="S" @selected(old('is_compound', $product->is_compound ?? 'N') === 'S')>Si</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Complemento</label>
        <select
            name="complement"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="NO" @selected(old('complement', $product->complement ?? 'NO') === 'NO')>No</option>
            <option value="HAS" @selected(old('complement', $product->complement ?? 'NO') === 'HAS')>Tiene complementos</option>
            <option value="IS" @selected(old('complement', $product->complement ?? 'NO') === 'IS')>Es complemento</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Modo complemento</label>
        <select
            name="complement_mode"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="" @selected(old('complement_mode', $product->complement_mode ?? '') === '')>Sin modo</option>
            <option value="ALL" @selected(old('complement_mode', $product->complement_mode ?? '') === 'ALL')>Todo gratis</option>
            <option value="QUANTITY" @selected(old('complement_mode', $product->complement_mode ?? '') === 'QUANTITY')>Cantidad gratis</option>
        </select>
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Clasificacion</label>
        <select
            name="classification"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="GOOD" @selected(old('classification', $product->classification ?? 'GOOD') === 'GOOD')>Bien</option>
            <option value="SERVICE" @selected(old('classification', $product->classification ?? 'GOOD') === 'SERVICE')>Servicio</option>
        </select>
    </div>

    <div class="lg:col-span-2" x-data="{ 
        imagePreview: '{{ isset($product) && $product->image && !empty($product->image) ? asset('storage/' . $product->image) : '' }}',
        fileName: '{{ isset($product) && $product->image && !empty($product->image) ? basename($product->image) : '' }}'
    }">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Imagen (opcional)</label>
        
        <!-- Vista previa de la imagen actual -->
        <div x-show="imagePreview" class="mb-2 flex items-center gap-3 p-2 bg-gray-50 dark:bg-gray-800/50 rounded-lg border border-gray-200 dark:border-gray-700">
            <img :src="imagePreview" alt="Vista previa" 
                class="h-16 w-16 object-cover rounded border border-gray-300 dark:border-gray-600 shadow-sm">
            <div class="flex-1 min-w-0">
                <p class="text-xs font-medium text-gray-700 dark:text-gray-300 truncate" x-text="fileName || 'Imagen actual'"></p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Vista previa</p>
            </div>
        </div>

        <input
            type="file"
            name="image"
            id="image-input"
            accept="image/jpeg,image/png,image/jpg,image/gif,image/webp"
            @change="
                const file = $event.target.files[0];
                if (file) {
                    if (file.size === 0) {
                        console.warn('Archivo vacío detectado');
                        $event.target.value = '';
                        alert('El archivo seleccionado está vacío. Por favor, elige otra imagen.');
                        imagePreview = '{{ isset($product) && $product->image && !empty($product->image) ? asset('storage/' . $product->image) : '' }}';
                        fileName = '{{ isset($product) && $product->image && !empty($product->image) ? basename($product->image) : '' }}';
                    } else if (file.size > 2048 * 1024) {
                        console.warn('Archivo demasiado grande');
                        $event.target.value = '';
                        alert('El archivo es demasiado grande. Máximo 2MB.');
                        imagePreview = '{{ isset($product) && $product->image && !empty($product->image) ? asset('storage/' . $product->image) : '' }}';
                        fileName = '{{ isset($product) && $product->image && !empty($product->image) ? basename($product->image) : '' }}';
                    } else {
                        fileName = file.name;
                        const reader = new FileReader();
                        reader.onload = (e) => { imagePreview = e.target.result; };
                        reader.onerror = () => {
                            console.error('Error al leer el archivo');
                            $event.target.value = '';
                            alert('Error al leer el archivo. Por favor, intenta con otra imagen.');
                        };
                        reader.readAsDataURL(file);
                    }
                } else {
                    imagePreview = '{{ isset($product) && $product->image && !empty($product->image) ? asset('storage/' . $product->image) : '' }}';
                    fileName = '{{ isset($product) && $product->image && !empty($product->image) ? basename($product->image) : '' }}';
                }
            "
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 file:mr-4 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/50 dark:file:text-blue-300"
        />
        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
            JPG, PNG, GIF, WEBP • Máximo 2MB
        </p>
        @error('image')
            <p class="mt-1 text-xs text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <div class="lg:col-span-3">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Caracteristicas</label>
        <textarea
            name="features"
            rows="3"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            placeholder="Ingrese las caracteristicas"
        >{{ old('features', $product->features ?? '') }}</textarea>
    </div>
</div>
