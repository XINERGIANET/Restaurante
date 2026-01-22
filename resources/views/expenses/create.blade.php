@extends('layouts.app')

@section('header')
    <h1 class="page-title">Egresos</h1>
@endsection


@section('content')
    <div class="content-inner mt-n5 py-0">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Crear Egreso
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive mt-4">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>N°</th>
                                        <th>Monto</th>
                                        <th>Descripción</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($expenses as $expense)
                                        <tr>
                                            <td>{{ ($expenses->currentPage() - 1) * $expenses->perPage() + $loop->iteration }}
                                            </td>
                                            <td>S/. {{ $expense->amount }}</td>
                                            <td>{{ $expense->description }}</td>
                                            <td>{{ $expense->date->format('d/m/Y h:i:s') }}</td>
                                            <td>
                                                <a href="{{ route('expenses.edit', $expense->id) }}" class="btn btn-sm btn-warning">Editar</a>
                                                
                                                <form action="{{ route('expenses.destroy', $expense->id) }}" method="POST" style="display: inline-block;">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger" 
                                                            onclick="return confirm('¿Estás seguro de eliminar este egreso?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">No hay egresos registrados.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Registrar Egreso</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('expenses.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="amount" class="form-label">Monto</label>
                            <input type="number" class="form-control" step="0.01" id="amount" name="amount"
                                placeholder="Ingrese el monto del egreso">
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Descripción</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Ingrese una descripción"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Aquí puedes agregar scripts específicos para esta vista
    </script>
@endsection
