@extends('layouts.app')

@section('header')
    <h2>Módulo de pago</h2>
    <p>Gestiona de pago de las órdenes</p>
@endsection

@section('content')
    <div class="container-fluid content-inner mt-n5 py-0">
        <div class="row">
            <div class="col-12">
                <div class="card h-100 shadow-lg border-0">
                    <div class="card-body p-4">
                        <div class="row g-4">
                            <!-- Panel Izquierdo: Lista de Pedidos -->
                            <div class="col-lg-5">
                                <div class="orders-panel bg-light rounded-3 p-4 h-100">
                                    <!-- Header -->
                                    <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                                        <h4 class="mb-0 fw-bold text-primary">
                                            <i class="bi bi-cart3 me-2"></i>Pedido
                                        </h4>
                                        <div class="list-group-item bg-transparent border-0 text-primary p-3"
                                            onclick="window.location.href='{{ route('restaurante.orders', [$mesa->id]) }}'"
                                            style="cursor:pointer; transition: all 0.3s;">
                                            <i class="bi bi-arrow-left-circle me-2"></i>
                                            <strong>Volver a Mesa</strong>
                                        </div>
                                    </div>

                                    <!-- Mesa Badge -->
                                    <div class="mb-4">
                                        <span class="badge bg-primary bg-gradient fs-6 px-3 py-2">
                                            <i class="bi bi-table me-2"></i>Mesa N°1
                                        </span>
                                    </div>

                                    <!-- Cliente Info -->
                                    <div class="bg-white rounded-3 p-3 mb-4 shadow-sm">
                                        <div class="mb-3">
                                            <label class="form-label fw-bold mb-2">
                                                <i class="bi bi-card-text me-2 text-primary"></i>DNI/RUC
                                            </label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" id="document" name="document"
                                                    maxlength="11" onkeypress="isNumber(event)"
                                                    placeholder="Ingrese documento">
                                                <button type="button" class="btn btn-primary"
                                                    onclick="searchAPI('#document','#name','#address')">
                                                    <i class="bi bi-search"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div class="mb-0">
                                            <label class="form-label fw-bold mb-2">
                                                <i class="bi bi-person me-2 text-primary"></i>Cliente
                                            </label>
                                            <input type="text" class="form-control" id="client" name="client"
                                                placeholder="Nombre del cliente">
                                        </div>
                                    </div>

                                    <!-- Lista de Productos -->
                                    <div id="orders-list" class="mb-4">
                                        <div class="d-flex mb-3 px-2 text-muted small fw-bold border-bottom pb-2">
                                            <div class="text-center" style="width: 55px;">CANT.</div>
                                            <div class="text-center flex-grow-1 ps-2">PRODUCTO</div>
                                            <div class="text-end" style="width: 70px;">P.U.</div>
                                            <div class="text-end" style="width: 70px;">DCTO</div>
                                            <div class="text-end" style="width: 80px;">SUBT</div>
                                        </div>
                                        <!-- Productos Scrollable -->
                                        <div class="order-products-scroll" id="order-product-items"
                                            style="max-height: 280px; overflow-y: auto;">
                                            @foreach ($products as $product)
                                                <div class="order-product-item bg-white rounded-3 px-2 py-2 mb-2 shadow-sm border border-light"
                                                    data-id="{{ $product['id'] }}">
                                                    <div class="d-flex align-items-center">
                                                        <div class="text-center" style="width: 55px;">
                                                            <span class="badge bg-secondary rounded-circle"
                                                                style="width: 32px; height: 32px; line-height: 22px; font-size: 15px;">{{ $product['quantity'] }}</span>
                                                        </div>
                                                        <div class="flex-grow-1 text-start ps-1 pe-2">
                                                            <div class="fw-bold text-dark small mb-1">{{ $product['name'] }}
                                                            </div>
                                                            @if (!empty($product['description']))
                                                                <small
                                                                    class="text-muted">{{ $product['description'] }}</small>
                                                            @endif
                                                        </div>
                                                        <div class="text-end" style="width: 70px;">
                                                            <span class="fw-bold text-success small">S/
                                                                {{ number_format($product['unit_price'], 2) }}</span>
                                                        </div>
                                                        <div class="text-end" style="width: 70px;">
                                                            <span class="fw-bold text-danger small">S/
                                                                {{ number_format($product['discount'], 2) }}</span>
                                                        </div>
                                                        <div class="text-end" style="width: 80px;">
                                                            <span class="fw-bold small">S/
                                                                {{ number_format($product['subtotal'], 2) }}</span>
                                                        </div>
                                                    </div>
                                                    <!-- Botón eliminar: oculto por ahora -->
                                                    <button class="btn btn-sm btn-outline-danger mt-2 w-100 d-none">
                                                        <i class="bi bi-trash3 me-1"></i>Eliminar
                                                    </button>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>

                                    <button id="btn-separar"
                                        class="btn btn-primary btn-lg w-100 py-3 shadow btn-separar-cuenta mb-2 d-none">
                                        <i class="bi bi-check-circle me-2"></i>
                                        <span class="fw-bold" id="btn-text">Separar cuenta</span>
                                    </button>

                                    <!-- Totales -->
                                    <div id="orders-total" class="bg-white rounded-3 p-3 shadow-sm">
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="text-muted">Subtotal:</span>
                                            <span class="fw-bold">S/ <span
                                                    id="subtotalAmount">{{ number_format($products->sum('subtotal'), 2, '.', '') }}</span></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="text-muted">Descuento:</span>
                                            <span class="fw-bold">S/ <span
                                                    id="discountAmount">{{ number_format($products->sum('discount'), 2, '.', '') }}</span></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                                            <span class="text-muted">IGV (18%):</span>
                                            <span class="fw-bold">S/ <span
                                                    id="IGVAmount">{{ number_format($products->sum('subtotal') * 0.18, 2, '.', '') }}</span></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                                            <span class="fs-5 fw-bold text-primary">Total:</span>
                                            <span class="fs-4 fw-bold text-success">S/ <span
                                                    id="totalAmount">{{ $totalPagar }}</span></span>
                                        </div>
                                        <div
                                            class="d-flex justify-content-between align-items-center bg-light rounded p-2">
                                            <span class="fw-bold">Pagado:</span>
                                            <span class="fw-bold text-info">S/ <span id="paidAmount">0.00</span></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Panel Derecho: Pagos -->
                            <div class="col-lg-7">
                                <div class="card shadow-sm border-0 h-100">
                                    <div class="card-body p-4">
                                        <!-- Total a Pagar Header -->
                                        <div class="text-center mb-4 pb-4 border-bottom">
                                            <p class="text-muted mb-2 text-uppercase fw-bold small">Total a Pagar</p>
                                            <h2 class="text-success fw-bold mb-0">
                                                S/ <span id="totalAmountDisplay">{{ $totalPagar }}</span>
                                            </h2>
                                        </div>

                                        <!-- Tipo de Comprobante -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold mb-3">
                                                <i class="bi bi-receipt me-2 text-primary"></i>TIPO DE COMPROBANTE
                                            </label>
                                            <div class="d-flex gap-2 flex-wrap">
                                                <button
                                                    class="btn btn-outline-primary flex-fill py-2 rounded-3 hover-shadow voucher-btn"
                                                    data-voucher-type="Factura">
                                                    <i class="bi bi-file-earmark-text fs-4 d-block mb-2"></i>
                                                    <span class="fw-bold">FACTURA</span>
                                                </button>
                                                <button
                                                    class="btn btn-outline-success flex-fill py-2 rounded-3 hover-shadow voucher-btn"
                                                    data-voucher-type="Boleta">
                                                    <i class="bi bi-file-earmark-check fs-4 d-block mb-2"></i>
                                                    <span class="fw-bold">BOLETA</span>
                                                </button>
                                                <button
                                                    class="btn btn-outline-info flex-fill py-2 rounded-3 hover-shadow voucher-btn"
                                                    data-voucher-type="Ticket">
                                                    <i class="bi bi-receipt-cutoff fs-4 d-block mb-2"></i>
                                                    <span class="fw-bold">TICKET</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Método de Pago -->
                                        <div class="mb-4">
                                            <label class="form-label fw-bold mb-3">
                                                <i class="bi bi-wallet2 me-2 text-primary"></i>MÉTODO DE PAGO
                                            </label>
                                            <div class="row g-2 mb-3">
                                                @foreach ($paymentMethods as $index => $pm)
                                                    <div class="col-2 d-flex justify-content-center">
                                                        <button type="button"
                                                            class="btn btn-outline-custom-{{ ($index % 5) + 1 }} w-100 py-2 rounded-3 hover-shadow payment-method-btn"
                                                            data-method-id="{{ $pm->id }}"
                                                            data-method-name="{{ $pm->name }}">
                                                            <i
                                                                class="bi bi-{{ strtolower($pm->name) === 'efectivo'
                                                                    ? 'cash-stack'
                                                                    : (strtolower($pm->name) === 'yape'
                                                                        ? 'phone'
                                                                        : (strtolower($pm->name) === 'plin'
                                                                            ? 'wallet'
                                                                            : (strtolower($pm->name) === 'transferencia'
                                                                                ? 'bank'
                                                                                : (strtolower($pm->name) === 'tarjeta'
                                                                                    ? 'credit-card'
                                                                                    : 'wallet2')))) }} fs-4 d-block mb-2"></i>
                                                            <span class="fw-bold">{{ strtoupper($pm->name) }}</span>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>

                                            <!-- Campos de Efectivo -->
                                            <div class="d-none" id="campos-pago">
                                                <div class="bg-light rounded-3 p-3">
                                                    <input type="hidden" id="selected-method-id">
                                                    <input type="hidden" id="selected-method-name">
                                                    <div class="mb-3">
                                                        <div class="d-flex align-items-center">
                                                            <p class="text-primary mb-0 me-4" id="display-method-name">
                                                            </p>
                                                            <input type="number" class="form-control form-control-sm"
                                                                id="payment-amount" placeholder="Ingrese monto"
                                                                step="0.01" min="0" style="width: 450px;">
                                                        </div>
                                                    </div>

                                                    <div class="d-flex gap-2">
                                                        <button type="button" class="btn btn-success flex-fill"
                                                            id="btn-save-payment">
                                                            <i class="bi bi-check-lg me-2"></i>Guardar
                                                        </button>
                                                        <button type="button" class="btn btn-secondary flex-fill"
                                                            id="btn-cancel-payment">
                                                            <i class="bi bi-x-lg me-2"></i>Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Lista de Pagos -->
                                        <div class="mb-4" id="pagos-list">
                                            <label class="form-label fw-bold mb-3">
                                                <i class="bi bi-list-check me-2 text-primary"></i>PAGOS REALIZADOS
                                            </label>
                                            <div class="table-responsive">
                                                <table class="table table-hover align-middle">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th class="fw-bold">MÉTODO DE PAGO</th>
                                                            <th class="fw-bold text-end">MONTO</th>
                                                            <th class="fw-bold text-center" style="width: 80px;">ACCIÓN
                                                            </th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @if (isset($pagos) && count($pagos) > 0)
                                                            @foreach ($pagos as $pago)
                                                                <tr>
                                                                    <td class="fw-bold">
                                                                        {{ $pago['metodo'] ?? 'Sin método' }}</td>
                                                                    <td class="fw-bold text-end">S/
                                                                        {{ number_format($pago['monto'] ?? 0, 2) }}</td>
                                                                    <td class="text-center">
                                                                        <button
                                                                            class="btn btn-sm btn-danger btn-delete-payment"
                                                                            data-index="{{ $loop->index }}"
                                                                            title="Eliminar pago">
                                                                            <i class="bi bi-trash"></i>
                                                                        </button>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        @else
                                                            <tr>
                                                                <td colspan="3" class="text-center text-muted">
                                                                    No hay pagos realizados aún.
                                                                </td>
                                                            </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div class="form-check d-flex align-items-center mt-3">
                                            <input class="form-check-input" type="checkbox" value=""
                                                id="tipCheck">
                                            <label class="form-check-label fw-bold ms-2 mb-0"
                                                for="tipCheck">Propina</label>

                                            <!-- campo al lado del check, oculto por defecto -->
                                            <div id="tip-field" class="d-none ms-3">
                                                <input type="number" class="form-control form-control-sm" id="tipAmount"
                                                    min="0" step="0.01" name="tip"
                                                    placeholder="Propina (S/ 0.00)" style="width:auto;">
                                            </div>
                                        </div>

                                        <!-- Monto Restante y Botón Final -->
                                        <div class="border-top pt-4">
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="mb-0 fw-bold">Falta por pagar:</h5>
                                                <h4 class="mb-0 fw-bold text-danger">S/ {{ $totalPagar }}</h4>
                                            </div>
                                            <button class="btn btn-success btn-lg w-100 py-3 shadow btn-completar-venta">
                                                <i class="bi bi-check-circle me-2"></i>
                                                <span class="fw-bold">COMPLETAR VENTA</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-outline-custom-1 {
            border-color: #007bff;
            color: #007bff;
        }

        .btn-outline-custom-2 {
            border-color: #28a745;
            color: #28a745;
        }

        .btn-outline-custom-3 {
            border-color: #ffc107;
            color: #ffc107;
        }

        .btn-outline-custom-4 {
            border-color: #6f42c1;
            color: #6f42c1;
        }

        .btn-outline-custom-5 {
            border-color: #17a2b8;
            color: #17a2b8;
        }



        .hover-shadow {
            transition: all 0.3s ease;
        }

        .hover-shadow:hover {
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            transform: translateY(-2px);
        }

        .order-products-scroll::-webkit-scrollbar {
            width: 6px;
        }

        .order-products-scroll::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .order-products-scroll::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .order-products-scroll::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        .order-product-item {
            transition: all 0.2s ease;
        }

        .order-product-item:hover {
            /* transform: translateX(5px); */
            box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, 0.1) !important;
        }

        .payment-method-btn.active {
            background-color: var(--bs-primary);
            color: white;
            border-color: var(--bs-primary);
        }

        .payment-method-btn.active i {
            color: white;
        }

        .payment-method-btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.7rem;
        }

        .payment-method-btn i {
            font-size: 1rem !important;
            margin-bottom: 0.15rem !important;
        }

        .voucher-btn.active {
            background-color: var(--bs-primary) !important;
            color: white !important;
            border-color: var(--bs-primary) !important;
        }

        .voucher-btn.active i {
            color: white !important;
        }

        .voucher-btn {
            padding: 0.35rem 0.5rem;
            font-size: 0.7rem;
        }

        .voucher-btn i {
            font-size: 1rem !important;
            margin-bottom: 0.15rem !important;
        }
    </style>
