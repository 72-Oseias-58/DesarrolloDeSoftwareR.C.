<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            // Sucursales
            [
                'nombre' => 'Ver sucursales',
                'slug' => 'ver_sucursales',
                'descripcion' => 'Permite ver el listado de sucursales',
            ],
            [
                'nombre' => 'Crear sucursales',
                'slug' => 'crear_sucursales',
                'descripcion' => 'Permite registrar nuevas sucursales',
            ],
            [
                'nombre' => 'Editar sucursales',
                'slug' => 'editar_sucursales',
                'descripcion' => 'Permite modificar datos de sucursales',
            ],
            [
                'nombre' => 'Cambiar estado sucursales',
                'slug' => 'cambiar_estado_sucursales',
                'descripcion' => 'Permite activar o inactivar sucursales',
            ],

            // Administradores
            [
                'nombre' => 'Ver administradores',
                'slug' => 'ver_administradores',
                'descripcion' => 'Permite ver administradores',
            ],
            [
                'nombre' => 'Crear administradores',
                'slug' => 'crear_administradores',
                'descripcion' => 'Permite crear administradores',
            ],
            [
                'nombre' => 'Editar administradores',
                'slug' => 'editar_administradores',
                'descripcion' => 'Permite editar administradores',
            ],
            [
                'nombre' => 'Cambiar estado administradores',
                'slug' => 'cambiar_estado_administradores',
                'descripcion' => 'Permite activar o inactivar administradores',
            ],

            // Control de administradores
            [
                'nombre' => 'Gestionar permisos de administradores',
                'slug' => 'gestionar_permisos_admin',
                'descripcion' => 'Permite modificar permisos de usuarios ADMIN',
            ],
            [
                'nombre' => 'Cambiar rol de administrador',
                'slug' => 'cambiar_rol_admin',
                'descripcion' => 'Permite cambiar el rol de un administrador',
            ],
            [
                'nombre' => 'Ascender cajero a administrador',
                'slug' => 'ascender_cajero_admin',
                'descripcion' => 'Permite ascender un CAJERO a ADMIN',
            ],
            [
                'nombre' => 'Degradar administrador a cajero',
                'slug' => 'degradar_admin_cajero',
                'descripcion' => 'Permite degradar un ADMIN a CAJERO',
            ],

            // Empleados
            [
                'nombre' => 'Ver empleados',
                'slug' => 'ver_empleados',
                'descripcion' => 'Permite ver empleados de la sucursal',
            ],
            [
                'nombre' => 'Crear empleados',
                'slug' => 'crear_empleados',
                'descripcion' => 'Permite crear empleados',
            ],
            [
                'nombre' => 'Editar empleados',
                'slug' => 'editar_empleados',
                'descripcion' => 'Permite editar empleados',
            ],
            [
                'nombre' => 'Cambiar estado empleados',
                'slug' => 'cambiar_estado_empleados',
                'descripcion' => 'Permite activar o inactivar empleados',
            ],

            // Control de cajeros
            [
                'nombre' => 'Crear cajeros',
                'slug' => 'crear_cajeros',
                'descripcion' => 'Permite crear usuarios cajeros',
            ],
            [
                'nombre' => 'Gestionar permisos de cajeros',
                'slug' => 'gestionar_permisos_cajero',
                'descripcion' => 'Permite modificar permisos de usuarios CAJERO',
            ],
            [
                'nombre' => 'Ascender empleado a cajero',
                'slug' => 'ascender_empleado_cajero',
                'descripcion' => 'Permite dar acceso de CAJERO a un empleado normal',
            ],
            [
                'nombre' => 'Degradar cajero a empleado',
                'slug' => 'degradar_cajero_empleado',
                'descripcion' => 'Permite quitar acceso de CAJERO a un empleado',
            ],

            // Inventario
            [
                'nombre' => 'Ver inventario',
                'slug' => 'ver_inventario',
                'descripcion' => 'Permite ver inventario',
            ],
            [
                'nombre' => 'Crear inventario',
                'slug' => 'crear_inventario',
                'descripcion' => 'Permite registrar inventario',
            ],
            [
                'nombre' => 'Editar inventario',
                'slug' => 'editar_inventario',
                'descripcion' => 'Permite editar inventario',
            ],

            // Jornadas
            [
                'nombre' => 'Ver jornadas',
                'slug' => 'ver_jornadas',
                'descripcion' => 'Permite ver jornadas',
            ],
            [
                'nombre' => 'Abrir jornada',
                'slug' => 'abrir_jornada',
                'descripcion' => 'Permite abrir jornada del día',
            ],
            [
                'nombre' => 'Cerrar jornada',
                'slug' => 'cerrar_jornada',
                'descripcion' => 'Permite cerrar jornada del día',
            ],

            // Cajas
            [
                'nombre' => 'Ver cajas',
                'slug' => 'ver_cajas',
                'descripcion' => 'Permite ver cajas',
            ],
            [
                'nombre' => 'Abrir caja',
                'slug' => 'abrir_caja',
                'descripcion' => 'Permite abrir caja',
            ],
            [
                'nombre' => 'Cerrar caja',
                'slug' => 'cerrar_caja',
                'descripcion' => 'Permite cerrar caja',
            ],

            // Pedidos y pagos
            [
                'nombre' => 'Ver pedidos',
                'slug' => 'ver_pedidos',
                'descripcion' => 'Permite ver pedidos',
            ],
            [
                'nombre' => 'Crear pedidos',
                'slug' => 'crear_pedidos',
                'descripcion' => 'Permite crear pedidos',
            ],
            [
                'nombre' => 'Registrar pagos',
                'slug' => 'registrar_pagos',
                'descripcion' => 'Permite registrar pagos',
            ],
            [
                'nombre' => 'Reimprimir ticket',
                'slug' => 'reimprimir_ticket',
                'descripcion' => 'Permite reimprimir tickets',
            ],

            // Compras internas
            [
                'nombre' => 'Ver compras internas',
                'slug' => 'ver_compras_internas',
                'descripcion' => 'Permite consultar compras internas de la jornada',
            ],
            [
                'nombre' => 'Registrar compras internas',
                'slug' => 'registrar_compras_internas',
                'descripcion' => 'Permite registrar, finalizar y anular compras internas',
            ],

            // Reportes
            [
                'nombre' => 'Ver reportes',
                'slug' => 'ver_reportes',
                'descripcion' => 'Permite ver reportes de todas las sucursales',
            ],
            [
                'nombre' => 'Crear reportes',
                'slug' => 'crear_reportes',
                'descripcion' => 'Permite generar reportes de la sucursal',
            ],
            [
                'nombre' => 'Ver reportes de jornada',
                'slug' => 'ver_reportes_jornada',
                'descripcion' => 'Permite consultar reportes de cierre de jornada',
            ],

            // Solicitudes
            [
                'nombre' => 'Ver solicitudes',
                'slug' => 'ver_solicitudes',
                'descripcion' => 'Permite ver solicitudes de administradores',
            ],
            [
                'nombre' => 'Crear solicitudes',
                'slug' => 'crear_solicitudes',
                'descripcion' => 'Permite crear solicitudes',
            ],// Pantallas
