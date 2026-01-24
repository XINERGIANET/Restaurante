@extends('layouts.restaurant')

@section('content')
<section class="mx-auto max-w-6xl px-6 pb-12 pt-16">
    <div class="rounded-[36px] border border-[#eadcca] bg-white/80 p-10 soft-shadow">
        <p class="text-xs uppercase tracking-[0.3em] text-[#7b5744]">Galeria</p>
        <h1 class="mt-4 font-playfair text-4xl sm:text-5xl">Momentos que inspiran</h1>
        <p class="mt-4 max-w-2xl text-sm text-[#5a3d2f]">
            Texturas, fuego y detalles del salon. Un vistazo a la experiencia Xinergia.
        </p>
    </div>
</section>

<section class="mx-auto max-w-6xl px-6 pb-20">
    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @for ($i = 0; $i < 9; $i++)
            <div class="group relative overflow-hidden rounded-[28px] border border-[#eadcca] bg-white/70">
                <div class="aspect-[4/5] bg-[radial-gradient(circle_at_top,#f3b27a,transparent_65%)]"></div>
                <div class="absolute inset-0 bg-[linear-gradient(to_top,rgba(43,28,22,0.85),transparent_55%)] opacity-0 transition group-hover:opacity-100"></div>
                <div class="absolute bottom-4 left-4 right-4 text-sm text-white opacity-0 transition group-hover:opacity-100">
                    <p class="font-playfair text-lg">Mesa {{ $i + 1 }}</p>
                    <p class="text-xs text-white/70">Sabores y escenas</p>
                </div>
            </div>
        @endfor
    </div>
</section>
@endsection