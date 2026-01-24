@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-10 pt-16">
    <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Nuestra historia</p>
        <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Un lugar pensado para quedarse</h1>
        <p class="mt-4 max-w-2xl text-sm text-[#5a3d2f]">
            Xinergia nace de la union entre cocina de costa, fuego lento y un servicio que cuida cada detalle.
            Creemos en el tiempo, en la conversacion y en los aromas que conectan recuerdos.
        </p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-16">
    <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="space-y-6">
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Filosofia</p>
                <h2 class="mt-3 font-playfair text-3xl">Cocina viva</h2>
                <p class="mt-3 text-sm text-[#5a3d2f]">
                    Trabajamos con productores locales, hornos de carbon y tecnicas de fermentacion.
                    Cada menu se construye desde el origen del ingrediente.
                </p>
            </div>
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Equipo</p>
                <h2 class="mt-3 font-playfair text-3xl">Manos expertas</h2>
                <p class="mt-3 text-sm text-[#5a3d2f]">
                    Cocineros, panaderos y mixologos trabajan en equipo para cuidar la experiencia de principio a fin.
                </p>
            </div>
        </div>
        <div class="rounded-[32px] border border-[#eadcca] bg-[#2b1c16] p-8 text-[#f6efe7]">
            <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Linea de tiempo</p>
            <div class="mt-6 space-y-5">
                <div>
                    <p class="text-sm font-semibold">2018</p>
                    <p class="text-sm text-[#f0dac2]">Abrimos con una mesa comun y 12 platos.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold">2021</p>
                    <p class="text-sm text-[#f0dac2]">Cocina abierta y barra de cocteles de autor.</p>
                </div>
                <div>
                    <p class="text-sm font-semibold">2024</p>
                    <p class="text-sm text-[#f0dac2]">Menu degustacion y experiencias privadas.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="grid gap-6 md:grid-cols-3">
        <div class="rounded-[28px] border border-[#eadcca] bg-white/80 p-6">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Reconocimiento</p>
            <h3 class="mt-3 font-playfair text-2xl">Premio Cocina Viva</h3>
            <p class="mt-2 text-sm text-[#5a3d2f]">Mejor propuesta gastronomica regional.</p>
        </div>
        <div class="rounded-[28px] border border-[#eadcca] bg-white/80 p-6">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Sostenibilidad</p>
            <h3 class="mt-3 font-playfair text-2xl">Cocina responsable</h3>
            <p class="mt-2 text-sm text-[#5a3d2f]">Procesos de cero desperdicio y energia eficiente.</p>
        </div>
        <div class="rounded-[28px] border border-[#eadcca] bg-white/80 p-6">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Hospitalidad</p>
            <h3 class="mt-3 font-playfair text-2xl">Servicio premium</h3>
            <p class="mt-2 text-sm text-[#5a3d2f]">Un equipo atento y preparado para cada detalle.</p>
        </div>
    </div>
</section>
@endsection