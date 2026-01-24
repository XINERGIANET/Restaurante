@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="grid gap-10 lg:grid-cols-[1.1fr_0.9fr]">
        <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
            <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Reservas</p>
            <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Reserva tu mesa</h1>
            <p class="mt-4 text-sm text-[#5a3d2f]">
                Disponemos de salon principal, barra de cocteles y terraza. Completa el formulario y nuestro equipo
                confirmara tu reserva en menos de 2 horas.
            </p>
            <form class="mt-8 grid gap-5">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Fecha</label>
                        <input type="date" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" />
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Hora</label>
                        <input type="time" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Nombre</label>
                        <input type="text" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="Nombre completo" />
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Telefono</label>
                        <input type="text" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="+57 300 000 0000" />
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Personas</label>
                        <select class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm">
                            <option>2 personas</option>
                            <option>4 personas</option>
                            <option>6 personas</option>
                            <option>8 personas</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Area</label>
                        <select class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm">
                            <option>Salon principal</option>
                            <option>Barra de cocteles</option>
                            <option>Terraza</option>
                            <option>Chef table</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Notas</label>
                    <textarea rows="3" class="mt-2 w-full rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm" placeholder="Alergias, celebraciones o preferencias"></textarea>
                </div>
                <button type="submit" class="rounded-full bg-[#b1492c] px-6 py-3 text-sm font-semibold text-white shadow-md shadow-[#b1492c]/40 transition hover:bg-[#923820]">Solicitar reserva</button>
            </form>
        </div>
        <div class="space-y-6">
            <div class="rounded-[32px] border border-[#eadcca] bg-[#2b1c16] p-8 text-[#f6efe7]">
                <p class="text-xs uppercase tracking-[0.3em] text-[#e6c8a7]">Politica</p>
                <h3 class="mt-3 font-playfair text-2xl">Confirmacion rapida</h3>
                <ul class="mt-4 space-y-3 text-sm text-[#f0dac2]">
                    <li>Reservas con al menos 2 horas de anticipacion.</li>
                    <li>Retrasos de 15 minutos pueden liberar la mesa.</li>
                    <li>Eventos privados con menu personalizado.</li>
                </ul>
            </div>
            <div class="rounded-[32px] border border-[#eadcca] bg-white/80 p-8">
                <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Contacto directo</p>
                <h3 class="mt-3 font-playfair text-2xl">Estamos para ayudarte</h3>
                <p class="mt-2 text-sm text-[#5a3d2f]">Para eventos corporativos o celebraciones especiales, escribe a eventos@xinergia.rest</p>
                <div class="mt-6 rounded-2xl border border-[#eadcca] bg-white px-4 py-3 text-sm text-[#5a3d2f]">Telefono: +57 320 000 0000</div>
            </div>
        </div>
    </div>
</section>
@endsection