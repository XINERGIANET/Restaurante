@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Sucursales</p>
        <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Estamos en dos ciudades</h1>
        <p class="mt-4 max-w-2xl text-sm text-[#5a3d2f]">
            Cada sede mantiene la misma esencia: cocina de fuego, ambiente calido y servicio cercano.
        </p>
    </div>
</section>

@php
    $locations = [
        [
            'name' => 'Xinergia Centro',
            'address' => 'Av. Del Sabor 125, Distrito Gastronomico',
            'hours' => 'Mar - Sab 12:00 - 00:00',
            'phone' => '+57 320 000 0000',
        ],
        [
            'name' => 'Xinergia Norte',
            'address' => 'Calle 85 #18-40, Zona Gourmet',
            'hours' => 'Lun - Dom 12:00 - 22:00',
            'phone' => '+57 320 000 1111',
        ],
    ];
@endphp

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($locations as $location)
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">{{ $location['hours'] }}</p>
                <h3 class="mt-3 font-playfair text-2xl">{{ $location['name'] }}</h3>
                <p class="mt-2 text-sm text-[#5a3d2f]">{{ $location['address'] }}</p>
                <p class="mt-2 text-sm text-[#5a3d2f]">{{ $location['phone'] }}</p>
                <a href="{{ route('restaurant.contact') }}" class="mt-4 inline-flex text-sm font-semibold text-[#b1492c]">Solicitar evento privado</a>
            </div>
        @endforeach
    </div>

    <div class="mt-10 rounded-[32px] border border-[#eadcca] bg-[#fff7ef] p-8">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Mapa</p>
        <div class="mt-4 h-64 rounded-2xl border border-[#eadcca] bg-[radial-gradient(circle_at_top,#f3b27a,transparent_70%)]"></div>
    </div>
</section>
@endsection