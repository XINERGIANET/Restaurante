@php
    $selectedDepartmentId = old('department_id', $selectedDepartmentId ?? null);
    $selectedProvinceId = old('province_id', $selectedProvinceId ?? null);
    $selectedDistrictId = old('location_id', $selectedDistrictId ?? ($person->location_id ?? null));
@endphp

<div
    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
    data-departments='@json($departments ?? [], JSON_HEX_APOS | JSON_HEX_QUOT)'
    data-provinces='@json($provinces ?? [], JSON_HEX_APOS | JSON_HEX_QUOT)'
    data-districts='@json($districts ?? [], JSON_HEX_APOS | JSON_HEX_QUOT)'
    data-department-id='@json(old('department_id', $selectedDepartmentId ?? null), JSON_HEX_APOS | JSON_HEX_QUOT)'
    data-province-id='@json(old('province_id', $selectedProvinceId ?? null), JSON_HEX_APOS | JSON_HEX_QUOT)'
    data-district-id='@json(old('location_id', $selectedDistrictId ?? ($person->location_id ?? null)), JSON_HEX_APOS | JSON_HEX_QUOT)'
    x-data="{
        departments: JSON.parse($el.dataset.departments || '[]'),
        provinces: JSON.parse($el.dataset.provinces || '[]'),
        districts: JSON.parse($el.dataset.districts || '[]'),
        departmentId: JSON.parse($el.dataset.departmentId || 'null') || '',
        provinceId: JSON.parse($el.dataset.provinceId || 'null') || '',
        districtId: JSON.parse($el.dataset.districtId || 'null') || '',
        init() {
            if (!this.provinceId && this.districtId) {
                const district = this.districts.find(d => d.id == this.districtId);
                if (district) {
                    this.provinceId = district.parent_location_id ?? '';
                }
            }
            if (!this.departmentId && this.provinceId) {
                const province = this.provinces.find(p => p.id == this.provinceId);
                if (province) {
                    this.departmentId = province.parent_location_id ?? '';
                }
            }
        },
        get filteredProvinces() {
            return this.provinces.filter(p => p.parent_location_id == this.departmentId);
        },
        get filteredDistricts() {
            return this.districts.filter(d => d.parent_location_id == this.provinceId);
        },
        onDepartmentChange() {
            this.provinceId = '';
            this.districtId = '';
        },
        onProvinceChange() {
            this.districtId = '';
        }
    }"
    x-init="init()"
>
    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Nombres</label>
        <input
            type="text"
            name="first_name"
            value="{{ old('first_name', $person->first_name ?? '') }}"
            required
            placeholder="Ingrese los nombres"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('first_name')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Apellidos</label>
        <input
            type="text"
            name="last_name"
            value="{{ old('last_name', $person->last_name ?? '') }}"
            required
            placeholder="Ingrese los apellidos"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('last_name')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Fecha de nacimiento</label>
        <input
            type="date"
            name="fecha_nacimiento"
            value="{{ old('fecha_nacimiento', $person->fecha_nacimiento ?? '') }}"
            onclick="this.showPicker && this.showPicker()"
            onfocus="this.showPicker && this.showPicker()"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        />
        @error('fecha_nacimiento')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Genero</label>
        <select
            name="genero"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione genero</option>
            <option value="MASCULINO" @selected(old('genero', $person->genero ?? '') === 'MASCULINO')>MASCULINO</option>
            <option value="FEMENINO" @selected(old('genero', $person->genero ?? '') === 'FEMENINO')>FEMENINO</option>
            <option value="OTRO" @selected(old('genero', $person->genero ?? '') === 'OTRO')>OTRO</option>
        </select>
        @error('genero')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Tipo de persona</label>
        <select
            name="person_type"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione tipo</option>
            <option value="DNI" @selected(old('person_type', $person->person_type ?? '') === 'DNI')>DNI</option>
            <option value="RUC" @selected(old('person_type', $person->person_type ?? '') === 'RUC')>RUC</option>
            <option value="CARNET DE EXTRANGERIA" @selected(old('person_type', $person->person_type ?? '') === 'CARNET DE EXTRANGERIA')>CARNET DE EXTRANGERIA</option>
            <option value="PASAPORTE" @selected(old('person_type', $person->person_type ?? '') === 'PASAPORTE')>PASAPORTE</option>
        </select>
        @error('person_type')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Documento</label>
        <input
            type="text"
            name="document_number"
            value="{{ old('document_number', $person->document_number ?? '') }}"
            required
            placeholder="Ingrese el documento"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('document_number')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Telefono</label>
        <input
            type="text"
            name="phone"
            value="{{ old('phone', $person->phone ?? '') }}"
            required
            placeholder="Ingrese el telefono"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('phone')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Email</label>
        <input
            type="email"
            name="email"
            value="{{ old('email', $person->email ?? '') }}"
            required
            placeholder="Ingrese el email"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('email')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-1 ">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Direccion</label>
        <input
            type="text"
            name="address"
            value="{{ old('address', $person->address ?? '') }}"
            required
            placeholder="Ingrese la direccion"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30"
        />
        @error('address')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>

    <div class="sm:col-span-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Departamento</label>
        <select
            name="department_id"
            x-model="departmentId"
            @change="onDepartmentChange()"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione departamento</option>
            <template x-for="department in departments" :key="department.id">
                <option :value="department.id" :selected="department.id == departmentId" x-text="department.name"></option>
            </template>
        </select>
    </div>

    <div class="sm:col-span-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Provincia</label>
        <select
            name="province_id"
            x-model="provinceId"
            @change="onProvinceChange()"
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione provincia</option>
            <template x-for="province in filteredProvinces" :key="province.id">
                <option :value="province.id" :selected="province.id == provinceId" x-text="province.name"></option>
            </template>
        </select>
    </div>

    <div class="sm:col-span-1">
        <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-400">Distrito</label>
        <select
            name="location_id"
            x-model="districtId"
            required
            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90"
        >
            <option value="">Seleccione distrito</option>
            <template x-for="district in filteredDistricts" :key="district.id">
                <option :value="district.id" :selected="district.id == districtId" x-text="district.name"></option>
            </template>
        </select>
        @error('location_id')
            <p class="mt-1 text-xs text-error-500">{{ $message }}</p>
        @enderror
    </div>
</div>
