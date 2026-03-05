{{-- Modal de selección de tipo de producto --}}
<div class="flex flex-col p-6 sm:p-8">
    {{-- Header --}}
    <div class="mb-8 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div class="flex items-start gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg">
                <i class="ri-box-3-line text-2xl"></i>
            </div>
            <div>
                <h2 class="text-xl font-bold text-gray-900 dark:text-white">Seleccionar Tipo de Producto</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Elige el tipo de producto que deseas registrar</p>
            </div>
        </div>
        <button type="button"
            @click="$dispatch('close-product-type-modal')"
            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gray-100 text-gray-500 transition-colors hover:bg-gray-200 hover:text-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white"
            aria-label="Cerrar">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Cards --}}
    <div class="mb-8 grid gap-6 sm:grid-cols-2">
        {{-- Ingrediente --}}
        <button type="button"
            @click="$dispatch('open-product-form-with-type', { type: 'INGREDENT' }); $dispatch('close-product-type-modal')"
            class="group flex flex-col rounded-2xl border-2 border-transparent bg-white p-6 text-left shadow-sm ring-2 ring-orange-200/60 transition-all duration-200 hover:border-orange-300 hover:ring-orange-300 hover:shadow-md dark:bg-gray-800/50 dark:ring-orange-500/30 dark:hover:ring-orange-500/50">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-orange-400 to-orange-600 text-white shadow-lg">
                <i class="ri-seedling-line text-3xl"></i>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">Ingrediente</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Materia prima o insumo utilizado en la elaboración de productos finales</p>
            <hr class="mb-4 border-gray-200 dark:border-gray-700" />
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                    No requiere receta
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                    Control de stock básico
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-orange-500"></span>
                    Configuración simplificada
                </li>
            </ul>
        </button>

        {{-- Producto Final --}}
        <button type="button"
            @click="$dispatch('open-product-form-with-type', { type: 'PRODUCT' }); $dispatch('close-product-type-modal')"
            class="group flex flex-col rounded-2xl border-2 border-transparent bg-white p-6 text-left shadow-sm ring-2 ring-blue-200/60 transition-all duration-200 hover:border-blue-300 hover:ring-blue-300 hover:shadow-md dark:bg-gray-800/50 dark:ring-blue-500/30 dark:hover:ring-blue-500/50">
            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg">
                <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
            </div>
            <h3 class="mb-2 text-lg font-bold text-gray-900 dark:text-white">Producto Final</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">Producto elaborado listo para la venta que puede incluir receta</p>
            <hr class="mb-4 border-gray-200 dark:border-gray-700" />
            <ul class="space-y-2 text-sm text-gray-600 dark:text-gray-300">
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Puede tener receta
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Control completo de kardex
                </li>
                <li class="flex items-center gap-2">
                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                    Configuración avanzada
                </li>
            </ul>
        </button>
    </div>

    {{-- Footer --}}
    <div class="flex justify-end">
        <button type="button"
            @click="$dispatch('close-product-type-modal')"
            class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
            Cancelar
        </button>
    </div>
</div>
