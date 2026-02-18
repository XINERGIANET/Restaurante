@props([
    'perPage' => 10,
    'options' => [10, 20, 50, 100],
    'submitForm' => true, 
])

<div {{ $attributes->merge(['class' => 'w-24']) }}> 
    <select 
        name="per_page"
        @if($submitForm) onchange="this.form.submit()" @endif
        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 cursor-pointer"
    >
        @foreach ($options as $size)
            <option value="{{ $size }}" @selected($perPage == $size)>
                {{ $size }} / pág
            </option>
        @endforeach
    </select>
</div>