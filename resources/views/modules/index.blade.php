@extends('layouts.app')

@section('content')
    <x-common.page-breadcrumb pageTitle="{{ $title ?? 'Modulos' }}" />
    <div class="min-h-screen rounded-2xl border border-gray-200 bg-white px-5 py-7 dark:border-gray-800 dark:bg-white/[0.03] xl:px-10 xl:py-12">
        <div class="mx-auto w-full">
            <div class="mb-6 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 text-theme-xl dark:text-white/90 sm:text-2xl">{{ $title ?? 'Modulos' }}</h3>
                <x-ui.button size="sm" variant="primary" @click="$dispatch('open-modal')">
                    Crear Modulo
                </x-ui.button>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="p-6 text-center">
                    <p class="text-sm text-gray-500 dark:text-gray-400 sm:text-base">
                        Lista de módulos. Aquí puedes agregar una tabla con los módulos existentes.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <x-ui.modal x-data="{ open: false }" @open-modal.window="open = true" :isOpen="false" class="max-w-md">
            <div class="p-6">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Crear Modulo</h3>
                
                <form class="space-y-4" action="{{ route('admin.modules.store') }}" method="POST">
                    @csrf
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Nombre del Modulo
                        </label>
                        <input type="text" name="name" placeholder="Ingrese el nombre"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Icono del Modulo
                        </label>
                        <input type="file" name="icon" placeholder="Ingrese el icono"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Orden del Modulo
                        </label>
                        <input type="number" name="order_num" placeholder="Ingrese el orden"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>

                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Menu del Modulo
                        </label>
                        <div x-data="{ isOptionSelected: false }" class="relative z-20 bg-transparent">
                            <select name="menu_id"
                                class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full appearance-none rounded-lg border border-gray-300 bg-transparent bg-none px-4 py-2.5 pr-11 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
                                :class="isOptionSelected && 'text-gray-800 dark:text-white/90'" @change="isOptionSelected = true">
                                <option value="" class="text-gray-700 dark:bg-gray-900 dark:text-gray-400">
                                    Seleccione un menu
                                </option>
                            </select>
                            <span
                                class="pointer-events-none absolute top-1/2 right-4 z-30 -translate-y-1/2 text-gray-500 dark:text-gray-400">
                                <svg class="stroke-current" width="20" height="20" viewBox="0 0 20 20" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path d="M4.79175 7.396L10.0001 12.6043L15.2084 7.396" stroke="" stroke-width="1.5"
                                        stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </span>
                        </div>
                    </div>

                    <div class="flex gap-3 pt-4">
                        <x-ui.button size="sm" variant="primary" type="submit">
                            Crear Modulo
                        </x-ui.button>
                        <x-ui.button size="sm" variant="outline" type="button" @click="open = false">
                            Cancelar
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>
@endsection
