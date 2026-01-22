@extends('layouts.app')

@section('header')
    <h2>Órdenes</h2>
    <p>Gestiona las órdenes de la mesa seleccionada</p>
@endsection

@section('content')
    <div class="container-fluid content-inner mt-n5 py-0">
        <div class="row g-0 h-100">
            <!-- Left Panel: Cart & Customer Info -->
            <div class="col-lg-5 bg-white border-end d-flex flex-column shadow-sm">
                <!-- Header with back button and table info -->
                <div class="bg-primary bg-gradient text-white">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item bg-transparent border-0 text-white p-3"
                            onclick="window.location.href='{{ route('sales.mozo') }}?area='+encodeURIComponent('{{ $area_id }}')"
                            style="cursor:pointer; transition: all 0.3s;">
                            <i class="bi bi-arrow-left-circle me-2"></i>
                            <strong>Volver a Mesas</strong>
                        </div>
                        <div
                            class="list-group-item bg-transparent border-0 text-white p-3 d-flex justify-content-between align-items-center">
                            <div class="bg-light text-dark p-2 rounded-3 d-flex align-items-center gap-2">
                                <i class="bi bi-receipt"></i>
                                <strong>{{ $mesa->name }} : @if (strtoupper($mesa->name) === 'DELIVERY' || request()->get('area') === 'delivery')
                                    <i class="bi bi-truck me-1"></i> </strong>
                                    @else
                                        {{ $order->number_persons == 0 ? 1 : $order->number_persons }} <i class="bi bi-person-circle me-1"></i> </strong>
                                        <button class="btn btn-sm btn-outline-secondary" onclick="editCantidadPersonas(event)">
                                            <i class="bi bi-pencil-square me-1"></i>
                                            Editar
                                        </button>
                                    @endif
                            </div>
                            <h3 class="badge bg-light text-primary">
                                <i class="bi bi-person-circle me-1"></i>{{ $order->employee->name ?? '' }}
                            </h3>
                        </div>
                    </div>
                </div>

                <!-- Search Bar -->
                <div class="p-3 border-bottom bg-light">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" id="search-product-pedidos" class="form-control border-start-0"
                            placeholder="Búsqueda rápida">
                    </div>
                </div>

                <!-- Cart items - flexible area -->
                <div class="flex-grow-1 overflow-hidden bg-light d-flex flex-column">
                    <div id="account-tabs" class="px-3 pt-3 pb-0">
                        <!-- Tabs de cuentas -->
                    </div>

                    <div class="overflow-auto px-3 py-2 cart-scroll-container" style="max-height: 240px;">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 bg-white rounded-2 overflow-hidden table-sm"
                                id="table-products">
                                <thead class="table p-3 sticky-top bg-white" style="position: sticky; top: 0; z-index: 10; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <tr>
                                        <th class="text-center" style="width: 60px;">Cant.</th>
                                        <th>Producto</th>
                                        <th class="text-center" style="width: 100px;">P.U.</th>
                                        <th class="text-center" style="width: 100px;">Dcto.</th>
                                        <th class="text-center" style="width: 100px;">Subt.</th>
                                        <th class="text-center" style="width: 80px;">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="empty-cart-message">
                                        <td colspan="6" class="text-center text-muted py-5">
                                            <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                            <small>El carrito está vacío</small>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Total section - fixed at bottom -->
                <div class="mt-auto p-3 border-top bg-white shadow-lg">
                    <div class="bg-light rounded-3 p-3 mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="text-muted">
                                <i class="bi bi-bag-check me-2"></i>
                                (<span id="cart-count">0</span> productos)
                            </span>
                            <span class="text-muted">Subtotal</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fs-4 fw-bold text-success" id="total-amount">S/ 0.00</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        @if (auth()->user()->hasRole('Caja') || auth()->user()->hasRole('Admin'))
                            <button class="btn btn-primary btn-lg shadow-sm" id="cobrar-order">
                                <i class="bi bi-cash-coin me-2"></i>
                                COBRAR ORDEN
                            </button>
                        @endif
                        <button class="btn btn-success btn-lg shadow-sm" id="confirm-order" onclick="confirmOrder()">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>CONFIRMAR PEDIDO</strong>
                        </button>
                        <button class="btn btn-outline-secondary" id="separate-accounts-btn" type="button">
                            <i class="bi bi-columns-gap me-2"></i>SEPARAR CUENTAS
                        </button>
                        <button class="btn btn-outline-warning" id="preaccount" onclick="preaccount()">
                            <i class="bi bi-printer-fill"></i>
                            PRECUENTA
                        </button>
                        @if (strtoupper($mesa->name) === 'DELIVERY' || request()->get('area') === 'delivery')
                            <button class="btn btn-warning btn-lg shadow-sm" id="confirm-delivery-order">
                                <i class="bi bi-truck me-2"></i>
                                <strong>DATOS DEL CLIENTE (Delivery)</strong>
                            </button>
                        @endif
                        
                        @if (auth()->user()->rol_id !== 11)
                        <button class="btn btn-outline-danger" onclick="cerrarMesa({{ $mesa->id }})">
                            <i class="bi bi-x-circle me-2"></i>
                            CANCELAR ORDEN
                        </button>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Right Panel: Products with categories -->
            <div class="col-lg-7 d-flex flex-column bg-light">
                <!-- Tab content -->
                <div class="flex-grow-1 p-4" style="overflow-y: auto;">
                    <!-- Categorías -->
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 p-3">
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="bi bi-grid-3x3-gap me-2"></i>Categorías
                            </h5>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap gap-2" id="categories-container">
                                @foreach ($categories as $category)
                                    <button class="btn btn-outline-primary category-btn" type="button"
                                        onclick="handleCategoryClick('{{ $category->id }}')">
                                        <i class="bi bi-tag me-2"></i>{{ $category->name }}
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Productos de la categoría seleccionada -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 p-3">
                            <h5 class="mb-0 fw-bold text-primary">
                                <i class="bi bi-box-seam me-2"></i>Productos Disponibles
                            </h5>
                        </div>
                        <div class="card-body p-3" style="max-height: 500px; overflow-y: auto;">
                            <div id="product-container">
                                <div class="text-center text-muted py-5">
                                    
                                    <i class="bi bi-tag-fill fs-1 d-block mb-3 text-secondary" style="opacity: 0.5;"></i>
                                    <p class="mb-0 text-secondary" style="opacity: 0.5;">Selecciona una categoría para ver los productos</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para editar cantidad de personas -->
    <div class="modal fade" id="editCantidadPersonasModal" tabindex="-1" aria-labelledby="editCantidadPersonasModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary bg-gradient text-dark border-0">
                    <h5 class="modal-title text-white" id="editCantidadPersonasModalLabel">
                        <i class="bi bi-people me-2"></i>Editar Cantidad de Personas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="editCantidadPersonasForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="cantidad-personas" class="form-label fw-bold"> Cantidad de Personas</label>
                                <input type="number" class="form-control" id="cantidad-personas"
                                    value="{{ $order->number_persons ?? 0 }}" min="1">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="save-cantidad-personas">
                        <i class="bi bi-check-circle me-2"></i>Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal para agregar producto -->
    <div class="modal fade" id="addProductModal" tabindex="-1" aria-labelledby="addProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary bg-gradient text-white border-0">
                    <h5 class="modal-title" id="addProductModalLabel">
                        <i class="bi bi-plus-circle me-2"></i>Agregar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Producto arriba -->
                    <div class="text-center mb-4 p-3 bg-light rounded-3">
                        <i class="bi bi-box-seam fs-1 text-primary mb-2 d-block"></i>
                        <h5 class="fw-bold mb-0" id="product-name">Nombre del Producto</h5>
                    </div>

                    <!-- Cantidad -->
                    <div class="mb-4">
                        <label for="product-quantity" class="form-label fw-bold">
                            <i class="bi bi-123 me-2 text-primary"></i>Cantidad
                        </label>
                        <div class="input-group input-group-lg">
                            <button class="btn btn-outline-secondary" type="button" id="decrease-qty">
                                <i class="bi bi-dash-lg"></i>
                            </button>
                            <input type="number" class="form-control text-center fw-bold fs-4" id="product-quantity"
                                value="1" min="1">
                            <button class="btn btn-outline-secondary" type="button" id="increase-qty">
                                <i class="bi bi-plus-lg"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Precio unitario -->
                    <div class="mb-4">
                        <label class="form-label fw-bold">
                            <i class="bi bi-currency-dollar me-2 text-primary"></i>Precio unitario
                        </label>
                        <div class="p-3 bg-light rounded-3">
                            <div class="fs-3 fw-bold text-success text-center" id="product-price">S/ 0.00</div>
                        </div>
                    </div>

                    <!-- Observación -->
                    <div class="mb-3">
                        <label for="product-notes" class="form-label fw-bold">
                            <i class="bi bi-chat-left-text me-2 text-primary"></i>Notas
                            <small class="text-muted">(Opcional)</small>
                        </label>
                        <textarea class="form-control" id="product-notes" rows="2" placeholder="Ej: Sin cebolla, sin ají..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="discount" class="form-label fw-bold">
                            <i class="bi bi-currency-dollar me-2 text-primary"></i>Descuento
                        </label>
                        <input class="form-control" id="discount_amount">
                    </div>
                    <div class="mb-3">
                        <label for="discount" class="form-label fw-bold">
                            <i class="bi bi-question-lg me-2 text-primary"></i>Motivo
                        </label>
                        <textarea class="form-control" id="discount_reason" rows="1" placeholder="Motivo de descuento"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-success" id="add-to-cart">
                        <i class="bi bi-cart-plus me-2"></i>Agregar al Carrito
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para datos de delivery -->
    <div class="modal fade" id="deliveryModal" tabindex="-1" aria-labelledby="deliveryModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-warning bg-gradient text-dark border-0">
                    <h5 class="modal-title" id="deliveryModalLabel">
                        <i class="bi bi-truck me-2"></i>Datos de Delivery
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <form id="deliveryForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="delivery-doc-number" class="form-label fw-bold">
                                    <i class="bi bi-card-text me-2 text-warning"></i>DNI/RUC
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="delivery-doc-number"
                                        name="delivery-doc-number" maxlength="11" onkeypress="return isNumber(event)"
                                        placeholder="Ingrese documento" required>
                                    <button type="button" class="btn btn-primary" id="search-delivery-client-btn"
                                        onclick="searchAPI('#delivery-doc-number','#delivery-client-name','#delivery-address')">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="delivery-client-name" class="form-label fw-bold">
                                    <i class="bi bi-person me-2 text-warning"></i>Cliente
                                </label>
                                <input type="text" class="form-control" id="delivery-client-name"
                                    placeholder="Nombre del cliente" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="delivery-phone" class="form-label fw-bold">
                                    <i class="bi bi-telephone me-2 text-warning"></i>Teléfono
                                </label>
                                <input type="text" class="form-control" id="delivery-phone"
                                    placeholder="Ingrese teléfono" required maxlength="15">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="delivery-date" class="form-label fw-bold">
                                    <i class="bi bi-calendar-event me-2 text-warning"></i>Fecha de Entrega
                                </label>
                                <input type="date" class="form-control" id="delivery-date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="delivery-hour" class="form-label fw-bold">
                                    <i class="bi bi-clock me-2 text-warning"></i>Hora de Entrega
                                </label>
                                <input type="time" class="form-control" id="delivery-hour" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="delivery-address" class="form-label fw-bold">
                                    <i class="bi bi-geo-alt me-2 text-warning"></i>Dirección
                                </label>
                                <input type="text" class="form-control" id="delivery-address"
                                    placeholder="Ingrese dirección completa" required>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="delivery-reference" class="form-label fw-bold">
                                    <i class="bi bi-signpost me-2 text-warning"></i>Referencia
                                </label>
                                <input type="text" class="form-control" id="delivery-reference"
                                    placeholder="Ej: Casa azul, frente al parque">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label for="delivery-observation" class="form-label fw-bold">
                                    <i class="bi bi-chat-left-text me-2 text-warning"></i>Observaciones
                                </label>
                                <textarea class="form-control" id="delivery-observation" rows="3" placeholder="Observaciones adicionales"></textarea>
                            </div>
                            <div class="col-md-12 mb-3" style="display: none;">
                                <label for="delivery-photo" class="form-label fw-bold">
                                    <i class="bi bi-camera me-2 text-warning"></i>Foto de Referencia
                                    <small class="text-muted">(Opcional)</small>
                                </label>
                                <input type="file" class="form-control" id="delivery-photo" accept="image/*"
                                    capture="environment">
                                <div id="photo-preview" class="mt-2"></div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-warning btn-lg px-4" id="save-delivery-data">
                        <i class="bi bi-check-circle me-2"></i>Guardar Datos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar cantidad de producto -->
    <div class="modal fade" id="removeProductModal" tabindex="-1" aria-labelledby="removeProductModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-danger bg-gradient text-white border-0">
                    <h5 class="modal-title" id="removeProductModalLabel">
                        <i class="bi bi-trash me-2"></i>Eliminar Producto
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="text-center mb-3">
                        <i class="bi bi-exclamation-triangle fs-1 text-warning mb-2 d-block"></i>
                        <h6 id="remove-product-name" class="fw-bold"></h6>
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Cantidad disponible: <strong id="remove-max-quantity">0</strong>
                    </div>

                    <div class="mb-3">
                        <label for="remove-quantity" class="form-label fw-bold">
                            ¿Cuántos productos deseas eliminar?
                        </label>
                        <div class="input-group input-group-lg">
                            <button class="btn btn-outline-secondary" type="button" id="decrease-remove-qty">
                                <i class="bi bi-dash"></i>
                            </button>
                            <input type="number" class="form-control text-center fw-bold" id="remove-quantity"
                                value="1" min="1">
                            <button class="btn btn-outline-secondary" type="button" id="increase-remove-qty">
                                <i class="bi bi-plus"></i>
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="bi bi-lightbulb me-1"></i>
                            Si eliminas toda la cantidad, el producto se quitará completamente del pedido.
                        </small>
                    </div>
                </div>
                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </button>
                    <button type="button" class="btn btn-danger" id="confirm-remove-product">
                        <i class="bi bi-trash me-2"></i>Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="separateAccountsModal" tabindex="-1" aria-labelledby="separateAccountsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-secondary bg-gradient text-light border-0">
                    <h5 class="modal-title text-white" id="separateAccountsModalLabel">
                        <i class="bi bi-columns-gap me-2"></i>Separar cuentas
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>

                <div class="modal-body p-3">
                    <!-- Nav tabs (se generan dinámicamente) -->
                    <ul class="nav nav-tabs mb-3" id="accountTabs" role="tablist"></ul>

                    <!-- Tab panes -->
                    <div class="tab-content" id="accountTabsContent"></div>

                    <!-- Selector de cuenta destino (se completa desde JS) -->
                    <div id="targetAccountSelector" class="mb-3"></div>

                    <div class="form-text text-muted mt-3">Selecciona la pestaña de la cuenta para ver sus
                        productos.</div>
                </div>

                <div class="modal-footer border-0 p-3">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="move-to-new-account-btn">
                        <i class="bi bi-arrow-right-circle me-2"></i>Agregar productos a nueva cuenta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .category-btn {
            transition: all 0.3s ease;
            border-width: 2px;
        }

        .category-btn.active,
        .category-btn.btn-primary {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }

        .category-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .product-item {
            cursor: pointer;
            transition: all 0.3s ease;
            border-width: 2px;
        }

        .product-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
            border-color: #28a745;
        }

        .product-card-btn {
            border: 2px solid #28a745;
            border-radius: 8px;
            transition: all 0.3s ease;
            min-width: 150px;
            background: white;
        }

        .product-card-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
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

        #product-container button {
            border-radius: 8px;
        }

        .confirmed-badge {
            font-size: 0.75rem;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.7;
            }
        }

        .confirmed-icon {
            font-size: 0.85rem;
            animation: checkmark 0.5s ease-in-out;
        }

        @keyframes checkmark {
            0% {
                transform: scale(0);
            }

            50% {
                transform: scale(1.2);
            }

            100% {
                transform: scale(1);
            }
        }

        /* Scrollbar personalizado */
        .overflow-auto::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        .overflow-auto::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .overflow-auto::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .overflow-auto::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Animación para agregar productos */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        #table-products tbody tr {
            animation: slideIn 0.3s ease-out;
        }

        /* Hover effect para volver */
        .list-group-item:hover {
            background-color: rgba(255, 255, 255, 0.1) !important;
        }

        /* Empty cart styling */
        .empty-cart-message i {
            color: #dee2e6;
        }

        /* Product card improvements */
        .product-item .fw-bold {
            font-size: 0.9rem;
        }

        /* Badge improvements */
        #cart-count-badge {
            font-size: 0.85rem;
            padding: 0.35em 0.65em;
        }

        /* Estilo mejorado para el scroll de la tabla */
        .cart-scroll-container::-webkit-scrollbar {
            width: 8px;
        }

        .cart-scroll-container::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .cart-scroll-container::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 10px;
        }

        .cart-scroll-container::-webkit-scrollbar-thumb:hover {
            background: #555;
        }

        /* Header fijo de la tabla */
        #table-products thead th {
            background-color: #f8f9fa !important;
            border-bottom: 2px solid #dee2e6;
        }
    </style>
