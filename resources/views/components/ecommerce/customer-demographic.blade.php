<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-sm h-full">
    <div class="flex items-center justify-between mb-8">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white/90">Más comprados/vendidos</h3>
        <button class="text-xs text-gray-500 hover:text-gray-800">Mensual ▼</button>
    </div>

    <div class="relative flex items-center justify-center -mt-2">
        <div id="topProductsDonut" style="min-height: 180px;" class="w-full"></div>
        <div class="absolute flex flex-col items-center pointer-events-none" style="top: 50%; left: 50%; transform: translate(-50%, -50%);">
            <span class="text-2xl font-bold text-gray-800 dark:text-white/90">{{ $topProducts->sum('count') }}</span>
            <span class="text-[10px] text-gray-400 font-medium">Total</span>
        </div>
    </div>

    <div class="mt-8 space-y-4">
        @foreach($topProducts as $index => $product)
        <div class="flex items-center gap-3">
            <div class="w-3.5 h-3.5 rounded-full {{ $index == 0 ? 'bg-blue-500' : 'bg-cyan-400' }}"></div>
            <div class="flex flex-col">
                <span class="text-xs font-bold text-gray-800 dark:text-white/90">{{ $product['name'] }}</span>
                <span class="text-[10px] text-gray-400">{{ $index == 0 ? 'Más vendido' : 'Segundo lugar' }}</span>
            </div>
            <span class="ml-auto text-sm font-bold text-gray-800 dark:text-white/90">{{ $product['count'] }}</span>
        </div>
        @endforeach
    </div>
</div>

<script>
    document.addEventListener('turbo:load', function() {
        const chartElement = document.getElementById('topProductsDonut');
        if (!chartElement) return;

        const data = @json($topProducts->pluck('count')).map(Number);
        const labels = @json($topProducts->pluck('name'));
        
        const options = {
            series: data,
            chart: {
                type: 'donut',
                height: 200,
                fontFamily: 'Inter, sans-serif',
                redrawOnParentResize: true
            },
            labels: labels,
            colors: ['#3B82F6', '#22D3EE'],
            stroke: { show: false },
            dataLabels: { enabled: false },
            legend: { show: false },
            plotOptions: {
                pie: {
                    donut: {
                        size: '80%',
                        labels: { show: false } // We use absolute div for custom label
                    }
                }
            },
            tooltip: { enabled: true }
        };

        setTimeout(() => {
            if (window.topProductsDonutInstance) {
                window.topProductsDonutInstance.destroy();
            }
            window.topProductsDonutInstance = new ApexCharts(chartElement, options);
            window.topProductsDonutInstance.render().then(() => {
                window.dispatchEvent(new Event('resize'));
            });
        }, 200);
    });
</script>
