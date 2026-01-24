@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Contacto</p>
            <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Hablemos de tu visita</h1>
            <p class="mt-4 text-sm text-[#5a3d2f]">
                Si deseas reservar un evento o hacer una consulta especial, escribenos.
            </p>
            <form class="mt-8 grid gap-5">
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Nombre</label>
                    <input type="text" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="Nombre completo" />
                </div>
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Correo</label>
                    <input type="email" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="correo@dominio.com" />
                </div>
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Mensaje</label>
                    <textarea rows="4" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="Cuantos invitados, fecha, horario"></textarea>
                </div>
                <button type="submit" class="rounded-full bg-[#b1492c] px-6 py-3 text-sm font-semibold text-white shadow-md shadow-[#b1492c]/40 transition hover:bg-[#923820]">Enviar mensaje</button>
            </form>
        </div>
        <div class="space-y-6">
            <div class="rounded-[32px] border border-[#eadcca] bg-[#2b1c16] p-8 text-[#f6efe7]">
                <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Direccion</p>
                <h3 class="mt-3 font-playfair text-2xl">Av. Del Sabor 125</h3>
                <p class="mt-2 text-sm text-[#f0dac2]">Distrito Gastronomico, ciudad principal.</p>
                <p class="mt-4 text-sm text-[#f0dac2]">+57 320 000 0000</p>
                <p class="text-sm text-[#f0dac2]">hola@xinergia.rest</p>
            </div>
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Horarios</p>
                <div class="mt-4 space-y-3 text-sm text-[#5a3d2f]">
                    <div class="flex items-center justify-between border-b border-[#eadcca] pb-2">
                        <span>Lun - Jue</span>
                        <span>12:00 - 22:30</span>
                    </div>
                    <div class="flex items-center justify-between border-b border-[#eadcca] pb-2">
                        <span>Vie - Sab</span>
                        <span>12:00 - 00:00</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span>Domingo</span>
                        <span>12:00 - 18:00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection