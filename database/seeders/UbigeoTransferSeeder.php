<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UbigeoTransferSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('Sincronizando ubigeo → locations (PostgreSQL → PostgreSQL)');

        $source = DB::connection('pgsql_local');
        $target = DB::connection('pgsql');

        $target->transaction(function () use ($source, $target) {

            // Mapa ubigeo.id → locations.id
            $idMap = [];

            // 1️⃣ Departamentos
            $departamentos = $source->table('ubigeo')->where('tipo', 2)->get();

            foreach ($departamentos as $dep) {
                $location = $target->table('locations')->where([
                    ['code', '=', $dep->codigo],
                    ['type', '=', 'department'],
                ])->first();

                if (!$location) {
                    $id = $target->table('locations')->insertGetId([
                        'name' => $dep->descripcion,
                        'code' => $dep->codigo,
                        'type' => 'department',
                        'parent_location_id' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $id = $location->id;
                }

                $idMap[$dep->id] = $id;
            }

            // 2️⃣ Provincias
            $provincias = $source->table('ubigeo')->where('tipo', 3)->get();

            foreach ($provincias as $prov) {
                $location = $target->table('locations')->where([
                    ['code', '=', $prov->codigo],
                    ['type', '=', 'province'],
                ])->first();

                if (!$location) {
                    $id = $target->table('locations')->insertGetId([
                        'name' => $prov->descripcion,
                        'code' => $prov->codigo,
                        'type' => 'province',
                        'parent_location_id' => $idMap[$prov->ubigeo_id] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    $id = $location->id;
                }

                $idMap[$prov->id] = $id;
            }

            // 3️⃣ Distritos
            $distritos = $source->table('ubigeo')->where('tipo', 4)->get();

            foreach ($distritos as $dist) {
                $exists = $target->table('locations')->where([
                    ['code', '=', $dist->codigo],
                    ['type', '=', 'district'],
                ])->exists();

                if (!$exists) {
                    $target->table('locations')->insert([
                        'name' => $dist->descripcion,
                        'code' => $dist->codigo,
                        'type' => 'district',
                        'parent_location_id' => $idMap[$dist->ubigeo_id] ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->command->info('✅ Sincronización completada sin romper claves foráneas.');
    }
}
