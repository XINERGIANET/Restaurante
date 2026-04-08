<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-sm" 
     x-data="{ 
        activeTab: 'overview',
        startDate: '{{ $startDate }}',
        endDate: '{{ $endDate }}',
        init() {
            flatpickr($refs.datePicker, {
                mode: 'range',
                dateFormat: 'Y-m-d',
                defaultDate: [this.startDate, this.endDate],
                onClose: (selectedDates) => {
                    if (selectedDates.length === 2) {
                        const start = selectedDates[0].toISOString().split('T')[0];
                        const end = selectedDates[1].toISOString().split('T')[0];
                        window.location.href = `{{ route('dashboard') }}?start_date=${start}&end_date=${end}`;
                    }
                }
            });
        },
        updateChart(type) {
            this.activeTab = type;
            const salesData = @json($sales);
            const profitData = @json($profit);
            
            let newSeries = [];
            if (type === 'overview' || type === 'sales') {
                newSeries = [{ name: 'Ventas', data: salesData }];
            } else if (type === 'profit') {
                newSeries = [{ name: 'Ganancia', data: profitData }];
            }
            
            if (window.statisticsChartInstance) {
                window.statisticsChartInstance.updateSeries(newSeries);
            }
        }
     }">
    <div class="flex flex-col gap-5 mb-8 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-lg font-bold text-gray-800 dark:text-white/90">Estadística</h3>
            <p class="text-xs text-gray-500">Objetivo que te has fijado para cada mes</p>
        </div>
        
        <div class="flex items-center gap-2 p-1 bg-gray-50 dark:bg-gray-800/50 rounded-xl">
            <button @click="updateChart('overview')" :class="activeTab === 'overview' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">
                Descripción general
            </button>
            <button @click="updateChart('sales')" :class="activeTab === 'sales' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">
                Ventas
            </button>
            <button @click="updateChart('profit')" :class="activeTab === 'profit' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500 hover:text-gray-700'" class="px-4 py-1.5 text-xs font-bold rounded-lg transition-all">
                Ganancia
            </button>
            
            <div class="h-6 w-px bg-gray-200 dark:bg-gray-700 mx-1"></div>
            
            <button x-ref="datePicker" class="flex items-center gap-2 px-3 py-1.5 text-xs font-bold text-gray-500 bg-transparent hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                <span x-text="startDate === endDate ? startDate : (new Date(startDate + 'T12:00:00').toLocaleDateString('es-ES', {month:'short', day:'numeric'}) + ' a ' + new Date(endDate + 'T12:00:00').toLocaleDateString('es-ES', {month:'short', day:'numeric'}))"></span>
            </button>
        </div>
    </div>

    <div id="statisticsChart" class="w-full"></div>
</div>

<script>
    document.addEventListener('turbo:load', function() {
        const chartElement = document.getElementById('statisticsChart');
        if (!chartElement) return;

        const options = {
            series: [{
                name: 'Ventas',
                data: @json($sales)
            }],
            chart: {
                height: 350,
                type: 'area',
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif',
                zoom: { enabled: false },
                redrawOnParentResize: true
            },
            colors: ['#3b82f6'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                    stops: [0, 90, 100]
                }
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'straight',
                width: 3
            },
            grid: {
                borderColor: '#f1f1f1',
                strokeDashArray: 4,
                yaxis: { lines: { show: true } }
            },
            xaxis: {
                categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                axisBorder: { show: false },
                axisTicks: { show: false }
            },
            yaxis: {
                labels: {
                    formatter: function (val) {
                        return val.toFixed(2);
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return "S/ " + val.toFixed(2);
                    }
                }
            }
        };

        setTimeout(() => {
            if (window.statisticsChartInstance) {
                window.statisticsChartInstance.destroy();
            }
            window.statisticsChartInstance = new ApexCharts(chartElement, options);
            window.statisticsChartInstance.render().then(() => {
                window.dispatchEvent(new Event('resize'));
            });
        }, 200);
    });
</script>
