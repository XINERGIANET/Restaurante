<div class="card delivery-card">
    <div class="card-body">

        <h2 class="h5 mt-3 mb-2"><span id="side-mesa-name">Seleccione una mesa</span></h2>
        <input type="hidden" id="side-mesa-id" name="mesa_id" value="">

        <!-- Input de búsqueda con 5 botones pegados al costado -->
        <div class="mb-3 d-flex flex-wrap gap-2">
            @foreach ($categories as $i => $cat)
            <button type="button" class="btn btn-outline-primary btn-cat{{ $i === 0 ? ' active' : '' }} btn-cat" data-cat-id="{{ $cat->id }}" onclick="showProducts('{{ $cat->id }}', this)">{{ $cat->name }}</button>
            @endforeach
        </div>

        <div id="products-container" class="row g-2">
            <!-- Aquí se mostrarán los productos -->
        </div>


        <ul class="nav nav-tabs" id="table-tabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="tab-pedidos" data-tab="pedidos" href="#" onclick="switchTable('pedidos', this)">Pedidos</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="tab-cuenta" data-tab="cuenta" href="#" onclick="switchTable('cuenta', this)">Cuenta</a>
            </li>
        </ul>

        <div id="tab-content" class="mt-2">
            <div id="content-pedidos" class="table-pane active">
                <input type="text" id="search-product-pedidos" class="form-control-sm" placeholder="Buscar producto">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm align-middle" id="selected-products-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Cantidad</th>
                                <th>Descripción</th>
                                <th style="width: 110px;">P.U.</th>
                                <th style="width: 110px;">Importe</th>
                                <th>Cortesía</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Productos agregados aparecerán aquí -->
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-success btn-xl" id="btn-confirmar-pedido"><i class="bi bi-check-lg"></i> Pedir</button>
            </div>
            <div id="content-cuenta" class="table-pane d-none">
                <div class="table-responsive mt-3">
                    <table class="table table-bordered table-sm align-middle" id="cuenta-table">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Cantidad</th>
                                <th>Descripción</th>
                                <th style="width: 110px;">P.U.</th>
                                <th style="width: 110px;">Importe</th>
                                <th style="width: 60px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end align-items-center mb-2">
                    <label for="descuento-input" class="me-2">Descuento:</label>
                    <input type="text" class="form-control-sm w-50 border-dark" id="descuento-input"  onkeypress="isDecimal(event)">
                </div>
                <div class="d-flex justify-content-end align-items-center mb-2">
                    <label for="motivo-input" class="me-2">Motivo:</label>
                    <input type="text" class="form-control-sm w-50 border-dark" id="motivo-input">
                </div>
                <h5 class="text-end"><strong>TOTAL: S/ <span id="totalAmount" name="total">0.00</span></strong></h5>
                <button class="btn btn-success btn-xl" id="btn-cobrar" onclick="window.location.href='{{ route('sales.restaurantePago', ['mesaId' => 'MESA_ID']) }}'.replace('MESA_ID', mesaSeleccionada.id)"><i class="bi bi-check-lg"></i> Cobrar</button>
            </div>

        </div>
    </div>
</div>



