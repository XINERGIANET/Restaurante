@php
    $printer = $printer ?? null;
    $connection = old('connection_type', $printer?->connection_type ?? (filled($printer?->ip) ? 'network' : 'usb'));
@endphp

<div x-data="{ connection: @js($connection) }" class="space-y-5">
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre visible</label>
            <input type="text" name="name" value="{{ old('name', $printer?->name) }}" required placeholder="Ej: Ticketera Cocina 1"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
            @error('name')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ubicación física</label>
            <input type="text" name="location" value="{{ old('location', $printer?->location) }}" placeholder="Ej: Cocina caliente / Caja 1"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white">
        </div>
    </div>

    <div>
        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de conexión</label>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <label class="cursor-pointer rounded-xl border p-4 transition" :class="connection === 'usb' ? 'border-[#C43B25] bg-orange-50 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-700'">
                <input class="sr-only" type="radio" name="connection_type" value="usb" x-model="connection">
                <span class="flex items-center gap-3"><i class="ri-usb-line text-2xl text-[#C43B25]"></i><span><strong class="block text-sm text-gray-900 dark:text-white">USB / local</strong><small class="text-gray-500">Conectada físicamente a una PC con QZ Tray</small></span></span>
            </label>
            <label class="cursor-pointer rounded-xl border p-4 transition" :class="connection === 'network' ? 'border-[#C43B25] bg-orange-50 dark:bg-orange-500/10' : 'border-gray-200 dark:border-gray-700'">
                <input class="sr-only" type="radio" name="connection_type" value="network" x-model="connection">
                <span class="flex items-center gap-3"><i class="ri-router-line text-2xl text-[#C43B25]"></i><span><strong class="block text-sm text-gray-900 dark:text-white">Red LAN</strong><small class="text-gray-500">La ticketera tiene IP propia</small></span></span>
            </label>
        </div>
    </div>

    <div x-show="connection === 'usb'" x-cloak class="rounded-xl border border-blue-100 bg-blue-50/60 p-4 dark:border-blue-900 dark:bg-blue-500/5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">PC a la que está conectada</label>
                <select name="print_station_id" :required="connection === 'usb'" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-3 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
                    <option value="">Seleccionar estación...</option>
                    @foreach($stations ?? [] as $station)<option value="{{ $station->id }}" @selected((string) old('print_station_id', $printer?->print_station_id) === (string) $station->id)>{{ $station->name }} · {{ $station->ip_address }}</option>@endforeach
                </select>
                @error('print_station_id')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre exacto en Windows/QZ</label>
                <input name="driver_name" value="{{ old('driver_name', $printer?->driver_name) }}" placeholder="Ej: EPSON TM-T20III Receipt"
                    class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">
            </div>
        </div>
    </div>

    <div x-show="connection === 'network'" x-cloak class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-500/5">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2"><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">IP de la ticketera</label><input name="ip" :required="connection === 'network'" value="{{ old('ip', $printer?->ip) }}" placeholder="192.168.1.50" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white">@error('ip')<p class="mt-1 text-sm text-error-500">{{ $message }}</p>@enderror</div>
            <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Puerto RAW</label><input type="number" name="port" min="1" max="65535" value="{{ old('port', $printer?->port ?? 9100) }}" class="h-11 w-full rounded-lg border border-gray-300 bg-white px-4 text-sm dark:border-gray-700 dark:bg-gray-900 dark:text-white"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Ancho de papel</label><select name="width" class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-sm dark:border-gray-700 dark:text-white"><option value="80" @selected((string) old('width', $printer?->width ?? '80') === '80')>80 mm</option><option value="58" @selected((string) old('width', $printer?->width) === '58')>58 mm</option></select></div>
        <div><label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">Notas</label><input name="notes" value="{{ old('notes', $printer?->notes) }}" placeholder="Modelo, función, observaciones..." class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 text-sm dark:border-gray-700 dark:text-white"></div>
    </div>
</div>
