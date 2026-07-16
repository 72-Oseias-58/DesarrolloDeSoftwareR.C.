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
                'descripcion' => 'Permite consultar el stock de bebidas',
            ],
            [
                'nombre' => 'Mover stock de bebidas',
                'slug' => 'mover_stock_bebidas',
                'descripcion' => 'Permite registrar movimientos de bebidas',
            ],
        ];

        $rolesPermitidos = DB::table('roles')
            ->whereIn('nombre', [
                'ADMIN',
                'CAJERO',
            ])
            ->pluck('id_rol');

        foreach ($permisos as $permiso) {
            DB::table('permisos')->updateOrInsert(
                ['slug' => $permiso['slug']],
                [
                    'nombre' => $permiso['nombre'],
                    'descripcion' => $permiso['descripcion'],
                    'updated_at' => now(),
                ]
            );

            $idPermiso = DB::table('permisos')
                ->where('slug', $permiso['slug'])
                ->value('id_permiso');

            if (!$idPermiso) {
                continue;
            }

            foreach ($rolesPermitidos as $idRol) {
                DB::table('permiso_rol')->updateOrInsert([
                    'id_rol' => $idRol,
                    'id_permiso' => $idPermiso,
                ]);
            }
        }
    }
}