@extends('layouts.app')

@section('nav')
    <ul class="nav justify-content-center">
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-primary active" href="{{ route('products.create') }}">Registro</a>
        </li>
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-secondary" href="{{ route('products.index') }}">Historico</a>
        </li>
    </ul>
@endsection

@section('header')
    <h1>Lista Productos</h1>
    <p>Listado de productos</p>
@endsection

@section('content')
    <div class="container-fluid content-inner mt-n5 py-0">
        <!-- Card que contiene el formulario y la tabla -->
        <div class="card shadow">
            <!-- Cuerpo del Card -->
            <div class="card-body">
                <!-- Tabla de Registros -->
                <div class="table-responsive mt-4">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>N°</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Precio de Venta</th>
                                <th>Stock</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($products as $product)
                                <tr>
                                    <td>{{ ($products->currentPage() - 1) * $products->perPage() + $loop->iteration }}</td>
                                    <td>{{ $product->name }}</td>
                                    <td>{{ $product->category->name ?? 'Sin categoria' }}</td>
                                    <td>{{ $product->unit_price }}</td>
                                    <td>{{ $product->quantity }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-product-btn"
                                            data-id="{{ $product->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-product-btn"
                                            data-id="{{ $product->id }}" data-name="{{ $product->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No hay productos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $products->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editProductForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Producto</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label">Nombre del Producto</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback" id="edit_name_error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_unit_price" class="form-label">Precio Unitario</label>
                            <input type="number" step="0.01" class="form-control" id="edit_unit_price" name="unit_price"
                                required>
                            <div class="invalid-feedback" id="edit_unit_price_error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_quantity" class="form-label">Cantidad</label>
                            <input type="number" class="form-control" id="edit_quantity" name="quantity" required>
                            <div class="invalid-feedback" id="edit_quantity_error"></div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_category_id" class="form-label">Categoría</label>
                            <select class="form-control" id="edit_category_id" name="category_id" required>
                                <option value="">Seleccione una categoría</option>
                            </select>
                            <div class="invalid-feedback" id="edit_category_id_error"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="editSaveBtn">
                            <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                            Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Eliminar Producto</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el producto <strong id="delete_product_name"></strong>?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        $(document).ready(function() {
            let currentProductId = null;

            // Editar producto
            $('.edit-product-btn').on('click', function() {
                currentProductId = $(this).data('id');

                // Mostrar modal y limpiar campos
                $('#editProductForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                $('#editModal').modal('show');

                // Obtener datos del producto
                $.ajax({
                    url: "{{ route('products.edit', ':id') }}".replace(':id', currentProductId),
                    type: 'GET',
                    success: function(response) {
                        if (response.status) {
                            const product = response.data.product;
                            const categories = response.data.categories;

                            // Llenar campos del producto
                            $('#edit_name').val(product.name);
                            $('#edit_unit_price').val(product.unit_price);
                            $('#edit_quantity').val(product.quantity);

                            // Llenar select de categorías
                            $('#edit_category_id').empty().append(
                                '<option value="">Seleccione una categoría</option>');
                            categories.forEach(function(category) {
                                const selected = category.id == product.category_id ?
                                    'selected' : '';
                                $('#edit_category_id').append(
                                    `<option value="${category.id}" ${selected}>${category.name}</option>`
                                );
                            });



                        } else {
                            ToastMessage.fire({
                                icon: 'error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        ToastMessage.fire({
                            icon: 'error',
                            text: 'Error al cargar los datos del producto'
                        });
                    }
                });
            });

            // Guardar cambios del producto
            $('#editProductForm').on('submit', function(e) {
                e.preventDefault();

                const saveBtn = $('#editSaveBtn');
                const spinner = saveBtn.find('.spinner-border');

                // Mostrar loading
                saveBtn.prop('disabled', true);
                spinner.removeClass('d-none');

                // Limpiar errores previos
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                $.ajax({
                    url: "{{ route('products.update', ':id') }}".replace(':id', currentProductId),
                    type: 'PUT',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        name: $('#edit_name').val(),
                        unit_price: $('#edit_unit_price').val(),
                        quantity: $('#edit_quantity').val(),
                        category_id: $('#edit_category_id').val(),
                    },
                    success: function(response) {
                        if (response.status) {
                            $('#editModal').modal('hide');
                            ToastMessage.fire({
                                icon: 'success',
                                text: response.message
                            });

                            // Recargar la página después de 1 segundo
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            ToastMessage.fire({
                                icon: 'error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            // Errores de validación
                            const errors = xhr.responseJSON.errors;

                            Object.keys(errors).forEach(function(field) {
                                $(`#edit_${field}`).addClass('is-invalid');
                                $(`#edit_${field}_error`).text(errors[field][0]);
                            });
                        } else {
                            console.error('Error:', xhr);
                            ToastMessage.fire({
                                icon: 'error',
                                text: 'Error al actualizar el producto'
                            });
                        }
                    },
                    complete: function() {
                        // Ocultar loading
                        saveBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Eliminar producto
            $('.delete-product-btn').on('click', function() {
                currentProductId = $(this).data('id');
                const productName = $(this).data('name');

                $('#delete_product_name').text(productName);
                $('#deleteModal').modal('show');
            });

            // Confirmar eliminación
            $('#confirmDeleteBtn').on('click', function() {
                const deleteBtn = $(this);
                const spinner = deleteBtn.find('.spinner-border');

                // Mostrar loading
                deleteBtn.prop('disabled', true);
                spinner.removeClass('d-none');

                $.ajax({
                    url: "{{ route('products.destroy', ':id') }}".replace(':id', currentProductId),
                    type: 'DELETE',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        $('#deleteModal').modal('hide');

                        if (response.status) {
                            ToastMessage.fire({
                                icon: 'success',
                                text: response.message
                            });

                            // Recargar la página después de 1 segundo
                            setTimeout(function() {
                                location.reload();
                            }, 1000);
                        } else {
                            ToastMessage.fire({
                                icon: 'error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        console.error('Error:', xhr);
                        const message = xhr.responseJSON?.message ||
                            'Error al eliminar el producto';
                        ToastMessage.fire({
                            icon: 'error',
                            text: message
                        });
                    },
                    complete: function() {
                        // Ocultar loading
                        deleteBtn.prop('disabled', false);
                        spinner.addClass('d-none');
                    }
                });
            });

            // Limpiar modales al cerrar
            $('#editModal').on('hidden.bs.modal', function() {
                $('#editProductForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                currentProductId = null;
            });

            $('#deleteModal').on('hidden.bs.modal', function() {
                currentProductId = null;
            });
        });
    </script>
@endsection
