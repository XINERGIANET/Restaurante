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

    <x-ui.modal x-data="{ open: false }" @open-modal.window="open = true" @close-modal.window="open = false" :isOpen="false" class="max-w-md">
            <div class="p-6">
                <h3 class="mb-6 text-lg font-semibold text-gray-800 dark:text-white/90">Crear Modulo</h3>
                
                <form id="module-form" class="space-y-4" action="{{ route('admin.modules.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                
                    <div id="error-message" class="mb-4 hidden rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                        <p id="error-text" class="text-sm text-red-600 dark:text-red-400"></p>
                    </div>

                    @if (session('success'))
                        <div class="mb-4 rounded-lg border border-green-300 bg-green-50 p-4 dark:border-green-800 dark:bg-green-900/20">
                            <p class="text-sm text-green-600 dark:text-green-400">{{ session('success') }}</p>
                        </div>
                    @endif
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Nombre del Modulo
                        </label>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Ingrese el nombre" required
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    </div>
                
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">
                            Icono del Modulo
                        </label>
                        <input type="file" name="icon" accept="image/*" required
                            class="focus:border-ring-brand-300 shadow-theme-xs focus:file:ring-brand-300 h-11 w-full overflow-hidden rounded-lg border border-gray-300 bg-transparent text-sm text-gray-500 transition-colors file:mr-5 file:border-collapse file:cursor-pointer file:rounded-l-lg file:border-0 file:border-r file:border-solid file:border-gray-200 file:bg-gray-50 file:py-3 file:pr-3 file:pl-3.5 file:text-sm file:text-gray-700 placeholder:text-gray-400 hover:file:bg-gray-100 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-gray-400 dark:text-white/90 dark:file:border-gray-800 dark:file:bg-white/[0.03] dark:file:text-gray-400 dark:placeholder:text-gray-400" />
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Seleccione una imagen para el icono (PNG, JPG, SVG, etc.)</p>
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
                        <x-ui.button size="sm" variant="primary" type="submit" id="submit-module">
                            Crear Modulo
                        </x-ui.button>
                        <x-ui.button size="sm" variant="outline" type="button" @click="open = false">
                            Cancelar
                        </x-ui.button>
                    </div>
                </form>
            </div>
        </x-ui.modal>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('module-form');
            const errorMessage = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            const submitButton = document.getElementById('submit-module');

            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir el comportamiento por defecto
                
                // Ocultar mensajes anteriores
                errorMessage.classList.add('hidden');
                
                // Deshabilitar el botón de envío
                submitButton.disabled = true;
                submitButton.textContent = 'Creando...';

                // Crear FormData
                const formData = new FormData(form);

                // Enviar petición AJAX
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || form.querySelector('input[name="_token"]').value
                    }
                })
                .then(response => {
                    if (!response.ok && response.status === 422) {
                        return response.json().then(data => {
                            throw { validation: true, data };
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Limpiar el formulario
                        form.reset();
                        
                        // Cerrar el modal y recargar la página
                        setTimeout(() => {
                            window.dispatchEvent(new CustomEvent('close-modal'));
                            setTimeout(() => {
                                window.location.reload();
                            }, 300);
                        }, 500);
                    } else if (data.error) {
                        // Mostrar mensaje de error
                        let errorMsg = data.error;
                        
                        // Si hay errores de validación, mostrarlos
                        if (data.errors) {
                            const errorList = Object.values(data.errors).flat().join(', ');
                            errorMsg = errorList || errorMsg;
                        }
                        
                        errorText.textContent = errorMsg;
                        errorMessage.classList.remove('hidden');
                        
                        // Ocultar el error después de 5 segundos
                        setTimeout(() => {
                            errorMessage.classList.add('hidden');
                        }, 5000);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Manejar errores de validación
                    if (error.validation && error.data) {
                        let errorMsg = error.data.error || 'Error de validación';
                        
                        if (error.data.errors) {
                            const errorList = Object.values(error.data.errors).flat().join(', ');
                            errorMsg = errorList || errorMsg;
                        }
                        
                        errorText.textContent = errorMsg;
                    } else {
                        errorText.textContent = 'Error al procesar la solicitud. Por favor, intente nuevamente.';
                    }
                    
                    errorMessage.classList.remove('hidden');
                    
                    // Ocultar el error después de 5 segundos
                    setTimeout(() => {
                        errorMessage.classList.add('hidden');
                    }, 5000);
                })
                .finally(() => {
                    // Rehabilitar el botón
                    submitButton.disabled = false;
                    submitButton.textContent = 'Crear Modulo';
                });
            });
        });
    </script>
@endsection