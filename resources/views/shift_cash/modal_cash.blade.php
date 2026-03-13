<div class="grid gap-5 sm:grid-cols-2">
    {{-- Columna 1 --}}
    <div class="space-y-2 p-5">
        <div class="flex items-center gap-2">
            <input type="checkbox" name="all_options" id="all_options" onchange="toggleAllOptions()">
            <label for="all_options">Todos</label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="sales_payments_summary" id="sales_payments_summary">
            <label for="sales_payments_summary">
                Resúmenes - Ventas pagadas
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="products_sold_summary" id="products_sold_summary">
            <label for="products_sold_summary">
                Consolidado de productos vendidos
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="cancellations_products" id="cancellations_products">
            <label for="cancellations_products">
                Anulaciones de productos
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="expenses_by_payment_method_paid" id="expenses_by_payment_method_paid">
            <label for="expenses_by_payment_method_paid">
                Egresos (gastos) por método de pago - pagados
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="discounts_by_product" id="discounts_by_product">
            <label for="discounts_by_product">
                Descuentos por producto
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="debts_sales" id="debts_sales">
            <label for="debts_sales">
                Ventas adeudadas
            </label>
        </div>
    </div>

    {{-- Columna 2 --}}
    <div class="space-y-2 p-5">
        <div class="flex items-center gap-2">
            <input type="checkbox" name="paid_sales_by_method" id="paid_sales_by_method">
            <label for="paid_sales_by_method">
                Ventas por métodos de pago - pagadas
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="sales_details_by_product" id="sales_details_by_product">
            <label for="sales_details_by_product">
                Detalles de venta - por producto
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="sales_cancellations" id="sales_cancellations">
            <label for="sales_cancellations">
                Anulaciones de ventas
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="cancellations_history" id="cancellations_history">
            <label for="cancellations_history">
                Histórico de anulaciones
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="income_by_payment_method_paid" id="income_by_payment_method_paid">
            <label for="income_by_payment_method_paid">
                Ingresos por método de pago - pagados
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="discounts_by_person" id="discounts_by_person">
            <label for="discounts_by_person">
                Descuentos - por persona
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="courtesies" id="courtesies">
            <label for="courtesies">
                Cortesías
            </label>
        </div>

        <div class="flex items-center gap-2">
            <input type="checkbox" name="debts_sales_summary" id="debts_sales_summary">
            <label for="debts_sales_summary">
                Resúmenes - ventas adeudadas
            </label>
        </div>
    </div>
</div>
<script>
    function toggleAllOptions() {
        const allOptions = document.getElementById('all_options');
        const options = document.querySelectorAll('input[type="checkbox"]');
        options.forEach(option => {
            option.checked = allOptions.checked;
        });
    }
</script>