@endsection

@section('scripts')
    <script>
        let deliveryData = null;

        document.addEventListener('DOMContentLoaded', function() {
            $('.sidebar-default').addClass('sidebar-mini');
            const confirmBtn = document.getElementById('confirm-delivery-order');
            if (!confirmBtn) return;

            confirmBtn.addEventListener('click', function() {
                const tbody = document.querySelector('#table-products tbody');
                const rows = tbody.querySelectorAll('tr[data-product-id]');

                if (rows.length === 0) {
                    showToast('No hay productos en el carrito para confirmar', 'warning');
                    return;
                }

                const deliveryModal = new bootstrap.Modal(document.getElementById('deliveryModal'));
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('delivery-date').min = today;
                document.getElementById('delivery-date').value = today;

                deliveryModal.show();
            });
        });


        document.getElementById('save-delivery-data').addEventListener('click', function() {

            const docNumber = document.getElementById('delivery-doc-number').value.trim();
            const clientName = document.getElementById('delivery-client-name').value.trim();
            const phone = document.getElementById('delivery-phone').value.trim();
            const date = document.getElementById('delivery-date').value;
            const hour = document.getElementById('delivery-hour').value;
            const address = document.getElementById('delivery-address').value.trim();

            if (!docNumber || !clientName || !phone || !date || !hour || !address) {
                showToast('Por favor complete todos los campos obligatorios', 'error');
                return;
            }

            if (docNumber.length !== 8 && docNumber.length !== 11) {
                showToast('El documento debe tener 8 (DNI) u 11 (RUC) dígitos', 'error');
                return;
            }
            const docType = docNumber.length === 8 ? 'DNI' : 'RUC';

            // Guardar datos
            deliveryData = {
                document_type: docType,
                document_number: docNumber,
                client_name: clientName,
                phone: phone,
                delivery_date: date,
                delivery_hour: hour,
                address: address,
                reference: document.getElementById('delivery-reference').value.trim(),
                observation: document.getElementById('delivery-observation').value.trim(),
                photo: document.getElementById('delivery-photo').files[0] || null
            };

            // Cerrar modal
            const deliveryModal = bootstrap.Modal.getInstance(document.getElementById('deliveryModal'));
            deliveryModal.hide();

            // Confirmar el pedido con datos de delivery
            confirmOrderWithDelivery();
            loadExistingProducts();
        });

        // Función para confirmar pedido con delivery
        function confirmOrderWithDelivery() {
            if (!orderId) {
                showToast('Error: No se pudo identificar la orden', 'error');
                return;
            }

            if (!deliveryData) {
                showToast('Error: No se capturaron los datos de delivery', 'error');
                return;
            }

            // Crear FormData para enviar la foto si existe
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('document_type', deliveryData.document_type);
            formData.append('document_number', deliveryData.document_number);
            formData.append('client_name', deliveryData.client_name);
            formData.append('phone', deliveryData.phone);
            formData.append('delivery_date', deliveryData.delivery_date);
            formData.append('delivery_hour', deliveryData.delivery_hour);
            formData.append('address', deliveryData.address);
            formData.append('reference', deliveryData.reference);
            formData.append('observation', deliveryData.observation);
            formData.append('is_delivery', 'true');
            formData.append('_token', '{{ csrf_token() }}');

            if (deliveryData.photo) {
                formData.append('foto', deliveryData.photo);
            }

            $.ajax({
                url: '{{ route('sales.delivery') }}',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(data) {
                    if (data.success) {
                        showToast('Pedido de delivery confirmado exitosamente', 'success');
                        showFullConfirmedState();

                        // Agregar badge de delivery
                        const headerTableName = document.querySelector('.list-group-item:nth-child(2)');
                        if (headerTableName && !headerTableName.querySelector('.delivery-badge')) {
                            const deliveryBadge = document.createElement('span');
                            deliveryBadge.className = 'delivery-badge badge bg-warning text-dark ms-2';
                            deliveryBadge.innerHTML = '<i class="bi bi-truck"></i> DELIVERY';
                            headerTableName.appendChild(deliveryBadge);
                        }

                        // Limpiar datos de delivery
                        deliveryData = null;
                        document.getElementById('deliveryForm').reset();
                    } else {
                        showToast('Error al confirmar pedido: ' + (data.message || 'Error desconocido'),
                            'error');
                        confirmButton.innerHTML = originalText;
                        confirmButton.disabled = false;
                    }
                },
                error: function() {
                    showToast('Error al confirmar el pedido de delivery', 'error');
                    confirmButton.innerHTML = originalText;
                    confirmButton.disabled = false;
                }
            });
        }

        // Preview de la foto
        document.getElementById('delivery-photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('photo-preview');

            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `
                    <div class="position-relative d-inline-block">
                        <img src="${e.target.result}" class="img-thumbnail" style="max-height: 150px;">
                        <button type="button" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1" 
                                onclick="clearPhoto()" title="Eliminar foto">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                `;
                };
                reader.readAsDataURL(file);
            }
        });

        function clearPhoto() {
            document.getElementById('delivery-photo').value = '';
            document.getElementById('photo-preview').innerHTML = '';
        }

        function isNumber(evt) {
            evt = evt || window.event;
            var charCode = evt.which || evt.keyCode;
            if (charCode < 48 || charCode > 57) {
                evt.preventDefault();
                return false;
            }
            return true;
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

        function editCantidadPersonas(event) {
            $('#editCantidadPersonasModal').modal('show');

        }
        $(document).ready(function() {
            $('#save-cantidad-personas').on('click', function() {
                saveCantidadPersonas();
            });
        });

        function saveCantidadPersonas() {
            const cantidadPersonas = $('#cantidad-personas').val();

            // Validamos si la cantidad es válida
            if (cantidadPersonas && cantidadPersonas > 0) {
                // Llamar a la función AJAX para actualizar
                updateCantidadPersonas(cantidadPersonas);

                // Cerrar el modal
                $('#editCantidadPersonasModal').modal('hide');

                // Actualizar el texto en la interfaz
                setTimeout(function() {
                    location.reload(); // Recargar página para mostrar cambios
                }, 500);
            } else {
                alert('La cantidad debe ser mayor que 0');
            }
        }

        function checkEnter(event) {
            // Si el usuario presiona Enter, guardamos los cambios
            if (event.key === 'Enter') {
                saveCantidadPersonas();
            }
        }

        function updateCantidadPersonas(cantidadPersonas) {
            // Aquí enviamos la nueva cantidad a la base de datos
            const mesaId = '{{ $mesa->id }}'; // ID de la mesa
            const url = "{{ route('sales.updateCantidadPersonas') }}"; // Ruta para actualizar la cantidad

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}', // CSRF Token
                    mesa_id: mesaId, // ID de la mesa
                    cantidad_personas: cantidadPersonas // Nueva cantidad
                },
                success: function(response) {
                    if (response.success) {
                        console.log('Cantidad de personas actualizada exitosamente');
                    } else {
                        alert('Hubo un error al actualizar la cantidad');
                    }
                },
                error: function() {
                    alert('Error en la solicitud');
                }
            });
        }

        // Variable global con el ID de la orden actual
        const orderId = {{ $order->id }};
        const mesaId = {{ $mesa->id }};
        const isAdmin = @json(auth()->user()->hasRole('Admin') || auth()->user()->hasRole('Caja'));

        if (!orderId) {
            console.warn('No se pudo obtener el ID de la orden');
        }


        // Cargar productos existentes al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            loadExistingProducts();
            document.getElementById('cobrar-order').addEventListener('click', function() {
                // obtener cuenta seleccionada (botón con .btn-primary)
                let selectedBtn = document.querySelector('.account-tab-btn.btn-primary') ||
                                  document.querySelector('.account-tab-btn.active') ||
                                  document.querySelector('.account-tab-btn[data-account]');
                let account = selectedBtn ? selectedBtn.getAttribute('data-account') : '1';

                const url = "{{ route('sales.restaurantePago', ['mesaId' => 'MESA_ID']) }}".replace('MESA_ID', mesaId);
                window.location.href = url + '?account_number=' + encodeURIComponent(account);
            });
        });

        function cargarDatosDelivery(data) {
            if (data.delivery) {
                document.getElementById('delivery-doc-number').value = data.delivery.document_number || '';
                document.getElementById('delivery-client-name').value = data.delivery.client_name || '';
                document.getElementById('delivery-phone').value = data.delivery.phone || '';
                document.getElementById('delivery-date').value = data.delivery.delivery_date || '';
                document.getElementById('delivery-hour').value = data.delivery.delivery_hour || '';
                document.getElementById('delivery-address').value = data.delivery.address || '';
                document.getElementById('delivery-reference').value = data.delivery.reference || '';
                document.getElementById('delivery-observation').value = data.delivery.observation || '';
            } else {
                document.getElementById('deliveryForm').reset();
            }
        }

        function loadExistingProducts() {
            if (!mesaId) {
                console.warn('No se pudo obtener el ID de la mesa');
                return;
            }

            $.ajax({
                url: `{{ route('sales.getOrdersByTable', '') }}/${mesaId}`,
                method: 'GET',
                success: function(data) {
                    const tbody = document.querySelector('#table-products tbody');
                    const tabsContainer = document.querySelector('#account-tabs');

                    if (data.success && data.orders && data.orders.length > 0) {
                        // Agrupar por account_number (fallback a 1)
                        const accounts = {};
                        data.orders.forEach(order => {
                            const acc = (order.account_number === null || order.account_number === undefined) ? '1' : String(order.account_number);
                            if (!accounts[acc]) accounts[acc] = [];
                            accounts[acc].push(order);
                        });

                        // construir tabs (botones) — se muestran arriba del table
                        const keys = Object.keys(accounts).sort((a,b) => parseInt(a) - parseInt(b));
                        let tabsHtml = `<div class="nav nav-tabs p-3 gap-3" role="group" aria-label="Cuentas">`;
                        keys.forEach((acc, i) => {
                            tabsHtml += `<button type="button" class="btn btn-sm ${i===0 ? 'btn-primary' : 'btn-outline-primary'} account-tab-btn" data-account="${acc}">Cuenta ${acc} <span class="badge bg-light text-dark">${accounts[acc].length}</span></button>`;
                        });
                        tabsHtml += `</div>`;
                        tabsContainer.innerHTML = tabsHtml;

                        // construir todas las filas en el tbody (todas las cuentas, sin anidar tablas)
                        let rowsHtml = '';
                        keys.forEach(acc => {
                            accounts[acc].forEach(order => {
                                const displayName = order.product_name || '-';
                                const discount = (order.discount || order.discount_amount) || 0;
                                const price = order.product_price || 0;
                                const subtotal = (((parseFloat(order.quantity || 0) * parseFloat(price || 0)) - parseFloat(discount || 0))).toFixed(2);
                                const notes = order.notes ? `<div class="text-muted small mt-1">${String(order.notes)}</div>` : '';
                                const productConfirmed = order.confirmed == 1;
                                const confirmedIcon = productConfirmed ? '<span class="confirmed-icon badge bg-success ms-1"><i class="bi bi-check"></i></span>' : '';
                                const deleteButton = `
                                    <button class="btn btn-sm btn-danger ${(productConfirmed && !isAdmin) ? 'd-none' : ''}" onclick="removeFromCart(${order.id})" title="Eliminar">
                                        <i class="bi bi-trash"></i>
                                    </button>`;

                                // cada fila lleva data-account-number para filtrar
                                rowsHtml += `<tr data-order-detail-id="${order.id}" data-product-id="${order.product_id}" data-account-number="${acc}" data-discount="${parseFloat(discount || 0)}">
                                    <td class="text-center quantity" style="width:60px">${order.quantity}</td>
                                    <td>
                                        <div class="fw-bold">${escapeHtml(displayName)}</div>
                                        ${notes}
                                    </td>
                                    <td class="text-center" style="width:100px">S/ ${parseFloat(price).toFixed(2)}</td>
                                    <td class="text-center" style="width:100px">${discount ? 'S/ ' + parseFloat(discount).toFixed(2) : '-'}</td>
                                    <td class="text-center subtotal" style="width:100px">S/ ${subtotal}</td>
                                    <td class="text-center" style="width:80px">
                                        ${deleteButton}
                                        ${confirmedIcon}
                                    </td>
                                </tr>`;
                            });
                        });

                        tbody.innerHTML = rowsHtml;

                        // activar comportamiento de tabs (mostrar/ocultar filas por account)
                        document.querySelectorAll('.account-tab-btn').forEach(btn => {
                            btn.addEventListener('click', function() {
                                document.querySelectorAll('.account-tab-btn').forEach(b => {
                                    b.classList.remove('btn-primary');
                                    b.classList.add('btn-outline-primary');
                                });
                                this.classList.remove('btn-outline-primary');
                                this.classList.add('btn-primary');

                                const selected = this.getAttribute('data-account');
                                filterRowsByAccount(selected);
                            });
                        });

                        // seleccionar la primera cuenta por defecto
                        const firstAcc = keys[0];
                        if (firstAcc) {
                            // marcar el primer botón y filtrar
                            const firstBtn = document.querySelector(`.account-tab-btn[data-account="${firstAcc}"]`);
                            if (firstBtn) {
                                firstBtn.classList.remove('btn-outline-primary');
                                firstBtn.classList.add('btn-primary');
                            }
                            filterRowsByAccount(firstAcc);
                        }

                        // Mostrar badge confirmado si aplica
                        const hasConfirmedProducts = data.orders.some(order => order.confirmed == 1);
                        if (hasConfirmedProducts) {
                            showConfirmedBadge();
                        }

                        updateCartSummary();

                        if (data.delivery) {
                            cargarDatosDelivery(data);
                        }
                    } else {
                        // No hay productos, mostrar mensaje de carrito vacío
                        tbody.innerHTML = `
                            <tr class="empty-cart-message">
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="bi bi-cart-x fs-1 d-block mb-2"></i>
                                    <small>El carrito está vacío</small>
                                </td>
                            </tr>
                        `;
                        tabsContainer.innerHTML = '';
                        updateCartSummary();
                    }
                },
                error: function() {
                    console.error('Error al cargar productos existentes');
                }
            });
        }

        // helper: muestra/oculta filas según account selected
        function filterRowsByAccount(account) {
            document.querySelectorAll('#table-products tbody tr[data-account-number]').forEach(tr => {
                const acc = String(tr.getAttribute('data-account-number') || '1');
                tr.style.display = (acc === String(account)) ? '' : 'none';
            });
            updateCartSummary(); // recalcular resumen con filas visibles
        }

        function showConfirmedBadge() {
            // Solo agregar el badge de confirmado al nombre de la mesa
            const headerTableName = document.querySelector('.list-group-item:nth-child(2)');
            if (headerTableName && !headerTableName.querySelector('.confirmed-badge')) {
                const confirmedBadge = document.createElement('span');
                confirmedBadge.className = 'confirmed-badge badge bg-success ms-2';
                confirmedBadge.innerHTML = '<i class="bi bi-check"></i> CONFIRMADO';
                headerTableName.appendChild(confirmedBadge);
            }
        }

        function showFullConfirmedState() {
            const confirmButton = document.getElementById('confirm-order');
            if (confirmButton) {
                confirmButton.textContent = 'PEDIDO CONFIRMADO';
                confirmButton.className = 'btn btn-success flex-fill';
                confirmButton.disabled = true;
            }
        }

        function handleCategoryClick(categoryId) {
            const productContainer = document.getElementById('product-container');

            // Mostrar loader mientras carga
            productContainer.innerHTML =
                '<div class="text-center p-3"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Cargando...</span></div></div>';

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
                            productElement.className = "btn btn-outline-success product-card-btn p-2";
                            productElement.type = "button";

                            // Mostrar nombre del producto con stock
                            const stock = producto.quantity || 0;
                            const precio = parseFloat(producto.unit_price || 0).toFixed(2);

                            productElement.innerHTML = `
                            <div class="text-center">
                                <div class="fw-bold text-success small mb-1">${producto.name.toUpperCase()}</div>
                                <div class="fw-bold text-success">S/ ${precio}</div>
                            </div>
                        `;

                            productElement.onclick = function() {
                                openProductModal(producto.id, producto.name, producto.unit_price,
                                    stock);
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
                    document.querySelectorAll('button[onclick*="handleCategoryClick"]').forEach(btn => {
                        btn.classList.remove('btn-primary');
                        btn.classList.add('btn-outline-primary');
                    });

                    const selectedButton = document.querySelector(
                        `button[onclick="handleCategoryClick('${categoryId}')"]`);
                    if (selectedButton) {
                        selectedButton.classList.remove('btn-outline-primary');
                        selectedButton.classList.add('btn-primary');
                    }
                },
                error: function() {
                    productContainer.innerHTML =
                        '<div class="alert alert-danger">Error al cargar los productos. Por favor, intente nuevamente.</div>';
                }
            });
        }

        function openProductModal(productId, productName, unitPrice, stock) {
            // Llenar datos del modal
            document.getElementById('product-name').textContent = productName;
            document.getElementById('product-price').textContent = 'S/ ' + parseFloat(unitPrice).toFixed(2);
            document.getElementById('product-quantity').value = 1;
            document.getElementById('product-quantity').max = stock;
            document.getElementById('product-notes').value = '';

            // Guardar datos del producto en el modal
            const modal = document.getElementById('addProductModal');
            modal.setAttribute('data-product-id', productId);
            modal.setAttribute('data-product-name', productName);
            modal.setAttribute('data-unit-price', unitPrice);
            modal.setAttribute('data-stock', stock);

            $('#discount_amount').val('');
            $('#discount_reason').val('');
            $('#product_notes').val('');
            $('#notes').val('');

            // Mostrar modal
            const bootstrapModal = new bootstrap.Modal(modal);
            bootstrapModal.show();
        }

        function getSelectedAccount() {
            const sel = document.querySelector('.account-tab-btn.btn-primary') ||
                        document.querySelector('.account-tab-btn.active') ||
                        document.querySelector('.account-tab-btn[data-account]');
            return sel ? sel.getAttribute('data-account') : '1';
        }

        function addProductToCart() {
            const modal = document.getElementById('addProductModal');
            const productId = modal.getAttribute('data-product-id');
            const productName = modal.getAttribute('data-product-name');
            const unitPrice = parseFloat(modal.getAttribute('data-unit-price'));
            const stock = parseInt(modal.getAttribute('data-stock'));
            const quantity = parseInt(document.getElementById('product-quantity').value);
            const notes = document.getElementById('product-notes').value.trim();
            const discountAmountRaw = (document.getElementById('discount_amount').value || '').toString().trim();
            const discountAmount = discountAmountRaw === '' ? null : parseFloat(discountAmountRaw);
            const discountReason = (document.getElementById('discount_reason').value || '').trim();

            if (!productId || quantity <= 0 || unitPrice < 0) {
                showToast('Por favor, verifica los datos del producto', 'error');
                return;
            }

            if (discountAmount !== null && !isNaN(discountAmount) && discountAmount > 0 && discountReason === '') {
                showToast('Debe ingresar un motivo de descuento', 'error');
                return;
            }

            if (discountAmount > unitPrice * quantity) {
                showToast('El monto del descuento no puede ser mayor al subtotal', 'error');
                return;
            }

            // Obtener cuenta seleccionada
            const account_number = getSelectedAccount();

            // Mostrar loading
            const addButton = modal.querySelector('#add-to-cart');
            const originalText = addButton.textContent;
            addButton.textContent = 'Agregando...';
            addButton.disabled = true;

            $.ajax({
                url: "{{ route('orders.addProduct', ':orderId') }}".replace(':orderId', orderId),
                method: 'POST',
                data: {
                    product_id: productId,
                    quantity: quantity,
                    product_price: unitPrice,
                    notes: notes,
                    discount_amount: discountAmount,
                    discount_reason: discountReason,
                    sumar: 'true',
                    account_number: account_number, 
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        // Cerrar modal
                        const bootstrapModal = bootstrap.Modal.getInstance(modal);
                        bootstrapModal.hide();
                        loadExistingProducts();
                    } else {
                        showToast('Error al agregar producto: ' + (data.message || 'Error desconocido'),
                            'error');
                    }
                },
                error: function() {
                    showToast('Error al agregar producto al carrito. Inténtalo de nuevo.', 'error');
                },
                complete: function() {
                    // Restaurar botón
                    addButton.textContent = originalText;
                    addButton.disabled = false;
                }
            });
        }

        function updateCartSummary() {
            const tbody = document.querySelector('#table-products tbody');
            if (!tbody) return;

            const rows = Array.from(tbody.querySelectorAll('tr[data-product-id]')); // solo filas de producto
            let totalItems = 0;
            let totalAmount = 0;

            rows.forEach(row => {
                // ignorar filas ocultas
                if (row.style.display === 'none' || getComputedStyle(row).display === 'none') return;

                const qtyEl = row.querySelector('.quantity');
                const subtotalEl = row.querySelector('.subtotal');
                if (!qtyEl || !subtotalEl) return;

                const quantity = parseInt(qtyEl.textContent.trim(), 10) || 0;
                const subtotalText = (subtotalEl.textContent || '').trim();
                const subtotalNum = parseFloat(subtotalText.replace(/[^\d.,-]/g, '').replace(',', '.')) || 0;

                totalItems += quantity;
                totalAmount += subtotalNum;
            });

            const cartCount = document.getElementById('cart-count');
            const totalAmountEl = document.getElementById('total-amount');
            if (cartCount) cartCount.textContent = totalItems;
            if (totalAmountEl) totalAmountEl.textContent = 'S/ ' + totalAmount.toFixed(2);
        }

        function showToast(message, type = 'info') {
            // Función simple para mostrar notificaciones
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: type,
                    title: message,
                    timer: 2000,
                    showConfirmButton: false,
                    toast: true,
                    position: 'top-end'
                });
            } else {
                alert(message);
            }
        }

        function removeFromCart(orderDetailId) {
            if (!orderId) {
                showToast('Error: No se pudo identificar la orden', 'error');
                return;
            }

            // Buscar información del producto en la tabla
            const productRow = document.querySelector(`button[onclick="removeFromCart(${orderDetailId})"]`)?.closest('tr');
            if (!productRow) {
                showToast('Error: No se pudo encontrar el producto', 'error');
                return;
            }

            const quantityCell = productRow.querySelector('.quantity');
            const productNameCell = productRow.querySelector('.fw-bold');

            const maxQuantity = parseInt(quantityCell?.textContent || 1);
            const productName = productNameCell?.textContent || 'Producto';

            // Si la cantidad es 1, eliminar directamente con confirmación
            if (maxQuantity === 1) {
                Swal.fire({
                    title: '¿Eliminar producto?',
                    text: `¿Estás seguro de eliminar "${productName}" del carrito?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((result) => {
                    if (result.isConfirmed) {
                        removeProductFromCart(orderDetailId, 1);
                        loadExistingProducts();
                    }
                });
                return;
            }

            // Si la cantidad es mayor a 1, mostrar el modal para elegir cuántos eliminar
            document.getElementById('remove-product-name').textContent = productName;
            document.getElementById('remove-max-quantity').textContent = maxQuantity;
            document.getElementById('remove-quantity').value = 1;
            document.getElementById('remove-quantity').max = maxQuantity;

            // Guardar el ID del detalle en el modal
            const modal = document.getElementById('removeProductModal');
            modal.setAttribute('data-order-detail-id', orderDetailId);
            modal.setAttribute('data-max-quantity', maxQuantity);

            // Mostrar el modal
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }

        function removeProductFromCart(orderDetailId, quantityToRemove) {
            $.ajax({
                url: "{{ route('orders.removeProduct', ':orderId') }}".replace(':orderId', orderId),
                method: 'POST',
                data: {
                    order_detail_id: orderDetailId,
                    quantity_to_remove: quantityToRemove,
                    _token: '{{ csrf_token() }}'
                },
                success: function(data) {
                    if (data.success) {
                        showToast(data.message || 'Producto actualizado correctamente', 'success');
                        loadExistingProducts();
                        updateCartSummary();
                    } else {
                        showToast('Error al eliminar producto: ' + (data.message || 'Error desconocido'),
                            'error');
                    }
                },
                error: function() {
                    showToast('Error al eliminar producto del carrito', 'error');
                }
            });
        }


        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners para el modal de agregar producto
            document.getElementById('increase-qty').addEventListener('click', function() {
                const input = document.getElementById('product-quantity');
                const maxStock = parseInt(document.getElementById('addProductModal').getAttribute(
                    'data-stock')) || 999;
                let currentValue = parseInt(input.value) || 1;

                if (currentValue < maxStock) {
                    input.value = currentValue + 1;
                }
            });

            document.getElementById('decrease-qty').addEventListener('click', function() {
                const input = document.getElementById('product-quantity');
                let currentValue = parseInt(input.value) || 1;

                if (currentValue > 1) {
                    input.value = currentValue - 1;
                }
            });

            document.getElementById('add-to-cart').addEventListener('click', function() {
                addProductToCart();
            });

            // Event listeners para el modal de eliminar productos
            const removeModal = document.getElementById('removeProductModal');

            if (removeModal) {
                // Aumentar cantidad a eliminar
                document.getElementById('increase-remove-qty')?.addEventListener('click', function() {
                    const input = document.getElementById('remove-quantity');
                    const maxQty = parseInt(removeModal.getAttribute('data-max-quantity')) || 999;
                    let currentValue = parseInt(input.value) || 1;

                    if (currentValue < maxQty) {
                        input.value = currentValue + 1;
                    }
                });

                // Disminuir cantidad a eliminar
                document.getElementById('decrease-remove-qty')?.addEventListener('click', function() {
                    const input = document.getElementById('remove-quantity');
                    let currentValue = parseInt(input.value) || 1;

                    if (currentValue > 1) {
                        input.value = currentValue - 1;
                    }
                });

                // Confirmar eliminación
                document.getElementById('confirm-remove-product')?.addEventListener('click', function() {
                    const orderDetailId = removeModal.getAttribute('data-order-detail-id');
                    const quantityToRemove = parseInt(document.getElementById('remove-quantity').value) ||
                        1;
                    const maxQuantity = parseInt(removeModal.getAttribute('data-max-quantity')) || 1;

                    if (quantityToRemove > maxQuantity) {
                        showToast('La cantidad a eliminar no puede ser mayor a la disponible', 'error');
                        return;
                    }

                    // Cerrar el modal
                    const bsModal = bootstrap.Modal.getInstance(removeModal);
                    bsModal.hide();

                    // Ejecutar la eliminación
                    removeProductFromCart(orderDetailId, quantityToRemove);
                });
            }

            // Actualizar el autocomplete para usar el modal
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
                                        label: item.name + ' - S/ ' +
                                            parseFloat(item.unit_price || 0)
                                            .toFixed(2),
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
                        response([]);
                    }
                },
                select: function(event, ui) {
                    // Abrir modal en lugar de agregar directamente
                    openProductModal(ui.item.id, ui.item.name, ui.item.unit_price, ui.item.quantity);

                    // Limpiar el campo de búsqueda
                    $('#search-product-pedidos').val('');
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

        function preaccount(showModal = true) {
            var order_id = orderId;
            $.ajax({
                url: '{{ route('mesas.precuenta') }}',
                method: 'GET',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    order_id
                },
                success: async function(response) {
                    if (response.status) {
                        var table = response.table;
                        var details = response.details;
                        var subtotal = response.subtotal;
                        var order = response.order;

                        const opts = {
                            // serial: serial,
                            nombreImpresora: 'Ticketera',
                            operaciones: [{
                                    nombre: 'Iniciar',
                                    argumentos: []
                                },
                                {
                                    nombre: 'Feed',
                                    argumentos: [2]
                                },
                                {
                                    nombre: "EstablecerAlineacion",
                                    argumentos: [1]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['----------------------------------------\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['DYC COMPANY FOOD S.A.C\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['DE CAJON SANTA VICTORIA\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['Chiclayo\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['RUC: 20611915277\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['Tel: N/N\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['Email: festejo.gastrobar@gmail.com\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [`Operacion: ${orderId}\n`]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['PRE-CUENTA\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['NO FISCAL / NO FISCAL\n']
                                },
                                {
                                    nombre: "EstablecerAlineacion",
                                    argumentos: [0]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [
                                        `MOZO: ${order.employee ? order.employee.name : '-'}\n`
                                    ]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [`MESA: ${order.table.name}\n`]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [`CANT. PERSONAS: ${order.number_persons}\n`]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [`CLIENTE: Publico general\n`]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [`DOC: -\n`]
                                },

                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['----------------------------------------\n']
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: [
                                        `${(new Date()).toLocaleDateString('es-PE')} ${(new Date()).toLocaleTimeString('es-PE', { hour: '2-digit', minute: '2-digit' })}\n`
                                    ]
                                },
                                {
                                    nombre: 'EscribirTexto',
                                    argumentos: ['----------------------------------------\n']
                                },
                            ]
                        };

                        details.forEach(function(detail) {
                            opts.operaciones.push({
                                nombre: 'TextoSegunPaginaDeCodigos',
                                argumentos: [
                                    2,
                                    'cp850',
                                    `${detail.quantity}    ${detail.product.name}     ${(detail.product_price * detail.quantity - detail.discount_amount).toFixed(2)}\n`
                                ]
                            }, );
                        });

                        opts.operaciones.push({
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: ['----------------------------------------\n']
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: [`Total a pagar: S/${(subtotal).toFixed(2)}\n`]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: [`RUC: _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _\n`]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: [
                                `RAZON SOCIAL: _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _\n`
                            ]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: [`DIRECCION: _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _\n`]
                        }, {
                            nombre: "EstablecerAlineacion",
                            argumentos: [1]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'EscribirTexto',
                            argumentos: [`Gracias\n`]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: 'Feed',
                            argumentos: [2]
                        }, {
                            nombre: "EstablecerAlineacion",
                            argumentos: [0]
                        }, {
                            nombre: 'Corte',
                            argumentos: [1]
                        });

                        try {
                            // IP de la PC que tiene la impresora (cámbiala por la tuya)
                            const IP_PC_IMPRESORA = '192.168.18.46';

                            let url;
                            let headers = {
                                'Content-Type': 'application/json; charset=utf-8'
                            };

                            // Verificar si estamos en Android o PC
                            let esAndroid = false;
                            try {
                                const platformResponse = await fetch('http://localhost:8000/version', {
                                    timeout: 3000 // Timeout de 3 segundos
                                });
                                const platformData = await platformResponse.json();
                                esAndroid = platformData.plataforma === "Puente";
                                console.log('Plataforma detectada:', esAndroid ? 'Android' : 'PC');
                            } catch (error) {
                                console.log('No se pudo detectar la plataforma, asumiendo PC');
                                esAndroid = false;
                            }

                            if (esAndroid) {
                                // Método Android con reenvío usando x-reenviar-a
                                url = 'http://localhost:8000';
                                headers['x-reenviar-a'] = `http://${IP_PC_IMPRESORA}:8000/imprimir`;
                                console.log('Usando método Android con reenvío');

                                // Enviar solicitud Android
                                const http = await fetch(url, {
                                    method: 'POST',
                                    body: JSON.stringify(opts),
                                    headers: headers
                                });

                                const res = await http.json();

                                if (res.ok) {
                                    console.log('Impresión Android exitosa');
                                    if (typeof ToastMessage !== 'undefined') {
                                        ToastMessage.fire({
                                            text: 'Documento enviado a impresión correctamente (Android)'
                                        });
                                    }
                                } else {
                                    throw new Error(res.message || 'Error en impresión Android');
                                }

                            } else {
                                // Método PC: intentar local primero, si falla usar reenvío
                                let impresionExitosa = false;

                                try {
                                    console.log('Intentando impresión local...');
                                    // Intentar impresión local directa
                                    const localResponse = await fetch('http://localhost:8000/imprimir', {
                                        method: 'POST',
                                        body: JSON.stringify(opts),
                                        headers: {
                                            'Content-Type': 'application/json; charset=utf-8'
                                        }
                                    });

                                    const localRes = await localResponse.json();

                                    if (localRes.ok) {
                                        console.log('Impresión local exitosa');
                                        if (typeof ToastMessage !== 'undefined') {
                                            ToastMessage.fire({
                                                text: 'Documento enviado a impresión correctamente (Local)'
                                            });
                                        }
                                        impresionExitosa = true;
                                    } else {
                                        throw new Error('Impresión local falló: ' + localRes.message);
                                    }

                                } catch (errorLocal) {
                                    console.log('Error en impresión local:', errorLocal.message);
                                    console.log('Intentando impresión remota...');

                                    try {
                                        // Usar el método de reenvío remoto
                                        const rutaRemota = `http://${IP_PC_IMPRESORA}:8000/imprimir`;
                                        const payload = {
                                            operaciones: opts.operaciones,
                                            nombreImpresora: opts.nombreImpresora,
                                            // serial: opts.serial,
                                        };

                                        const remoteResponse = await fetch(
                                            'http://localhost:8000/reenviar?host=' + rutaRemota, {
                                                method: 'POST',
                                                body: JSON.stringify(payload),
                                                headers: {
                                                    'Content-Type': 'application/json; charset=utf-8'
                                                }
                                            });

                                        const remoteRes = await remoteResponse.json();

                                        if (remoteRes.ok) {
                                            console.log('Impresión remota exitosa');
                                            if (typeof ToastMessage !== 'undefined') {
                                                ToastMessage.fire({
                                                    text: 'Documento enviado a impresión correctamente (Remoto)'
                                                });
                                            }
                                            impresionExitosa = true;
                                        } else {
                                            throw new Error('Impresión remota falló: ' + remoteRes.message);
                                        }

                                    } catch (errorRemoto) {
                                        console.log('Error en impresión remota:', errorRemoto.message);
                                        throw new Error('Falló tanto la impresión local como la remota');
                                    }
                                }

                                if (!impresionExitosa) {
                                    throw new Error('No se pudo completar la impresión');
                                }
                            }

                        } catch (error) {
                            console.error('Error en el proceso de impresión:', error);

                            // Mostrar error específico según el tipo
                            let errorMessage = 'Error desconocido';

                            if (error.name === 'TypeError' && error.message.includes('fetch')) {
                                errorMessage =
                                    'No se pudo conectar con el servicio de impresión. Verifica que esté funcionando.';
                            } else if (error.message.includes('timeout')) {
                                errorMessage = 'Timeout: El servicio de impresión no responde.';
                            } else if (error.message.includes('HTTP Error')) {
                                errorMessage = `Error de servidor: ${error.message}`;
                            } else {
                                errorMessage = error.message;
                            }

                            if (typeof ToastError !== 'undefined') {
                                ToastError.fire({
                                    text: `Error al imprimir: ${errorMessage}`
                                });
                            }
                        }


                    } else {
                        //ToastError.fire({ text: response.error });
                    }
                },
                error: function(err) {
                    console.log('Ocurrió un error');
                }
            });
        }

        function confirmOrder() {
            // Evitar múltiples clicks
            const btn = document.getElementById('confirm-order');
            if (btn) {
                btn.disabled = true;
            }

            $.ajax({
                url: '{{ route('orders.confirm') }}',
                method: 'POST',
                data: {
                    order_id: orderId,
                    _token: '{{ csrf_token() }}'
                },
                success: async function(response) {
                    if (btn) btn.disabled = false;

                    if (response.status) {
                        // Mostrar notificación de éxito con SweetAlert2 si está disponible
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Pedido confirmado',
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                        } else {
                            showToast('Pedido confirmado', 'success');
                        }

                        // Refrescar la tabla para que los productos muestren el nuevo estado
                        try {
                            loadExistingProducts();
                        } catch (e) {
                            console.warn('No se pudo recargar productos:', e.message);
                        }

                        // Nuevo flujo: usar print_jobs proporcionados por el backend (agrupados por impresora)
                        const printJobs = response.print_jobs || null;
                        console.log('confirmOrder: print_jobs from backend:', printJobs);
                        const order = response.order || {};

                        // Helper que envía un payload de impresión al servicio local/remoto (reutiliza lógica previa)
                        const sendPrintPayload = async (opts) => {
                            const IP_PC_IMPRESORA = '192.168.18.46';
                            let esAndroid = false;

                            try {
                                const platformResponse = await fetch('http://localhost:8000/version', {
                                    timeout: 3000
                                });
                                const platformData = await platformResponse.json();
                                esAndroid = platformData.plataforma === 'Puente';
                            } catch (err) {
                                esAndroid = false;
                            }

                            if (esAndroid) {
                                const url = 'http://localhost:8000';
                                const headers = {
                                    'Content-Type': 'application/json; charset=utf-8',
                                    'x-reenviar-a': `http://${IP_PC_IMPRESORA}:8000/imprimir`
                                };
                                const http = await fetch(url, {
                                    method: 'POST',
                                    body: JSON.stringify(opts),
                                    headers
                                });
                                const res = await http.json();
                                console.log('sendPrintPayload Android response:', res);
                                if (!res.ok) throw new Error(res.message ||
                                    'Error en impresión Android');
                                return true;
                            }

                            // PC: intentar impresión local y si falla, reenvío remoto
                            try {
                                const localResponse = await fetch('http://localhost:8000/imprimir', {
                                    method: 'POST',
                                    body: JSON.stringify(opts),
                                    headers: {
                                        'Content-Type': 'application/json; charset=utf-8'
                                    }
                                });
                                const localText = await localResponse.text();
                                let localRes = null;
                                try {
                                    localRes = JSON.parse(localText);
                                } catch (e) {
                                    console.warn('sendPrintPayload: local response not JSON:',
                                        localText);
                                }
                                console.log('sendPrintPayload local response parsed:', localRes, 'raw:',
                                    localText);
                                if (localRes && localRes.ok) return true;
                                throw new Error((localRes && localRes.message) ? localRes.message :
                                    'Impresión local fallida');
                            } catch (errLocal) {
                                const rutaRemota = `http://${IP_PC_IMPRESORA}:8000/imprimir`;
                                const payload = {
                                    operaciones: opts.operaciones,
                                    nombreImpresora: opts.nombreImpresora
                                };
                                const remoteResponse = await fetch(
                                    'http://localhost:8000/reenviar?host=' + rutaRemota, {
                                        method: 'POST',
                                        body: JSON.stringify(payload),
                                        headers: {
                                            'Content-Type': 'application/json; charset=utf-8'
                                        }
                                    });
                                const remoteText = await remoteResponse.text();
                                let remoteRes = null;
                                try {
                                    remoteRes = JSON.parse(remoteText);
                                } catch (e) {
                                    console.warn('sendPrintPayload: remote response not JSON:',
                                        remoteText);
                                }
                                console.log('sendPrintPayload remote response parsed:', remoteRes,
                                    'raw:', remoteText);
                                if (remoteRes && remoteRes.ok) return true;
                                throw new Error((remoteRes && remoteRes.message) ? remoteRes.message :
                                    'Impresión remota fallida');
                            }
                        };

                        try {
                            if (printJobs && printJobs.length > 0) {
                                // Enviar cada job por impresora usando exactamente el nombre de la categoría
                                for (const job of printJobs) {
                                    console.log('confirmOrder: printing job', job);

                                    const opts = {
                                        nombreImpresora: job.printer,
                                        operaciones: []
                                    };

                                    opts.operaciones.push({
                                        nombre: 'Iniciar',
                                        argumentos: []
                                    });
                                    opts.operaciones.push({
                                        nombre: 'EscribirTexto',
                                        argumentos: ['COMANDA\n']
                                    });
                                    opts.operaciones.push({
                                        nombre: 'EscribirTexto',
                                        argumentos: [
                                            `MESA: ${job.table || (order.table ? order.table.name : '-')}\n`
                                        ]
                                    });
                                    opts.operaciones.push({
                                        nombre: 'EscribirTexto',
                                        argumentos: ['--------------------------------\n']
                                    });

                                    (job.lines || []).forEach(function(item) {
                                        const nota = (item.notes && item.notes.toString().trim()
                                            .length) ? item.notes : 'S';
                                        opts.operaciones.push({
                                            nombre: 'TextoSegunPaginaDeCodigos',
                                            argumentos: [2, 'cp850',
                                                `${item.quantity}    ${item.product_name}   (${nota})\n`
                                            ]
                                        });
                                    });

                                    opts.operaciones.push({
                                        nombre: 'Feed',
                                        argumentos: [2]
                                    });
                                    opts.operaciones.push({
                                        nombre: 'EscribirTexto',
                                        argumentos: ['--------------------------------\n']
                                    });
                                    opts.operaciones.push({
                                        nombre: 'Feed',
                                        argumentos: [2]
                                    });
                                    opts.operaciones.push({
                                        nombre: 'Feed',
                                        argumentos: [2]
                                    });
                                    opts.operaciones.push({
                                        nombre: 'Corte',
                                        argumentos: [1]
                                    });

                                    // Enviar cada job y capturar errores por job para que un fallo
                                    // no impida intentar los siguientes (antes el try/catch externo
                                    // detenía todo en el primer error de fetch).
                                    try {
                                        // Verificar si esta impresora es "BARRA" para usar método de reenvío
                                        if (job.printer && job.printer === 'BARRA') {
                                            console.log('confirmOrder: usando reenvío para impresora BARRA');
                                            // Usar método de reenvío remoto para impresora barra
                                            const IP_PC_IMPRESORA = '192.168.18.240';
                                            const rutaRemota = `http://${IP_PC_IMPRESORA}:8000/imprimir`;
                                            const payload = {
                                                operaciones: opts.operaciones,
                                                nombreImpresora: opts.nombreImpresora
                                            };

                                            const remoteResponse = await fetch(
                                                'http://localhost:8000/reenviar?host=' + rutaRemota, {
                                                    method: 'POST',
                                                    body: JSON.stringify(payload),
                                                    headers: {
                                                        'Content-Type': 'application/json; charset=utf-8'
                                                    }
                                                });

                                            const remoteRes = await remoteResponse.json();

                                            if (!remoteRes.ok) {
                                                throw new Error('Reenvío a barra falló: ' + (remoteRes
                                                    .message || 'Error desconocido'));
                                            }

                                            console.log('confirmOrder: reenvío OK para barra');
                                        } else {
                                            // Usar método normal para otras impresoras
                                            await sendPrintPayload(opts);
                                            console.log('confirmOrder: impresión OK para', job.printer);
                                        }
                                    } catch (errJob) {
                                        console.error('confirmOrder: fallo impresión para', job.printer,
                                            errJob);
                                        // seguir con el siguiente job
                                    }
                                }
                            } else {
                                // No hay trabajos de impresión asignados por categoría; no hacemos impresión automática
                                console.warn(
                                    'confirmOrder: no print_jobs returned by backend — skipping printing');
                            }
                        } catch (error) {
                            console.error('Error impresión confirmOrder:', error);
                            if (typeof ToastError !== 'undefined') {
                                ToastError.fire({
                                    text: `Error al imprimir: ${error.message || 'Desconocido'}`
                                });
                            } else {
                                showToast('Error al imprimir: ' + (error.message || 'Desconocido'), 'error');
                            }
                        }

                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: response.error || 'Error al confirmar'
                            });
                        } else {
                            showToast(response.error || 'Error al confirmar', 'error');
                        }
                    }
                },
                error: function(err) {
                    if (btn) btn.disabled = false;
                    console.log('Ocurrió un error en la petición de confirmación', err);
                    showToast('Ocurrió un error al confirmar el pedido', 'error');
                }
            });
        }

        async function cerrarMesa(mesaId) {
            const result = await Swal.fire({
                title: '¿Liberar mesa?',
                text: 'Esto eliminará el pedido y liberará la mesa.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sí, liberar',
                cancelButtonText: 'Cancelar',
            });

            if (!result.isConfirmed) return;

            // Usar la ruta nombrada de Laravel y reemplazar el placeholder con el id real
            const url = "{{ route('mesas.cerrar', ['id' => 'MESA_ID']) }}".replace('MESA_ID', mesaId);
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            // Mostrar indicador mientras se procesa
            Swal.fire({
                title: 'Liberando mesa...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            try {
                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'Accept': 'application/json'
                    }
                });

                let data = null;
                try {
                    data = await res.json();
                } catch (e) {
                    // respuesta no JSON
                    console.error('Respuesta no JSON al cerrar mesa:', e);
                }

                if (!res.ok || (data && data.success === false)) {
                    const msg = (data && data.message) ? data.message : 'No se pudo cerrar la mesa.';
                    Swal.fire('Error', msg, 'error');
                    return;
                }

                // Éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Mesa liberada',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000
                });

                // Mandar a restaurante vista
                window.location.href = "{{ route('sales.mozo') }}";


            } catch (err) {
                console.error('Error al cerrar la mesa:', err);
                Swal.fire('Error', 'Error inesperado al cerrar la mesa.', 'error');
            }
        }
    </script>
    <script>
        function collectProductsByAccount() {
            const rows = document.querySelectorAll('#table-products tbody tr');
            const accounts = {}; // { accountNum: [ { id, name, qty, subtotal } ] }

            rows.forEach((tr, idx) => {
                // intentar extraer datos desde celdas conocidas
                const qtyEl = tr.querySelector('.quantity');
                const nameEl = tr.querySelector('.fw-bold') || tr.children[1];
                const subtotalEl = tr.querySelector('.subtotal');
                const accountAttr = tr.getAttribute('data-account-number') || tr.dataset.accountNumber || tr.getAttribute('data-account') || null;

                const qty = (qtyEl ? qtyEl.textContent.trim() : (tr.children[0] ? tr.children[0].textContent.trim() : '0')) || '0';
                const name = nameEl ? nameEl.textContent.trim() : ('Producto ' + (idx + 1));
                const subtotal = subtotalEl ? subtotalEl.textContent.trim() : '';
                const discountAttr = tr.getAttribute('data-discount') || tr.dataset.discount || '0';
                const discountNum = parseFloat(String(discountAttr).replace(/[^\\d.-]/g, '')) || 0;
                const orderDetailId = tr.getAttribute('data-order-detail-id') || tr.dataset.orderDetailId || tr.getAttribute('data-product-id') || ('row' + idx);
                const account = accountAttr ? accountAttr.trim() : '1';

                if (!accounts[account]) accounts[account] = [];
                accounts[account].push({
                    id: orderDetailId,
                    name: name,
                    qty: qty,
                    subtotal: subtotal,
                    discount: discountNum
                });
            });

            return accounts;
        }

        // Renderiza tabs y listas dentro del modal
        function renderAccountsModal() {
            const accounts = collectProductsByAccount();
            const tabsEl = document.getElementById('accountTabs');
            const contentEl = document.getElementById('accountTabsContent');
            tabsEl.innerHTML = '';
            contentEl.innerHTML = '';

            // ordenar keys numéricamente cuando sean números
            const keys = Object.keys(accounts).sort((a, b) => {
                const na = parseInt(a), nb = parseInt(b);
                if (!isNaN(na) && !isNaN(nb)) return na - nb;
                return a.localeCompare(b);
            });

            if (keys.length === 0) {
                tabsEl.innerHTML = `<li class="nav-item"><span class="nav-link active">Cuenta 1</span></li>`;
                contentEl.innerHTML = `<div class="tab-pane fade show active p-3">No hay productos</div>`;
                // también poblar selector de cuenta destino con solo la opción crear nueva
                const selectorContainerEmpty = document.getElementById('targetAccountSelector');
                if (selectorContainerEmpty) {
                    selectorContainerEmpty.innerHTML = `<label class="form-label">Cuenta destino</label><select class="form-select" id="target-account-select"><option value="__new__" selected>Crear nueva cuenta</option></select>`;
                }
                return;
            }

            keys.forEach((acc, i) => {
                const tabId = `acc-tab-${acc}`;
                const paneId = `acc-pane-${acc}`;

                // Tab button
                const li = document.createElement('li');
                li.className = 'nav-item';
                li.role = 'presentation';
                li.innerHTML = `<button class="nav-link ${i === 0 ? 'active' : ''}" id="${tabId}" data-bs-toggle="tab" data-bs-target="#${paneId}" type="button" role="tab" aria-controls="${paneId}" aria-selected="${i === 0 ? 'true' : 'false'}">Cuenta ${acc}</button>`;
                tabsEl.appendChild(li);

                // Pane
                const pane = document.createElement('div');
                pane.className = `tab-pane fade ${i === 0 ? 'show active' : ''}`;
                pane.id = paneId;
                pane.role = 'tabpanel';
                pane.setAttribute('aria-labelledby', tabId);

                // Lista simple de productos
                const list = document.createElement('div');
                list.className = 'list-group';

                accounts[acc].forEach(item => {
                    const itemEl = document.createElement('div');
                    itemEl.className = 'list-group-item d-flex justify-content-between align-items-start';
                    // Añadir checkbox y campo para cantidad a mover. Si tiene descuento, bloquear movimiento
                    const hasDiscount = item.discount && parseFloat(item.discount) > 0;
                    itemEl.innerHTML = `
                        <div class="d-flex align-items-center">
                            <div class="form-check">
                                <input class="form-check-input move-checkbox me-2" type="checkbox" value="${escapeHtml(item.id)}" id="move-${acc}-${escapeHtml(item.id)}" ${hasDiscount ? 'disabled' : ''}>
                            </div>
                            <div class="ms-1">
                                <div class="fw-bold">${escapeHtml(item.name)} ${hasDiscount ? '<span class="badge bg-warning text-dark ms-2 small">CON DESCUENTO</span>' : ''}</div>
                                <small class="text-muted">Disponible: ${escapeHtml(String(item.qty))}</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <input type="number" min="1" max="${escapeHtml(String(item.qty))}" value="1" class="form-control form-control-sm move-qty" id="moveqty-${acc}-${escapeHtml(item.id)}" style="width:90px;" ${hasDiscount ? 'disabled' : ''}>
                            <div class="text-end">
                                <small class="text-muted">${escapeHtml(item.subtotal || '')}</small>
                            </div>
                        </div>
                    `;
                    list.appendChild(itemEl);
                });

                pane.appendChild(list);
                contentEl.appendChild(pane);
            });

            // Construir selector de cuentas destino
            const selectorContainer = document.getElementById('targetAccountSelector');
            if (selectorContainer) {
                let html = `<label class="form-label">Cuenta destino</label><select class="form-select" id="target-account-select">`;
                // opción para crear nueva cuenta
                html += `<option value="__new__">Crear nueva cuenta</option>`;
                keys.forEach(k => {
                    html += `<option value="${k}">Cuenta ${k}</option>`;
                });
                html += `</select>`;
                selectorContainer.innerHTML = html;
            }
        }

        // Abrir modal y poblar vista
        document.getElementById('separate-accounts-btn')?.addEventListener('click', function () {
            renderAccountsModal();
            const modal = new bootstrap.Modal(document.getElementById('separateAccountsModal'));
            modal.show();
        });

        // Mover productos seleccionados a una nueva cuenta
        document.getElementById('move-to-new-account-btn')?.addEventListener('click', function () {
            const checkedEls = Array.from(document.querySelectorAll('#separateAccountsModal .move-checkbox:checked'));
            if (!checkedEls || checkedEls.length === 0) {
                showToast('Selecciona al menos un producto para mover', 'warning');
                return;
            }

            const moves = checkedEls.map(el => {
                const id = el.value;
                // buscar el input de cantidad correspondiente
                const qtyInput = document.getElementById('moveqty-' + el.closest('.tab-pane')?.id?.replace('acc-pane-','') + '-' + id) || document.getElementById(el.id.replace('move-', 'moveqty-'));
                let qty = 1;
                if (qtyInput && qtyInput.value) qty = parseInt(qtyInput.value) || 1;
                return { id: parseInt(id), quantity: qty };
            });

            const moveBtn = this;
            moveBtn.disabled = true;
            moveBtn.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Procesando...';
            const dataObj = {
                _token: '{{ csrf_token() }}',
                order_detail_moves: moves
            };
            const targetSelect = document.getElementById('target-account-select');
            if (targetSelect && targetSelect.value && targetSelect.value !== '__new__') {
                dataObj.target_account = parseInt(targetSelect.value);
            }

            $.ajax({
                url: '{{ route('orders.split', ['orderId' => 'ORDER_ID']) }}'.replace('ORDER_ID', orderId),
                method: 'POST',
                data: dataObj,
                success: function (data) {
                    if (data.success) {
                        showToast(data.message || ('Productos movidos a la cuenta ' + data.new_account), 'success');
                        // Recargar productos y modal
                        loadExistingProducts();
                        renderAccountsModal();
                        const bsModal = bootstrap.Modal.getInstance(document.getElementById('separateAccountsModal'));
                        if (bsModal) bsModal.hide();
                    } else {
                        showToast(data.message || 'Error al mover productos', 'error');
                    }
                },
                error: function (xhr) {
                    let msg = 'Error al separar cuentas';
                    try { msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : msg; } catch (e) {}
                    showToast(msg, 'error');
                },
                complete: function () {
                    moveBtn.disabled = false;
                    moveBtn.innerHTML = '<i class="bi bi-arrow-right-circle me-2"></i>Agregar productos a nueva cuenta';
                }
            });
        });

        // helper escapeHtml (si ya existe, mantiene)
        if (typeof escapeHtml === 'undefined') {
            function escapeHtml(str) {
                return String(str || '').replace(/[&<>"']/g, function (s) {
                    return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[s];
                });
            }
        }
    </script>
@endsection
