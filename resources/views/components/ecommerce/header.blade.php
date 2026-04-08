<div class="mb-8 w-full">
    <div class="bg-white rounded-3xl p-6 shadow-sm border border-gray-100 flex items-center gap-6">
        <!-- Icon section -->
        <div class="flex-shrink-0 w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center">
            <i class="ri-user-line text-2xl text-blue-600"></i>
        </div>

        <!-- Text section -->
        <div class="flex-grow">
            <h1 class="text-xl font-black text-gray-800 tracking-tight">
                ¡Hola, {{ $userName }}!
            </h1>
            <p class="mt-1 text-sm font-medium text-gray-500">
                Bienvenido al panel central de <span class="font-bold text-gray-600 uppercase tracking-wider">{{ $companyName }}</span>. Gestiona tus operaciones diarias con eficiencia.
            </p>
        </div>
    </div>
</div>
