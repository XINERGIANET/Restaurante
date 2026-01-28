@props([
    'href' => '#',
    'size' => 'md',
    'variant' => 'primary',
    'startIcon' => null,
    'endIcon' => null,
    'className' => '',
    'disabled' => false,
])

@php
    $base = 'inline-flex items-center justify-center font-medium gap-2 rounded-lg transition';

    $sizeMap = [
        'sm' => 'px-4 py-3 text-sm',
        'md' => 'px-5 py-3.5 text-sm',
        'icon' => 'h-10 w-10 p-0 text-sm rounded-full',
    ];
    $sizeClass = $sizeMap[$size] ?? $sizeMap['md'];

    $variantMap = [
        'primary' => 'bg-brand-500 text-white shadow-theme-xs hover:bg-brand-600',
        'outline' => 'bg-white text-gray-700 ring-1 ring-inset ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700 dark:hover:bg-white/[0.03] dark:hover:text-gray-300',
    ];
    $variantClass = $variantMap[$variant] ?? $variantMap['primary'];

    $disabledClass = $disabled ? 'cursor-not-allowed opacity-50 pointer-events-none' : '';

    $classes = trim("{$base} {$sizeClass} {$variantClass} {$className} {$disabledClass}");
@endphp

<a
    href="{{ $disabled ? '#' : $href }}"
    {{ $attributes->merge(['class' => $classes]) }}
    @if($disabled) aria-disabled="true" tabindex="-1" @endif
>
    @if($startIcon)
        <span class="flex items-center">{!! $startIcon !!}</span>
    @endif

    {{ $slot }}

    @if($endIcon)
        <span class="flex items-center">{!! $endIcon !!}</span>
    @endif
</a>

