<div class="grid gap-5">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">RUC</label>
        <div class="relative">
            <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                <i class="ri-id-card-line"></i>
            </span>
            <input
                type="text"
                name="tax_id"
                value="{{ old('tax_id', $company->tax_id ?? '') }}"
                required
                placeholder="Ingrese RUC"
                @if(($useAlpine ?? false)) x-model="form.tax_id" @endif
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
        </div>
        @error('tax_id')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Razon social</label>
        <div class="relative">
            <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                <i class="ri-building-line"></i>
            </span>
            <input
                type="text"
                name="legal_name"
                value="{{ old('legal_name', $company->legal_name ?? '') }}"
                required
                placeholder="Ingrese la razon social"
                @if(($useAlpine ?? false)) x-model="form.legal_name" @endif
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
        </div>
        @error('legal_name')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Direccion</label>
        <div class="relative">
            <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                <i class="ri-map-pin-line"></i>
            </span>
            <input
                type="text"
                name="address"
                value="{{ old('address', $company->address ?? '') }}"
                required
                placeholder="Ingrese la direccion"
                @if(($useAlpine ?? false)) x-model="form.address" @endif
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
        </div>
        @error('address')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>
