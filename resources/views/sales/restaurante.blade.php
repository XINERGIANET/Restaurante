@extends('layouts.app')

@section('nav')
    <style>
        /* Diseño exacto de las tarjetas de mesa según imagen - vertical y centrado */
        .card-mesa {
            background: white;
            border-radius: 8px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.12), 0 2px 4px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
            position: relative;
            border-top: 5px solid;
            height: 100%;
            border: none;
            display: flex;
            flex-direction: column;
        }

        .card-mesa:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15), 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        /* Borde superior naranja para mesas ocupadas */
        .card-mesa.occupied {
            border-top-color: #ff6b35;
            background-color: #fff5ed;
        }

        /* Borde superior verde para mesas libres */
        .card-mesa:not(.occupied) {
            border-top-color: #28a745;
            background-color: white;
        }

        .mesa-card-header {
            padding: 1.25rem 1rem 0.75rem;
            text-align: center;
        }

        .mesa-card-title {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: #212529;
            line-height: 1.2;
        }

        .mesa-card-body {
            padding: 0.75rem 1rem 1rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .mesa-status-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #212529;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            flex-shrink: 0;
        }

        .status-dot.ocupado {
            background-color: #ff6b35;
        }

        .status-dot.libre {
            background-color: #28a745;
        }

        .mesa-personas-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin-bottom: 0.75rem;
            font-size: 0.9rem;
            color: #212529;
        }

        .mesa-personas-header i {
            font-size: 1.1rem;
            color: #495057;
        }

        .mesa-divider {
            height: 1px;
            background-color: #e9ecef;
            margin: 0.75rem 0;
            width: 100%;
        }

        .mesa-monto {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
            margin: 0.5rem 0;
            text-align: center;
        }

        .mesa-tiempo {
            font-size: 0.9rem;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.4rem;
            margin: 0.5rem 0;
        }

        .mesa-tiempo i {
            font-size: 1rem;
        }

        .mesa-personas-group {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.1rem;
            margin-top: 0.1rem;
            padding-top: 0.5rem;
        }

        .mesa-persona-item {
            display: flex;
            align-items: center;
            gap: 0.1rem;
            font-size: 0.85rem;
            color: #6c757d;
        }

        .mesa-persona-item i {
            font-size: 0.95rem;
        }

        /* Estilos para campos de delivery */
        #camposDelivery {
            background-color: #f8f9fa;
            padding: 15px;
            margin-top: 10px;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        #camposDelivery .form-label {
            font-weight: 600;
        }

        /* Ocultar el div container-fluid iq-container solo en restaurante */
        .iq-navbar-header {
            display: none !important;
            height: 0 !important;
        }

        /* Ajustar el layout para que el footer esté en su posición correcta */
        .main-content {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .main-content .footer {
            margin-top: auto;
            position: relative;
        }

        /* Estilos para tarjetas de delivery */
        .delivery-card-item {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            padding: 1.5rem;
            background: #ffffff;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .delivery-card-item:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            transform: translateY(-3px);
        }

        .delivery-card-item .client-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1a1a1a;
            margin-bottom: 0.5rem;
        }

        .delivery-card-item .client-address {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .delivery-card-item .delivery-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #28a745;
            margin-bottom: 0.5rem;
        }

        .delivery-card-item .delivery-time {
            color: #6c757d;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .delivery-card-item .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            white-space: nowrap;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .delivery-card-item .status-pagado {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .delivery-card-item .status-sinpagar {
            background-color: #ffebee;
            color: #c62828;
        }

        .delivery-card-item .status-sinentrega {
            background-color: #fff3e0;
            color: #e65100;
        }

        .delivery-card-item .status-entregado {
            background-color: #e3f2fd;
            color: #1565c0;
        }

        .delivery-card-item .status-pendiente {
            background-color: #fce4ec;
            color: #880e4f;
        }

        .btn-nuevo-delivery {
            background-color: #4caf50;
            color: white;
            border: none;
            padding: 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            height: 100%;
            min-height: 200px;
        }

        .btn-nuevo-delivery:hover {
            background-color: #45a049;
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.4);
        }

        .btn-nuevo-delivery i {
            font-size: 3rem;
        }

        /* Estilos para el botón de cancelar orden */
        .btn-cancelar-orden {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            z-index: 10;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }

        .btn-cancelar-orden:hover {
            background-color: #c82333;
            transform: scale(1.1);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.3);
        }

        .btn-cancelar-orden i {
            font-size: 0.9rem;
        }

        .delivery-card-item {
            position: relative;
        }
    </style>
