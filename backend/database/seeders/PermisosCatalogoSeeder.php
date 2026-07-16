<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            [
                'nombre' => 'Ver catálogo de productos',
                'slug' => 'ver_catalogo_pedidos',
                'descripcion' => 'Permite consultar platos y bebidas',
            ],
            [
                'nombre' => 'Crear productos del catálogo',
                'slug' => 'crear_catalogo_pedidos',
                'descripcion' => 'Permite crear platos y bebidas',
            ],
            [
                'nombre' => 'Editar catálogo de productos',
                'slug' => 'editar_catalogo_pedidos',
                'descripcion' => 'Permite editar platos y bebidas',
            ],
            [
                'nombre' => 'Eliminar productos del catálogo',
                'slug' => 'eliminar_catalogo_pedidos',
                'descripcion' => 'Permite eliminar productos sin ventas',
            ],
        ];

        $idRolAdmin = DB::table('roles')
            ->where('nombre', 'ADMIN')
            ->value('id_rol');

        $idRolCajero = DB::table('roles')
            ->where('nombre', 'CAJERO')
            ->value('id_rol');

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

            if ($idRolAdmin) {
                DB::table('permiso_rol')->updateOrInsert([
                    'id_rol' => $idRolAdmin,
                    'id_permiso' => $idPermiso,
                ]);
            }

            if ($idRolCajero) {
                DB::table('permiso_rol')
                    ->where('id_rol', $idRolCajero)
                    ->where('id_permiso', $idPermiso)
                    ->delete();
            }
        }

        $idsPermisosAntiguos = DB::table('permisos')
            ->whereIn('slug', [
                'crear_platos_catalogo',
                'crear_bebidas_catalogo',
            ])
            ->pluck('id_permiso');

        if (
            $idRolCajero
            && $idsPermisosAntiguos->isNotEmpty()
        ) {
            DB::table('permiso_rol')
                ->where('id_rol', $idRolCajero)
                ->whereIn(
                    'id_permiso',
                    $idsPermisosAntiguos
                )
                ->delete();
        }
    }
}