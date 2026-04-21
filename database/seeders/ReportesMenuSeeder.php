<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportesMenuSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando módulo y opciones de Reportes...');

        // 1. Módulo "Reportes"
        $existing = DB::table('modules')->where('name', 'Reportes')->first();

        if ($existing) {
            $moduleId = $existing->id;
            DB::table('modules')->where('id', $moduleId)->update([
                'icon'       => 'ri-bar-chart-box-line',
                'order_num'  => 9,
                'updated_at' => now(),
            ]);
            $this->command->info("  ✔ Módulo 'Reportes' ya existe (ID: $moduleId), actualizado.");
        } else {
            $moduleId = DB::table('modules')->insertGetId([
                'name'       => 'Reportes',
                'icon'       => 'ri-bar-chart-box-line',
                'order_num'  => 9,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->command->info("  ✔ Módulo 'Reportes' creado (ID: $moduleId).");
        }

        // 2. Vista por defecto (requerida por FK de menu_option)
        $defaultView = DB::table('views')->first();
        $viewId = $defaultView
            ? $defaultView->id
            : DB::table('views')->insertGetId([
                'name'       => 'Vista General',
                'status'     => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        // 3. Opciones de menú
        $options = [
            [
                'name'   => 'Consolidado de productos',
                'action' => 'reports.consolidated_products',
                'icon'   => 'ri-file-chart-line',
            ],
            [
                'name'   => 'Método de Pago',
                'action' => 'reports.payment_method',
                'icon'   => 'ri-bank-card-line',
            ],
            [
                'name'   => 'Ventas por Cliente',
                'action' => 'reports.sales_by_customer',
                'icon'   => 'ri-user-star-line',
            ],
        ];

        $inserted = 0;

        foreach ($options as $opt) {
            $exists = DB::table('menu_option')
                ->where('name', $opt['name'])
                ->where('module_id', $moduleId)
                ->exists();

            if (!$exists) {
                DB::table('menu_option')->insert([
                    'name'         => $opt['name'],
                    'icon'         => $opt['icon'],
                    'action'       => $opt['action'],
                    'view_id'      => $viewId,
                    'module_id'    => $moduleId,
                    'status'       => 1,
                    'quick_access' => false,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
                $inserted++;
                $this->command->info("    + Opción '{$opt['name']}' creada.");
            } else {
                $this->command->info("    ~ Opción '{$opt['name']}' ya existe, omitida.");
            }
        }

        $this->command->info("✅ Seeder finalizado. $inserted opciones de menú insertadas.");
    }
}