@endsection

@section('scripts')
    <script>
        console.log('🚀 Script de pago cargado');

        // Variables globales
        let pagosRealizados = @json($pagos ?? []);
        let totalPagar = {{ $totalPagar }};
        let subtotalBase = {{ $subtotal }};
        let igvBase = {{ $igv }};
        let totalPagado = 0;

        // Calcular total pagado
        function calcularTotalPagado() {
            totalPagado = pagosRealizados.reduce((sum, pago) => sum + parseFloat(pago.monto || 0), 0);
            return totalPagado;
        }

        $(document).on('change', '#tipCheck', function() {
            if ($(this).is(':checked')) {
                $('#tip-field').removeClass('d-none');
                $('#tipAmount').focus();
            } else {
                $('#tip-field').addClass('d-none');
                $('#tipAmount').val('');
            }
        });

        // Actualizar UI
        function actualizarUI() {
            calcularTotalPagado();

            // Actualizar montos en panel izquierdo
            $('#subtotalAmount').text(subtotalBase.toFixed(2));
            $('#IGVAmount').text(igvBase.toFixed(2));
            $('#totalAmount').text(totalPagar.toFixed(2));
            $('#paidAmount').text(totalPagado.toFixed(2));

            // Actualizar monto en panel derecho
            $('#totalAmountDisplay').text(totalPagar.toFixed(2));

            let faltaPagar = totalPagar - totalPagado;
            // Actualizar el "Falta por pagar"
            $('.border-top .text-danger').text('S/ ' + faltaPagar.toFixed(2));

            // Actualizar tabla de pagos
            actualizarTablaPagos();
        }

        // Actualizar tabla de pagos
        function actualizarTablaPagos() {
            let tbody = $('#pagos-list tbody');
            tbody.empty();

            if (pagosRealizados.length === 0) {
                tbody.append(`
            <tr>
                <td colspan="3" class="text-center text-muted">
                    No hay pagos realizados aún.
                </td>
            </tr>
        `);
            } else {
                pagosRealizados.forEach((pago, index) => {
                    tbody.append(`
                <tr>
                    <td class="fw-bold">${pago.metodo}</td>
                    <td class="fw-bold text-end">S/ ${parseFloat(pago.monto).toFixed(2)}</td>
                    <td class="text-center">
                        <button class="btn btn-sm btn-danger btn-delete-payment" 
                                data-index="${index}" 
                                title="Eliminar pago">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
                });
            }
        }

        // Click en método de pago
        $(document).on('click', '.payment-method-btn', function() {
            console.log('✅ Click en botón de pago detectado');
            let methodId = $(this).data('method-id');
            let methodName = $(this).data('method-name');
            console.log('Método seleccionado:', methodName, 'ID:', methodId);

            $('#selected-method-id').val(methodId);
            $('#selected-method-name').val(methodName);
            $('#display-method-name').text(methodName.toUpperCase());
            $('#payment-amount').val('');

            // Mostrar formulario
            $('#campos-pago').removeClass('d-none');
            if (pagosRealizados.length === 0) {
                $('#payment-amount').val(totalPagar);
            }
            $('#payment-amount').focus();

            // Marcar botón seleccionado
            $('.payment-method-btn').removeClass('active');
            $(this).addClass('active');
            console.log('✅ Formulario mostrado');
        });

        // Guardar pago
        $(document).on('click', '#btn-save-payment', function() {
            let amount = parseFloat($('#payment-amount').val());
            let methodName = $('#selected-method-name').val();
            let methodId = $('#selected-method-id').val();

            if (!amount || amount <= 0) {
                ToastError.fire({
                    text: 'Ingrese un monto válido'
                });
                return;
            }

            let faltaPagar = totalPagar - totalPagado;
            if (amount > faltaPagar) {
                ToastError.fire({
                    text: 'El monto excede lo que falta por pagar (S/ ' + faltaPagar.toFixed(2) + ')'
                });
                return;
            }

            // Agregar pago al array
            pagosRealizados.push({
                metodo: methodName,
                metodo_id: methodId,
                monto: amount
            });

            // Actualizar UI
            actualizarUI();

            // Ocultar formulario
            $('#campos-pago').addClass('d-none');
            $('.payment-method-btn').removeClass('active');

            ToastMessage.fire({
                text: 'Pago agregado: S/ ' + amount.toFixed(2)
            });
        });

        // Cancelar pago
        $(document).on('click', '#btn-cancel-payment', function() {
            $('#campos-pago').addClass('d-none');
            $('.payment-method-btn').removeClass('active');
            $('#payment-amount').val('');
        });

        // Eliminar pago
        $(document).on('click', '.btn-delete-payment', function() {
            let index = $(this).data('index');
            let montoEliminado = pagosRealizados[index].monto;
            pagosRealizados.splice(index, 1);
            actualizarUI();
            ToastMessage.fire({
                text: 'Pago de S/ ' + montoEliminado.toFixed(2) + ' eliminado'
            });
        });

        function isNumber(evt) {
            evt = evt || window.event;
            var charCode = evt.which || evt.keyCode;
            if (charCode < 48 || charCode > 57) {
                evt.preventDefault();
                return false;
            }
            return true;
        }

        function isDecimal(evt) {
            evt = evt || window.event;
            var charCode = evt.which || evt.keyCode;
            if ((charCode >= 48 && charCode <= 57) || charCode === 46) {
                var input = evt.target || evt.srcElement;
                if (charCode === 46 && input.value.includes('.')) {
                    evt.preventDefault();
                    return false;
                }
                return true;
            } else {
                evt.preventDefault();
                return false;
            }
        }

        function searchAPI(docEl, nameEl, addressEl) {
            var doc = $(docEl).val();

            $(nameEl).val('');
            $(addressEl).val('');
            $('#client').val('');

            if (doc.length != 8 && doc.length != 11) {
                return;
            }

            Swal.showLoading();

            $.ajax({
                url: "{{ url('sunat/consultar') }}?doc=" + doc,
                method: 'GET',
                success: function(response) {
                    if (response.success) {
                        var data = response.data;
                        if (doc.length === 8) {
                            var fullName = `${data.nombre} ${data.apellido_paterno} ${data.apellido_materno}`;
                            $(nameEl).val(fullName);
                            $(addressEl).val(data.domicilio?.direccion || '');
                            $('#client').val(fullName);
                        } else {
                            $(nameEl).val(data.nombre);
                            $(addressEl).val(data.domicilio?.direccion || '');
                            $('#client').val(data.nombre);
                        }
                    } else {
                        ToastError.fire({
                            text: response.message || 'No se encontró información'
                        });
                    }
                    Swal.close();
                },
                error: function(xhr) {
                    ToastError.fire({
                        text: 'Error al consultar SUNAT/RENIEC'
                    });
                    Swal.close();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            var sidebar = $('.sidebar');
            if (sidebar.length > 0) {
                sidebar.addClass('sidebar-mini');
            }
            @if (!empty($delivery))
                const deliveryData = @json($delivery);

                // Autocompletar los campos visibles
                document.getElementById('document').value = deliveryData.document_number || '';
                document.getElementById('client').value = deliveryData.client_name || '';
                document.getElementById('address').value = deliveryData.address || '';

                // Guardar los demás datos internamente
                window.deliveryExtra = {
                    phone: deliveryData.phone || '',
                    reference: deliveryData.reference || '',
                    observation: deliveryData.observation || '',
                    delivery_date: deliveryData.delivery_date || '',
                    delivery_hour: deliveryData.delivery_hour || '',
                    foto: deliveryData.photo_path || null
                };
            @endif
        });

        // Agregar este evento después de los otros eventos
        $(document).on('click', '.btn-completar-venta', function() {
            // Validar que haya pagos
            if (pagosRealizados.length === 0) {
                ToastError.fire({
                    text: 'Debe agregar al menos un método de pago'
                });
                return;
            }

            // Validar que esté completamente pagado
            let faltaPagar = totalPagar - totalPagado;
            if (faltaPagar > 0.01) {
                ToastError.fire({
                    text: 'Aún falta pagar S/ ' + faltaPagar.toFixed(2)
                });
                return;
            }

            // Obtener tipo de comprobante seleccionado
            let voucherType = $('.voucher-btn.active').data('voucher-type');
            if (!voucherType) {
                ToastError.fire({
                    text: 'Seleccione un tipo de comprobante'
                });
                return;
            }

            Swal.fire({
                title: '¿Completar venta?',
                text: 'Se registrará la venta y se cerrará la mesa',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, completar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.showLoading();

                    $.ajax({
                        url: "{{ route('restaurante.completar-venta') }}",
                        method: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}',
                            order_id: {{ $orderId ?? 0 }},
                            mesa_id: {{ $mesa->id ?? 0 }},
                            voucher_type: voucherType,
                            document: $('#document').val(),
                            client: $('#client').val(),
                            tip: $('#tipAmount').val(),
                            pagos: pagosRealizados,
                            phone: window.deliveryExtra?.phone || '',
                            address: $('#address').val() || '',
                            reference: window.deliveryExtra?.reference || '',
                            observation: window.deliveryExtra?.observation || '',
                            delivery_date: window.deliveryExtra?.delivery_date || '',
                            delivery_hour: window.deliveryExtra?.delivery_hour || '',
                            foto: window.deliveryExtra?.foto || '',
                            type_status: 2,
                            account_number: (new URLSearchParams(window.location.search)).get(
                                'account_number'),
                        },
                        success: async function(response) {
                            Swal.close();
                            ToastMessage.fire({
                                text: response.message
                            });

                            await imprimirVenta(response.sale_id);

                            // Redirigir al listado de mesas
                            setTimeout(() => {
                                window.location.href =
                                    "{{ route('sales.mozo') }}";
                            }, 2500);
                        },
                        error: function(xhr) {
                            Swal.close();
                            let message = xhr.responseJSON?.message ||
                                'Error al completar la venta';
                            ToastError.fire({
                                text: message
                            });
                        }
                    });
                }
            });
        });

        $(document).on('click', '.voucher-btn', function() {
            $('.voucher-btn').removeClass('active');
            $(this).addClass('active');
        });

        // Inicializar cuando el DOM esté listo
        $(document).ready(function() {
            console.log('✅ jQuery cargado correctamente');
            console.log('Total de botones de pago encontrados:', $('.payment-method-btn').length);

            actualizarUI();

            // Tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });

        // Forzar sidebar-mini después de que todo esté cargado
        window.addEventListener('load', function() {
            setTimeout(function() {
                var sidebar = document.querySelector('.sidebar');
                if (sidebar !== null) {
                    sidebar.classList.add('sidebar-mini');
                    // Remover la clase on-resize si existe para evitar que hope-ui.js la quite
                    sidebar.classList.remove('on-resize');
                    console.log('✅ Sidebar-mini aplicado');
                }
            }, 100);
        });

        document.getElementById('btn-separar').addEventListener('click', function() {
            const header = document.querySelector('#orders-list .d-flex.mb-3');
            const productItems = document.querySelectorAll('#order-product-items .order-product-item');
            const buttonText = document.getElementById('btn-text');
            const icon = this.querySelector('i');

            if (document.querySelector('#orders-list .separar-header')) {
                document.querySelector('#orders-list .separar-header').remove();
                productItems.forEach(item => {
                    const sepInput = item.querySelector('.sep-container');
                    if (sepInput) sepInput.remove();
                });

                buttonText.textContent = 'Separar cuenta';
                this.classList.remove('btn-danger');
                this.classList.add('btn-primary');
                icon.classList.remove('bi-x-circle');
                icon.classList.add('bi-check-circle');
            } else {
                const sepHeader = document.createElement('div');
                sepHeader.classList.add('text-center', 'separar-header');
                sepHeader.style.width = '60px';
                sepHeader.textContent = 'SEP.';
                header.prepend(sepHeader);
                productItems.forEach(item => {
                    const sepDiv = document.createElement('div');
                    sepDiv.classList.add('text-center', 'sep-container');
                    sepDiv.style.width = '70px';

                    const quantityEl = item.querySelector('.badge');
                    const maxQty = parseInt(quantityEl.textContent.trim()) || 0;
                    const detailId = item.dataset.id;
                    sepDiv.innerHTML = `
                        <input type="number"
                            class="form-control form-control-sm separar-input"
                            min="0"
                            max="${maxQty}"
                            value="0"
                            style="width: 60px; text-align: center;">
                    `;

                    const input = sepDiv.querySelector('input');

                    // Bloquear puntos, comas y letras (solo enteros)
                    input.addEventListener('keypress', function(e) {
                        const char = e.key;
                        if (char === '.' || char === ',' || char === '-' || isNaN(char)) {
                            e.preventDefault();
                        }
                    });

                    // Evitar que se peguen valores no enteros
                    input.addEventListener('paste', function(e) {
                        const pasted = e.clipboardData.getData('text');
                        if (!/^\d+$/.test(pasted)) {
                            e.preventDefault();
                        }
                    });

                    input.addEventListener('input', function() {
                        let val = parseInt(this.value) || 0;
                        if (val > maxQty) {
                            this.value = maxQty;
                        } else if (val < 0) {
                            this.value = 0;
                        }

                        actualizarCantidad(detailId, this.value);
                    });
                    item.querySelector('.d-flex.align-items-center').prepend(sepDiv);
                });
                buttonText.textContent = 'Unir cuentas';
                this.classList.remove('btn-primary');
                this.classList.add('btn-danger');
                icon.classList.remove('bi-check-circle');
                icon.classList.add('bi-x-circle');
            }
        });

        function actualizarCantidad(orderId, productId, cantidad) {
            $.ajax({
                url: "{{ route('order.updateQuantity') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    order_id: orderId,
                    product_id: productId,
                    quantity_account: cantidad
                },
                success: function(response) {
                    if (response.success) {
                        ToastMessage.fire({
                            icon: 'success',
                            text: 'Cantidad actualizada correctamente'
                        });
                    } else {
                        ToastError.fire({
                            text: response.message || 'Error al guardar cantidad'
                        });
                    }
                },
                error: function(xhr) {
                    let msg = xhr.responseJSON?.message || 'Error al actualizar cantidad';
                    ToastError.fire({
                        text: msg
                    });
                }
            });
        }


        async function imprimirVenta(saleId) {

            $.ajax({
                url: "{{ route('anticipated_print') }}",
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    sale_id: saleId
                },
                success: async function(response) {
                    if (!response.status) {
                        ToastError.fire({
                            text: response.error || 'Error al obtener datos de la venta'
                        });
                        return;
                    }

                    const data = response;
                    const venta = data.venta;
                    const productos = data.productos;
                    const pagos = data.pagos;
                    const voucherType = (venta.voucher_type || '').toLowerCase();
                    let descuento = 0;
                    // Formato especial para boleta/factura
                    if (voucherType === 'boleta' || voucherType === 'factura') {
                        // Calcular OP. GRAVADA e IGV
                        let opGravada = 0;
                        let igv = 0;
                        let total = 0;
                        let productosLineas = [];

                        productos.forEach(function(producto) {
                            const cantidad = parseFloat(producto.cantidad) || 0;
                            const precio = parseFloat(producto.precio) || 0;
                            const subtotal = parseFloat(producto.subtotal) || (cantidad * precio);
                            const discount = parseFloat(producto.discount) || 0;
                            descuento += discount;

                            opGravada += subtotal;
                            productosLineas.push({
                                nombre: producto.nombre,
                                cantidad: cantidad,
                                precio: precio,
                                discount: discount,
                                subtotal: subtotal
                            });
                        });

                        let opGravadaSinIGV = opGravada / 1.18;
                        igv = opGravada - opGravadaSinIGV;
                        total = opGravada;

                        let operaciones = [{
                                nombre: "Iniciar",
                                argumentos: []
                            },
                            {
                                nombre: "EstablecerAlineacion",
                                argumentos: [1]
                            },
                            {
                                nombre: "EstablecerEnfatizado",
                                argumentos: [true]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["DE CAJON\n"]
                            },
                            {
                                nombre: "EstablecerEnfatizado",
                                argumentos: [false]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["RUC 20611915277\n"]
                            },
                            // {
                            //     nombre: "EscribirTexto",
                            //     argumentos: ["AV. JOSE BALTA NRO. 054 P.J. CHINO ZAMORA CHICLAYO\n"]
                            // },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["CHICLAYO LAMBAYEQUE\n"]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["=================================================\n"]
                            },
                            {
                                nombre: "EstablecerEnfatizado",
                                argumentos: [true]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: [voucherType === 'boleta' ?
                                    "BOLETA DE VENTA ELECTRÓNICA\n" : "FACTURA ELECTRÓNICA\n"
                                ]
                            },
                            {
                                nombre: "EstablecerEnfatizado",
                                argumentos: [false]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: [`${venta.number || ''}\n`]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: [
                                    voucherType === 'factura' ?
                                    `RAZON SOCIAL: ${venta.cliente || 'CLIENTE VARIOS'}\n` :
                                    `NOMBRE: ${venta.cliente || 'CLIENTE VARIOS'}\n`
                                ]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: [
                                    voucherType === 'factura' ?
                                    `RUC: ${venta.document || '00000000000'}\n` :
                                    `DNI: ${venta.document || '00000000'}\n`
                                ]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: [`EMISION: ${data.now || ''}\n`]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["MONEDA:  SOL (PEN)\n"]
                            },
                            {
                                nombre: "EscribirTexto",
                                argumentos: ["METODOS DE PAGO\n"]
                            }
                        ];

                        // Agregar métodos de pago
                        if (pagos && pagos.length > 0) {
                            pagos.forEach(function(pago) {
                                operaciones.push({
                                    nombre: 'EscribirTexto',
                                    argumentos: [
                                        `${pago.metodo_pago}: S/${parseFloat(pago.monto).toFixed(2)}\n`
                                    ]
                                });
                            });
                        }

                        // Agregar productos
                        operaciones.push({
                            nombre: "EscribirTexto",
                            argumentos: ["------------------------------------------------\n"]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: ['CODIGO DESCRIPCION   CANT   P.UNIT   P.TOTAL\n']
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["-------------------------------------------------\n"]
                        });

                        productosLineas.forEach(function(prod) {
                            // Divide el nombre en líneas de máximo 20 caracteres
                            let nombre = prod.nombre;
                            let lineas = [];
                            while (nombre.length > 20) {
                                lineas.push(nombre.substring(0, 20));
                                nombre = nombre.substring(20);
                            }
                            if (nombre.length > 0) lineas.push(nombre);

                            // Imprime la primera línea con las columnas
                            let cantidad = prod.cantidad.toFixed(2).padStart(5);
                            let precio = prod.precio.toFixed(2).padStart(8);
                            let subtotal = prod.subtotal.toFixed(2).padStart(8);
                            operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [lineas[0].padEnd(20) + cantidad + precio +
                                    subtotal + '\n'
                                ]
                            });

                            // Imprime las siguientes líneas solo con el nombre
                            for (let i = 1; i < lineas.length; i++) {
                                operaciones.push({
                                    nombre: 'EscribirTexto',
                                    argumentos: [lineas[i] + '\n']
                                });
                            }
                        });

                        // Totales
                        operaciones.push({
                            nombre: "EscribirTexto",
                            argumentos: ["------------------------------------------------\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["OP. GRAVADA   : S/ " + opGravadaSinIGV.toFixed(2) + "\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["IGV           : S/ " + igv.toFixed(2) + "\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["DESCUENTO     : S/ " + descuento.toFixed(2) + "\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["IMPORTE TOTAL : S/ " + total.toFixed(2) + "\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["SON: " + convertirMontoALetras(total) + "\n"]
                        });

                        // Información adicional
                        operaciones.push({
                            nombre: "EscribirTexto",
                            argumentos: ["\nINFORMACION ADICIONAL:\n"]
                        });

                        // Agrega dirección si existe
                        if (venta.direccion) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`DIRECCION: ${venta.direccion}\n`]
                            });
                        }

                        // Agrega referencia si existe
                        if (venta.referencia) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`REFERENCIA: ${venta.referencia}\n`]
                            });
                        }

                        // Agrega teléfono si existe
                        if (venta.telefono) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`TELEFONO: ${venta.telefono}\n`]
                            });
                        }

                        // Agrega usuario si existe
                        if (venta.user_id) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`USUARIO: ${venta.user_id}\n`]
                            });
                        }

                        // Agrega fecha de entrega si existe
                        if (venta.fecha_entrega) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`FECHA ENTREGA: ${venta.fecha_entrega}\n`]
                            });
                        }

                        // Agrega hora de entrega si existe
                        if (venta.hora_entrega) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`HORA ENTREGA: ${venta.hora_entrega}\n`]
                            });
                        }

                        // Agrega observación si existe
                        if (venta.observacion) {
                            operaciones.push({
                                nombre: "EscribirTexto",
                                argumentos: [`OBSERVACION: ${venta.observacion}\n`]
                            });
                        }

                        // Footer
                        operaciones.push({
                            nombre: "Feed",
                            argumentos: [2]
                        }, {
                            nombre: "EstablecerAlineacion",
                            argumentos: [1]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["Gracias por su preferencia\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: ["Implementado por xinergia.net\n"]
                        }, {
                            nombre: "EscribirTexto",
                            argumentos: [`IMPRESION: ${data.now}\n`]
                        }, {
                            nombre: "Feed",
                            argumentos: [1]
                        }, {
                            nombre: "Corte",
                            argumentos: [1]
                        });

                        // IMPRESIÓN DE BOLETA/FACTURA
                        try {
                            // Intentar impresión local primero
                            const http = await fetch('http://localhost:8000/imprimir', {
                                method: 'POST',
                                // headers: {
                                //     'Content-Type': 'application/json'
                                // },
                                body: JSON.stringify({
                                    // serial: serial,
                                    nombreImpresora: 'Ticketera',
                                    operaciones: operaciones
                                })
                            });

                            const res = await http.json();
                            if (!res.ok) {
                                throw new Error(res.message || 'Error al imprimir localmente');
                            } else {
                                ToastMessage.fire({
                                    text: 'Comprobante impreso correctamente'
                                });

                            }
                        } catch (error) {
                            console.log('Error en impresión local, intentando remota:', error.message);

                            // Si falla local, intentar impresión remota
                            try {
                                const rutaRemota = `http://192.168.18.46:8000/imprimir`;
                                const payload = {
                                    operaciones: operaciones,
                                    nombreImpresora: 'Ticketera',
                                    // serial: serial,
                                };

                                const remoteResponse = await fetch('http://localhost:8000/reenviar?host=' +
                                    rutaRemota, {
                                        method: 'POST',
                                        body: JSON.stringify(payload),
                                        // headers: {
                                        //     'Content-Type': 'application/json; charset=utf-8'
                                        // }
                                    });

                                const remoteRes = await remoteResponse.json();
                                if (remoteRes.ok) {
                                    ToastMessage.fire({
                                        text: 'Comprobante impreso correctamente (Remoto)'
                                    });

                                } else {
                                    throw new Error('Impresión remota falló: ' + remoteRes.message);
                                }
                            } catch (errorRemoto) {
                                console.error('Error al imprimir boleta/factura:', errorRemoto);
                                ToastError.fire({
                                    text: 'Error al imprimir la boleta/factura: ' + errorRemoto
                                        .message
                                });
                                return;
                            }
                        }

                        // Si llegó aquí, la impresión fue exitosa, terminar función
                        return;
                    }

                    // FORMATO ORIGINAL PARA TICKET (solo si NO es boleta/factura)
                    const opts = {
                        // serial: serial,
                        nombreImpresora: 'Ticketera',
                        operaciones: [{
                                nombre: 'Iniciar',
                                argumentos: []
                            },
                            {
                                nombre: "EstablecerAlineacion",
                                argumentos: [1]
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: ['DE CAJON\n']
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: ['----------------------------------------\n']
                            },
                            {
                                nombre: "EstablecerAlineacion",
                                argumentos: [0]
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: ['----------------------------------------\n']
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: [`NUMERO: ${venta.number || 'N/A'}\n`]
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: [`USUARIO: ${venta.user_id || 'Usuario'}\n`]
                            },
                            {
                                nombre: 'EscribirTexto',
                                argumentos: [`FECHA VENTA: ${venta.fecha}\n`]
                            }
                        ]
                    };

                    // Métodos de pago
                    if (pagos && pagos.length > 0) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: ['METODOS DE PAGO:\n']
                        });
                        pagos.forEach(function(pago) {
                            opts.operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [`${pago.metodo_pago}: S/${pago.monto}\n`]
                            });
                        });
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: ['----------------------------------------\n']
                        });
                    }
                    let descuentoTotal = 0;
                    productos.forEach(function(producto) {
                        const discount = parseFloat(producto.discount) || 0;
                        descuentoTotal += discount;
                    });

                    // Productos
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['PRODUCTOS:\n']
                    });

                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['CANT PRODUCTO        P.U     TOTAL\n']
                    });
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });

                    productos.forEach(function(producto) {
                        const cant = producto.cantidad.toString().padEnd(4);
                        const precio = `S/${parseFloat(producto.precio).toFixed(2)}`.padStart(8);
                        const total = `S/${parseFloat(producto.subtotal).toFixed(2)}`.padStart(8);

                        if (producto.nombre.length > 15) {
                            opts.operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [`${cant} ${producto.nombre}\n`]
                            });
                            opts.operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [`${' '.repeat(19)} ${precio} ${total}\n`]
                            });
                        } else {
                            const nombre = producto.nombre.padEnd(15);
                            opts.operaciones.push({
                                nombre: 'EscribirTexto',
                                argumentos: [`${cant} ${nombre} ${precio} ${total}\n`]
                            });
                        }
                    });

                    // Footer del ticket
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });

                    // Mostrar descuento si existe
                    if (descuentoTotal > 0) {
                        const subtotal = parseFloat(venta.total) + descuentoTotal;
                        opts.operaciones.push({
                            nombre: "EstablecerAlineacion",
                            argumentos: [2]
                        });
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`SUBTOTAL: S/${subtotal.toFixed(2)}\n`]
                        });
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`DESCUENTO: -S/${descuentoTotal.toFixed(2)}\n`]
                        });
                    }

                    opts.operaciones.push({
                        nombre: "EstablecerAlineacion",
                        argumentos: [2]
                    });
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: [`TOTAL: S/${parseFloat(venta.total).toFixed(2)}\n`]
                    });
                    opts.operaciones.push({
                        nombre: "EstablecerAlineacion",
                        argumentos: [0]
                    });
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    });
                    opts.operaciones.push({
                        nombre: "EstablecerAlineacion",
                        argumentos: [1]
                    });
                    opts.operaciones.push({
                        nombre: 'EscribirTexto',
                        argumentos: ['INFORMACION ADICIONAL\n']
                    });

                    if (venta.client_name) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`CLIENTE: ${venta.client_name}\n`]
                        });
                    }
                    if (venta.direccion) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`DIRECCION: ${venta.direccion}\n`]
                        });
                    }
                    if (venta.referencia) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`REFERENCIA: ${venta.referencia}\n`]
                        });
                    }
                    if (venta.observacion) {
                        opts.operaciones.push({
                            nombre: 'EscribirTexto',
                            argumentos: [`OBSERVACION: ${venta.observacion}\n`]
                        });
                    }


                    opts.operaciones.push({
                        nombre: "EstablecerAlineacion",
                        argumentos: [1]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: ['----------------------------------------\n']
                    }, {
                        nombre: "EstablecerAlineacion",
                        argumentos: [0]
                    }, {
                        nombre: 'Feed',
                        argumentos: [2]
                    }, {
                        nombre: "EstablecerAlineacion",
                        argumentos: [1]
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: ['Gracias por su preferencia\n']
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: ['Implementado por xinergia.net\n']
                    }, {
                        nombre: 'EscribirTexto',
                        argumentos: [`IMPRESION: ${data.now}\n`]
                    }, {
                        nombre: 'Feed',
                        argumentos: [1]
                    }, {
                        nombre: 'Corte',
                        argumentos: [1]
                    });

                    // IMPRESIÓN DEL TICKET
                    try {
                        // Intentar impresión local primero
                        const http = await fetch('http://localhost:8000/imprimir', {
                            method: 'POST',
                            /* headers: {
                                'Content-Type': 'application/json'
                            }, */
                            body: JSON.stringify({
                                // serial: serial,
                                nombreImpresora: 'Ticketera',
                                operaciones: opts.operaciones
                            })
                        });

                        const res = await http.json();
                        if (!res.ok) {
                            throw new Error(res.message || 'Error al imprimir localmente');
                        } else {
                            ToastMessage.fire({
                                text: 'Ticket impreso correctamente'
                            });

                        }
                    } catch (error) {
                        console.log('Error en impresión local, intentando remota:', error.message);

                        // Si falla local, intentar impresión remota
                        try {
                            const rutaRemota = `http://192.168.18.46:8000/imprimir`;
                            const payload = {
                                operaciones: opts.operaciones,
                                nombreImpresora: 'Ticketera',
                                // serial: serial,
                            };

                            const remoteResponse = await fetch('http://localhost:8000/reenviar?host=' +
                                rutaRemota, {
                                    method: 'POST',
                                    body: JSON.stringify(payload),
                                    /* headers: {
                                        'Content-Type': 'application/json; charset=utf-8'
                                    } */
                                });

                            const remoteRes = await remoteResponse.json();
                            if (remoteRes.ok) {
                                ToastMessage.fire({
                                    text: 'Ticket impreso correctamente (Remoto)'
                                });

                            } else {
                                throw new Error('Impresión remota falló: ' + remoteRes.message);
                            }
                        } catch (errorRemoto) {
                            console.error('Error al imprimir ticket:', errorRemoto);
                            ToastError.fire({
                                text: 'Error al imprimir el ticket: ' + errorRemoto.message
                            });
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error en la solicitud:', error);
                    ToastError.fire({
                        text: 'Error al obtener datos para impresión'
                    });
                }
            });
        }
    </script>
@endsection
