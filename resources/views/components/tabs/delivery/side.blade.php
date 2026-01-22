<div class="delivery-side">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h5 class="mb-0"><i class="bi bi-bicycle"></i> Pedido Delivery</h5>
    </div>
    <input type="hidden" id="delivery-client-id" name="delivery_client_id" value="">

    <!-- Categorías de productos -->
    <div class="card mb-3">
        <div class="card-header p-2">
            <h6 class="mb-0">Categorías</h6>
        </div>
        <div class="card-body p-2">
            <div class="d-flex flex-wrap gap-1" id="categories-container">
                @foreach ($categories as $category)
                <button class="btn btn-outline-primary btn-sm" type="button"
                    onclick="handleDeliveryCategoryClick('{{ $category->id }}')">
                    <small>{{ $category->name }}</small>
                </button>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Productos de la categoría seleccionada -->
    <div class="card mb-3">
        <div class="card-header p-2">
            <h6 class="mb-0">Productos</h6>
        </div>
        <div class="card-body p-2" style="max-height: 200px; overflow-y: auto;">
            <div id="delivery-product-container">
                <p class="text-muted text-center small">Selecciona una categoría para ver los productos</p>
            </div>
        </div>
    </div>

    <!-- Card con tabs de Pedidos y Cuenta -->
    <div class="card mb-3">
        <div class="card-body">
            <!-- Tabs de navegación -->
            <ul class="nav nav-tabs" id="delivery-table-tabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="delivery-tab-pedidos" data-tab="pedidos" onclick="switchCountDelivery('pedidos', this)" style="cursor: pointer;">Pedidos</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="delivery-tab-cuenta" data-tab="cuenta" onclick="switchCountDelivery('cuenta', this)" style="cursor: pointer;">Cuenta</a>
                </li>
            </ul>

            <div id="delivery-tab-content" class="mt-2">
                <div id="delivery-content-pedidos" class="table-pane active">
                    <input type="text" id="delivery-search-product-pedidos" class="form-control-sm" placeholder="Buscar producto">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm align-middle" id="selected-products-table-delivery">
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
                                <!-- Productos agregados aparecerán aquí -->
                            </tbody>
                        </table>
                    </div>
                <button class="btn btn-success btn-xl" id="delivery-btn-confirmar-pedido"><i class="bi bi-check-lg"></i> Pedir</button>
                </div>
                <div id="delivery-content-cuenta" class="table-pane d-none">
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-sm align-middle" id="cuenta-table-delivery">
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
                    <h5><strong>TOTAL: S/ <span id="delivery-totalAmount" name="total">0.00</span></strong></h5>
                    <button class="btn btn-success btn-xl" id="delivery-btn-cobrar" onclick="abrirModalCobroDelivery()"><i class="bi bi-check-lg"></i> Cobrar</button>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Modal para cortesía -->
<div class="modal fade" id="modalCortesiaDelivery" tabindex="-1" aria-labelledby="modalCortesiaDeliveryLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCortesiaDeliveryLabel">Cortesía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="cortesia-product-name" class="fw-bold mb-2"></p>
                <p class="text-muted small mb-2">Cantidad disponible: <span id="cortesia-max-qty">0</span></p>
                <div class="mb-3">
                    <label for="cortesia-quantity" class="form-label">Cantidad de cortesía:</label>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-decrement-cortesia">
                            <i class="bi bi-dash"></i>
                        </button>
                        <input type="number" class="form-control text-center" id="cortesia-quantity" min="1" value="1" style="max-width: 80px;">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btn-increment-cortesia">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>
                </div>
                <input type="hidden" id="cortesia-product-id">
                <input type="hidden" id="cortesia-row-index">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-warning btn-sm" id="btn-confirmar-cortesia">
                    <i class="bi bi-gift"></i> Confirmar Cortesía
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .category-btn.active {
        background-color: #0d6efd;
        color: white;
    }

    .product-item {
        cursor: pointer;
        transition: background-color 0.2s;
    }

    .product-item:hover {
        background-color: #f8f9fa;
    }

    .quantity-controls {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .quantity-controls button {
        width: 25px;
        height: 25px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
    }

    .quantity-controls input {
        width: 60px;
        height: 25px;
        text-align: center;
        font-size: 12px;
    }

    /* Estilos simples para botones de productos */
    #delivery-product-container button {
        border-radius: 4px;
    }
</style>

