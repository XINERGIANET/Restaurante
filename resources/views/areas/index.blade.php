@extends('layouts.app')

@section('nav')
    <ul class="nav justify-content-center">
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-primary active" href="{{ route('areas.create') }}">Registro</a>
        </li>
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-secondary" href="{{ route('areas.index') }}">Histórico</a>
        </li>
    </ul>
@endsection

@section('header')
    <h1>Lista de Áreas</h1>
    <p>Listado de áreas</p>
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
                                <th>Nombre del Área</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($areas as $area)
                                <tr>
                                    <td>{{ ($areas->currentPage() - 1) * $areas->perPage() + $loop->iteration }}</td>
                                    <td>{{ $area->name }}</td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-area-btn"
                                            data-id="{{ $area->id }}">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-area-btn"
                                            data-id="{{ $area->id }}" data-name="{{ $area->name }}">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center">No hay áreas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-center mt-3">
                    {{ $areas->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Editar -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="editAreaForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Área</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="edit_name" class="form-label">Nombre del Área</label>
                                    <input type="text" class="form-control" id="edit_name" name="name" required>
                                    <div class="invalid-feedback" id="edit_name_error"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
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
                    <h5 class="modal-title">Eliminar Área</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el área <strong id="delete_area_name"></strong>?</p>
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
            let currentAreaId = null;

            // SweetAlert2 Toast Configuration
            const ToastMessage = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });

            // Editar área
            $('.edit-area-btn').on('click', function() {
                currentAreaId = $(this).data('id');

                // Limpiar formulario
                $('#editAreaForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');

                // Obtener datos del área
                $.ajax({
                    url: "{{ route('areas.edit', ':id') }}".replace(':id', currentAreaId),
                    type: 'GET',
                    success: function(response) {
                        if (response.status) {
                            const area = response.data;
                            $('#edit_name').val(area.name);
                            $('#editModal').modal('show');
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
                            text: 'Error al obtener datos del área'
                        });
                    }
                });
            });

            // Actualizar área
            $('#editAreaForm').on('submit', function(e) {
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
                    url: "{{ route('areas.update', ':id') }}".replace(':id', currentAreaId),
                    type: 'PUT',
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content'),
                        name: $('#edit_name').val()
                    },
                    success: function(response) {
                        if (response.status) {
                            $('#editModal').modal('hide');
                            ToastMessage.fire({
                                icon: 'success',
                                text: response.message
                            });
                            location.reload(); // Recargar la página
                        } else {
                            ToastMessage.fire({
                                icon: 'error',
                                text: response.message
                            });
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            // Marcar inputs como inválidos
                            Object.keys(errors).forEach(field => {
                                $(`#edit_${field}`).addClass('is-invalid');
                                $(`#edit_${field}_error`).text(errors[field][0]);
                            });
                        } else {
                            console.error('Error:', xhr);
                            ToastMessage.fire({
                                icon: 'error',
                                text: 'Error al actualizar el área'
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

            // Eliminar área
            $('.delete-area-btn').on('click', function() {
                currentAreaId = $(this).data('id');
                const areaName = $(this).data('name');

                $('#delete_area_name').text(areaName);
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
                    url: "{{ route('areas.destroy', ':id') }}".replace(':id', currentAreaId),
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
                            'Error al eliminar el área';
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
                $('#editAreaForm')[0].reset();
                $('.is-invalid').removeClass('is-invalid');
                $('.invalid-feedback').text('');
                currentAreaId = null;
            });

            $('#deleteModal').on('hidden.bs.modal', function() {
                currentAreaId = null;
            });
        });
    </script>
@endsection