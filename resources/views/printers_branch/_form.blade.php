@php
    $printer = $printer ?? null;
    $connection = old('connection_type', $printer?->connection_type ?? (filled($printer?->ip) ? 'network' : 'usb'));
    $assignedIds = old('product_branch_ids', $assignedProductBranchIds ?? ($printer ? $printer->productBranches->pluck('id')->toArray() : []));
    
    $productBranchesCollection = collect($productBranches ?? []);
    $allIds = $productBranchesCollection->pluck('id')->map(fn($id) => (int)$id)->values()->all();
    $groupedProducts = $productBranchesCollection->groupBy(fn($pb) => $pb->product?->category?->description ?? 'Sin Categoría');
@endphp

<div x-data="{
    connection: @js($connection),
    selectedProducts: @js(array_map('intval', $assignedIds)),
    allProductIds: @js($allIds),
    search: '',

    selectAll() {
        this.selectedProducts = [...this.allProductIds];
    },

    deselectAll() {
        this.selectedProducts = [];
    },

    toggleCategory(categoryIds) {
        const catIds = categoryIds.map(Number);
        const allSelected = catIds.every(id => this.selectedProducts.includes(id));
        if (allSelected) {
            this.selectedProducts = this.selectedProducts.filter(id => !catIds.includes(id));
        } else {
            const newSet = new Set([...this.selectedProducts, ...catIds]);
            this.selectedProducts = Array.from(newSet);
        }
    },

    isCategorySelected(categoryIds) {
        if (!categoryIds || categoryIds.length === 0) return false;
        return categoryIds.every(id => this.selectedProducts.includes(Number(id)));
    }
}" class="space-y-5">

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre visible</label>
            <input type="text" name="name" value="{{ old('name', $printer?->name) }}" required placeholder="Ej: Ticketera Cocina 1"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
            @error('name')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación física</label>
            <input type="text" name="location" value="{{ old('location', $printer?->location) }}" placeholder="Ej: Cocina caliente / Caja 1"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        </div>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de conexión</label>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="cursor-pointer rounded-xl border p-4 transition" :class="connection === 'usb' ? 'border-[#C43B25] bg-orange-50 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-700'">
                <input class="sr-only" type="radio" name="connection_type" value="usb" x-model="connection">
                <span class="flex items-center gap-3"><i class="ri-usb-line text-2xl text-[#C43B25]"></i><span><strong class="block text-sm text-gray-900 dark:text-white">USB / local</strong><small class="text-gray-500">Conectada físicamente a una PC con QZ Tray</small></span></span>
            </label>
            <label class="cursor-pointer rounded-xl border p-4 transition" :class="connection === 'network' ? 'border-[#C43B25] bg-orange-50 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-700'">
                <input class="sr-only" type="radio" name="connection_type" value="network" x-model="connection">
                <span class="flex items-center gap-3"><i class="ri-router-line text-2xl text-[#C43B25]"></i><span><strong class="block text-sm text-gray-900 dark:text-white">Red LAN</strong><small class="text-gray-500">La ticketera tiene IP propia</small></span></span>
            </label>
        </div>
    </div>

    <div x-show="connection === 'usb'" x-cloak class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-900 dark:bg-blue-500/5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">PC a la que está conectada</label>
                <select name="print_station_id" :required="connection === 'usb'" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Seleccionar estación...</option>
                    @foreach($stations ?? [] as $station)
                        <option value="{{ $station->id }}" @selected((string) old('print_station_id', $printer?->print_station_id) === (string) $station->id)>{{ $station->name }} · {{ $station->ip_address }}</option>
                    @endforeach
                </select>
                @error('print_station_id')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre exacto en Windows/QZ</label>
                <input name="driver_name" value="{{ old('driver_name', $printer?->driver_name) }}" placeholder="Ej: EPSON TM-T20III Receipt"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
    </div>

    <div x-show="connection === 'network'" x-cloak class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-500/5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">IP de la ticketera</label>
                <input name="ip" :required="connection === 'network'" value="{{ old('ip', $printer?->ip) }}" placeholder="192.168.1.50" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                @error('ip')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Puerto RAW</label>
                <input type="number" name="port" min="1" max="65535" value="{{ old('port', $printer?->port ?? 9100) }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho de papel</label>
            <select name="width" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white">
                <option value="80" @selected((string) old('width', $printer?->width ?? '80') === '80')>80 mm</option>
                <option value="58" @selected((string) old('width', $printer?->width) === '58')>58 mm</option>
            </select>
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Notas</label>
            <input name="notes" value="{{ old('notes', $printer?->notes) }}" placeholder="Modelo, función, observaciones..." class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        </div>
    </div>

    {{-- SECCIÓN: ASIGNACIÓN MASIVA DE PRODUCTOS --}}
    <div class="mt-6 rounded-2xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/60">
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <i class="ri-restaurant-2-line text-[#C43B25] text-lg"></i>
                    <span>Productos asignados a esta ticketera</span>
                </h4>
                <p class="text-xs text-gray-500">Los productos seleccionados se imprimirán en esta ticketera al comandar.</p>
            </div>
            
            <div class="flex items-center gap-2">
                <span class="rounded-full bg-[#C43B25]/10 px-3 py-1 text-xs font-bold text-[#C43B25]">
                    <span x-text="selectedProducts.length"></span> de {{ count($productBranchesCollection) }} seleccionados
                </span>
            </div>
        </div>

        {{-- Barra de Controles y Búsqueda --}}
        <div class="mb-3 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="relative flex-1">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <i class="ri-search-line text-sm"></i>
                </span>
                <input type="text" x-model="search" placeholder="Filtrar productos..."
                    class="h-9 w-full rounded-lg border border-gray-300 bg-white px-3 pl-9 text-xs dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>

            <div class="flex items-center gap-2">
                <button type="button" @click="selectAll()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-emerald-50 px-3 text-xs font-bold text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-500/10 dark:text-emerald-400">
                    <i class="ri-checkbox-circle-line"></i> Seleccionar Todos
                </button>
                <button type="button" @click="deselectAll()"
                    class="inline-flex h-9 items-center gap-1.5 rounded-lg bg-gray-200 px-3 text-xs font-bold text-gray-700 hover:bg-gray-300 dark:bg-gray-800 dark:text-gray-300">
                    <i class="ri-close-circle-line"></i> Desmarcar Todos
                </button>
            </div>
        </div>

        {{-- Lista de Productos agrupados por Categoría --}}
        <div class="max-h-72 overflow-y-auto space-y-3 rounded-xl border border-gray-200 bg-white p-3 dark:border-gray-800 dark:bg-gray-900">
            @forelse($groupedProducts as $categoryName => $pItems)
                @php
                    $catIds = $pItems->pluck('id')->map(fn($id) => (int)$id)->values()->all();
                @endphp
                <div x-data="{
                    categoryName: @js($categoryName),
                    catIds: @js($catIds),
                    matchesSearch(name) {
                        if (!search.trim()) return true;
                        return name.toLowerCase().includes(search.toLowerCase());
                    }
                }" class="space-y-1">
                    {{-- Encabezado de Categoría --}}
                    <div class="flex items-center justify-between rounded-lg bg-gray-100 px-3 py-1.5 dark:bg-gray-800/80">
                        <span class="text-xs font-bold uppercase tracking-wider text-gray-800 dark:text-gray-200">
                            {{ $categoryName }} ({{ count($pItems) }})
                        </span>
                        <button type="button" @click="toggleCategory(catIds)"
                            class="text-[11px] font-bold text-blue-600 hover:underline dark:text-blue-400">
                            <span x-text="isCategorySelected(catIds) ? 'Desmarcar Categoría' : 'Marcar Categoría'"></span>
                        </button>
                    </div>

                    {{-- Grilla de Productos --}}
                    <div class="grid grid-cols-1 gap-1.5 sm:grid-cols-2 lg:grid-cols-3 pt-1">
                        @foreach($pItems as $pb)
                            @php
                                $pbId = (int) $pb->id;
                                $pName = $pb->product?->description ?? $pb->product?->name ?? 'Producto ID ' . $pb->id;
                            @endphp
                            <label x-show="matchesSearch(@js($pName))"
                                class="flex items-center gap-2 rounded-lg border border-gray-100 p-2 text-xs hover:bg-gray-50 cursor-pointer dark:border-gray-800 dark:hover:bg-gray-800/50">
                                <input type="checkbox" name="product_branch_ids[]" value="{{ $pbId }}" x-model.number="selectedProducts"
                                    class="rounded border-gray-300 text-[#C43B25] focus:ring-[#C43B25]">
                                <span class="truncate font-medium text-gray-800 dark:text-gray-200">{{ $pName }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="p-4 text-center text-xs text-gray-400">No hay productos registrados en esta sucursal.</p>
            @endforelse
        </div>
    </div>
</div>