@endsection

@section('header')
    <h2>Punto de Venta</h2>
    <p>Lista de mesas</p>
@endsection

@section('content')
    @php
        $colors = [
            'btn-outline-primary',
            'btn-outline-success',
            'btn-outline-info',
            'btn-outline-warning',
            'btn-outline-danger',
            'btn-outline-dark',
        ];
    @endphp
    <div class="container-fluid iq-container mt-3 py-0">
        <div class="card shadow">
            <div class="card-body">
                <ul class="nav nav-tabs mb-3 gap-2" id="pdv-tabs" role="tablist">
                    @foreach ($areas as $area)
                        <li class="nav-item">
                            <a class="nav-link" id="tab-area-{{ $area->id }}" onclick="getTables({{ $area->id }})"
                                style="cursor:pointer">{{ $area->name }}</a>
                        </li>
                    @endforeach
                    <li class="nav-item">
                        <a class="nav-link" id="tab-delivery" @if (auth()->user()->hasRole('mozo') )
                            onclick="verificarPinMotorizado(event)"
                        @else
                            onclick="getDeliveryOrders(event)"
                        @endif  style="cursor:pointer">
                            <i class="fas fa-shopping-bag me-1"></i> Delivery
                        </a>
                    </li>
                </ul>
                <div class="row g-4">
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Abrir Mesa -->
    <div class="modal fade" id="abrirMesaModal" tabindex="-1" aria-labelledby="abrirMesaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="abrirMesaModalLabel">Abrir Mesa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <div class="container-fluid">
                        <!-- Seleccionar Productos -->
                        <div class="form-group">
                            <label for="producto_id"
                                class="col-sm-3 col-form-label text-start"><strong>Producto</strong></label>
                            <div class="col-md-12">
                                <input hidden type="number" class="form-control" name="producto_id" id="producto_id">
                                <input type="text" class="form-control" name="name" id="search-product"
                                    placeholder="Buscar Producto">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-3 col-form-label text-start"><strong>Categorías</strong></label>
                            <div class="mb-3">
                                @foreach ($pc as $category)
                                    <button class="btn btn-outline-primary btn-sm m-1" type="button"
                                        onclick="handleCategoryClick('{{ $category->id }}')">
                                        {{ $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                        <div id="product-container"></div>
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-striped text-xs">
                                <thead>
                                    <tr class="text-center">
                                        <th>N°</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Subtotal</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="row justify-content-end mb-3">
                            <div class="col-md-5 text-end">
                                <h5><strong>TOTAL: S/ <span id="totalAmount" name="total">0.00</span></strong></h5>
                                <input hidden type="number" step="0.01" name="total" id="totalAmountInput"
                                    value="0">
                                <button class="btn me-2 mt-3 btn-warning" type="button"
                                    onclick="confirmOrder()">Confirmar</button>
                                <button class="btn me-2 mt-3 btn-secondary" type="button"
                                    onclick="preaccount()">Precuenta</button>
                                <button class="btn me-2 mt-3 btn-success" type="button"
                                    onclick="abrirModalCobro()">Cobrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!auth()->user()->hasRole('Caja') && !auth()->user()->hasRole('Admin'))
        <div class="modal fade" id="pinModal" tabindex="-1" aria-labelledby="pinModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pinModalLabel">Autenticación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pinInput" class="form-label">Ingrese su PIN</label>
                            <input type="password" class="form-control" id="pinInput" placeholder="••••" maxlength="4"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div id="pinError" class="text-danger mt-2 d-none">PIN incorrecto</div>
                        </div>

                        <div class="mb-3">
                            <label for="cantidadPersonas" class="form-label">Cantidad de Personas</label>

                            <input type="number" class="form-control" id="cantidadPersonas"
                                placeholder="Cantidad de personas">
                            <div id="cantidadError" class="text-danger mt-2 d-none">Por favor ingrese una cantidad válida
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="pinSubmitBtn">Ingresar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal de Confirmación de Cancelar Orden -->
    <div class="modal fade" id="modalCancelarOrden" tabindex="-1" aria-labelledby="modalCancelarOrdenLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalCancelarOrdenLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i>Cancelar Orden
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">¿Está seguro que desea cancelar esta orden?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Nota:</strong> Esta acción restaurará el stock de los productos y anulará los pagos asociados.
                    </div>
                    <input type="hidden" id="venta-id-cancelar" value="">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, mantener orden</button>
                    <button type="button" class="btn btn-danger" id="confirmar-cancelar">
                        <i class="bi bi-x-circle me-2"></i>Sí, cancelar orden
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Entrega -->
    <div class="modal fade" id="modalConfirmarEntrega" tabindex="-1" aria-labelledby="modalConfirmarEntregaLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalConfirmarEntregaLabel">Confirmación de entrega</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>¿Productos entregados?</p>
                    <input type="hidden" id="venta-id-entregar">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No</button>
                    <button type="button" class="btn btn-success" id="confirmar-entrega">Sí</button>
                </div>
            </div>
        </div>
    </div>

    @if (!auth()->user()->hasRole('Caja') && !auth()->user()->hasRole('Admin'))
        <div class="modal fade" id="pinMotorizadoModal" tabindex="-1" aria-labelledby="pinMotorizadoModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="pinMotorizadoModalLabel">Autenticación</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="pinMotorizadoInput" class="form-label">Ingrese su PIN</label>
                            <input type="password" class="form-control" id="pinMotorizadoInput" placeholder="••••" maxlength="4"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div id="pinMotorizadoError" class="text-danger mt-2 d-none">PIN incorrecto</div>
                        </div>
                    </div>  
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="pinMotorizadoSubmitBtn">Ingresar</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@section('scripts')
    <script>
        function verificarPinMotorizado(event) {
            event.preventDefault();
            $('#pinMotorizadoModal').modal('show');
        }
        document.getElementById('pinMotorizadoSubmitBtn').addEventListener('click', function() {
            const pinMotorizado = document.getElementById('pinMotorizadoInput').value;
            if (!pinMotorizado) {
                document.getElementById('pinMotorizadoError').textContent = 'Ingrese un PIN';
                document.getElementById('pinMotorizadoError').classList.remove('d-none');
                return;
            }
            $.ajax({
                url: "{{ route('employees.validarPinMotorizado') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    pin: pinMotorizado
                },
                success: function(response) {
                    if (response.valid) {
                        $('#pinMotorizadoModal').modal('hide');
                        getDeliveryOrders();
                    } else {
                        document.getElementById('pinMotorizadoError').textContent = 'PIN motorizado incorrecto';
                        document.getElementById('pinMotorizadoError').classList.remove('d-none');
                    }
                },
                error: function() {
                    document.getElementById('pinMotorizadoError').textContent = 'Error al validar PIN motorizado';
                    document.getElementById('pinMotorizadoError').classList.remove('d-none');
                }
            });
        });
        function getDeliveryOrders() {
            const $container = $('.row.g-4');
            if (!$container.length) return;

            // Remover clase active de todos los tabs
            $('#pdv-tabs .nav-link').removeClass('active');
            $('#tab-delivery').addClass('active');

            // Mostrar loader
            $container.html(
                '<div class="col-12 text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>'
            );

            // Obtener pedidos de delivery
            $.ajax({
                url: "{{ route('sales.anticipated') }}",
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(html) {
                    // Extraer datos del HTML si viene HTML completo
                    // O hacer una petición JSON si modificamos el endpoint
                    loadDeliveryCards();
                },
                error: function() {
                    // Si falla, cargar igualmente los datos
                    loadDeliveryCards();
                }
            }); 
        }
        function cancelarOrden(ventaId) {
            $('#venta-id-cancelar').val(ventaId);
            $('#modalCancelarOrden').modal('show');
            
            // Remover eventos anteriores para evitar múltiples registros
            $('#confirmar-cancelar').off('click').on('click', function() {
                const saleId = $('#venta-id-cancelar').val();
                
                $.ajax({
                    url: "{{ route('sales.anular') }}",
                    method: 'GET',
                    data: {
                        sale_id: saleId
                    },
                    success: function(response) {
                        $('#modalCancelarOrden').modal('hide');
                        
                        if (response.status) {
                            ToastMessage.fire({
                                text: response.message || 'Orden cancelada exitosamente'
                            });
                            // Recargar las tarjetas de delivery
                            getDeliveryOrders();
                        } else {
                            ToastError.fire({
                                text: response.error || 'Error al cancelar la orden'
                            });
                        }
                    },
                    error: function(xhr) {
                        $('#modalCancelarOrden').modal('hide');
                        let errorMessage = 'Error al cancelar la orden';
                        
                        if (xhr.responseJSON && xhr.responseJSON.error) {
                            errorMessage = xhr.responseJSON.error;
                        }
                        
                        ToastError.fire({
                            text: errorMessage
                        });
                    }
                });
            });
        }

        function confirmarEntrega(ventaId) {
            $('#venta-id-entregar').val(ventaId);
            $('#modalConfirmarEntrega').modal('show');
            
            // Remover eventos anteriores para evitar múltiples registros
            $('#confirmar-entrega').off('click').on('click', function() {
                $.ajax({
                    url: "{{ route('sales.entregar') }}",
                    method: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: ventaId
                    },
                    success: function(response) {
                        $('#modalConfirmarEntrega').modal('hide');
                        ToastMessage.fire({
                            text: 'Entrega confirmada exitosamente'
                        });
                        // Recargar las tarjetas de delivery
                        getDeliveryOrders();
                    },
                    error: function(xhr) {
                        ToastError.fire({
                            text: 'Error al confirmar la entrega'
                        });
                    }
                });
            });
        }

        function loadDeliveryCards() {
            const $container = $('.row.g-4');

            // Hacer petición para obtener datos JSON de pedidos
            $.ajax({
                url: "{{ route('sales.anticipated') }}",
                method: 'GET',
                data: {
                    json: true
                },
                dataType: 'json',
                success: function(response) {
                    $container.empty();

                    // Agregar botón de Nuevo Pedido
                    const deliveryMesaId = {{ $mesa_directa ? $mesa_directa->id : 'null' }};
                    const newOrderBtn = `@if (!auth()->user()->hasRole('Mozo'))
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <button class="btn-nuevo-delivery w-100" onclick="irANuevoDelivery(${deliveryMesaId})">
                                <i class="bi bi-plus-circle"></i>
                                <span>Nuevo Pedido</span>
                            </button>
                        </div>
                    @endif`;
                    $container.append(newOrderBtn);

                    // Si hay pedidos, mostrarlos
                    if (response.success && response.anticipadas && response.anticipadas.length > 0) {
                        response.anticipadas.forEach(function(pedido) {
                            // Debug: Ver los datos del pedido
                            console.log('Pedido:', pedido.id, 'Total:', pedido.total, 'Saldo:', pedido
                                .saldo);

                            // Determinar badges de pago y entrega
                            let paymentBadge = '';
                            let paymentClass = '';
                            let deliveryBadge = '';
                            let deliveryClass = '';
                            let horaEntrega = '';
                            // Badge de pago - convertir saldo a número
                            const saldo = parseFloat(pedido.saldo) || 0;
                            console.log('Saldo parseado:', saldo); // Debug

                            if (saldo <= 0) {
                                paymentClass = 'status-pagado';
                                paymentBadge = 'Pagado';
                            } else {
                                paymentClass = 'status-sinpagar';
                                paymentBadge = 'Sin pagar';
                            }

                            // Badge de entrega (siempre "Sin Entregar" porque solo mostramos status = 0)
                            deliveryClass = 'status-sinentrega';
                            deliveryBadge = 'Sin Entregar';

                            // Formatear hora de entrega
                            let horaFormateada = 'Hora no especificada';
                            if (pedido.delivery_hour) {
                                // Si viene en formato HH:MM:SS, extraer solo HH:MM
                                const horaPartes = pedido.delivery_hour.split(':');
                                if (horaPartes.length >= 2) {
                                    horaFormateada = `${horaPartes[0]}:${horaPartes[1]}`;
                                } else {
                                    horaFormateada = pedido.delivery_hour;
                                }
                            }

                            // Formatear fecha de entrega
                            let fechaFormateada = '';
                            if (pedido.delivery_date) {
                                try {
                                    const fecha = new Date(pedido.delivery_date);
                                    const opciones = { day: '2-digit', month: 'short' };
                                    fechaFormateada = fecha.toLocaleDateString('es-ES', opciones);
                                } catch (e) {
                                    console.error('Error formateando fecha:', e);
                                }
                            }

                            const cardHtml = `
                                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                                    <div class="delivery-card-item" onclick="confirmarEntrega(${pedido.id})">
                                        <button class="btn-cancelar-orden" onclick="event.stopPropagation(); cancelarOrden(${pedido.id})" title="Cancelar orden">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                        <div class="d-flex gap-2 mb-2 flex-wrap">
                                            <span class="status-badge ${deliveryClass}">${deliveryBadge}</span>
                                        </div>
                                        <div class="client-name">${pedido.client_name}</div>
                                        <div class="client-address">
                                            <i class="bi bi-geo-alt"></i>
                                            <span>${pedido.address}</span>
                                        </div>
                                        <div class="delivery-price">S/ ${parseFloat(pedido.total).toFixed(2)}</div>
                                        <div class="delivery-time">
                                            <i class="bi bi-clock"></i>
                                            <span>${horaFormateada}</span>
                                            ${fechaFormateada ? `<small class="text-muted ms-1">(${fechaFormateada})</small>` : ''}
                                        </div>
                                        ${pedido.details_count > 0 ? `
                                                    <div style="border-top: 1px solid #e0e0e0; margin-top: 0.75rem; padding-top: 0.75rem;">
                                                        <small class="text-muted">
                                                            ${pedido.details_count} ${pedido.details_count == 1 ? 'producto' : 'productos'}
                                                        </small>
                                                    </div>
                                                ` : ''}
                                    </div>
                                </div>
                            `;
                            $container.append(cardHtml);
                        });
                    } else {
                        // No hay pedidos
                        const emptyMessage = `
                            <div class="col-12">
                                <div class="alert alert-info text-center">
                                    <i class="bi bi-inbox" style="font-size: 3rem; color: #e0e0e0;"></i>
                                    <h5 class="mt-3">No hay pedidos de delivery pendientes</h5>
                                    <p class="text-muted">Los pedidos aparecerán aquí cuando se creen.</p>
                                </div>
                            </div>
                        `;
                        $container.append(emptyMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error al cargar delivery:', status, error);
                    $container.empty();

                    // Agregar botón de Nuevo Pedido aunque falle
                    const deliveryMesaId = {{ $mesa_directa ? $mesa_directa->id : 'null' }};
                    const newOrderBtn = `
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <button class="btn-nuevo-delivery w-100" onclick="irANuevoDelivery(${deliveryMesaId})">
                                <i class="bi bi-plus-circle"></i>
                                <span>Nuevo Pedido</span>
                            </button>
                        </div>
                    `;
                    $container.append(newOrderBtn);

                    const errorMessage = `
                        <div class="col-12">
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle"></i> Error al cargar los pedidos de delivery.
                                <br><small class="text-muted">Por favor, intenta nuevamente.</small>
                            </div>
                        </div>
                    `;
                    $container.append(errorMessage);
                }
            });
        }

        function irANuevoDelivery(mesaId) {
            if (!mesaId) {
                alert('No se encontró la mesa de delivery');
                return;
            }

            // Redirigir a la vista de pedidos con la mesa de delivery
            const newUrl = new URL(
                "{{ route('restaurante.orders', ['mesaId' => 'MESA_ID']) }}".replace('MESA_ID', mesaId)
            );

            newUrl.searchParams.set('employeeId', '{{ auth()->id() }}');
            newUrl.searchParams.set('employeeName', '{{ auth()->user()->name }}');
            newUrl.searchParams.set('cantidadPersonas', '1');
            newUrl.searchParams.set('area', 'delivery');

            window.location.href = newUrl.toString();
        }

        function handleDeliveryClick() {
            const deliveryId = document.getElementById("btn-delivery").getAttribute("data-id");
            const newUrl = new URL(
                "{{ route('restaurante.orders', ['mesaId' => 'MESA_ID']) }}".replace('MESA_ID', deliveryId)
            );
            const areaParam = 'null';

            newUrl.searchParams.set('area', areaParam); // Pasar el valor como null
            newUrl.searchParams.set('employeeId', '{{ auth()->id() }}');
            newUrl.searchParams.set('employeeName', '{{ auth()->user()->name }}');
            newUrl.searchParams.set('cantidadPersonas', '1');

            window.location.href = newUrl.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Contraer el sidebar agregando la clase sidebar-mini al body
            $('.sidebar-default').addClass('sidebar-mini');
            const params = new URLSearchParams(window.location.search);
            let areaParam = params.get('area');

            if (!areaParam) {
                const firstTab = document.querySelector('#pdv-tabs .nav-link');
                if (firstTab && firstTab.id) {
                    const parts = firstTab.id.split('-');
                    areaParam = parts[parts.length - 1];
                }
            }
            if (areaParam) {
                getTables(areaParam);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            @foreach ($mesas as $mesa)
                @if ($mesa->status === 'Ocupado' && $mesa->opened_at)
                    try {
                        iniciarContadorMesa("{{ $mesa->id }}", "{{ $mesa->opened_at }}");
                    } catch (error) {
                        console.error('Error al iniciar contador para mesa {{ $mesa->id }}:', error);
                    }
                @endif
            @endforeach
        });

        function verPedido(mesaId) {
            mesaSeleccionada = mesaId;

            @if (auth()->user()->hasRole('Caja') || auth()->user()->hasRole('Admin'))
                // Caja va directo a agregar productos sin PIN
                const currentUrl = new URL(window.location.href);
                const areaParam = currentUrl.searchParams.get('area');

                const newUrl = new URL(
                    "{{ route('restaurante.orders', ['mesaId' => 'MESA_ID']) }}".replace('MESA_ID', mesaId));

                if (areaParam) {
                    newUrl.searchParams.set('area', areaParam);
                }
                // Para Caja, usar datos del usuario autenticado
                // newUrl.searchParams.set('employeeId', '{{ auth()->id() }}');
                // newUrl.searchParams.set('employeeName', '{{ auth()->user()->name }}');
                // newUrl.searchParams.set('cantidadPersonas', '1'); // Valor por defecto

                window.location.href = newUrl.toString();
            @else
                // Mozo necesita PIN
                $('#cantidadPersonas').val('');
                $('#cantidadActual').addClass('d-none');

                $.ajax({
                    url: "{{ route('mesas.pedido', ['id' => 'MESA_ID']) }}".replace('MESA_ID', mesaId),
                    method: 'GET',
                    success: function(response) {
                        if (response.success && response.number_persons > 0) {
                            $('#cantidadActualValor').text(response.number_persons);
                            $('#cantidadActual').removeClass('d-none');
                            $('#cantidadPersonas').val(response.number_persons);
                        }
                        $('#pinModal').modal('show');
                    },
                    error: function() {
                        $('#pinModal').modal('show');
                    }
                });
            @endif
        }

        document.getElementById('pinSubmitBtn').addEventListener('click', function() {
            const pin = document.getElementById('pinInput').value;
            const cantidadPersonas = document.getElementById('cantidadPersonas').value;

            if (!pin || !mesaSeleccionada || !cantidadPersonas || cantidadPersonas <= 0) {
                if (!cantidadPersonas || cantidadPersonas <= 0) {
                    document.getElementById('cantidadError').classList.remove('d-none');
                }
                return;
            }

            $.ajax({
                url: "{{ route('employees.validarPin') }}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    pin: pin
                },
                success: function(response) {
                    if (response.valid) {
                        // Validar si es motorizado y la mesa no es delivery
                        if (response.is_motoriced == 1) {
                            // Obtener el nombre de la mesa desde el DOM
                            const mesaCard = document.querySelector(`#mesa-card-${mesaSeleccionada}`);
                            let mesaName = '';
                            if (mesaCard) {
                                const mesaTitle = mesaCard.querySelector('.mesa-card-title');
                                if (mesaTitle) {
                                    mesaName = mesaTitle.textContent.trim().toUpperCase();
                                }
                            }
                            
                            // Si no se pudo obtener desde el DOM, hacer petición
                            if (!mesaName && mesaSeleccionada) {
                                $.ajax({
                                    url: "{{ route('tables.show', ['table' => 'TABLE_ID']) }}".replace('TABLE_ID', mesaSeleccionada),
                                    method: 'GET',
                                    async: false,
                                    success: function(tableResponse) {
                                        if (tableResponse.status && tableResponse.data) {
                                            mesaName = (tableResponse.data.name || '').toUpperCase();
                                        }
                                    }
                                });
                            }
                            
                            // Validar que la mesa sea delivery
                            if (mesaName !== 'DELIVERY') {
                                document.getElementById('pinError').textContent = 'Los empleados motorizados solo pueden abrir mesas de delivery';
                                document.getElementById('pinError').classList.remove('d-none');
                                return;
                            }
                        }
                        
                        bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
                        const currentUrl = new URL(window.location.href);
                        const areaParam = currentUrl.searchParams.get('area');

                        const newUrl = new URL(
                            "{{ route('restaurante.orders', ['mesaId' => 'MESA_ID']) }}".replace(
                                'MESA_ID', mesaSeleccionada));

                        if (areaParam) {
                            newUrl.searchParams.set('area', areaParam);
                        }
                        newUrl.searchParams.set('employeeId', response.employeeId);
                        newUrl.searchParams.set('employeeName', encodeURIComponent(response
                            .employeeName));
                        newUrl.searchParams.set('cantidadPersonas', cantidadPersonas);
                        if (response.is_motoriced == 1) {
                            newUrl.searchParams.set('is_motoriced', 'true');
                        } else {
                            newUrl.searchParams.set('is_motoriced', 'false');
                        }

                        window.location.href = newUrl.toString();
                    } else {
                        document.getElementById('pinError').classList.remove('d-none');
                    }
                },
                error: function() {
                    document.getElementById('pinError').textContent = 'Error al validar PIN';
                    document.getElementById('pinError').classList.remove('d-none');
                }
            });
        });

        function getTables(area_id) {
            const $container = $('.row.g-4');
            if (!$container.length) return;

            try {
                const url = new URL(window.location.href);
                url.searchParams.set('area', area_id);
                history.replaceState(null, '', url.toString());
            } catch (e) {
                const qp = '?area=' + encodeURIComponent(area_id);
                history.replaceState(null, '', window.location.pathname + qp);
            }

            $('#pdv-tabs .nav-link').removeClass('active');
            $(`#tab-area-${area_id}`).addClass('active');

            // Loader
            $container.html(
                '<div class="col-12 text-center p-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>'
            );

            $.ajax({
                url: "{{ route('tables.get') }}" + '?area=' + encodeURIComponent(area_id),
                method: 'GET',
                data: {
                    area_id: area_id
                },
                dataType: 'json',
                success: function(data) {
                    console.log('Response getTables:', data);
                    $container.empty();
                    const tables = Array.isArray(data) ? data : (data.data || []);
                    if (!Array.isArray(tables) || tables.length === 0) {
                        $container.html(
                            '<div class="col-12"><div class="alert alert-info">No hay mesas en esta área.</div></div>'
                        );
                        return;
                    }
                    tables.forEach(function(mesa) {
                        const estadoLibre = String(mesa.status || '').toLowerCase() === 'libre' ||
                            String(mesa.status || '').toLowerCase() === 'free';
                        const estadoText = mesa.status ? mesa.status : 'Desconocido';
                        const cantidadPersonas = mesa.order ? mesa.order.number_persons : 0;
                        const totalConsumo = mesa.order ? (mesa.order.total_price || 0) : 0;
                        let minutos = '00:00';
                        if (mesa.opened_at && !estadoLibre) {
                            const openedAt = new Date(String(mesa.opened_at).replace(' ', 'T'));
                            if (!isNaN(openedAt.getTime())) {
                                const diffMs = Date.now() - openedAt.getTime();
                                const totalSec = Math.max(0, Math.floor(diffMs / 1000));
                                const min = Math.floor(totalSec / 60);
                                minutos = `${min} min`;
                            } else {
                                minutos = '00:00';
                            }
                        } else {
                            minutos = '00:00';
                        }

                        const cardHtml = `
                        <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                            <div class="card-mesa ${estadoLibre ? '' : 'occupied'} shadow" style="border-top: 7px solid ${estadoLibre ? '#28a745' : '#ff6b35'};"
                                id="mesa-card-${mesa.id}"
                                data-mesa-id="${mesa.id}"
                                ${mesa.opened_at ? `data-opened-at="${mesa.opened_at}"` : ''} 
                                onclick="verPedido(${mesa.id})">
                                
                                <div class="mesa-card-header">
                                    <h3 class="mesa-card-title">${mesa.name}</h3>
                                </div>
                                
                                <div class="mesa-card-body">
                                    <div class="mesa-status-badge">
                                        <span class="status-dot ${estadoLibre ? 'libre' : 'ocupado'}"></span>
                                        <span id="estado-mesa-${mesa.id}">${estadoText}</span>
                                    </div>
                                    
                                    <div class="mesa-personas-header text-center">
                                        <i class="bi bi-person-circle"></i>
                                        <span id="cantidad-personas-${mesa.id}">${cantidadPersonas > 0 ? cantidadPersonas : '0'}</span>
                                    </div>
                                    
                                    
                                    <div class="mesa-monto" id="total-consumo-${mesa.id}">
                                        S/ ${parseFloat(totalConsumo).toFixed(2)}
                                    </div>
                                    
                                    <div class="mesa-tiempo">
                                        <i class="bi bi-clock"></i>
                                        <span id="contador-${mesa.id}">${minutos}</span>
                                    </div>
                                
                                </div>
                            </div>
                        </div>
                    `;
                        $container.append(cardHtml);
                    });
                },
                error: function(xhr, status, err) {
                    console.error('Error al cargar mesas:', status, err);
                    $container.html(
                        '<div class="col-12"><div class="alert alert-danger">Error al cargar mesas. Revise la consola.</div></div>'
                    );
                }
            });
        }

        document.addEventListener('DOMContentLoaded', function() {
            const params = new URLSearchParams(window.location.search);
            let areaParam = params.get('area');

            if (!areaParam) {
                const firstTab = document.querySelector('#pdv-tabs .nav-link');
                if (firstTab && firstTab.id) {
                    const parts = firstTab.id.split('-');
                    areaParam = parts[parts.length - 1];
                }
            }

            if (areaParam) {
                getTables(areaParam);
            }
        });
    </script>
@endsection
