<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('thermal_print_jobs') ||
            ! Schema::hasColumn('thermal_print_jobs', 'attempts') ||
            ! Schema::hasColumn('thermal_print_jobs', 'source')) {
            return;
        }

        // Históricamente se contó la preparación como un intento. Retiramos
        // ese incremento base para que solo figuren despachos reales.
        DB::table('thermal_print_jobs')
            ->where('source', 'kitchen_order')
            ->where('attempts', '>', 0)
            ->decrement('attempts');
    }

    public function down(): void
    {
        if (! Schema::hasTable('thermal_print_jobs') ||
            ! Schema::hasColumn('thermal_print_jobs', 'attempts') ||
            ! Schema::hasColumn('thermal_print_jobs', 'source')) {
            return;
        }

        DB::table('thermal_print_jobs')
            ->where('source', 'kitchen_order')
            ->increment('attempts');
    }
};
