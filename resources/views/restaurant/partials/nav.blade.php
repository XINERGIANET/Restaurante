@php
    $links = [
        ['label' => 'Inicio', 'route' => 'restaurant.home'],
        ['label' => 'Menu', 'route' => 'restaurant.menu'],
        ['label' => 'Reservas', 'route' => 'restaurant.reservations'],
        ['label' => 'Historia', 'route' => 'restaurant.about'],
        ['label' => 'Eventos', 'route' => 'restaurant.events'],
        ['label' => 'Galeria', 'route' => 'restaurant.gallery'],
        ['label' => 'Contacto', 'route' => 'restaurant.contact'],
        ['label' => 'Sucursales', 'route' => 'restaurant.locations'],
    ];
@endphp

<header class="sticky top-0 z-50 border-b border-[#e6d8c7] bg-[#f6efe7]/80 backdrop-blur" x-data="{ open: false }">
    <div class="mx-auto flex max-w-6xl items-center justify-between gap-6 px-6 py-4">
        <a href="{{ route('restaurant.home') }}" class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-full bg-[#2b1c16] text-sm font-semibold uppercase tracking-[0.2em] text-[#f6efe7]">XR</span>
            <div class="leading-tight">
                <p class="font-playfair text-lg font-semibold">Xinergia</p>
                <p class="text-xs uppercase tracking-[0.28em] text-[#7b5744]">Restaurante</p>
            </div>
        </a>

        <nav class="hidden items-center gap-6 text-sm font-medium lg:flex">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="transition {{ request()->routeIs($link['route']) ? 'text-[#b1492c]' : 'text-[#4b3427] hover:text-[#b1492c]' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <a href="{{ route('restaurant.menu') }}" class="rounded-full border border-[#c9aa8b] px-4 py-2 text-sm font-semibold text-[#4b3427] transition hover:border-[#b1492c] hover:text-[#b1492c]">Ver carta</a>
            <a href="{{ route('restaurant.reservations') }}" class="rounded-full bg-[#b1492c] px-4 py-2 text-sm font-semibold text-white shadow-md shadow-[#b1492c]/30 transition hover:bg-[#923820]">Reservar mesa</a>
        </div>

        <button
            type="button"
            class="flex h-10 w-10 items-center justify-center rounded-full border border-[#d8c3ad] text-[#4b3427] lg:hidden"
            @click="open = !open"
            aria-label="Open menu"
        >
            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round">
                <path d="M4 6h16" />
                <path d="M4 12h16" />
                <path d="M4 18h16" />
            </svg>
        </button>
    </div>

    <div x-cloak x-show="open" x-transition class="border-t border-[#e6d8c7] bg-[#f6efe7] lg:hidden">
        <div class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-5 text-sm font-medium">
            @foreach ($links as $link)
                <a
                    href="{{ route($link['route']) }}"
                    class="rounded-xl px-3 py-2 transition {{ request()->routeIs($link['route']) ? 'bg-[#f1e4d4] text-[#b1492c]' : 'text-[#4b3427] hover:bg-[#f1e4d4]' }}"
                >
                    {{ $link['label'] }}
                </a>
            @endforeach
            <div class="flex flex-col gap-2 pt-3">
                <a href="{{ route('restaurant.menu') }}" class="rounded-full border border-[#c9aa8b] px-4 py-2 text-center text-sm font-semibold text-[#4b3427]">Ver carta</a>
                <a href="{{ route('restaurant.reservations') }}" class="rounded-full bg-[#b1492c] px-4 py-2 text-center text-sm font-semibold text-white">Reservar mesa</a>
            </div>
        </div>
    </div>
</header>