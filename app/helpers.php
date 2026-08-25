<?php

if (!function_exists('effective_branch_id')) {
    /**
     * Devuelve el branch_id a usar para filtrar listados.
     * Si el usuario es Administrador de sistema, devuelve null (ve todo).
     * Si no, devuelve el branch_id de la sesión.
     */
    function effective_branch_id(): ?int
    {
        $user = auth()->user();
        if ($user && $user->isSystemAdmin()) {
            return null;
        }
        $id = session('branch_id');
        return $id !== null ? (int) $id : null;
    }
}

if (!function_exists('current_user_is_mozo')) {
    /**
     * Indica si el perfil actual en sesión corresponde al perfil Mozo.
     */
    function current_user_is_mozo(): bool
    {
        $profileId = session('profile_id') ?? auth()->user()?->profile_id;
        $resolved = $profileId !== null && $profileId !== '' ? (int) $profileId : null;

        return \App\Models\Profile::userHasMozoProfile($resolved);
    }
}

if (!function_exists('effective_cash_register_id')) {
    /**
     * Devuelve la caja seleccionada en sesión si pertenece a la sucursal actual.
     */
    function effective_cash_register_id(?int $branchId = null): ?int
    {
        $branchId = $branchId ?? effective_branch_id() ?? (session('branch_id') !== null ? (int) session('branch_id') : null);
        $cashRegisterId = session('cash_register_id');

        if ($cashRegisterId === null) {
            return null;
        }

        $cashRegisterId = (int) $cashRegisterId;
        if ($cashRegisterId <= 0) {
            return null;
        }

        $query = \App\Models\CashRegister::query()->where('id', $cashRegisterId);
        if ($branchId !== null) {
            $query->where('branch_id', $branchId);
        }

        return $query->exists() ? $cashRegisterId : null;
    }
}

if (!function_exists('cash_register_selection_required')) {
    /**
     * Indica si la sucursal actual tiene cajas y aún no hay una seleccionada en sesión.
     */
    function cash_register_selection_required(?int $branchId = null): bool
    {
        if (current_user_is_mozo()) {
            return false;
        }

        $branchId = $branchId ?? effective_branch_id() ?? (session('branch_id') !== null ? (int) session('branch_id') : null);

        if ($branchId === null) {
            return false;
        }

        $hasRegisters = \App\Models\CashRegister::query()
            ->where('branch_id', $branchId)
            ->exists();

        if (! $hasRegisters) {
            return false;
        }

        return effective_cash_register_id($branchId) === null;
    }
}

if (!function_exists('effective_default_sale_document_type_id')) {
    /**
     * Resuelve el tipo de documento por defecto para ventas/cobros en la sucursal.
     * Prioriza branch_parameters -> parameters.value -> fallback id 5.
     */
    function effective_default_sale_document_type_id(?int $branchId = null, array $movementTypeIds = [2]): ?int
    {
        $branchId = $branchId ?? effective_branch_id() ?? (session('branch_id') !== null ? (int) session('branch_id') : null);

        $parameterQuery = \Illuminate\Support\Facades\DB::table('parameters')
            ->whereNull('parameters.deleted_at')
            ->where(function ($query) {
                $query->whereRaw('LOWER(TRIM(parameters.description)) = ?', ['tipo de comprobante por defecto'])
                    ->orWhereRaw('LOWER(TRIM(parameters.description)) = ?', ['tipo documento por defecto'])
                    ->orWhereRaw('LOWER(TRIM(parameters.description)) = ?', ['tipo de documento por defecto']);
            });

        $parameter = $parameterQuery->first(['parameters.id', 'parameters.value']);

        $resolvedId = null;
        if ($parameter && $branchId) {
            $branchValue = \Illuminate\Support\Facades\DB::table('branch_parameters')
                ->whereNull('deleted_at')
                ->where('branch_id', $branchId)
                ->where('parameter_id', $parameter->id)
                ->value('value');

            $rawValue = $branchValue !== null && $branchValue !== '' ? $branchValue : $parameter->value;
            if (is_numeric($rawValue)) {
                $resolvedId = (int) $rawValue;
            }
        } elseif ($parameter && is_numeric($parameter->value)) {
            $resolvedId = (int) $parameter->value;
        }

        if (! $resolvedId) {
            $resolvedId = 5;
        }

        $query = \App\Models\DocumentType::query()->where('id', $resolvedId);
        if (! empty($movementTypeIds)) {
            $query->whereIn('movement_type_id', $movementTypeIds);
        }
        if ($query->exists()) {
            return $resolvedId;
        }

        return \App\Models\DocumentType::query()
            ->when(! empty($movementTypeIds), fn ($q) => $q->whereIn('movement_type_id', $movementTypeIds))
            ->orderBy('id')
            ->value('id');
    }
}

