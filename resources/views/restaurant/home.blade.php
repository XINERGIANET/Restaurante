@extends('layouts.restaurant')

@section('content')
<section class="relative overflow-hidden">
    <div class="mx-auto grid max-w-6xl items-center gap-12 px-6 pb-20 pt-14 lg:grid-cols-2">
        <div class="space-y-6">
            <p class="text-xs uppercase tracking-[0.45em] text-[#7b5744] animate-reveal">Restaurante de autor</p>
            <h1 class="font-playfair text-4xl font-semibold leading-tight text-[#2b1c16] sm:text-5xl lg:text-6xl animate-reveal animate-reveal-delay">
                Fuego lento, mar fresco y una experiencia que despierta los sentidos.
            </h1>
            <p class="text-base text-[#5a3d2f] sm:text-lg animate-reveal animate-reveal-delay-2">
                Xinergia es un restaurante contemporaneo que mezcla tecnica, producto local y un ambiente
                calido. Cada plato se construye en capas, con maridajes pensados para una noche completa.
            </p>
            <div class="flex flex-wrap gap-4 animate-reveal animate-reveal-delay-2">
                <a href="{{ route('restaurant.reservations') }}" class="rounded-full bg-[#b1492c] px-6 py-3 text-sm font-semibold text-white shadow-md shadow-[#b1492c]/40 transition hover:bg-[#923820]">Reservar mesa</a>
                <a href="{{ route('restaurant.menu') }}" class="rounded-full border border-[#c9aa8b] px-6 py-3 text-sm font-semibold text-[#4b3427] transition hover:border-[#b1492c] hover:text-[#b1492c]">Ver menu completo</a>
            </div>
            <div class="grid grid-cols-2 gap-6 rounded-2xl border border-[#eadcca] bg-white/70 p-4 text-sm text-[#4b3427] shadow-sm backdrop-blur">
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Horarios</p>
                    <p>Mar - Sab</p>
                    <p class="font-semibold">12:00 - 00:00</p>
                </div>
                <div>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Telefono</p>
                    <p>+57 320 000 0000</p>
                    <p class="font-semibold">Reservas directas</p>
                </div>
            </div>
        </div>
        <div class="relative">
            <div class="absolute -top-6 right-4 h-28 w-28 rounded-full border border-[#e8d6c4] bg-[#f9f2e8] animate-float"></div>
            <div class="rounded-[32px] border border-[#eadcca] bg-[#fff7ef] p-6 soft-shadow">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl bg-[#261a15] p-4 text-[#f6efe7]">
                        <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Chef table</p>
                        <p class="mt-3 font-playfair text-2xl">Menu degustacion</p>
                        <p class="mt-2 text-sm text-[#f0dac2]">7 tiempos con maridaje</p>
                    </div>
                    <div class="rounded-2xl border border-[#e8d6c4] bg-white p-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Plato icono</p>
                        <p class="mt-3 font-playfair text-2xl text-[#2b1c16]">Brasa del Pacifico</p>
                        <p class="mt-2 text-sm text-[#6d4f3e]">Atun sellado, cacao y humo</p>
                    </div>
                    <div class="rounded-2xl border border-[#e8d6c4] bg-[#fff] p-4">
                        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Cocteleria</p>
                        <p class="mt-3 font-playfair text-2xl text-[#2b1c16]">Ahumado</p>
                        <p class="mt-2 text-sm text-[#6d4f3e]">Ron, cafe frio y citricos</p>
                    </div>
                    <div class="rounded-2xl bg-[#b1492c] p-4 text-white">
                        <p class="text-xs uppercase tracking-[0.3em] text-white/70">Reserva rapida</p>
                        <p class="mt-3 font-playfair text-2xl">En 30 segundos</p>
                        <a href="{{ route('restaurant.reservations') }}" class="mt-4 inline-flex items-center text-sm font-semibold uppercase tracking-[0.2em]">Ir a reservas</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 py-16">
    <div class="grid gap-10 lg:grid-cols-3">
        <div class="rounded-3xl border border-[#eadcca] bg-white/80 p-6 soft-shadow">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Experiencia</p>
            <h3 class="mt-3 font-playfair text-2xl">Salon con alma</h3>
            <p class="mt-3 text-sm text-[#5a3d2f]">Iluminacion calida, musica curada y mesas amplias para conversaciones sin prisa.</p>
        </div>
        <div class="rounded-3xl border border-[#eadcca] bg-white/80 p-6 soft-shadow">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Producto</p>
            <h3 class="mt-3 font-playfair text-2xl">Cocina de mercado</h3>
            <p class="mt-3 text-sm text-[#5a3d2f]">Ingredientes diarios de pescadores locales, huerta propia y proveedores de confianza.</p>
        </div>
        <div class="rounded-3xl border border-[#eadcca] bg-white/80 p-6 soft-shadow">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Equipo</p>
            <h3 class="mt-3 font-playfair text-2xl">Servicio atento</h3>
            <p class="mt-3 text-sm text-[#5a3d2f]">Un equipo entrenado para explicar cada plato y sugerir el maridaje ideal.</p>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 py-16">
    <div class="flex flex-wrap items-end justify-between gap-6">
        <div>
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Menu destacado</p>
            <h2 class="mt-3 font-playfair text-3xl sm:text-4xl">Sabores que cuentan una historia</h2>
        </div>
        <a href="{{ route('restaurant.menu') }}" class="rounded-full border border-[#c9aa8b] px-5 py-2 text-sm font-semibold text-[#4b3427] transition hover:border-[#b1492c] hover:text-[#b1492c]">Ver menu completo</a>
    </div>

    @php
        $signature = [
            ['name' => 'Fuego del Pacifico', 'desc' => 'Atun, cacao, pure de platanos y sal marina.', 'price' => '$28'],
            ['name' => 'Lomo braseado', 'desc' => 'Carne madurada, mantequilla de hierbas y vino tinto.', 'price' => '$31'],
            ['name' => 'Ravioli de hongos', 'desc' => 'Masa artesanal, crema de trufa y queso curado.', 'price' => '$24'],
            ['name' => 'Postre de cacao', 'desc' => 'Espuma tibia, tierra de chocolate y cafe frio.', 'price' => '$12'],
        ];
    @endphp

    <div class="mt-10 grid gap-6 md:grid-cols-2">
        @foreach ($signature as $dish)
            <div class="flex items-start justify-between gap-6 rounded-3xl border border-[#eadcca] bg-white/80 p-6">
                <div>
                    <h3 class="font-playfair text-2xl">{{ $dish['name'] }}</h3>
                    <p class="mt-2 text-sm text-[#5a3d2f]">{{ $dish['desc'] }}</p>
                </div>
                <span class="text-sm font-semibold text-[#b1492c]">{{ $dish['price'] }}</span>
            </div>
        @endforeach
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 py-16">
    <div class="grid gap-12 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[32px] border border-[#eadcca] bg-[#2b1c16] p-10 text-[#f6efe7]">
            <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Chef</p>
            <h2 class="mt-4 font-playfair text-3xl">Chef Lucia Mendoza</h2>
            <p class="mt-4 text-sm text-[#f0dac2]">15 anos de cocina internacional, formacion en tecnicas de fuego y fermentos.</p>
            <div class="mt-8 grid gap-4 sm:grid-cols-2">
                <div class="rounded-2xl border border-white/15 p-4">
                    <p class="text-2xl font-playfair">12</p>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Reconocimientos</p>
                </div>
                <div class="rounded-2xl border border-white/15 p-4">
                    <p class="text-2xl font-playfair">40</p>
                    <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Platos creados</p>
                </div>
            </div>
        </div>
        <div class="space-y-6">
            <div class="rounded-3xl border border-[#eadcca] bg-white/80 p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Maridajes</p>
                <h3 class="mt-3 font-playfair text-2xl">Carta de vinos selecta</h3>
                <p class="mt-2 text-sm text-[#5a3d2f]">60 etiquetas de productores boutique y catas guiadas cada viernes.</p>
            </div>
            <div class="rounded-3xl border border-[#eadcca] bg-white/80 p-6">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Eventos</p>
                <h3 class="mt-3 font-playfair text-2xl">Musica en vivo</h3>
                <p class="mt-2 text-sm text-[#5a3d2f]">Sesiones acusticas y noches de jazz en la terraza principal.</p>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 py-16">
    <div class="rounded-[36px] bg-[#b1492c] p-10 text-white soft-shadow">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <p class="text-xs uppercase tracking-[0.3em] text-white/70">Reserva abierta</p>
                <h2 class="mt-3 font-playfair text-3xl">Asegura tu mesa para una noche especial</h2>
                <p class="mt-2 text-sm text-white/80">Mesas para parejas, grupos y eventos privados con menu personalizado.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('restaurant.reservations') }}" class="rounded-full bg-white px-6 py-3 text-sm font-semibold text-[#b1492c]">Reservar ahora</a>
                <a href="{{ route('restaurant.events') }}" class="rounded-full border border-white/50 px-6 py-3 text-sm font-semibold text-white">Ver eventos</a>
            </div>
        </div>
    </div>
</section>
@endsection