<script>
    function switchCountDelivery(tabName, element) {
        // Remover clase active de todos los nav-links
        document.querySelectorAll('#delivery-table-tabs .nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Agregar clase active al tab seleccionado
        element.classList.add('active');

        // Ocultar todos los contenidos de tabs
        document.querySelectorAll('#delivery-tab-content .table-pane').forEach(pane => {
            pane.classList.remove('active');
            pane.classList.add('d-none');
        });


        // Mostrar el contenido del tab seleccionado
        const targetContent = document.getElementById('delivery-content-' + tabName);
        if (targetContent) {
            targetContent.classList.add('active');
            targetContent.classList.remove('d-none');
        }
    }

    function handleDeliveryCategoryClick(categoryId) {
        const productContainer = document.getElementById('delivery-product-container');

        // Mostrar loader mientras carga
        productContainer.innerHTML = '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

        // Hacer petición AJAX para obtener productos de la categoría
        $.ajax({
            url: "{{ route('sales.getProductsByCategory', '') }}/" + categoryId,
            method: 'GET',
            success: function(products) {
                // Limpiar contenedor
                productContainer.innerHTML = '';

                if (products && products.length > 0) {
                    // Crear contenedor para los productos
                    const productsDiv = document.createElement('div');
                    productsDiv.className = 'd-flex flex-wrap gap-2';

                    products.forEach(producto => {
                        const productCol = document.createElement('div');

                        const productElement = document.createElement('button');
                        productElement.className = "btn btn-outline-success btn-sm";
                        productElement.type = "button";

                        // Mostrar nombre del producto con stock
                        const stock = producto.quantity || 0;
                        const precio = parseFloat(producto.unit_price || 0).toFixed(2);

                        productElement.innerHTML = `
                            <div class="text-start">
                                <div class="fw-bold">${producto.name.toUpperCase()}</div>
                            </div>
                        `;

                        productElement.onclick = function() {
                            handleDeliveryProductClick(producto.id, producto.name, producto.unit_price, stock);
                        };

                        productCol.appendChild(productElement);
                        productsDiv.appendChild(productCol);
                    });

                    productContainer.appendChild(productsDiv);
                } else {
                    // Mostrar mensaje si no hay productos
                    const noProductsMsg = document.createElement('p');
                    noProductsMsg.className = 'text-muted text-center small';
                    noProductsMsg.textContent = 'No hay productos disponibles en esta categoría.';
                    productContainer.appendChild(noProductsMsg);
                }

                // Resaltar categoría seleccionada
                document.querySelectorAll('button[onclick*="handleDeliveryCategoryClick"]').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });

                const selectedButton = document.querySelector(`button[onclick="handleDeliveryCategoryClick('${categoryId}')"]`);
                if (selectedButton) {
                    selectedButton.classList.remove('btn-outline-primary');
                    selectedButton.classList.add('btn-primary');
                }
            },
            error: function() {
                productContainer.innerHTML = '<div class="alert alert-danger">Error al cargar los productos. Por favor, intente nuevamente.</div>';
            }
        });
    }

    function handleDeliveryProductClick(productId, productName, unitPrice, stock) {
        // Obtener el tbody de la tabla de pedidos
        const tbody = document.querySelector('#selected-products-table-delivery tbody');

        // Verificar si el producto ya existe en la tabla
        const existingRow = tbody.querySelector(`tr[data-product-id="${productId}"]`);

        if (existingRow) {
            // Si existe, incrementar la cantidad
            const quantityInput = existingRow.querySelector('.quantity-input');
            let currentQuantity = parseInt(quantityInput.value);

            currentQuantity++;
            quantityInput.value = currentQuantity;

            // Actualizar el importe
            const importe = (currentQuantity * parseFloat(unitPrice)).toFixed(2);
            existingRow.querySelector('.importe-cell small').textContent = 'S/ ' + importe;

        } else {
            // Si no existe, crear una nueva fila
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-product-id', productId);
            newRow.setAttribute('data-unit-price', unitPrice);
            newRow.setAttribute('data-stock', stock);

            newRow.innerHTML = `
                <td>
                    <div class="quantity-controls">
                        <button class="btn btn-sm btn-outline-secondary btn-decrement-delivery">-</button>
                        <input type="number" class="form-control form-control-sm quantity-input" value="1" min="1" max="${stock}">
                        <button class="btn btn-sm btn-outline-secondary btn-increment-delivery">+</button>
                    </div>
                </td>
                <td><small>${productName}</small></td>
                <td class="precio-unitario"><small>S/ ${parseFloat(unitPrice).toFixed(2)}</small></td>
                <td class="importe-cell"><small>S/ ${parseFloat(unitPrice).toFixed(2)}</small></td>
                <td>
                    <button class="btn btn-sm btn-warning btn-cortesia-delivery">
                        <i class="bi bi-gift"></i>
                    </button>
                    <button class="btn btn-sm btn-danger btn-remove-delivery">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;

            tbody.appendChild(newRow);
        }
    }

    function incrementDeliveryQuantity(button, maxStock) {
        const input = button.parentElement.querySelector('.quantity-input');
        let currentValue = parseInt(input.value) || 0;
        
        if (currentValue < maxStock) {
            input.value = currentValue + 1;
            
            // Obtener el precio unitario desde la columna precio
            const row = input.closest('tr');
            const precioCell = row.querySelector('.precio-unitario small');
            
            if (precioCell) {
                const precioText = precioCell.textContent;
                const unitPrice = parseFloat(precioText.replace('S/', '').replace('S/ ', '').trim());
                
                // Actualizar el importe
                const importe = (parseInt(input.value) * unitPrice).toFixed(2);
                const importeCell = row.querySelector('.importe-cell small');
                if (importeCell) {
                    importeCell.textContent = 'S/ ' + importe;
                }
            }
        }
    }

    function decrementDeliveryQuantity(button, maxStock) {
        const input = button.parentElement.querySelector('.quantity-input');
        let currentValue = parseInt(input.value) || 0;
        
        if (currentValue > 1) {
            input.value = currentValue - 1;
            
            // Obtener el precio unitario desde la columna precio
            const row = input.closest('tr');
            const precioCell = row.querySelector('.precio-unitario small');
            
            if (precioCell) {
                const precioText = precioCell.textContent;
                const unitPrice = parseFloat(precioText.replace('S/', '').replace('S/ ', '').trim());
                
                // Actualizar el importe
                const importe = (parseInt(input.value) * unitPrice).toFixed(2);
                const importeCell = row.querySelector('.importe-cell small');
                if (importeCell) {
                    importeCell.textContent = 'S/ ' + importe;
                }
            }
        }
    }

    function updateDeliveryImporte(input, unitPrice, maxStock) {
        let quantity = parseInt(input.value);

        // Validar cantidad
        if (quantity < 1) quantity = 1;
        if (quantity > maxStock) quantity = maxStock;
        input.value = quantity;

        // Actualizar importe
        const row = input.closest('tr');
        const importe = (quantity * parseFloat(unitPrice)).toFixed(2);
        row.querySelector('.importe-cell small').textContent = 'S/ ' + importe;
    }

    function removeDeliveryProduct(button) {
        const row = button.closest('tr');
        row.remove();
    }

    // Delegación de eventos para botones dinámicos
    document.addEventListener('click', function(e) {
        // Botón incrementar
        if (e.target.classList.contains('btn-increment-delivery') || e.target.closest('.btn-increment-delivery')) {
            const button = e.target.classList.contains('btn-increment-delivery') ? e.target : e.target.closest('.btn-increment-delivery');
            const row = button.closest('tr');
            const maxStock = parseInt(row.getAttribute('data-stock')) || 9999;
            incrementDeliveryQuantity(button, maxStock);
        }
        
        // Botón decrementar
        if (e.target.classList.contains('btn-decrement-delivery') || e.target.closest('.btn-decrement-delivery')) {
            const button = e.target.classList.contains('btn-decrement-delivery') ? e.target : e.target.closest('.btn-decrement-delivery');
            const row = button.closest('tr');
            const maxStock = parseInt(row.getAttribute('data-stock')) || 9999;
            decrementDeliveryQuantity(button, maxStock);
        }
        
        // Botón cortesía
        if (e.target.classList.contains('btn-cortesia-delivery') || e.target.closest('.btn-cortesia-delivery')) {
            e.preventDefault();
            const button = e.target.classList.contains('btn-cortesia-delivery') ? e.target : e.target.closest('.btn-cortesia-delivery');
            const row = button.closest('tr');
            
            // Obtener datos del producto
            const productId = row.getAttribute('data-product-id');
            const productName = row.querySelector('td:nth-child(2) small').textContent;
            const currentQty = parseInt(row.querySelector('.quantity-input').value) || 0;
            
            
            // Mostrar confirmación con ToastConfirm
            ToastConfirm.fire({
                title: '¿Ofrecer como cortesía?',
                html: `<small>¿Deseas ofrecer "<b>${productName}</b>" como cortesía?</small>`,
            }).then((result) => {
                if (result.isConfirmed) {
                    // Abrir modal para ingresar cantidad
                    document.getElementById('cortesia-product-name').textContent = productName;
                    document.getElementById('cortesia-max-qty').textContent = currentQty;
                    document.getElementById('cortesia-quantity').value = 1;
                    document.getElementById('cortesia-quantity').max = currentQty;
                    document.getElementById('cortesia-product-id').value = productId;
                    
                    // Guardar referencia a la fila
                    document.getElementById('cortesia-row-index').value = productId;
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalCortesiaDelivery'));
                    modal.show();
                }
            });
        }
        
        // Botón eliminar
        if (e.target.classList.contains('btn-remove-delivery') || e.target.closest('.btn-remove-delivery')) {
            const button = e.target.classList.contains('btn-remove-delivery') ? e.target : e.target.closest('.btn-remove-delivery');
            removeDeliveryProduct(button);
        }
    });

    // Delegación de eventos para input change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('quantity-input') && e.target.closest('#selected-products-table-delivery')) {
            const row = e.target.closest('tr');
            const unitPrice = parseFloat(row.getAttribute('data-unit-price')) || 0;
            const maxStock = parseInt(row.getAttribute('data-stock')) || 9999;
            updateDeliveryImporte(e.target, unitPrice, maxStock);
        }
    });


    document.addEventListener('DOMContentLoaded', function() {
        // Botones +/- del modal de cortesía
        document.getElementById('btn-increment-cortesia').addEventListener('click', function() {
            const input = document.getElementById('cortesia-quantity');
            const maxQty = parseInt(document.getElementById('cortesia-max-qty').textContent);
            let currentValue = parseInt(input.value) || 1;
            
            if (currentValue < maxQty) {
                input.value = currentValue + 1;
            }
        });

        document.getElementById('btn-decrement-cortesia').addEventListener('click', function() {
            const input = document.getElementById('cortesia-quantity');
            let currentValue = parseInt(input.value) || 1;
            
            if (currentValue > 1) {
                input.value = currentValue - 1;
            }
        });

        // Evento para confirmar cortesía
        document.getElementById('btn-confirmar-cortesia').addEventListener('click', function() {
            const productId = document.getElementById('cortesia-product-id').value;
            const cortesiaQty = parseInt(document.getElementById('cortesia-quantity').value);
            const maxQty = parseInt(document.getElementById('cortesia-max-qty').textContent);
            
            // Validar cantidad
            if (cortesiaQty < 1 || cortesiaQty > maxQty) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cantidad inválida',
                    text: `La cantidad debe estar entre 1 y ${maxQty}`,
                    confirmButtonColor: '#d33'
                });
                return;
            }
            
            // Buscar la fila del producto
            const tbody = document.querySelector('#selected-products-table-delivery tbody');
            const row = tbody.querySelector(`tr[data-product-id="${productId}"]`);
            
            if (!row) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'No se encontró el producto',
                    confirmButtonColor: '#d33'
                });
                return;
            }
            
            // Obtener datos del producto
            const productName = row.querySelector('td:nth-child(2) small').textContent;
            const currentQty = parseInt(row.querySelector('.quantity-input').value);
            const unitPrice = parseFloat(row.getAttribute('data-unit-price'));
            const stock = parseInt(row.getAttribute('data-stock'));
            
            // Calcular nueva cantidad en la fila original
            const newQty = currentQty - cortesiaQty;
            
            if (newQty > 0) {
                // Actualizar cantidad en la fila existente
                row.querySelector('.quantity-input').value = newQty;
                const newImporte = (newQty * unitPrice).toFixed(2);
                row.querySelector('.importe-cell small').textContent = 'S/ ' + newImporte;
            } else {
                // Eliminar la fila si la cantidad es 0
                row.remove();
            }
            
            // Crear nueva fila con precio 0 (cortesía)
            const newRow = document.createElement('tr');
            newRow.setAttribute('data-product-id', productId + '-cortesia-' + Date.now());
            newRow.setAttribute('data-unit-price', 0);
            newRow.setAttribute('data-stock', stock);
            newRow.classList.add('table-warning'); // Resaltar como cortesía
            
            newRow.innerHTML = `
                <td>
                    <div class="quantity-controls">
                        <button class="btn btn-sm btn-outline-secondary btn-decrement-delivery">-</button>
                        <input type="number" class="form-control form-control-sm quantity-input" value="${cortesiaQty}" min="1" max="${stock}">
                        <button class="btn btn-sm btn-outline-secondary btn-increment-delivery">+</button>
                    </div>
                </td>
                <td><small>${productName} <span class="badge bg-warning text-dark">CORTESÍA</span></small></td>
                <td class="precio-unitario"><small>S/ 0.00</small></td>
                <td class="importe-cell"><small>S/ 0.00</small></td>
                <td>
                    <button class="btn btn-sm btn-danger btn-remove-delivery">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            
            // Cerrar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalCortesiaDelivery'));
            modal.hide();
            
            // Mostrar mensaje de éxito
            Swal.fire({
                icon: 'success',
                title: 'Cortesía agregada',
                text: `Se agregaron ${cortesiaQty} unidades de "${productName}" como cortesía`,
                timer: 2000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        });

        $('#delivery-search-product-pedidos').autocomplete({
            source: function(request, response) {
                let currentTerm = $('#delivery-search-product-pedidos').val();
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
            appendTo: '.delivery-side',
            select: function(event, ui) {
                // Agregar producto a la tabla de delivery usando la función existente
                handleDeliveryProductClick(ui.item.id, ui.item.name, ui.item.unit_price, ui.item.quantity);

                // Limpiar el campo de búsqueda
                $('#delivery-search-product-pedidos').val('');
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

    document.getElementById('delivery-btn-confirmar-pedido').addEventListener('click', function() {
        const mesaId = 'DELIVERY'; // Identificador fijo para delivery

        // Recolectar productos de la tabla de delivery
        const detalles = [];
        $('#selected-products-table-delivery tbody tr').each(function() {
            const $row = $(this);
            const productId = $row.data('product-id');
            const esCortesia = $row.hasClass('table-warning'); // Las cortesías tienen esta clase
            
            // Crear el detalle
            const detalle = {
                product_id: productId,
                quantity: $row.find('.quantity-input').val(),
                product_price: $row.find('td:nth-child(3)').text().replace('S/ ', '').trim(),
                is_cortesia: esCortesia ? 1 : 0 // Marcar si es cortesía
            };
            
            detalles.push(detalle);
        });

        if (detalles.length === 0) {
            alert('Agregue al menos un producto al pedido.');
            return;
        }

        $.ajax({
            url: "{{ route('sales.addOrders', ['mesaId' => 'DELIVERY']) }}",
            method: 'POST',
            data: {
                detalles: detalles,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                if (data.success) {
                    alert('Pedido registrado correctamente');
                    limpiarDeliveryPedido();
                    verDeliveryPedido();
                    switchCountDelivery('cuenta', document.getElementById('delivery-tab-cuenta'));
                } else {
                    alert('Error al registrar pedido: ' + (data.errors?.map(e => e.error).join(', ') || ''));
                }
            },
            error: function() {
                alert('Error de red o servidor');
            }
        });
    });

    limpiarDeliveryPedido = function() {
        const $tbody = $('#selected-products-table-delivery tbody');
        $tbody.html('');
    };

    verDeliveryPedido = function() {
        const mesaId = 'DELIVERY';
        $.ajax({
            url: "{{ route('sales.getOrdersByTable', ['mesaId' => 'DELIVERY']) }}",
            method: 'GET',
            success: function(data) {
                const $tbody = $('#cuenta-table-delivery tbody');
                $tbody.html('');
                let total = 0;

                if (data.orders && data.orders.length > 0) {
                    data.orders.forEach(function(order) {
                        const importe = parseFloat(order.quantity) * parseFloat(order.product_price);
                        total += importe;

                        const row = `
                            <tr>
                                <td><small>${order.quantity}</small></td>
                                <td><small>${order.product_name}</small></td>
                                <td><small>S/ ${parseFloat(order.product_price).toFixed(2)}</small></td>
                                <td><small>S/ ${importe.toFixed(2)}</small></td>
                                <td></td>
                            </tr>
                        `;
                        $tbody.append(row);
                    });
                }

                $('#delivery-totalAmount').text(total.toFixed(2));
            },
            error: function() {
                alert('Error al cargar el pedido');
            }
        });
    };
</script>