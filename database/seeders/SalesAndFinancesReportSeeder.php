<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SalesAndFinancesReportSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Obtener el módulo "Reportes"
        $module = DB::table('modules')->where('name', 'Reportes')->first();

        if (!$module) {
            $this->command->error("No se encontró el módulo 'Reportes'. Por favor, ejecuta primero ReportesMenuSeeder.");
            return;
        }

        // 2. Vista por defecto (requerida por menu_option)
        $defaultView = DB::table('views')->first();
        $viewId = $defaultView ? $defaultView->id : 1;

        // 3. Registrar la nueva opción
        $optionName = 'Ventas y Finanzas';
        $exists = DB::table('menu_option')
            ->where('name', $optionName)
            ->where('module_id', $module->id)
            ->exists();

        if (!$exists) {
            DB::table('menu_option')->insert([
                'name'         => $optionName,
                'icon'         => 'ri-funds-line',
                'action'       => 'reports.sales_and_finances',
                'view_id'      => $viewId,
                'module_id'    => $module->id,
                'status'       => 1,
                'quick_access' => false,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);
            $this->command->info("✅ Opción '$optionName' registrada con éxito.");
        } else {
            $this->command->info("ℹ️ La opción '$optionName' ya está registrada.");
        }
    }
}