[   
    'nombre' => 'Ver pantallas',
    'slug' => 'ver_pantallas',
    'descripcion' => 'Permite consultar las pantallas configuradas en la sucursal',
],
[
    'nombre' => 'Crear pantallas',
    'slug' => 'crear_pantallas',
    'descripcion' => 'Permite registrar pantallas en la sucursal',
],
[
    'nombre' => 'Editar pantallas',
    'slug' => 'editar_pantallas',
    'descripcion' => 'Permite modificar pantallas y sus áreas asignadas',
],
[
    'nombre' => 'Eliminar pantallas',
    'slug' => 'eliminar_pantallas',
    'descripcion' => 'Permite eliminar pantallas de la sucursal',
],
            
        ];

        foreach ($permisos as $permiso) {
            DB::table('permisos')->updateOrInsert(
                ['slug' => $permiso['slug']],
                [
                    'nombre' => $permiso['nombre'],
                    'descripcion' => $permiso['descripcion'],
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $superadmin = DB::table('roles')
            ->where('nombre', 'SUPERADMIN')
            ->first();

        $admin = DB::table('roles')
            ->where('nombre', 'ADMIN')
            ->first();

        $cajero = DB::table('roles')
            ->where('nombre', 'CAJERO')
            ->first();

        $permisosSuperadmin = [
            'ver_sucursales',
            'crear_sucursales',
            'editar_sucursales',
            'cambiar_estado_sucursales',

            'ver_administradores',
            'crear_administradores',
            'editar_administradores',
            'cambiar_estado_administradores',

            'gestionar_permisos_admin',
            'cambiar_rol_admin',
            'ascender_cajero_admin',
            'degradar_admin_cajero',

            'ver_reportes',
            'ver_solicitudes',
        ];

        $permisosAdmin = [
    'ver_empleados',
    'crear_empleados',
    'editar_empleados',
    'cambiar_estado_empleados',

    'crear_cajeros',
    'gestionar_permisos_cajero',
    'ascender_empleado_cajero',
    'degradar_cajero_empleado',

    'ver_inventario',
    'crear_inventario',
    'editar_inventario',

    'ver_jornadas',
    'abrir_jornada',
    'cerrar_jornada',

    'ver_cajas',

    'ver_compras_internas',
    'registrar_compras_internas',

    'crear_reportes',
    'ver_reportes_jornada',

    'crear_solicitudes',

    // Pantallas
    'ver_pantallas',
    'crear_pantallas',
    'editar_pantallas',
    'eliminar_pantallas',
];

        $permisosCajero = [
            'ver_cajas',
            'abrir_caja',
            'cerrar_caja',

            'ver_pedidos',
            'crear_pedidos',
            'registrar_pagos',
            'reimprimir_ticket',
        ];

        $this->asignarPermisos(
            $superadmin?->id_rol,
            $permisosSuperadmin
        );

        $this->asignarPermisos(
            $admin?->id_rol,
            $permisosAdmin
        );

        $this->asignarPermisos(
            $cajero?->id_rol,
            $permisosCajero
        );
    }

    private function asignarPermisos(
        ?int $idRol,
        array $slugs
    ): void {
        if (!$idRol) {
            return;
        }

        $idsPermisos = DB::table('permisos')
            ->whereIn('slug', $slugs)
            ->pluck('id_permiso');

        foreach ($idsPermisos as $idPermiso) {
            DB::table('permiso_rol')->updateOrInsert([
                'id_rol' => $idRol,
                'id_permiso' => $idPermiso,
            ]);
        }
    }
}