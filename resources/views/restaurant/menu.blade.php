@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="rounded-[36px] border border-[#eadcca] bg-white/70 p-10 soft-shadow">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Menu Xinergia</p>
        <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Carta de temporada</h1>
        <p class="mt-4 max-w-2xl text-sm text-[#5a3d2f]">
            Platos inspirados en la costa y el fuego. Cada categoria se renueva con ingredientes frescos.
            Pregunta por opciones vegetarianas y maridajes especiales.
        </p>
    </div>
</section>

@php
    $sections = [
        [
            'title' => 'Entradas',
            'items' => [
                ['name' => 'Ostra tibia', 'desc' => 'Mantequilla de hierbas, lima y sal marina.', 'price' => '$12'],
                ['name' => 'Tostada de atun', 'desc' => 'Aguacate, ajonjoli tostado y aceite de chile.', 'price' => '$14'],
                ['name' => 'Ensalada de huerta', 'desc' => 'Brotes, queso de cabra y vinagreta citrica.', 'price' => '$11'],
            ],
        ],
        [
            'title' => 'Platos fuertes',
            'items' => [
                ['name' => 'Brasa del Pacifico', 'desc' => 'Atun sellado, cacao y humo de maderas.', 'price' => '$28'],
                ['name' => 'Lomo braseado', 'desc' => 'Carne madurada, pure cremoso y vino tinto.', 'price' => '$31'],
                ['name' => 'Pescado al carbon', 'desc' => 'Salsa verde, maiz tierno y vegetales.', 'price' => '$26'],
                ['name' => 'Ravioli de hongos', 'desc' => 'Trufa suave y queso curado.', 'price' => '$24'],
            ],
        ],
        [
            'title' => 'Postres',
            'items' => [
                ['name' => 'Cacao y sal', 'desc' => 'Espuma tibia, tierra de chocolate y cafe frio.', 'price' => '$12'],
                ['name' => 'Frutas asadas', 'desc' => 'Helado de vainilla y miel especiada.', 'price' => '$10'],
                ['name' => 'Queso curado', 'desc' => 'Conservas de temporada y pan artesanal.', 'price' => '$11'],
            ],
        ],
        [
            'title' => 'Bebidas',
            'items' => [
                ['name' => 'Coctel Ahumado', 'desc' => 'Ron oscuro, cafe frio y citricos.', 'price' => '$13'],
                ['name' => 'Spritz de la casa', 'desc' => 'Vino blanco, hierbas y naranja.', 'price' => '$12'],
                ['name' => 'Mocktail Botanico', 'desc' => 'Infusion de hierbas y tonica artesanal.', 'price' => '$9'],
            ],
        ],
    ];
@endphp

<section class="mx-auto max-w-6xl px-6 pb-16">
    <div class="grid gap-10">
        @foreach ($sections as $section)
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <div class="flex items-center justify-between border-b border-[#eadcca] pb-4">
                    <h2 class="font-playfair text-3xl">{{ $section['title'] }}</h2>
                    <span class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Seleccion</span>
                </div>
                <div class="mt-6 grid gap-5 md:grid-cols-2">
                    @foreach ($section['items'] as $item)
                        <div class="flex items-start justify-between gap-6">
                            <div>
                                <p class="font-semibold text-[#2b1c16]">{{ $item['name'] }}</p>
                                <p class="mt-1 text-sm text-[#5a3d2f]">{{ $item['desc'] }}</p>
                            </div>
                            <span class="text-sm font-semibold text-[#b1492c]">{{ $item['price'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="rounded-[32px] bg-[#2b1c16] p-10 text-[#f6efe7]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Menu degustacion</p>
                <h2 class="mt-3 font-playfair text-3xl">7 tiempos con maridaje</h2>
                <p class="mt-2 text-sm text-[#f0dac2]">Disponible con reserva previa. Pregunta por la opcion vegetariana.</p>
            </div>
            <a href="{{ route('restaurant.reservations') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#2b1c16]">Reservar degustacion</a>
        </div>
    </div>
</section>
@endsection