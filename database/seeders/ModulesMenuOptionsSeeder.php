<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ModulesMenuOptionsSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Creando vistas...');
        
        // Primero creamos algunas vistas básicas
        $views = [
            [
                'name' => 'Dashboard',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Listado',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Formulario',
                'status' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $viewIds = [];
        foreach ($views as $view) {
            // Verificar si ya existe
            $existing = DB::table('views')->where('name', $view['name'])->first();
            if ($existing) {
                $viewIds[$view['name']] = $existing->id;
                $this->command->info("  ✓ Vista '{$view['name']}' ya existe (ID: {$existing->id})");
            } else {
                $id = DB::table('views')->insertGetId($view);
                $viewIds[$view['name']] = $id;
                $this->command->info("  ✓ Vista '{$view['name']}' creada (ID: {$id})");
            }
        }

        $this->command->info('Creando módulos...');
        
        // Luego creamos los módulos
        $modules = [
            [
                'name' => 'Dashboard',
                'icon' => 'mdi-view-dashboard',
                'order_num' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Configuración',
                'icon' => 'mdi-cog',
                'order_num' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Reportes',
                'icon' => 'mdi-chart-bar',
                'order_num' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $moduleIds = [];
        foreach ($modules as $module) {
            // Verificar si ya existe
            $existing = DB::table('modules')->where('name', $module['name'])->first();
            if ($existing) {
                $moduleIds[$module['name']] = $existing->id;
                $this->command->info("  ✓ Módulo '{$module['name']}' ya existe (ID: {$existing->id})");
            } else {
                $id = DB::table('modules')->insertGetId($module);
                $moduleIds[$module['name']] = $id;
                $this->command->info("  ✓ Módulo '{$module['name']}' creado (ID: {$id})");
            }
        }

        $this->command->info('Creando opciones de menú...');
        
        // Finalmente creamos las opciones de menú
        $menuOptions = [
            [
                'name' => 'Inicio',
                'icon' => 'mdi-home',
                'action' => 'dashboard.index',
                'view_id' => $viewIds['Dashboard'],
                'module_id' => $moduleIds['Dashboard'],
                'status' => 1,
                'quick_access' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Módulos',
                'icon' => 'mdi-view-module',
                'action' => 'modules.index',
                'view_id' => $viewIds['Listado'],
                'module_id' => $moduleIds['Configuración'],
                'status' => 1,
                'quick_access' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Empresas',
                'icon' => 'mdi-domain',
                'action' => 'companies.index',
                'view_id' => $viewIds['Listado'],
                'module_id' => $moduleIds['Configuración'],
                'status' => 1,
                'quick_access' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Ventas',
                'icon' => 'mdi-cash-register',
                'action' => 'sales.index',
                'view_id' => $viewIds['Listado'],
                'module_id' => $moduleIds['Reportes'],
                'status' => 1,
                'quick_access' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        $inserted = 0;
        foreach ($menuOptions as $menuOption) {
            // Verificar si ya existe
            $existing = DB::table('menu_option')
                ->where('name', $menuOption['name'])
                ->where('module_id', $menuOption['module_id'])
                ->first();
            
            if ($existing) {
                $this->command->info("  ✓ Opción de menú '{$menuOption['name']}' ya existe (ID: {$existing->id})");
            } else {
                DB::table('menu_option')->insert($menuOption);
                $inserted++;
                $this->command->info("  ✓ Opción de menú '{$menuOption['name']}' creada");
            }
        }

        $this->command->info("✅ Seeder completado. {$inserted} opciones de menú nuevas insertadas.");
    }
}