if (!function_exists('effective_parameter_value_by_descriptions')) {
    /**
     * Resuelve el valor efectivo de un parámetro buscando por descripción.
     * Prioriza branch_parameters -> parameters.value.
     */
    function effective_parameter_value_by_descriptions(array $descriptions, ?int $branchId = null): ?string
    {
        $normalized = collect($descriptions)
            ->map(fn ($value) => mb_strtolower(trim((string) $value), 'UTF-8'))
            ->filter()
            ->values();

        if ($normalized->isEmpty()) {
            return null;
        }

        $branchId = $branchId ?? effective_branch_id() ?? (session('branch_id') !== null ? (int) session('branch_id') : null);

        $parameter = \Illuminate\Support\Facades\DB::table('parameters')
            ->whereNull('parameters.deleted_at')
            ->where(function ($query) use ($normalized) {
                foreach ($normalized as $description) {
                    $query->orWhereRaw('LOWER(TRIM(parameters.description)) = ?', [$description]);
                }
            })
            ->orderBy('parameters.id')
            ->first(['parameters.id', 'parameters.value']);

        if (! $parameter) {
            return null;
        }

        if ($branchId) {
            $branchValue = \Illuminate\Support\Facades\DB::table('branch_parameters')
                ->whereNull('deleted_at')
                ->where('branch_id', $branchId)
                ->where('parameter_id', $parameter->id)
                ->value('value');

            if ($branchValue !== null) {
                return (string) $branchValue;
            }
        }

        return $parameter->value !== null ? (string) $parameter->value : null;
    }
}

if (!function_exists('ensure_branch_parameter_exists')) {
    /**
     * Garantiza que exista el parametro y su fila por sucursal.
     */
    function ensure_branch_parameter_exists(string $description, string $defaultValue = ''): ?int
    {
        $description = trim($description);
        if ($description === '') {
            return null;
        }

        $db = \Illuminate\Support\Facades\DB::table('parameters');
        $normalizedDescription = mb_strtolower($description, 'UTF-8');
        $now = now();

        $categoryId = \Illuminate\Support\Facades\DB::table('parameter_categories')
            ->where(function ($query) {
                $query->whereRaw('LOWER(description) LIKE ?', ['%sistema%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%config%']);
            })
            ->orderBy('id')
            ->value('id');

        if (! $categoryId) {
            $categoryId = \Illuminate\Support\Facades\DB::table('parameter_categories')->orderBy('id')->value('id');
        }

        if (! $categoryId) {
            $categoryId = \Illuminate\Support\Facades\DB::table('parameter_categories')->insertGetId([
                'description' => 'Configuracion de Sistema',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $parameter = $db
            ->whereRaw('LOWER(TRIM(description)) = ?', [$normalizedDescription])
            ->first();

        if ($parameter) {
            \Illuminate\Support\Facades\DB::table('parameters')
                ->where('id', $parameter->id)
                ->update([
                    'parameter_category_id' => $parameter->parameter_category_id ?: $categoryId,
                    'status' => 1,
                    'deleted_at' => null,
                    'updated_at' => $now,
                ]);

            $parameterId = (int) $parameter->id;
        } else {
            $parameterId = \Illuminate\Support\Facades\DB::table('parameters')->insertGetId([
                'description' => $description,
                'value' => $defaultValue,
                'status' => 1,
                'parameter_category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        \Illuminate\Support\Facades\DB::table('branches')
            ->pluck('id')
            ->each(function ($branchId) use ($parameterId, $defaultValue, $now) {
                $existing = \Illuminate\Support\Facades\DB::table('branch_parameters')
                    ->where('branch_id', (int) $branchId)
                    ->where('parameter_id', $parameterId)
                    ->first();

                if ($existing) {
                    \Illuminate\Support\Facades\DB::table('branch_parameters')
                        ->where('id', $existing->id)
                        ->update([
                            'deleted_at' => null,
                            'updated_at' => $now,
                        ]);

                    return;
                }

                \Illuminate\Support\Facades\DB::table('branch_parameters')->insert([
                    'branch_id' => (int) $branchId,
                    'parameter_id' => $parameterId,
                    'value' => $defaultValue,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            });

        return $parameterId;
    }
}

if (!function_exists('effective_sale_delete_admin_password')) {
    /**
     * Clave de administrador para eliminar ventas.
     */
    function effective_sale_delete_admin_password(?int $branchId = null): ?string
    {
        return effective_parameter_value_by_descriptions([
            'Clave administrador eliminar venta',
            'Clave de administrador para eliminar venta',
            'Contraseña administrador eliminar venta',
            'Password administrador eliminar venta',
        ], $branchId);
    }
}

if (!function_exists('effective_close_table_admin_password')) {
    /**
     * Clave de administrador para cerrar/anular mesas ocupadas.
     */
    function effective_close_table_admin_password(?int $branchId = null): ?string
    {
        ensure_branch_parameter_exists('Clave para cerrar mesa en pedidos', '');

        return effective_parameter_value_by_descriptions([
            'Clave para cerrar mesa en pedidos',
            'Clave administrador cerrar mesa',
            'Clave de administrador para cerrar mesa',
            'Contrasena administrador cerrar mesa',
            'Password administrador cerrar mesa',
        ], $branchId);
    }
}
