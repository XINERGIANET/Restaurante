@props([
    'label' => null,
    'options' => [], 
    'placeholder' => 'Seleccione una opción...',
    'required' => false,
    'icon' => 'ri-price-tag-3-line',
    'name' => '',
    'iconClickEvent' => null, // NUEVA PROPIEDAD: Si se envía, el ícono izquierdo se vuelve un botón
])

<div x-data="{
        allOptions: {{ \Illuminate\Support\Js::from($options) }},
        value: null,
        query: '',
        open: false,

        init() {
            this.syncQueryFromId();
            this.$watch('value', () => this.syncQueryFromId());
        },

        updateOptions(newOptions) {
            this.allOptions = Array.isArray(newOptions) ? newOptions : [];
            this.syncQueryFromId();
        },

        syncQueryFromId() {
            if (this.value && this.allOptions.length > 0) {
                const found = this.allOptions.find(c => c.id == this.value);
                this.query = found ? found.description : '';
            } else {
                this.query = '';
            }
        },

        get filteredOptions() {
            if (this.query === '') return this.allOptions;
            return this.allOptions.filter(item => 
                (item.description || '').toLowerCase().includes(this.query.toLowerCase())
            );
        },

        selectOption(item) {
            this.value = item.id;
            this.query = item.description;
            this.open = false;
        },

        closeDropdown() {
            if (this.open) {
                this.open = false;
                this.syncQueryFromId();
            }
        }
    }"
    x-modelable="value"
    {{ $attributes->whereStartsWith('x-model') }} 
    
    @update-combobox-options.window="
        if (!$event.detail || !$event.detail.options) return;
        if (($event.detail.name || '') !== @js($name ?? '')) return;
        updateOptions($event.detail.options)
    "

    class="space-y-1.5 relative"
    x-on:mousedown.document="if (!$el.contains($event.target)) closeDropdown()"
>

    {{-- Label --}}
    @if($label)
        <label class="block text-sm font-medium text-gray-600 dark:text-gray-400">
            {{ $label }} 
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <div class="relative">
        
        {{-- CONDICIONAL PARA EL ÍCONO IZQUIERDO: ¿ES BOTÓN O ES ADORNO? --}}
        @if($iconClickEvent)
            <button 
                type="button" 
                @click="$dispatch('{{ $iconClickEvent }}')"
                class="absolute inset-y-0 left-1 my-auto flex h-9 w-9 items-center justify-center rounded-md text-gray-400 hover:bg-[#244BB3]/10 hover:text-[#244BB3] focus:outline-none transition-colors z-10"
                title="Acción"
            >
                <i class="{{ $icon }} text-[18px]"></i>
            </button>
        @else
            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
                <i class="{{ $icon }} text-gray-400 text-[18px]"></i>
            </div>
        @endif

        {{-- Input principal (se ajusta el padding pl-11 para que no pise el ícono) --}}
        <input 
            type="text" 
            x-model="query"
            @focus="open = true"
            @input="open = true"
            @dblclick="$el.select()" 
            @keydown.escape="closeDropdown()"
            @keydown.enter.prevent="if(filteredOptions.length > 0) selectOption(filteredOptions[0])"
            placeholder="{{ $placeholder }}"
            class="h-11 w-full rounded-lg border border-gray-200 bg-white pl-11 pr-10 text-sm text-gray-800 placeholder-gray-400 focus:border-[#244BB3] focus:ring-1 focus:ring-[#244BB3] dark:border-gray-700 dark:bg-dark-900 dark:text-white/90 transition-all"
            autocomplete="off"
        >

        @if($name)
            <input type="hidden" name="{{ $name }}" x-model="value">
        @endif

        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
            <i class="ri-arrow-down-s-line text-gray-400 transition-transform duration-200" :class="{'rotate-180': open}"></i>
        </div>

        <div x-show="open" 
             x-transition.opacity.duration.200ms
             class="absolute z-50 mt-1 w-full overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
             style="display: none; max-height: 12rem;">
            
            <ul class="py-1">
                <template x-for="item in filteredOptions" :key="item.id">
                    <li @click="selectOption(item)"
                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-[#244BB3]/5 hover:text-[#244BB3] font-medium transition-colors">
                        <span x-text="item.description"></span>
                    </li>
                </template>
                
                <li x-show="filteredOptions.length === 0" class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400 cursor-default">
                    No se encontraron resultados.
                </li>
            </ul>
        </div>
    </div>
</div>