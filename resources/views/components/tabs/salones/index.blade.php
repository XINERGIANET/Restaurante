<div class="card delivery-card">
    <div class="card-body">
        <ul class="nav nav-tabs">
            @foreach ($areas as $area)
                <li class="nav-item">
                    <a class="nav-link areas" id="tab-{{ $area->id }}" data-tab="{{ $area->id }}" href="#"
                        onclick="setActiveTab(this, {{ $area->id }}); return false;">{{ $area->name }}</a>
                </li>
            @endforeach
        </ul>

        <div id="mesas-container" class="row mt-3 g-2">
            <!-- Aquí se mostrarán las mesas -->
        </div>
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
                            <input type="password" class="form-control" id="pinInput" placeholder="••••" maxlength="4" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                            <div id="pinError" class="text-danger mt-2 d-none">PIN incorrecto</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="button" class="btn btn-primary" id="pinSubmitBtn">Ingresar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<script>
    let mesaSeleccionada = null;
    const areas = @json($areas);

    function setActiveTab(element, areaId) {
        // Quitar 'active' de todos los nav-link
        document.querySelectorAll('.areas.nav-link[data-tab]').forEach(el => el.classList.remove('active'));
        // Poner 'active' al actual
        element.classList.add('active');
        showMesas(areaId);
    }

    function seleccionarMesa(mesa) {
        const container = document.getElementById('mesas-container');
        container.querySelectorAll('.mesa-item').forEach(m => m.classList.remove('mesa-seleccionada'));

        const clicked = container.querySelector(`[data-mesa-id='${mesa.id}']`);
        if (clicked) clicked.classList.add('mesa-seleccionada');

        const sideName = document.getElementById('side-mesa-name');
        const sideId = document.getElementById('side-mesa-id');
        if (sideName) sideName.textContent = mesa.name;
        if (sideId) sideId.value = mesa.id;

        const side_salones = document.getElementById('side-salones');
        side_salones.classList.add('active');
        side_salones.classList.remove('d-none');

        verPedido(mesa.id);
    }

    function showMesas(areaId) {
        const container = document.getElementById('mesas-container');
        container.innerHTML = '';
        const area = areas.find(a => a.id === areaId);
        if (!area || !area.tables) return;
        area.tables.forEach(mesa => {
            const div = document.createElement('div');
            div.className = 'col-4 col-md-3 mb-2';
            div.innerHTML =
                `<div class='card card-mesa bg-success text-white text-center p-3 mesa-item' data-mesa-id='${mesa.id}' style='cursor:pointer;'>${mesa.name}</div>`;
            div.querySelector('.mesa-item').addEventListener('click', function() {

                mesaSeleccionada = mesa;
                document.getElementById('pinInput').value = '';
                document.getElementById('pinError').classList.add('d-none');
                const modal = new bootstrap.Modal(document.getElementById('pinModal'));
                modal.show();
            });
            container.appendChild(div);
        });
    }
    document.getElementById('pinSubmitBtn').addEventListener('click', function() {
        const pin = document.getElementById('pinInput').value;
        if (!pin || !mesaSeleccionada) return;

        $.ajax({
            url: "{{ route('employees.validarPin') }}",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                pin: pin
            },
            success: function(response) {
                if (response.valid) {
                    // Cerrar modal
                    bootstrap.Modal.getInstance(document.getElementById('pinModal')).hide();
                    // Seleccionar mesa
                    seleccionarMesa(mesaSeleccionada);
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
    // Mostrar mesas del primer área por defecto
    document.addEventListener('DOMContentLoaded', function() {
        if (areas.length > 0) {
            // Activar el primer tab y mostrar sus mesas
            const firstTab = document.querySelector('.areas.nav-link[data-tab]');
            if (firstTab) setActiveTab(firstTab, areas[0].id);
        }
    });


    function verPedido(mesaId) {
        // Cambia el contenido de la tabla a "Cargando..." mientras llega la data
        const $tbody = $('#cuenta-table tbody');
        $tbody.html('<tr><td colspan="5" class="text-center">Cargando...</td></tr>');

        const url = "{{ route('mesas.pedido', ['id' => 'MESA_ID']) }}".replace('MESA_ID', mesaId);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.productos && response.productos.length > 0) {
                    let html = '';
                    response.productos.forEach(function(det) {
                        html += `
                                <tr data-product-id="${det.product.id}">
                                    <td>${det.quantity}</td>
                                    <td>${det.product.name}</td>
                                    <td>${det.product_price}</td>
                                    <td>${(det.quantity * det.product_price).toFixed(2)}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger btn-del-prod" data-det-id="${det.id}"><i class="bi bi-trash"></i></button>
                                    </td>
                                </tr>
                            `;
                    });
                    $tbody.html(html);
                    let total = 0;
                    response.productos.forEach(function(det) {
                        total += det.quantity * det.product_price;
                    });
                    $('#totalAmount').text(total.toFixed(2));
                } else {
                    $tbody.html('<tr><td colspan="5" class="text-center">La cuenta está vacía</td></tr>');
                }
            },
            error: function() {
                $tbody.html(
                    '<tr><td colspan="5" class="text-center text-danger">Error al cargar la cuenta</td></tr>'
                );
            }
        });
    }
</script>

<style>
    .mesa-seleccionada {
        border: 2px solid #e53935 !important;
        box-shadow: 0 0 0 4px rgba(229, 57, 53, 0.12);
        z-index: 2;
    }
</style>