<!-- Modal de Cobro -->
<div class="modal fade" id="modalCobro" tabindex="-1" aria-labelledby="modalCobroLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar Cobro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body px-4 py-3">
                <form id="formCobro">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Selección de Comprobante -->
                            <div class="mb-3">
                                <label class="mb-2"><strong>Tipo de Comprobante</strong></label>
                                <div class="btn-group d-flex justify-content-start mb-4">
                                    <button type="button" class="btn btn-outline-primary me-1" id="btn-boleta"
                                        onclick="selectVoucherType('boleta', this)">Boleta</button>
                                    <button type="button" class="btn btn-outline-success me-1" id="btn-factura"
                                        onclick="selectVoucherType('factura', this)">Factura</button>
                                    <button type="button" class="btn btn-outline-info me-1" id="btn-ticket"
                                        onclick="selectVoucherType('ticket', this)">Ticket</button>
                                </div>
                                <input type="hidden" name="voucher_type" id="voucher_type" value="">
                            </div>

                            <div class="mb-3">
                                <label class="form-label"><strong>Empleado</strong></label>
                                <select class="form-control" name="employee_id" id="employee_id">
                                    <option value="">Seleccione un empleado</option>
                                    @foreach ($employees as $employee)
                                    <option value="{{ $employee->id }}">{{ $employee->name }} {{ $employee->last_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Documento y Cliente -->
                            <div class="mb-3">
                                <label class="col-sm-4 col-form-label text-start"><strong>Documento</strong></label>
                                <div class="input-group">
                                    <input type="text" class="form-control form-control-xs" id="document"
                                        name="document" maxlength="11" onkeypress="isNumber(event)">
                                    <button type="button" class="btn btn-primary btn-xs"
                                        onclick="searchAPI('#document','#name','#address')"><i
                                            class="bi bi-search"></i></button>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Cliente</strong></label>
                                <input type="text" class="form-control" id="name" name="client">
                            </div>
                            <div class="mb-3">
                                <label class="form-label"><strong>Observación</strong></label>
                                <textarea class="form-control" id="observacion" name="observacion" rows="2" placeholder="Observaciones adicionales (opcional)"></textarea>
                            </div>
                            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">
                            <input hidden type="number" name="type_sale" id="type_sale" value="1">
                            <input hidden type="number" name="status" id="status" value="1">
                            <input hidden type="number" name="type_status" id="type_status" value="0">
                        </div>

                        <div class="col-md-6">
                            <!-- Métodos de pago -->
                            <div class="mb-3">
                                <label class="mb-2"><strong>Método de Pago</strong></label>
                                <div class="d-flex flex-wrap">
                                    @foreach ($pms as $index => $method)
                                    @php
                                    $colorClass = $colors[$index % count($colors)];
                                    @endphp
                                    <button
                                        type="button"
                                        id="btn-{{ $method->id }}"
                                        class="btn {{ $colorClass }} me-2 mb-2"
                                        data-campos="campos-{{ str_replace(' ', '-', $method->name) }}"
                                        data-id="{{ $method->id }}"
                                        onclick="seleccionarMedioPago('{{ $method->id }}', event)">
                                        {{ strtoupper($method->name) }}
                                    </button>
                                    @endforeach
                                </div>
                            </div>

                            <!-- Campos por método de pago -->
                            @foreach ($pms as $method)
                            <div class="mb-3 d-none align-items-center gap-3" id="campos-{{ str_replace(' ', '-', $method->name) }}">
                                <label class="form-label mb-0">
                                    <strong>{{ strtoupper(Str::limit($method->name, 4, '.')) }}</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">S/</span>
                                    <input type="text" class="form-control" placeholder="Ingrese Monto"
                                        name="monto[{{ $method->id }}]" onkeypress="isDecimal(event)" oninput="calcularSaldo()">
                                </div>
                            </div>
                            @endforeach

                            <!-- Checkbox para Delivery -->
                            <!-- <div class="mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkDelivery" onchange="toggleDelivery()">
                                    <label class="form-check-label" for="checkDelivery">
                                        <strong>Delivery</strong>
                                    </label>
                                </div>
                            </div> -->

                            <!-- Campos de Delivery (inicialmente ocultos) -->
                            <!-- <div id="camposDelivery" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label"><strong>Fecha de Entrega</strong></label>
                                    <input type="date" class="form-control" name="delivery_date" id="delivery_date">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>Hora de Entrega</strong></label>
                                    <input type="text" class="form-control" name="delivery_hour" id="delivery_hour" placeholder="Ej: 14:30 o 2:30 PM">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label"><strong>Dirección de Entrega</strong></label>
                                    <textarea class="form-control" name="delivery_address" id="delivery_address" rows="2" placeholder="Ingrese la dirección completa de entrega"></textarea>
                                </div>
                            </div> -->
                        </div>
                    </div>

                    <div class="modal-footer mt-4">
                        <!-- Mostrar el total y saldo -->
                        <div class="mb-2 text-end w-100">
                            <h5><strong>TOTAL: S/ <span id="totalAmountModal">0.00</span></strong></h5>
                            <h6><strong>SALDO: S/ <span id="saldoAmount" class="text-danger">0.00</span></strong></h6>
                        </div>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Finalizar Venta</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<script>
    const categories = @json($categories);

    function showProducts(catId, btn) {
        // Quitar 'active' de todos los botones
        document.querySelectorAll('.btn-cat').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const container = document.getElementById('products-container');
        container.innerHTML = '';
        const cat = categories.find(c => c.id == catId);
        if (!cat || !cat.products) return;
        cat.products.forEach(prod => {
            const div = document.createElement('div');
            div.className = 'col-6 col-md-4 mb-2';
            div.innerHTML = `<div class='card card-product border-sutil text-center p-2' style='cursor:pointer;' data-product-id='${prod.id}' data-product-name='${prod.name}' data-product-price='${prod.price ?? 0}'>${prod.name}</div>`;
            // Al hacer click, agregar a la tabla
            div.querySelector('.card-product').addEventListener('click', function() {
                addProductToTable({
                    id: prod.id,
                    name: prod.name,
                    unit_price: prod.unit_price ?? 0
                });
            });
            container.appendChild(div);
        });

        function updateRowImporte(row, unit_price) {
            let qty = parseInt(row.querySelector('.qty-input').value) || 1;
            let importe = qty * parseFloat(unit_price);
            row.querySelector('.importe span').textContent = importe.toFixed(2);
        }
    }

    // --- Lógica para agregar productos a la tabla ---
    function addProductToTable(product) {
        const table = document.getElementById('selected-products-table').querySelector('tbody');
        // Buscar si ya existe el producto
        let row = table.querySelector(`tr[data-product-id='${product.id}']`);
        switchTable('pedidos', document.getElementById('tab-pedidos')); //cambia a la tabla pedidos
        if (row) {
            // Acumular cantidad
            let qtyInput = row.querySelector('.qty-input');
            qtyInput.value = parseInt(qtyInput.value) + 1;
            updateRowImporte(row, product.unit_price);
        } else {
            // Crear nueva fila
            row = document.createElement('tr');
            row.setAttribute('data-product-id', product.id);
            row.innerHTML = `
                <td class='d-flex align-items-center gap-1'>
                    <button type='button' class='btn btn-light btn-sm btn-qty-minus' tabindex='-1' style='width:28px;padding:0 0.5rem;'>-</button>
                    <input type='number' min='1' value='1' class='form-control form-control-sm qty-input text-center' style='width:48px;display:inline-block;'>
                    <button type='button' class='btn btn-light btn-sm btn-qty-plus' tabindex='-1' style='width:28px;padding:0 0.5rem;'>+</button>
                </td>
                <td>${product.name}</td>
                <td class='text-end'>S/ <span class='pu'>${parseFloat(product.unit_price).toFixed(2)}</span></td>
                <td class='text-end importe'>S/ <span>${parseFloat(product.unit_price).toFixed(2)}</span></td>
                <td>
                    <div class="form-check text-center">
                        <input class="form-check-input" type="checkbox" value="" id="flexCheckDefault">
                    </div>
                </td>
                <td class='text-center'><button type='button' class='btn btn-sm btn-danger btn-del-prod'><i class='bi bi-trash'></i></button></td>
            `;
            // Evento para eliminar
            row.querySelector('.btn-del-prod').addEventListener('click', function() {
                row.remove();
            });
            // Evento para actualizar importe al cambiar cantidad
            row.querySelector('.qty-input').addEventListener('input', function() {
                if (this.value < 1) this.value = 1;
                updateRowImporte(row, product.unit_price);
            });
            // Botón +
            row.querySelector('.btn-qty-plus').addEventListener('click', function() {
                let qtyInput = row.querySelector('.qty-input');
                qtyInput.value = parseInt(qtyInput.value) + 1;
                qtyInput.dispatchEvent(new Event('input'));
            });
            // Botón -
            row.querySelector('.btn-qty-minus').addEventListener('click', function() {
                let qtyInput = row.querySelector('.qty-input');
                if (parseInt(qtyInput.value) > 1) {
                    qtyInput.value = parseInt(qtyInput.value) - 1;
                    qtyInput.dispatchEvent(new Event('input'));
                }
            });
            table.appendChild(row);
        }
    }
    // Mostrar productos de la primera categoría por defecto
    document.addEventListener('DOMContentLoaded', function() {
        if (categories.length > 0) {
            const firstBtn = document.querySelector('.btn-cat');
            if (firstBtn) showProducts(categories[0].id, firstBtn);
        }


        $('#search-product-pedidos').autocomplete({
            source: function(request, response) {
                let currentTerm = $('#search-product-pedidos').val();
                // Solo buscar si hay al menos una letra
                if (currentTerm && currentTerm.length > 0) {
                    $.ajax({
                        url: "{{ route('products.searchrs') }}",
                        method: 'GET',
                        data: {
                            query: currentTerm
                        },
                        success: function(data) {
                            response($.map(data, function(item) {
                                return {
                                    label: item.name + ' - S/ ' + parseFloat(item.unit_price || 0).toFixed(2),
                                    value: item.name,
                                    id: item.id,
                                    name: item.name,
                                    unit_price: item.unit_price,
                                    quantity: item.quantity || 0
                                };
                            }));
                        }
                    });
                } else {
                    // Si no hay letras, limpia el autocomplete
                    response([]);
                }
            },
            appendTo: '#abrirMesaModal',
            select: function(event, ui) {
                addProductToTable({
                    id: ui.item.id,
                    name: ui.item.name,
                    unit_price: ui.item.unit_price
                });
                // Limpiar el campo de búsqueda
                $('#search-product').val('');
                $('#product_id').val('');
                return false; // Previene que se llene el input con el valor
            },
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append(`<div class="d-flex justify-content-between">
                        <span>${item.name}</span>
                        <small>S/ ${parseFloat(item.unit_price || 0).toFixed(2)}</small>
                    </div>`)
                .appendTo(ul);
        };
    });

    function switchTable(tabName, element) {
        // Remover clase active de todos los nav-links
        document.querySelectorAll('#table-tabs .nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Agregar clase active al tab seleccionado
        element.classList.add('active');

        // Ocultar todos los contenidos de tabs
        document.querySelectorAll('.table-pane').forEach(pane => {
            pane.classList.remove('active');
            pane.classList.add('d-none');
        });


        // Mostrar el contenido del tab seleccionado
        const targetContent = document.getElementById('content-' + tabName);
        if (targetContent) {
            targetContent.classList.add('active');
            targetContent.classList.remove('d-none');
        }
    }

    document.getElementById('btn-confirmar-pedido').addEventListener('click', function() {
        const mesaId = document.getElementById('side-mesa-id').value;
        if (!mesaId) {
            alert('Seleccione una mesa antes de confirmar el pedido.');
            return;
        }

        // Recolectar productos de la tabla
        const detalles = [];
        $('#selected-products-table tbody tr').each(function() {
            detalles.push({
                product_id: $(this).data('product-id'),
                quantity: $(this).find('.qty-input').val(),
                product_price: $(this).find('.pu').text()
            });
        });

        if (detalles.length === 0) {
            alert('Agregue al menos un producto al pedido.');
            return;
        }

        $.ajax({
            url: "{{ route('sales.addOrders', ['mesaId' => 'MESA_ID']) }}".replace('MESA_ID', mesaId),
            method: 'POST',
            data: {
                detalles: detalles,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                if (data.success) {
                    alert('Pedido registrado correctamente');
                    limpiarPedido();
                    verPedido(mesaId);
                    switchTable('cuenta', document.getElementById('tab-cuenta'));
                } else {
                    alert('Error al registrar pedido: ' + (data.errors?.map(e => e.error).join(', ') || ''));
                }
            },
            error: function() {
                alert('Error de red o servidor');
            }
        });
    });

    limpiarPedido = function() {
        const $tbody = $('#selected-products-table tbody');
        $tbody.html('');
    };



    function abrirModalCobro() {
        // Sincronizar total en el modal
        const total = $('#totalAmount').text();
        $('#totalAmountModal').text(total);

        // Inicializar el observador del total
        inicializarObservadorTotal();

        // Calcular saldo inicial
        setTimeout(() => {
            calcularSaldo();
        }, 100);

        const modal = new bootstrap.Modal(document.getElementById('modalCobro'));
        modal.show();
    }

    function selectVoucherType(type, button) {
        // Remover clases activas de todos los botones
        document.querySelectorAll('#btn-boleta, #btn-factura, #btn-ticket').forEach(btn => {
            // Resetear a clases outline
            btn.classList.remove('btn-primary', 'btn-success', 'btn-info');
            if (btn.id === 'btn-boleta') {
                btn.classList.add('btn-outline-primary');
            } else if (btn.id === 'btn-factura') {
                btn.classList.add('btn-outline-success');
            } else if (btn.id === 'btn-ticket') {
                btn.classList.add('btn-outline-info');
            }
        });

        // Activar el botón seleccionado
        button.classList.remove('btn-outline-primary', 'btn-outline-success', 'btn-outline-info');
        if (type === 'boleta') {
            button.classList.add('btn-primary');
        } else if (type === 'factura') {
            button.classList.add('btn-success');
        } else if (type === 'ticket') {
            button.classList.add('btn-info');
        }

        // Establecer el valor en el campo oculto con la primera letra en mayúscula (como espera el backend)
        const voucherValue = type.charAt(0).toUpperCase() + type.slice(1);
        document.getElementById('voucher_type').value = voucherValue;

        console.log('Tipo de comprobante seleccionado:', voucherValue);
    }

    function isDecimal(evt) {
        evt = evt || window.event;
        var charCode = evt.which || evt.keyCode;

        // Solo permite números y un solo punto decimal
        if ((charCode >= 48 && charCode <= 57) || charCode === 46) {
            const input = evt.target || evt.srcElement;
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

    function calcularSaldo() {
        const total = parseFloat($('#totalAmount').text()) || 0;
        let totalPagado = 0;

        // Sumar todos los montos de pago visibles
        document.querySelectorAll('input[name^="monto["]').forEach(input => {
            const container = input.closest('.d-flex, .mb-3, .mb-4');
            if (container && !container.classList.contains('d-none') && container.style.display !== 'none') {
                totalPagado += parseFloat(input.value) || 0;
            }
        });

        const saldo = total - totalPagado;
        const saldoElement = $('#saldoAmount');

        if (saldoElement.length) {
            if (total === 0) {
                saldoElement.text('0.00');
                saldoElement.removeClass('text-danger text-success');
            } else {
                saldoElement.text(Math.abs(saldo).toFixed(2));

                // Cambiar color según el saldo
                if (saldo > 0.01) {
                    saldoElement.removeClass('text-success').addClass('text-danger'); // Debe dinero
                } else if (saldo < -0.01) {
                    saldoElement.removeClass('text-danger').addClass('text-warning'); // Sobra dinero (vuelto)
                } else {
                    saldoElement.removeClass('text-danger').addClass('text-success'); // Exacto
                }
            }
        }

        console.log('Cálculo saldo - Total:', total, 'Pagado:', totalPagado, 'Saldo:', saldo);
        return parseFloat(saldo) || 0;
    }

    document.getElementById('formCobro').addEventListener('submit', function(e) {
        e.preventDefault();

        const botonesMedioPago = document.querySelectorAll('.d-flex.flex-wrap button');
        const metodoPagoSeleccionado = Array.from(botonesMedioPago).some(btn => btn.classList.contains('active'));
        const comprobante = document.getElementById('voucher_type').value;

        if (!comprobante) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar un tipo de comprobante.'
            });
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            return;
        }

        if (!metodoPagoSeleccionado) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Debe seleccionar al menos un método de pago.'
            });
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            return;
        }

        // Obtener valores actuales de type_status y status
        const typeStatus = document.getElementById('type_status').value;
        const status = document.getElementById('status').value;
        const checkDeliveryEl = document.getElementById('checkDelivery');

        // Validar saldos según el tipo de venta
        const saldoActual = calcularSaldo();
        const saldoElement = document.getElementById('saldoAmount');

        // Para ventas directas (type_status=0, status=1) NO permitir saldos
        // Para delivery (type_status=2) NO permitir saldos NUNCA (sin importar el status)
        if (saldoElement && saldoActual < 0 && saldoElement.classList.contains('text-warning')) {
            ToastMessage.fire({
                icon: 'error',
                text: 'El saldo no puede ser negativo.'
            });
            $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            return;
        }

        if ((typeStatus == '0' && status == '1') || typeStatus == '2' || (checkDeliveryEl && checkDeliveryEl.checked)) {
            if (saldoActual > 0.01) {
                const tipoVenta = (checkDeliveryEl && checkDeliveryEl.checked) ? 'delivery' : 'venta directa';
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: `Para ${tipoVenta} debe cancelar el monto completo antes de registrar la venta. El saldo actual es: S/ ${saldoActual.toFixed(2)}`
                });
                $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
                return;
            }
        }

        // Validar campos de delivery si está activado
        if (checkDeliveryEl && checkDeliveryEl.checked) {
            const deliveryDate = document.getElementById('delivery_date').value;
            const deliveryHour = document.getElementById('delivery_hour').value;
            const deliveryAddress = document.getElementById('delivery_address').value;

            if (!deliveryDate || !deliveryHour || !deliveryAddress.trim()) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Debe completar todos los campos de delivery: fecha, hora y dirección de entrega.'
                });
                $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
                return;
            }
        }

        // Crear FormData ANTES de usarlo
        const form = this;
        const formData = new FormData(form);

        // Por este bloque:
        const cuentaProducts = [];
        $('#cuenta-table tbody tr').each(function() {
            const $tds = $(this).find('td');
            cuentaProducts.push({
                id: $(this).data('product-id') || null, // si tienes el id en data-product-id, si no puedes omitirlo
                cantidad: $tds.eq(0).text().trim(),
                nombre: $tds.eq(1).text().trim(),
                precio: $tds.eq(2).text().trim()
            });
        });
        formData.append('products', JSON.stringify(cuentaProducts));

        const totalElement = document.getElementById('totalAmountInput');
        const totalValue = totalElement ? totalElement.value : '0';
        formData.append('total', totalValue);

        formData.append('voucher_type', comprobante);
        formData.append('restaurant', 1);

        // Agregar documento del cliente
        const documentElement = document.getElementById('document');
        const documentValue = documentElement ? documentElement.value || '' : '';
        formData.append('document', documentValue);

        const employeeElement = document.getElementById('employee_id');
        const employeeValue = employeeElement ? employeeElement.value || '' : '';
        formData.append('employee_id', employeeValue);

        const discount = parseFloat(document.getElementById('descuento-input').value) || 0;
        formData.append('discount', discount);

        const discount_reason = document.getElementById('motivo-input').value;
        formData.append('discount_reason', discount_reason);

        // Agregar nombre del cliente si existe
        const nameElement = document.getElementById('name');
        const nameValue = nameElement ? nameElement.value || '' : '';
        formData.append('client_name', nameValue);

        // Agregar dirección del cliente si existe
        const addressElement = document.getElementById('address');
        const addressValue = addressElement ? addressElement.value || '' : '';
        formData.append('client_address', addressValue);

        // Agregar datos de delivery si está activado
        if (checkDeliveryEl && checkDeliveryEl.checked) {
            const deliveryDate = document.getElementById('delivery_date');
            const deliveryHour = document.getElementById('delivery_hour');
            const deliveryAddress = document.getElementById('delivery_address');

            formData.append('fecha_entrega', deliveryDate ? deliveryDate.value : '');
            formData.append('hora_entrega', deliveryHour ? deliveryHour.value : '');
            formData.append('direccion', deliveryAddress ? deliveryAddress.value : '');
        }

        formData.append('table_id', document.getElementById('side-mesa-id').value);
        let openedMesaId = document.getElementById('side-mesa-id').value;

        if (openedMesaId) formData.append('mesa_id', openedMesaId);

        const resetFormulario = () => {
            location.reload();
            // selectedProducts = [];
            // addProductToTable();
            // document.getElementById('formCobro').reset();
            // document.getElementById('totalAmount').textContent = '0.00';
            // document.getElementById('totalAmountInput').value = 0;
            // document.getElementById('voucher_type').value = '';

            // // Limpiar variables globales
            // currentOrderId = null;
            // const mesaIdToReset = openedMesaId;
            // openedMesaId = null;

            // // Limpiar borde de la mesa si existe
            // if (mesaIdToReset) {
            //     const card = document.getElementById(`mesa-card-${mesaIdToReset}`);
            //     if (card) {
            //         card.classList.remove('borde-verde', 'borde-naranja', 'borde-rojo');
            //         console.log('Borde removido de mesa:', mesaIdToReset, 'durante resetFormulario');
            //     }
            // }

            // // Resetear botones de comprobante
            // document.querySelectorAll('#btn-boleta, #btn-factura, #btn-ticket').forEach(btn => {
            //     btn.classList.remove('btn-primary', 'btn-success', 'btn-info');
            //     if (btn.id === 'btn-boleta') {
            //         btn.classList.add('btn-outline-primary');
            //     } else if (btn.id === 'btn-factura') {
            //         btn.classList.add('btn-outline-success');
            //     } else if (btn.id === 'btn-ticket') {
            //         btn.classList.add('btn-outline-info');
            //     }
            // });

            // // Resetear métodos de pago
            // document.querySelectorAll('[id^="btn-"].active').forEach(btn => {
            //     btn.classList.remove('active', 'btn-success');
            //     const campos = btn.dataset.campos;
            //     $(`#${campos}`).addClass('d-none').removeClass('d-flex');
            //     $(`#${campos} input[type="text"]`).val('');
            // });

            // // Resetear campos de delivery
            // const checkDeliveryReset = document.getElementById('checkDelivery');
            // const camposDeliveryReset = document.getElementById('camposDelivery');
            // if (checkDeliveryReset) {
            //     checkDeliveryReset.checked = false;
            // }
            // if (camposDeliveryReset) {
            //     camposDeliveryReset.classList.add('d-none');
            // }

            // // Limpiar campos de delivery
            // const deliveryDateReset = document.getElementById('delivery_date');
            // const deliveryHourReset = document.getElementById('delivery_hour');
            // const deliveryAddressReset = document.getElementById('delivery_address');
            // if (deliveryDateReset) deliveryDateReset.value = '';
            // if (deliveryHourReset) deliveryHourReset.value = '';
            // if (deliveryAddressReset) deliveryAddressReset.value = '';

            // // Restaurar valores de status
            // const typeStatusReset = document.getElementById('type_status');
            // const statusReset = document.getElementById('status');
            // if (typeStatusReset) typeStatusReset.value = 0;
            // if (statusReset) statusReset.value = 1;

            // console.log('Formulario reseteado completamente para mesa:', mesaIdToReset);
        };

        fetch(`{{ route('sales.store') }}`, { //deberia estar con Ajax
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
            })
            .then(res => res.json())
            .then(response => {
                console.log('Respuesta venta:', response);
                if (response.status) {

                    $('#modalCobro').modal('hide');
                    $('#abrirMesaModal').modal('hide');

                    if (typeof imprimirVenta === 'function') {
                        imprimirVenta(response.venta.id);
                    }

                    if (typeof ToastMessage !== 'undefined') {
                        ToastMessage.fire({
                            text: 'Venta registrada correctamente'
                        });
                    }

                    // Cerrar mesa y restaurar UI
                    cerrarMesaFrom(openedMesaId);
                    resetFormulario();
                } else {
                    if (typeof ToastError !== 'undefined') {
                        ToastError.fire({
                            text: response.message || 'Error al registrar venta'
                        });
                    } else {
                        alert(response.message || 'Error al registrar venta');
                    }
                }
            })
            .catch((error) => {
                console.error('Error:', error);
                if (typeof ToastError !== 'undefined') {
                    ToastError.fire({
                        text: 'Error de red al enviar la venta'
                    });
                } else {
                    alert('Error de red al enviar la venta');
                }
            })
            .finally(() => {
                $('#global-spinner').removeClass('spinner-visible').addClass('spinner-hidden');
            });
    });

    function isNumber(evt) {
        evt = evt || window.event;
        var charCode = evt.which || evt.keyCode;

        // Solo permite números (0–9)
        if (charCode < 48 || charCode > 57) {
            evt.preventDefault();
            return false;
        }

        return true;
    }

    function inicializarObservadorTotal() {
        const totalAmountElement = document.getElementById('totalAmount');
        if (totalAmountElement && !totalAmountElement.hasObserver) {
            const observer = new MutationObserver(() => {
                // Sincronizar total en el modal cuando cambie
                const total = $('#totalAmount').text();
                $('#totalAmountModal').text(total);
                calcularSaldo();
            });

            observer.observe(totalAmountElement, {
                childList: true, // Cambios en los nodos hijos (texto)
                characterData: true, // Cambios en texto directo
                subtree: true // Observar todo el subtree
            });

            totalAmountElement.hasObserver = true;
            console.log('Observador del total inicializado');
        }
    }

    function toggleDelivery() {
        const checkDelivery = document.getElementById('checkDelivery');
        const camposDelivery = document.getElementById('camposDelivery');
        const typeStatus = document.getElementById('type_status');
        const status = document.getElementById('status');

        if (!checkDelivery || !camposDelivery) {
            console.error('Elementos de delivery no encontrados');
            return;
        }

        if (checkDelivery.checked) {
            // Mostrar campos de delivery
            camposDelivery.classList.remove('d-none');

            // Cambiar valores para delivery: type_status = 2, status = 0
            if (typeStatus) typeStatus.value = 2;
            if (status) status.value = 0;

            // Establecer fecha actual como valor por defecto
            const deliveryDate = document.getElementById('delivery_date');
            const deliveryHour = document.getElementById('delivery_hour');

            if (deliveryDate && !deliveryDate.value) {
                const hoy = new Date();
                const fechaFormateada = hoy.toISOString().split('T')[0];
                deliveryDate.value = fechaFormateada;
            }

            console.log('Delivery activado - type_status: 2, status: 0');
        } else {
            // Ocultar campos de delivery
            camposDelivery.classList.add('d-none');

            // Restaurar valores para venta directa: type_status = 0, status = 1
            if (typeStatus) typeStatus.value = 0;
            if (status) status.value = 1;

            // Limpiar campos de delivery
            const deliveryDateClear = document.getElementById('delivery_date');
            const deliveryHourClear = document.getElementById('delivery_hour');
            const deliveryAddressClear = document.getElementById('delivery_address');

            if (deliveryDateClear) deliveryDateClear.value = '';
            if (deliveryHourClear) deliveryHourClear.value = '';
            if (deliveryAddressClear) deliveryAddressClear.value = '';

            console.log('Delivery desactivado - type_status: 0, status: 1');
        }
    }

    function seleccionarMedioPago(medio_id, event) {
        const btn = event.target;
        const camposId = btn.dataset.campos;
        const camposElement = document.getElementById(camposId);
        const totalActual = parseFloat($('#totalAmount').text()) || 0;

        btn.classList.toggle('active');
        btn.classList.toggle('btn-success');

        if (btn.classList.contains('active')) {
            camposElement.classList.remove('d-none');
            camposElement.classList.add('d-flex', 'align-items-center');
        } else {
            camposElement.classList.add('d-none');
            camposElement.classList.remove('d-flex', 'align-items-center');
            const input = camposElement.querySelector('input[name^="monto["]');
            if (input) input.value = '';
        }

        const activos = document.querySelectorAll('[id^="btn-"].active');

        if (activos.length === 1) {
            // Solo un método activo, asignar total
            const id = activos[0].dataset.id;
            const campoUnico = document.querySelector(`#${activos[0].dataset.campos}`);
            const inputUnico = campoUnico?.querySelector(`input[name="monto[${id}]"]`);
            if (inputUnico) inputUnico.value = totalActual.toFixed(2);

        } else {
            // Más de uno activo, limpiar todos los inputs
            document.querySelectorAll('[id^="campos-"] input[name^="monto["]').forEach(input => {
                input.value = '0.00';
            });
        }

        calcularSaldo();
    }

    function cerrarMesaFrom(mesaId) {
        fetch(`{{ url('/mesas') }}/${mesaId}/cerrar`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(res => res.json())
            .then(data => {
                // if (data.success) {
                //     console.log('Mesa cerrada exitosamente desde backend:', mesaId);

                //     Swal.fire({
                //         icon: 'success',
                //         title: 'Mesa liberada',
                //         toast: true,
                //         position: 'top-end',
                //         showConfirmButton: false,
                //         timer: 2000
                //     });

                //     // Restaurar UI usando helper (esto debería quitar el borde)
                //     restoreMesaUI(mesaId);

                // } else {
                //     console.error('Error al cerrar mesa desde backend:', data.message);
                //     Swal.fire('Error', data.message || 'No se pudo cerrar la mesa.', 'error');
                // }
            })
            .catch(err => {
                console.error('Error al cerrar la mesa:', err);
                Swal.fire('Error', 'Error inesperado al cerrar la mesa.', 'error');
            });
    }
</script>

<style>
    .border-sutil {
        border: 1.5px solid #bfc9d1 !important;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.03);
    }

    input::-webkit-outer-spin-button,
    input::-webkit-inner-spin-button {
        /* display: none; <- Crashes Chrome on hover */
        -webkit-appearance: none;
        margin: 0;
        /* <-- Apparently some margin are still there even though it's hidden */
    }

    input[type=number] {
        -moz-appearance: textfield;
        /* Firefox */
    }
</style>