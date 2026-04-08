<div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6 shadow-sm h-full">
    <div class="flex items-center justify-between mb-8">
        <h3 class="text-sm font-bold text-gray-800 dark:text-white/90">Ingresos/Egresos</h3>
        <div class="flex items-center gap-4">
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 uppercase">
                <div class="w-2.5 h-2.5 rounded-full bg-blue-500"></div> Ingresos
            </div>
            <div class="flex items-center gap-1.5 text-[10px] font-semibold text-gray-500 uppercase">
                <div class="w-2.5 h-2.5 rounded-full bg-cyan-400"></div> Egresos
            </div>
            <button class="text-xs text-gray-500 hover:text-gray-800">Mensual ▼</button>
        </div>
    </div>

    <div id="incomeExpenseChart" class="h-[250px] w-full"></div>
</div>

<script>
    document.addEventListener('turbo:load', function() {
        const chartElement = document.getElementById('incomeExpenseChart');
        if (!chartElement) return;

        const incomeData = @json($incomeTrend);
        const expenseData = @json($expenseTrend);
        const labels = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'].slice(0, incomeData.length);

        const options = {
            series: [{
                name: 'Ingresos',
                data: incomeData
            }, {
                name: 'Egresos',
                data: expenseData
            }],
            chart: {
                type: 'bar',
                height: 250,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif',
                redrawOnParentResize: true
            },
            colors: ['#3B82F6', '#22D3EE'],
            plotOptions: {
                bar: {
                    horizontal: false,
                    columnWidth: '55%',
                    borderRadius: 4
                }
            },
            dataLabels: { enabled: false },
            legend: { show: false },
            grid: {
                borderColor: '#F3F4F6',
                strokeDashArray: 4,
                xaxis: { lines: { show: false } }
            },
            xaxis: {
                categories: labels,
                labels: { style: { colors: '#9CA3AF', fontSize: '12px' } }
            },
            yaxis: {
                labels: {
                    style: { colors: '#9CA3AF', fontSize: '12px' },
                    formatter: function(val) { return val / 1000 + 'k'; }
                }
            },
            tooltip: { enabled: true }
        };

        setTimeout(() => {
            if (window.incomeExpenseChartInstance) {
                window.incomeExpenseChartInstance.destroy();
            }
            window.incomeExpenseChartInstance = new ApexCharts(chartElement, options);
            window.incomeExpenseChartInstance.render().then(() => {
                window.dispatchEvent(new Event('resize'));
            });
        }, 200);
    });
</script>
