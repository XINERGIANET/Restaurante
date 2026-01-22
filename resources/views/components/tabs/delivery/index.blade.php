<!-- Input de búsqueda con 5 botones pegados al costado -->
<div class="mb-3">
    <div class="input-group input-group-sm">
        <input type="text" id="search-client" class="form-control" placeholder="Buscar..." aria-label="Buscar">
        <input type="hidden" id="client_id" name="client_id">
        <button type="button" class="btn btn-outline-primary" onclick="clearClientCard();"><i class="bi bi-x-lg"></i></button>
        <button type="button" class="btn btn-outline-secondary" id="delivery-open-form-btn"><i class="bi bi-plus-lg"></i></button>
        <button type="button" class="btn btn-outline-success"><i class="bi bi-exclamation-triangle-fill"></i></button>
        <button type="button" class="btn btn-outline-warning"><i class="bi bi-power"></i></button>
        <button type="button" class="btn btn-outline-danger" id="delivery-reload-btn"><i class="bi bi-arrow-repeat"></i></button>
    </div>
</div>

<!-- Área para mostrar/añadir "cosos" debajo -->

<div class="mb-2 delivery-container">
    <div id="delivery-list" class="mt-2" style="min-height:300px;">
        <!-- Placeholder visual grande para 'Agregar' (solo visual) -->
        <div id="delivery-placeholder" class="w-100" data-visual="true" style="display: flex; align-items: center; justify-content: center; min-height:300px;">
            <div class="text-center">
                <div class="big-add mx-auto mb-2" aria-hidden="true">
                    <!-- simple SVG de + dentro de un círculo -->
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="11" stroke="#6c757d" stroke-width="1" fill="rgba(0,0,0,0.03)" />
                        <path d="M12 7v10M7 12h10" stroke="#6c757d" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </div>
                <div class="text-muted small">Busca y selecciona un cliente registrado</div>
            </div>
        </div>

        <!-- Card del cliente (inicialmente oculto) -->
        <div id="client-card" class="card d-none w-100" data-item="true">
            <div class="card-body">
                <!-- Checkboxes de tipo de delivery -->
                <div class="row mb-3">
                    <div class="col-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="delivery-recoger" onchange="handleDeliveryTypeChange('recoger')">
                            <label class="form-check-label" for="delivery-recoger">
                                Delivery por recoger
                            </label>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="delivery-programado" onchange="handleDeliveryTypeChange('programado')">
                            <label class="form-check-label" for="delivery-programado">
                                Delivery programado
                            </label>
                        </div>
                    </div>
                </div>

                <!-- Recibe -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Recibe:</strong></label>
                    </div>
                    <div class="col-8">
                        <span id="client-name" class="form-control-plaintext form-control-sm p-0"></span>
                    </div>
                </div>

                <!-- Teléfono -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Teléfono:</strong></label>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control form-control-sm" id="client-phone">
                    </div>
                </div>

                <!-- Dirección de envío -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Dirección de Envío:</strong></label>
                    </div>
                    <div class="col-8">
                        <input type="text" class="form-control form-control-sm" id="client-address">
                    </div>
                </div>

                <!-- Motorizado -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Motorizado:</strong></label>
                    </div>
                    <div class="col-8">
                        <select class="form-select form-select-sm" id="motorizado">
                            <option>Ninguno</option>
                            <option>Moto 1</option>
                            <option>Moto 2</option>
                        </select>
                    </div>
                </div>

                <!-- Comprobante -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Comprobante:</strong></label>
                    </div>
                    <div class="col-8">
                        <select class="form-select form-select-sm" id="comprobante">
                            <option>Boleta</option>
                            <option>Factura</option>
                            <option>Ticket</option>
                        </select>
                    </div>
                </div>

                <!-- Costo envío -->
                <div class="row mb-3 align-items-center">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Costo envío S/:</strong></label>
                    </div>
                    <div class="col-8">
                        <input type="number" class="form-control form-control-sm" id="costo-envio" value="0.00" step="0.01">
                    </div>
                </div>

                <!-- Paga con -->
                <div class="row mb-3 align-items-start">
                    <div class="col-4">
                        <label class="form-label mb-0"><strong>Paga con S/:</strong></label>
                    </div>
                    <div class="col-8">
                        <input type="number" class="form-control form-control-sm" id="paga-con" value="" step="0.01" placeholder="Monto">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="paga-tarjeta" onchange="toggleMetodosPago()">
                            <label class="form-check-label" for="paga-tarjeta">
                                Paga con tarjeta
                            </label>
                        </div>
                        <!-- Select de métodos de pago (inicialmente oculto) -->
                        <div id="metodos-pago-container" class="mt-2 d-none">
                            <select class="form-select form-select-sm" id="metodo-pago">
                                <option value="">Seleccionar método de pago</option>
                                <option value="visa">Visa</option>
                                <option value="mastercard">Mastercard</option>
                                <option value="american_express">American Express</option>
                                <option value="yape">Yape</option>
                                <option value="plin">Plin</option>
                                <option value="transferencia">Transferencia bancaria</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Nota de delivery -->
                <div class="mb-3">
                    <label class="form-label"><i class="bi bi-journal-text"></i> <strong>Nota de delivery</strong></label>
                    <textarea class="form-control form-control-sm" id="nota-delivery" rows="2" placeholder="Agregar nota..."></textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- Spinner de recarga -->
    <div id="delivery-spinner" class="delivery-spinner d-none">
        <div class="spinner-border text-primary" role="status">
        </div>
    </div>
