<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando módulos...');
        
        // Array solo con los módulos
        $modules = [
            [
                'name' => 'Herramientas de administración',
                'icon' => 'assistant',
                'order_num' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Dashboard',
                'icon' => 'dashboard',
                'order_num' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Pedidos',
                'icon' => 'task',
                'order_num' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ventas',
                'icon' => 'ecommerce',
                'order_num' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Compras',
                'icon' => 'forms',
                'order_num' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Almacen',
                'icon' => 'pages',
                'order_num' => 6,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Caja',
                'icon' => 'support-ticket',
                'order_num' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Configuración',
                'icon' => 'config',
                'order_num' => 8,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $inserted = 0;

        foreach ($modules as $module) {
            $existing = DB::table('modules')->where('name', $module['name'])->first();

            if ($existing) {
                $this->command->info("  ✓ Módulo '{$module['name']}' ya existe (ID: {$existing->id})");
            } else {
                DB::table('modules')->insert($module);
                $inserted++;
                $this->command->info("  ✓ Módulo '{$module['name']}' creado exitosamente");
            }
        }

        $this->command->info("✅ Proceso finalizado. {$inserted} módulos nuevos insertados.");
    }
}