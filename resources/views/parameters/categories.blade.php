@extends('layouts.app')
@section('content')
    <x-common.page-breadcrumb pageTitle="Categorias de parametros"/>
    <div class="min-h-screen rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03] xl:px-10 xl:py-12">
        <div class="mx-auto w-full">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-theme-xl dark:text-white/90 sm:text-2xl">Categorias de parametros</h3>
            </div>
        </div>
        <x-ui.button size="sm" variant="primary" @click="$dispatch('open-modal')">
            Crear Modulo
        </x-ui.button>
    </div>

@endsection