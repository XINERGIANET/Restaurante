<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('parameter_categories')
            ->where(function ($query) {
                $query->whereRaw('LOWER(description) LIKE ?', ['%sistema%'])
                    ->orWhereRaw('LOWER(description) LIKE ?', ['%config%']);
            })
            ->orderBy('id')
            ->value('id');

        if (! $categoryId) {
            $categoryId = DB::table('parameter_categories')->orderBy('id')->value('id');
        }

        if (! $categoryId) {
            $categoryId = DB::table('parameter_categories')->insertGetId([
                'description' => 'Configuracion de Sistema',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $parameter = DB::table('parameters')
            ->whereRaw('LOWER(TRIM(description)) = ?', ['clave administrador eliminar venta'])
            ->first();

        $now = now();

        if ($parameter) {
            DB::table('parameters')
                ->where('id', $parameter->id)
                ->update([
                    'parameter_category_id' => $parameter->parameter_category_id ?: $categoryId,
                    'status' => 1,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ]);

            $parameterId = (int) $parameter->id;
        } else {
            $parameterId = DB::table('parameters')->insertGetId([
                'description' => 'Clave administrador eliminar venta',
                'value' => '',
                'status' => 1,
                'parameter_category_id' => $categoryId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('branches')->pluck('id')->each(function ($branchId) use ($parameterId, $now) {
            $existing = DB::table('branch_parameters')
                ->where('branch_id', (int) $branchId)
                ->where('parameter_id', $parameterId)
                ->first();

            if ($existing) {
                DB::table('branch_parameters')
                    ->where('id', $existing->id)
                    ->update([
                        'deleted_at' => null,
                        'updated_at' => $now,
                    ]);

                return;
            }

            DB::table('branch_parameters')->insert([
                'branch_id' => (int) $branchId,
                'parameter_id' => $parameterId,
                'value' => '',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        });
    }

    public function down(): void
    {
        $parameterId = DB::table('parameters')
            ->whereRaw('LOWER(TRIM(description)) = ?', ['clave administrador eliminar venta'])
            ->value('id');

        if (! $parameterId) {
            return;
        }

        DB::table('branch_parameters')->where('parameter_id', $parameterId)->delete();
        DB::table('parameters')->where('id', $parameterId)->delete();
    }
};
