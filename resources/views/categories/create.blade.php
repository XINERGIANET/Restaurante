@extends('layouts.app')

@section('nav')
    <ul class="nav justify-content-center">
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-primary active" href="{{ route('categories.create') }}">Registro</a>
        </li>
        <li class="nav-item" style="margin: 0 10px 5px 10px;">
            <!-- Margen personalizado: 0 arriba, 20px a los lados, 5px abajo -->
            <a class="nav-link btn btn-secondary" href="{{ route('categories.index') }}">Historico</a>
        </li>
    </ul>
@endsection

@section('header')
    <h1>Registro Categoría Producto</h1>
    <p>Registrar una nueva categoría de producto</p>
@endsection

@section('content')
    <div class="container-fluid content-inner mt-n5 py-0">
        <div class="card shadow">
            <div class="card-body">
                <form id="formRegistro" action="{{ route('categories.store') }}" method="POST">
                    @csrf
                    <!-- Campo Nombre -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label">Nombre de la categoría</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="printer" class="form-label">Nombre de la impresora</label>
                            <select name="printer" class="form-select" id="printer" required>
                                <option value="">Seleccione una impresora</option>
                                <option value="Ticketera">Ticketera Mozo</option>
                                <option value="BARRA">Ticketera Caja</option>
                                <option value="COCINA">Ticketera Cocina</option>
                            </select>
                        </div>
                    </div>
                    <!-- Botones -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    Guardar
                                </button>
                            </div>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </div>
@endsection
