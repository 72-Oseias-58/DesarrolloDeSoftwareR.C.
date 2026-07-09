<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosCatalogoSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | PERMISOS DEL CATÁLOGO DE PEDIDOS
        |--------------------------------------------------------------------------
        */

        $permisos = [
            [
                'nombre' => 'Ver catálogo de pedidos',
                'slug' => 'ver_catalogo_pedidos',
            ],
            [
                'nombre' => 'Crear platos del catálogo',
                'slug' => 'crear_platos_catalogo',
            ],
            [
                'nombre' => 'Crear bebidas del catálogo',
                'slug' => 'crear_bebidas_catalogo',
            ],
            [
                'nombre' => 'Editar catálogo de pedidos',
                'slug' => 'editar_catalogo_pedidos',
            ],
            [
                'nombre' => 'Eliminar productos del catálogo',
                'slug' => 'eliminar_catalogo_pedidos',
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

            /*
            |--------------------------------------------------------------------------
            | ASIGNAR POR DEFECTO A ADMIN Y CAJERO
            |--------------------------------------------------------------------------
            | ADMIN luego puede quitar estos permisos a cajeros específicos.
            |--------------------------------------------------------------------------
            */

            $rolesPermitidos = [
                2, // ADMIN
                3, // CAJERO
            ];

            foreach ($rolesPermitidos as $idRol) {
                $existePermisoRol = DB::table('permiso_rol')
                    ->where('id_rol', $idRol)
                    ->where('id_permiso', $idPermiso)
                    ->exists();

                if (!$existePermisoRol) {
                    DB::table('permiso_rol')->insert([
                        'id_rol' => $idRol,
                        'id_permiso' => $idPermiso,
                    ]);
                }
            }
        }
    }
}