</div>


<!-- Modal XL para agregar/editar cliente (esqueleto de formulario) -->
<div class="modal fade" id="deliveryFormModal" tabindex="-1" aria-labelledby="deliveryFormModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deliveryFormModalLabel"><i class="bi bi-person-fill"></i> Registro rápido de cliente (Escribe los datos del nuevo cliente.)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="delivery-form">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="client-channel" class="form-label">Canal</label>
                            <select id="client-channel" name="channel" class="form-select">
                                <option value="delivery">Delivery</option>
                                <option value="pickup">Recoger</option>
                                <option value="external">Envío externo</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="client-nombres" class="form-label">Nombres</label>
                            <input type="text" class="form-control" id="client-nombres" name="nombres" placeholder="Nombres">
                        </div>
                        <div class="col-md-4">
                            <label for="client-apellidos" class="form-label">Apellidos</label>
                            <input type="text" class="form-control" id="client-apellidos" name="apellidos" placeholder="Apellidos">
                        </div>

                        <div class="col-md-4">
                            <label for="client-phone" class="form-label">Teléfono</label>
                            <input type="tel" class="form-control" id="client-phone" name="phone" placeholder="Teléfono">
                        </div>
                        <div class="col-md-8">
                            <label for="client-address" class="form-label">Dirección de entrega</label>
                            <input type="text" class="form-control" id="client-address" name="address" placeholder="Dirección de entrega">
                        </div>

                        <div class="col-md-4">
                            <label for="client-complemento" class="form-label">Complemento</label>
                            <input type="text" class="form-control" id="client-complemento" name="complemento" placeholder="Referencia/Complemento">
                        </div>
                        <div class="col-md-4">
                            <label for="client-referencia" class="form-label">Referencia</label>
                            <input type="text" class="form-control" id="client-referencia" name="referencia" placeholder="Referencia adicional">
                        </div>
                        <div class="col-md-4">
                            <label for="client-costo-envio" class="form-label">Costo envío</label>
                            <input type="number" step="0.01" class="form-control" id="client-costo-envio" name="costo_envio" placeholder="0.00">
                        </div>

                        <div class="col-md-6">
                            <label for="client-email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="client-email" name="email" placeholder="email@ejemplo.com">
                        </div>
                        <div class="col-md-6">
                            <label for="client-limite-credito" class="form-label">Límite de crédito</label>
                            <input type="number" step="0.01" class="form-control" id="client-limite-credito" name="limite_credito" placeholder="0.00">
                        </div>

                        <div class="col-12 d-flex align-items-center">
                            <div class="form-check me-3">
                                <input class="form-check-input" type="checkbox" id="client-quiere-factura" name="quiere_factura">
                                <label class="form-check-label" for="client-quiere-factura">¿El cliente requiere factura?</label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="delivery-form-save">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        console.log('DOM cargado, inicializando componente delivery'); // Debug

        const list = document.getElementById('delivery-list');
        const search = document.getElementById('search-client');
        const openFormBtn = document.getElementById('delivery-open-form-btn');
        const reloadBtn = document.getElementById('delivery-reload-btn');
        const deliveryFormModalEl = document.getElementById('deliveryFormModal');
        const deliveryForm = document.getElementById('delivery-form');
        const deliveryFormSave = document.getElementById('delivery-form-save');
        let clientSearchTimeout;

        let bootstrapModalInstance = null;

        // Inicializar Bootstrap Modal
        try {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrapModalInstance = new bootstrap.Modal(deliveryFormModalEl);
            }
        } catch (e) {}

        // Abrir modal
        openFormBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            if (bootstrapModalInstance) {
                bootstrapModalInstance.show();
            }
        });

        // Recargar componente
        reloadBtn?.addEventListener('click', (e) => {
            e.preventDefault();
            showSpinner();

            // Simular tiempo de recarga
            setTimeout(() => {
                clearClientCard();
                hideSpinner();
            }, 800); // 800ms para simular carga
        });


        // Mostrar/ocultar placeholder
        function updatePlaceholder() {
            const placeholder = document.getElementById('delivery-placeholder');
            const clientCard = document.getElementById('client-card');

            if (clientCard.classList.contains('d-none')) {
                placeholder.classList.remove('d-none');
            } else {
                placeholder.classList.add('d-none');
            }
        }

        // Función para limpiar el card del cliente y ocultar el side
        function clearClientCard() {
            console.log('Limpiando card del cliente y ocultando side'); // Debug

            const clientCard = document.getElementById('client-card');
            const placeholder = document.getElementById('delivery-placeholder');

            if (clientCard) {
                clientCard.classList.add('d-none');
            }

            if (placeholder) {
                placeholder.classList.remove('d-none');
            }

            // Ocultar el side de delivery
            const sideDelivery = document.getElementById('side-delivery');
            if (sideDelivery) {
                sideDelivery.classList.add('d-none');
                sideDelivery.classList.remove('active');

                // Limpiar el título del side
                const deliveryClientName = sideDelivery.querySelector('.delivery-side h5');
                if (deliveryClientName) {
                    deliveryClientName.innerHTML = '<i class="bi bi-bicycle"></i> Pedido Delivery';
                }

                // Limpiar el ID del cliente en el side
                const deliveryClientId = sideDelivery.querySelector('#delivery-client-id');
                if (deliveryClientId) {
                    deliveryClientId.value = '';
                }

                // Limpiar variables globales de delivery (si existen)
                if (typeof deliveryOrderItems !== 'undefined') {
                    deliveryOrderItems = [];
                    deliveryOrderCounter = 0;
                }

                // Llamar a las funciones de actualización si existen
                if (typeof updateDeliveryOrderTable === 'function') {
                    updateDeliveryOrderTable();
                }
                if (typeof updateDeliveryOrderSummary === 'function') {
                    updateDeliveryOrderSummary();
                }

                // Limpiar el contenedor de productos
                const deliveryProductContainer = sideDelivery.querySelector('#delivery-product-container');
                if (deliveryProductContainer) {
                    deliveryProductContainer.innerHTML = '<p class="text-muted text-center small">Selecciona una categoría para ver los productos</p>';
                }

                // Limpiar las tablas de pedidos y cuentas
                const pedidosTableBody = sideDelivery.querySelector('#selected-products-table-delivery tbody');
                if (pedidosTableBody) {
                    pedidosTableBody.innerHTML = '';
                }

                const cuentasTableBody = sideDelivery.querySelector('#cuenta-table-delivery tbody');
                if (cuentasTableBody) {
                    cuentasTableBody.innerHTML = '';
                }

                // Limpiar el total de la cuenta
                const totalAmount = sideDelivery.querySelector('#delivery-totalAmount');
                if (totalAmount) {
                    totalAmount.textContent = '0.00';
                }

                // Limpiar el input de búsqueda de productos
                const searchProductInput = sideDelivery.querySelector('#delivery-search-product-pedidos');
                if (searchProductInput) {
                    searchProductInput.value = '';
                }

                // Quitar la clase active de todos los botones de categoría
                const categoryButtons = sideDelivery.querySelectorAll('button[onclick*="handleDeliveryCategoryClick"]');
                categoryButtons.forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                });

                // Asegurarse de que el tab de pedidos esté activo
                const tabPedidosLink = document.getElementById('delivery-tab-pedidos');
                if (tabPedidosLink) {
                    // Activar el tab de pedidos
                    tabPedidosLink.click();
                }
            }

            // Limpiar campos de manera segura
            const clientName = document.getElementById('client-name');
            if (clientName) clientName.textContent = '---';

            const deliveryRecoger = document.getElementById('delivery-recoger');
            if (deliveryRecoger) deliveryRecoger.checked = false;

            const deliveryProgramado = document.getElementById('delivery-programado');
            if (deliveryProgramado) deliveryProgramado.checked = false;

            const canalDelivery = document.getElementById('canal-delivery');
            if (canalDelivery) canalDelivery.selectedIndex = 0;

            const comprobante = document.getElementById('comprobante');
            if (comprobante) comprobante.selectedIndex = 0;

            const costoEnvio = document.getElementById('costo-envio');
            if (costoEnvio) costoEnvio.value = '0.00';

            const pagaCon = document.getElementById('paga-con');
            if (pagaCon) pagaCon.value = '';

            const pagaTarjeta = document.getElementById('paga-tarjeta');
            if (pagaTarjeta) pagaTarjeta.checked = false;

            const metodosPagoContainer = document.getElementById('metodos-pago-container');
            if (metodosPagoContainer) metodosPagoContainer.classList.add('d-none');

            const metodoPago = document.getElementById('metodo-pago');
            if (metodoPago) metodoPago.selectedIndex = 0;

            const notaDelivery = document.getElementById('nota-delivery');
            if (notaDelivery) notaDelivery.value = '';

            // Limpiar campos de búsqueda con jQuery
            if (typeof $ !== 'undefined') {
                $('#search-client').val('');
                $('#client_id').val('');
            }
        }

        // Funciones para manejar el spinner
        function showSpinner() {
            console.log('Mostrando spinner'); // Debug
            const spinner = document.getElementById('delivery-spinner');
            if (spinner) {
                spinner.classList.remove('d-none');
            } else {
                console.error('Elemento delivery-spinner no encontrado');
            }
        }

        function hideSpinner() {
            console.log('Ocultando spinner'); // Debug
            const spinner = document.getElementById('delivery-spinner');
            if (spinner) {
                spinner.classList.add('d-none');
            } else {
                console.error('Elemento delivery-spinner no encontrado');
            }
        }

        // Hacer funciones globales para uso externo
        window.updatePlaceholder = updatePlaceholder;
        window.clearClientCard = clearClientCard;
        window.reloadDeliveryComponent = () => {
            if (reloadBtn) reloadBtn.click();
        };


        $('#search-client').autocomplete({
            source: function(request, response) {
                clearTimeout(clientSearchTimeout);
                clientSearchTimeout = setTimeout(function() {
                    let currentTerm = $('#search-client').val();
                    // Solo buscar si hay al menos una letra
                    if (currentTerm && currentTerm.length > 0) {
                        $.ajax({
                            url: '{{ route("clients.search") }}',
                            method: 'GET',
                            data: {
                                query: currentTerm
                            },
                            success: function(data) {
                                response($.map(data, function(item) {
                                    return {
                                        label: item.business_name ? item.business_name : item.contact_name,
                                        value: item.business_name ? item.business_name : item.contact_name,
                                        id: item.id,
                                    };
                                }));
                            }
                        });
                    } else {
                        // Si no hay letras, limpia el autocomplete
                        response([]);
                    }
                }, 750);
            },
            appendTo: '.container-fluid',
            select: function(event, ui) {
                $('#client_id').val(ui.item.id);

                // Llenar datos en el card preestablecido
                document.getElementById('client-name').textContent = ui.item.label;

                // Mostrar el card y ocultar el placeholder
                document.getElementById('client-card').classList.remove('d-none');
                document.getElementById('delivery-placeholder').classList.add('d-none');

                // NUEVA FUNCIONALIDAD: Mostrar el side de delivery y cargar información del cliente
                if (typeof showDeliverySide === 'function') {
                    showDeliverySide(ui.item);
                }

                // Limpiar el campo de búsqueda
                $('#search-client').val('');
                return false;
            },
        }).autocomplete("instance")._renderItem = function(ul, item) {
            return $("<li>")
                .append(`<div class="d-flex justify-content-between"><span>${item.label}</span></div>`)
                .appendTo(ul);
        };

        $('#search-client').on('input', function() {
            $('#client_id').val('');
        });

        // Función para manejar el cambio de tipo de delivery (mutuamente excluyentes)
        window.handleDeliveryTypeChange = function(type) {
            const recoger = document.getElementById('delivery-recoger');
            const programado = document.getElementById('delivery-programado');

            // Si se marca uno, desmarcar el otro
            if (type === 'recoger' && recoger.checked) {
                programado.checked = false;
            } else if (type === 'programado' && programado.checked) {
                recoger.checked = false;
            }
            // Si se desmarca, permitir que ambos estén desmarcados (no hacer nada)
        };

        // Función para mostrar/ocultar métodos de pago
        window.toggleMetodosPago = function() {
            const pagaTarjeta = document.getElementById('paga-tarjeta');
            const metodosPagoContainer = document.getElementById('metodos-pago-container');

            if (pagaTarjeta.checked) {
                metodosPagoContainer.classList.remove('d-none');
            } else {
                metodosPagoContainer.classList.add('d-none');
                // Resetear el select cuando se desmarca
                const metodoPago = document.getElementById('metodo-pago');
                if (metodoPago) {
                    metodoPago.selectedIndex = 0;
                }
            }
        };
    });
</script>