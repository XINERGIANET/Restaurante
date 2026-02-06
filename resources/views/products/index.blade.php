@extends('layouts.app')

@section('content')
    <script>
        // Datos globales de ProductBranch
        window.productBranchData = {
            @php
                $branchId = session('branch_id');
            @endphp
            @foreach ($products as $product)
                @php
                    $productBranch = $product->productBranches->where('branch_id', $branchId)->first();
                @endphp
                @if($productBranch)
                    {{ $product->id }}: {
                        stock: {{ $productBranch->stock }},
                        price: {{ $productBranch->price }},
                        tax_rate_id: {{ $productBranch->tax_rate_id }},
                        stock_minimum: {{ $productBranch->stock_minimum }},
                        stock_maximum: {{ $productBranch->stock_maximum }},
                        minimum_sell: {{ $productBranch->minimum_sell ?? 0 }},
                        minimum_purchase: {{ $productBranch->minimum_purchase ?? 0 }}
                    },
                @endif
            @endforeach
        };

        // Rutas de productos
        window.productRoutes = {
            @foreach ($products as $product)
                {{ $product->id }}: '{{ route('admin.products.product_branches.store', $product) }}',
            @endforeach
        };

        console.log('Loaded productBranchData:', window.productBranchData);
    </script>

    <div x-data="{
        openRow: null
    }">
        
        <x-common.page-breadcrumb pageTitle="Productos" />

        <x-common.component-card title="Productos" desc="Gestiona los productos.">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-center xl:justify-between">
                <form method="GET" class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="w-29">
                        <select name="per_page"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
                            onchange="this.form.submit()">
                            @foreach ([10, 20, 50, 100] as $size)
                                <option value="{{ $size }}" @selected($perPage == $size)>{{ $size }} /
                                    pagina</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative flex-1">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400">
                            <i class="ri-search-line"></i>
                        </span>
                        <input type="text" name="search" value="{{ $search }}"
                            placeholder="Buscar por codigo o descripcion"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-12 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            <i class="ri-search-line"></i>
                            <span>Buscar</span>
                        </x-ui.button>
                        <x-ui.link-button size="sm" variant="outline" href="{{ route('admin.products.index') }}">
                            <i class="ri-close-line"></i>
                            <span>Limpiar</span>
                        </x-ui.link-button>
                    </div>
                </form>

                <x-ui.button size="md" variant="primary" type="button"
                    style=" background-color: #12f00e; color: #111827;" @click="$dispatch('open-product-modal')">
                    <i class="ri-add-line"></i>
                    <span>Nuevo producto</span>
                </x-ui.button>
            </div>

            <div
                class="mt-4 rounded-xl border border-gray-200 bg-white overflow-visible dark:border-gray-800 dark:bg-white/[0.03]">
                <table class="w-full">
                    <thead>
                        <th class="w-12 px-4 py-4 text-center"></th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Codigo</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Descripcion</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Categoria</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Unidad base</p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Tipo</p>
                        </th>
                        <th class="px-5 py-3 text-right sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Acciones</p>
                        </th>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr
                                class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-4 py-4 text-center">
                                    <button type="button"
                                        @click="openRow === {{ $product->id }} ? openRow = null : openRow = {{ $product->id }}"
                                        class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-brand-500 text-white transition hover:bg-brand-600 dark:bg-brand-500 dark:text-white">
                                        <i class="ri-add-line" x-show="openRow !== {{ $product->id }}"></i>
                                        <i class="ri-subtract-line" x-show="openRow === {{ $product->id }}"></i>
                                    </button>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="font-medium text-gray-800 text-theme-sm dark:text-white/90">
                                        {{ $product->code }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $product->description }}
                                    </p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                        {{ $product->category?->description ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">
                                        {{ $product->baseUnit?->description ?? '-' }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400">{{ $product->type }}</p>
                                </td>
                                <td class="px-5 py-4 sm:px-6">
                                    <div class="flex items-center justify-end gap-2">

                                        <div class="relative group">
                                            <x-ui.button size="icon" type="button"
                                                @click="$dispatch('open-product-branch-modal', { productId: {{ $product->id }} })"
                                                className="bg-blue-500 text-white hover:bg-blue-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #2493fb; color: #111827;"
                                                aria-label="Agregar a sucursal">
                                                <i class="ri-store-line"></i>
                                            </x-ui.button>
                                            <span
                                                class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                                style="transition-delay: 0.5s;">Agregar a sucursal</span>
                                        </div>
                                        <div class="relative group">
                                            <x-ui.link-button size="icon" variant="edit"
                                                href="{{ route('admin.products.edit', $product) }}"
                                                className="bg-warning-500 text-white hover:bg-warning-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #FBBF24; color: #111827;"
                                                aria-label="Editar">
                                                <i class="ri-pencil-line"></i>
                                            </x-ui.link-button>
                                            <span
                                                class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                                style="transition-delay: 0.5s;">Editar</span>
                                        </div>

                                        <form method="POST" action="{{ route('admin.products.destroy', $product) }}"
                                            class="relative group js-swal-delete" data-swal-title="Eliminar producto?"
                                            data-swal-text="Se eliminara {{ $product->description }}. Esta accion no se puede deshacer."
                                            data-swal-confirm="Si, eliminar" data-swal-cancel="Cancelar"
                                            data-swal-confirm-color="#ef4444" data-swal-cancel-color="#6b7280">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button size="icon" variant="eliminate" type="submit"
                                                className="bg-error-500 text-white hover:bg-error-600 ring-0 rounded-full"
                                                style="border-radius: 100%; background-color: #EF4444; color: #FFFFFF;"
                                                aria-label="Eliminar">
                                                <i class="ri-delete-bin-line"></i>
                                            </x-ui.button>
                                            <span
                                                class="pointer-events-none absolute top-full left-1/2 -translate-x-1/2 mt-2 whitespace-nowrap rounded-md bg-gray-900 px-2 py-1 text-xs text-white opacity-0 transition group-hover:opacity-100 z-50"
                                                style="transition-delay: 0.5s;">Eliminar</span>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            <tr x-show="openRow === {{ $product->id }}" x-cloak class="bg-gray-50/60 dark:bg-gray-800/40">
                                <td colspan="7" class="px-6 py-4">
                                    @php
                                        $branchId = session('branch_id');
                                        $productBranch = $product->productBranches->where('branch_id', $branchId)->first();
                                        if ($productBranch) {
                                            $productBranch->loadMissing('taxRate');
                                        }
                                    @endphp
                                    @if($productBranch)
                                        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 text-sm">
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-gray-400">Stock</p>
                                                <p class="font-medium text-gray-700 dark:text-gray-200">{{ $productBranch->stock }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-gray-400">Precio</p>
                                                <p class="font-medium text-gray-700 dark:text-gray-200">${{ number_format($productBranch->price, 2) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-gray-400">Tasa de impuesto</p>
                                                <p class="font-medium text-gray-700 dark:text-gray-200">
                                                    @if($productBranch->tax_rate_id && $productBranch->taxRate)
                                                        {{ number_format($productBranch->taxRate->tax_rate, 2) }}%
                                                    @else
                                                        <span class="text-gray-400">Sin tasa</span>
                                                    @endif
                                                </p>
                                            </div>
                                            <div>
                                                <p class="text-xs uppercase tracking-wide text-gray-400">Sucursal</p>
                                                <p class="font-medium text-gray-700 dark:text-gray-200">{{ $productBranch->branch?->legal_name ?? '-' }}</p>
                                            </div>
                                        </div>
                                    @else
                                        <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:border-gray-800 dark:bg-gray-900/30">
                                            Este producto no está registrado en la sucursal actual.
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12">
                                    <div class="flex flex-col items-center gap-3 text-center text-sm text-gray-500">
                                        <div
                                            class="rounded-full bg-gray-100 p-3 text-gray-400 dark:bg-gray-800 dark:text-gray-300">
                                            <i class="ri-restaurant-line"></i>
                                        </div>
                                        <p class="text-base font-semibold text-gray-700 dark:text-gray-200">No hay
                                            productos registrados.</p>
                                        <p class="text-gray-500">Crea el primer producto para comenzar.</p>
                                        <x-ui.button size="sm" variant="primary" type="button"
                                            @click="$dispatch('open-product-modal')">
                                            <i class="ri-add-line"></i>
                                            <span>Registrar producto</span>
                                        </x-ui.button>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="text-sm text-gray-500">
                    Mostrando
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $products->firstItem() ?? 0 }}</span>
                    -
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $products->lastItem() ?? 0 }}</span>
                    de
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $products->total() }}</span>
                </div>
                <div>
                    {{ $products->links() }}
                </div>
            </div>
        </x-common.component-card>

        <x-ui.modal
            x-data="{
                open: false,
                productId: null,
                formData: {
                    stock: 0,
                    price: 0.00,
                    tax_rate_id: '',
                    stock_minimum: 0.000000,
                    stock_maximum: 0.000000,
                    minimum_sell: 0.000000,
                    minimum_purchase: 0.000000
                },
                handleOpen(event) {
                    this.productId = event.detail?.productId || null;
                    console.log('Product ID:', this.productId);
                    console.log('Available data:', window.productBranchData);
                    
                    if (this.productId) {
                        // Cargar datos existentes si hay
                        if (window.productBranchData && window.productBranchData[this.productId]) {
                            console.log('Loading existing data:', window.productBranchData[this.productId]);
                            this.formData = {...window.productBranchData[this.productId]};
                            console.log('Form data after loading:', this.formData);
                        } else {
                            console.log('No existing data, using defaults');
                            // Resetear a valores por defecto
                            this.formData = {
                                stock: 0,
                                price: 0.00,
                                tax_rate_id: '',
                                stock_minimum: 0.000000,
                                stock_maximum: 0.000000,
                                minimum_sell: 0.000000,
                                minimum_purchase: 0.000000
                            };
                        }
                        const form = document.getElementById('product-branch-form');
                        if (form && window.productRoutes && window.productRoutes[this.productId]) {
                            form.action = window.productRoutes[this.productId];
                        }
                    }
                    this.open = true;
                }
            }"
            @open-product-branch-modal.window="handleOpen($event)"
            @close-product-branch-modal.window="open = false"
            :isOpen="false"
            :showCloseButton="false"
            class="max-w-5xl"
        >
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-store-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Agregar producto a sucursal</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion del producto para la sucursal.</p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="open = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Cerrar"
                    >
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-5">
                        <x-ui.alert variant="error" title="Revisa los campos" message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form id="product-branch-form" method="POST" action="{{ route('admin.product_branches.store_generic') }}" class="space-y-6">
                    @csrf
                    <input type="hidden" name="product_id" x-model="productId">

                    @include('products.product_branch._form', [
                        'productBranch' => null,
                        'currentBranch' => $currentBranch,
                        'taxRates' => $taxRates,
                    ])

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="md" variant="primary">
                            <i class="ri-save-line"></i>
                            <span>Guardar</span>
                        </x-ui.button>
                        <x-ui.button type="button" size="md" variant="outline" @click="open = false">
                            <i class="ri-close-line"></i>
                            <span>Cancelar</span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>

        <x-ui.modal x-data="{ open: false }" @open-product-modal.window="open = true"
            @close-product-modal.window="open = false" :isOpen="false" :showCloseButton="false" class="max-w-5xl">
            <div class="p-6 sm:p-8">
                <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/10">
                            <i class="ri-restaurant-line text-2xl"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white/90">Registrar producto</h3>
                            <p class="mt-1 text-sm text-gray-500">Ingresa la informacion del producto.</p>
                        </div>
                    </div>
                    <button type="button" @click="open = false"
                        class="flex h-11 w-11 items-center justify-center rounded-full bg-gray-100 text-gray-400 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
                        aria-label="Cerrar">
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>

                @if ($errors->any())
                    <div class="mb-5">
                        <x-ui.alert variant="error" title="Revisa los campos"
                            message="Hay errores en el formulario, corrige los datos e intenta nuevamente." />
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    @include('products._form', ['product' => null])

                    <div class="flex flex-wrap gap-3">
                        <x-ui.button type="submit" size="md" variant="primary">
                            <i class="ri-save-line"></i>
                            <span>Guardar</span>
                        </x-ui.button>
                        <x-ui.button type="button" size="md" variant="outline" @click="open = false">
                            <i class="ri-close-line"></i>
                            <span>Cancelar</span>
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>
    </div>
@endsection
