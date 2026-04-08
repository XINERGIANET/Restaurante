<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-sm">
    <div class="flex items-center justify-between mb-8">
        <h3 class="text-lg font-bold text-gray-800 dark:text-white/90">Últimos productos</h3>
        <button class="text-xs text-blue-600 hover:underline font-semibold flex items-center gap-1">
            Ver todos →
        </button>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-gray-100 dark:border-gray-800">
                    <th class="py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Nombre</th>
                    <th class="py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider text-center">Ventas</th>
                    <th class="py-3 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Ganancia</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
                @foreach($products as $product)
                <tr>
                    <td class="py-4">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center dark:bg-blue-500/10">
                                @if($product['image'])
                                    <img src="{{ $product['image'] }}" class="w-10 h-10 rounded-xl object-cover" alt="{{ $product['name'] }}">
                                @else
                                    <div class="w-6 h-6 rounded-lg bg-blue-500/20"></div>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-gray-800 dark:text-white/90">{{ $product['name'] }}</span>
                        </div>
                    </td>
                    <td class="py-4 text-center">
                        <span class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $product['sales'] }}</span>
                    </td>
                    <td class="py-4">
                        <div class="flex items-center gap-3">
                            <div class="flex-1 h-1.5 w-full bg-gray-100 rounded-full dark:bg-gray-800 max-w-[200px]">
                                <div class="h-full rounded-full {{ $product['profit_percent'] >= 75 ? 'bg-green-500' : ($product['profit_percent'] >= 50 ? 'bg-blue-500' : 'bg-cyan-400') }}" style="width: {{ $product['profit_percent'] }}%"></div>
                            </div>
                            <span class="text-xs font-bold text-gray-800 dark:text-white/90">{{ $product['profit_percent'] }}%</span>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
