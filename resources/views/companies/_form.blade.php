@php
    use Illuminate\Support\HtmlString;

    $useAlpine = $useAlpine ?? false;

    $RucIcon = new HtmlString('
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="1.6" />
            <path d="M7 9H11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M7 13H12" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <circle cx="16.5" cy="11" r="2" stroke="currentColor" stroke-width="1.6" />
            <path d="M14 15H19" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
    ');

    $CompanyIcon = new HtmlString('
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M4 21V5C4 3.89543 4.89543 3 6 3H18C19.1046 3 20 3.89543 20 5V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M9 21V15H15V21" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M8 7H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M14 7H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M8 11H10" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
            <path d="M14 11H16" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" />
        </svg>
    ');

    $AddressIcon = new HtmlString('
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 21C12 21 18 16 18 10.5C18 7.18629 15.3137 4.5 12 4.5C8.68629 4.5 6 7.18629 6 10.5C6 16 12 21 12 21Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
            <circle cx="12" cy="10.5" r="2.5" stroke="currentColor" stroke-width="1.6" />
        </svg>
    ');
@endphp

<div class="grid gap-5">
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">RUC</label>
        <div class="relative">
            <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                {!! $RucIcon !!}
            </span>
            <input
                type="text"
                name="tax_id"
                value="{{ old('tax_id', $company->tax_id ?? '') }}"
                required
                placeholder="Ingrese RUC"
                @if($useAlpine) x-model="form.tax_id" @endif
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
                {!! $CompanyIcon !!}
            </span>
            <input
                type="text"
                name="legal_name"
                value="{{ old('legal_name', $company->legal_name ?? '') }}"
                required
                placeholder="Ingrese la razon social"
                @if($useAlpine) x-model="form.legal_name" @endif
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
                {!! $AddressIcon !!}
            </span>
            <input
                type="text"
                name="address"
                value="{{ old('address', $company->address ?? '') }}"
                required
                placeholder="Ingrese la direccion"
                @if($useAlpine) x-model="form.address" @endif
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
            />
        </div>
        @error('address')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>
