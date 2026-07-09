<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosStockBebidasSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            [
                'nombre' => 'Ver stock de bebidas',
                'slug' => 'ver_stock_bebidas',
            ],
            [
                'nombre' => 'Mover stock de bebidas',
                'slug' => 'mover_stock_bebidas',
            ],
        ];

        foreach ($permisos as $permiso) {
            $idPermiso = DB::table('permisos')
                ->where('slug', $permiso['slug'])
                ->value('id_permiso');

            if (!$idPermiso) {
                $idPermiso = DB::table('permisos')->insertGetId([
                    'nombre' => $permiso['nombre'],
                    'slug' => $permiso['slug'],
                ]);
            }

            $rolesPermitidos = [
                2, // ADMIN
                3, // CAJERO
            ];

            foreach ($rolesPermitidos as $idRol) {
                $existe = DB::table('permiso_rol')
                    ->where('id_rol', $idRol)
                    ->where('id_permiso', $idPermiso)
                    ->exists();

                if (!$existe) {
                    DB::table('permiso_rol')->insert([
                        'id_rol' => $idRol,
                        'id_permiso' => $idPermiso,
                    ]);
                }
            }
        }
    }
}