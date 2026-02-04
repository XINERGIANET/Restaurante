<div class="grid gap-5">
    
    <input type="hidden" name="movement_type_id" value="4">

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Concepto / Descripción</label>
        <div class="relative">
            <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                <i class="ri-file-text-line"></i>
            </span>
            <input
                type="text"
                name="comment"
                required
                placeholder="Ej: Pago de servicios..."
                x-model="formConcept"
                :readonly="formConcept === 'Apertura de caja'"
                :class="formConcept === 'Apertura de caja' ? 'bg-gray-100 cursor-not-allowed text-gray-500' : 'bg-transparent'"
                x-init="
                    let oldVal = '{{ old('comment', $movement->comment ?? '') }}';
                    if(oldVal) formConcept = oldVal;
                "
                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30 
                @error('comment') border-red-500 focus:border-red-500 @enderror" 
            />
        </div>
        @error('comment')
            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-2 gap-5">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Selecciona la Caja</label>
            <div class="relative">
                <span class="absolute top-1/2 left-0 -translate-y-1/2 border-r border-gray-200 px-3.5 py-3 text-gray-500 dark:border-gray-800 dark:text-gray-400">
                    <i class="ri-archive-drawer-line"></i> 
                </span>
                
                <select 
                    name="cash_register_id"
                    class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 pl-[62px] text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90
                    @error('cash_register_id') border-red-500 focus:border-red-500 @enderror"
                >
                    <option value="">-- Seleccionar --</option>
                    @foreach($cashRegisters as $box)
                        <option 
                            value="{{ $box->id }}" 
                            {{ old('cash_register_id', $movement->cash_register_id ?? '') == $box->id ? 'selected' : '' }}
                        >
                            {{ $box->number }}
                        </option>
                    @endforeach
                </select>
                <span class="pointer-events-none absolute top-1/2 right-4 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                    </svg>
                </span>
            </div>            
            @error('cash_register_id')
                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
            @enderror
        </div>
    </div>
</div>