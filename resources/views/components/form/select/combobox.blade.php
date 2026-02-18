@props([
    'label',
    'options' => [], 
    'placeholder' => 'Seleccione una opción...',
    'required' => false,
    'icon' => 'ri-price-tag-3-line',
    'name' => '',
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

        // ESCUCHADOR DE EVENTO: Aquí recibimos la orden del padre
        updateOptions(newOptions) {
            console.log('Combobox recibiendo datos:', newOptions); // Para depurar
            this.allOptions = newOptions;
            this.value = null; 
            this.query = '';
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
    
    {{-- ESCUCHAMOS EL EVENTO DEL PADRE --}}
    @update-combobox-options.window="updateOptions($event.detail.options)"

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
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4">
            <i class="{{ $icon }} text-gray-400"></i>
        </div>

        <input 
            type="text" 
            x-model="query"
            @focus="open = true"
            @input="open = true" 
            @keydown.escape="closeDropdown()"
            @keydown.enter.prevent="if(filteredOptions.length > 0) selectOption(filteredOptions[0])"
            placeholder="{{ $placeholder }}"
            class="h-11 w-full rounded-lg border border-gray-200 bg-white pl-10 pr-4 text-sm text-gray-800 placeholder-gray-400 focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:border-gray-700 dark:bg-dark-900 dark:text-white/90 transition-all"
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
             class="absolute z-50 mt-1 max-h-60 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-800"
             style="display: none;">
            
            <ul class="py-1">
                <template x-for="item in filteredOptions" :key="item.id">
                    <li @click="selectOption(item)"
                        class="cursor-pointer px-4 py-2 text-sm text-gray-700 hover:bg-brand-50 hover:text-brand-600 dark:text-gray-200 dark:hover:bg-gray-700/50 dark:hover:text-brand-400 transition-colors">
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