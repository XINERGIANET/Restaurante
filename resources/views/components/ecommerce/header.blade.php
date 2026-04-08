<div class="mb-8 w-full">
    @php
        $resolvedUserName = $userName ?? 'Administrador';
        $resolvedCompanyName = $companyName ?? config('app.name', 'Sistema');
    @endphp

    <div class="flex items-center gap-6 rounded-3xl border border-gray-100 bg-white p-6 shadow-sm">
        <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-50">
            <i class="ri-user-line text-2xl text-blue-600"></i>
        </div>

        <div class="flex-grow">
            <h1 class="text-xl font-black tracking-tight text-gray-800">
                Hola, {{ $resolvedUserName }}!
            </h1>
            <p class="mt-1 text-sm font-medium text-gray-500">
                Bienvenido al panel central de <span class="font-bold uppercase tracking-wider text-gray-600">{{ $resolvedCompanyName }}</span>. Gestiona tus operaciones diarias con eficiencia.
            </p>
        </div>
    </div>
</div>
