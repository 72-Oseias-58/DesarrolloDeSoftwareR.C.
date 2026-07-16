<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosMovimientosCarneSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            [
                'nombre' => 'Ver movimientos de carne',
                'slug' => 'ver_movimientos_carne',
                'descripcion' => 'Permite consultar entradas, salidas, ajustes, ventas y mermas de carne.',
            ],
            [
                'nombre' => 'Registrar movimientos de carne',
                'slug' => 'registrar_movimientos_carne',
                'descripcion' => 'Permite registrar llegadas, salidas, ajustes y mermas de carne.',
            ],
        ];

        $idRolAdmin = DB::table('roles')
            ->where('nombre', 'ADMIN')
            ->value('id_rol');

        if (!$idRolAdmin) {
            return;
        }

        foreach ($permisos as $permiso) {
            DB::table('permisos')->updateOrInsert(
                ['slug' => $permiso['slug']],
                $permiso
            );

            $idPermiso = DB::table('permisos')
                ->where('slug', $permiso['slug'])
                ->value('id_permiso');

            DB::table('permiso_rol')->updateOrInsert([
                'id_rol' => $idRolAdmin,
                'id_permiso' => $idPermiso,
            ]);
        }
    }
}