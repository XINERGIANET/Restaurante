@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Eventos</p>
        <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Noches especiales en Xinergia</h1>
        <p class="mt-4 max-w-2xl text-sm text-[#5a3d2f]">
            Agenda semanal con musica en vivo, cenas maridadas y experiencias privadas para grupos.
        </p>
    </div>
</section>

@php
    $events = [
        [
            'title' => 'Cena maridaje premium',
            'date' => 'Viernes - 8:00 PM',
            'desc' => '5 tiempos con vinos seleccionados por nuestro sommelier.',
        ],
        [
            'title' => 'Noche de jazz',
            'date' => 'Sabado - 9:00 PM',
            'desc' => 'Trio acustico en la terraza con menu especial de tapas.',
        ],
        [
            'title' => 'Chef table privado',
            'date' => 'Domingo - 7:00 PM',
            'desc' => 'Experiencia intima con el chef y menu sorpresa.',
        ],
    ];
@endphp

<section class="mx-auto max-w-6xl px-6 pb-16">
    <div class="grid gap-6 md:grid-cols-3">
        @foreach ($events as $event)
            <div class="rounded-[28px] border border-[#eadcca] bg-white/80 p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">{{ $event['date'] }}</p>
                <h3 class="mt-3 font-playfair text-2xl">{{ $event['title'] }}</h3>
                <p class="mt-2 text-sm text-[#5a3d2f]">{{ $event['desc'] }}</p>
                <a href="{{ route('restaurant.reservations') }}" class="mt-4 inline-flex text-sm font-semibold text-[#b1492c]">Reservar cupo</a>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="rounded-[36px] bg-[#2b1c16] p-10 text-[#f6efe7]">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Eventos privados</p>
                <h2 class="mt-3 font-playfair text-3xl">Celebra con menu personalizado</h2>
                <p class="mt-2 text-sm text-[#f0dac2]">Espacios para lanzamientos, cenas corporativas y celebraciones familiares.</p>
            </div>
            <a href="{{ route('restaurant.contact') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#2b1c16]">Consultar disponibilidad</a>
        </div>
    </div>
</section>
@endsection