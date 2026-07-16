<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\AdministradorController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\CompraInternaController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\EstadisticaVentaController;
use App\Http\Controllers\Api\InventarioController;
use App\Http\Controllers\Api\JornadaController;
use App\Http\Controllers\Api\MovimientoCarneController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\PermisoUsuarioController;
use App\Http\Controllers\Api\ProductoVentaController;
use App\Http\Controllers\Api\ReporteController;
use App\Http\Controllers\Api\SucursalController;

// Autenticación
Route::post('/login', [AuthController::class, 'login']);

// Rutas autenticadas
Route::middleware('auth:api')->group(function () {

    // Sesión
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Datos generales
    Route::get(
        '/jornadas/actual',
        [JornadaController::class, 'actual']
    );

    Route::get(
        '/tipos-carne',
        [JornadaController::class, 'tiposCarne']
    );

    Route::get(
        '/productos-venta',
        [ProductoVentaController::class, 'index']
    );

    // Permisos de usuarios
    Route::get(
        '/usuarios/{id}/permisos',
        [PermisoUsuarioController::class, 'index']
    )->whereNumber('id');

    Route::put(
        '/usuarios/{id}/permisos',
        [PermisoUsuarioController::class, 'update']
    )->whereNumber('id');

    // Prueba de permiso de sucursales
    Route::get('/permiso/prueba-sucursales', function () {
        return response()->json([
            'message' => 'Tienes permiso para ver sucursales.',
        ]);
    })->middleware('permission:ver_sucursales');

    // Prueba de permiso de empleados
    Route::get('/permiso/prueba-empleados', function () {
        return response()->json([
            'message' => 'Tienes permiso para ver empleados.',
        ]);
    })->middleware('permission:ver_empleados');

    // SUPERADMIN
    Route::middleware('role:SUPERADMIN')->group(function () {

        // Prueba SUPERADMIN
        Route::get('/superadmin/prueba', function () {
            return response()->json([
                'message' => 'Ruta exclusiva para SUPERADMIN.',
            ]);
        });

        // Cambio de rol
        Route::patch(
            '/usuarios/{id}/rol',
            [PermisoUsuarioController::class, 'cambiarRol']
        )->whereNumber('id');

        // Listar sucursales
        Route::get(
            '/sucursales',
            [SucursalController::class, 'index']
        )->middleware('permission:ver_sucursales');

        // Crear sucursal
        Route::post(
            '/sucursales',
            [SucursalController::class, 'store']
        )->middleware('permission:crear_sucursales');

        // Ver sucursal
        Route::get(
            '/sucursales/{id}',
            [SucursalController::class, 'show']
        )
            ->whereNumber('id')
            ->middleware('permission:ver_sucursales');

        // Editar sucursal
        Route::put(
            '/sucursales/{id}',
            [SucursalController::class, 'update']
        )
            ->whereNumber('id')
            ->middleware('permission:editar_sucursales');

        // Cambiar estado de sucursal
        Route::patch(
            '/sucursales/{id}/estado',
            [SucursalController::class, 'cambiarEstado']
        )
            ->whereNumber('id')
            ->middleware('permission:cambiar_estado_sucursales');

        // Listar administradores
        Route::get(
            '/administradores',
            [AdministradorController::class, 'index']
        )->middleware('permission:ver_administradores');

        // Listar usuarios gestionables
        Route::get(
            '/superadmin/usuarios-gestionables',
            [AdministradorController::class, 'usuariosGestionables']
        )->middleware('permission:ver_administradores');

        // Crear administrador
        Route::post(
            '/administradores',
            [AdministradorController::class, 'store']
        )->middleware('permission:crear_administradores');

        // Ver administrador
        Route::get(
            '/administradores/{id}',
            [AdministradorController::class, 'show']
        )
            ->whereNumber('id')
            ->middleware('permission:ver_administradores');

        // Editar administrador
        Route::put(
            '/administradores/{id}',
            [AdministradorController::class, 'update']
        )
            ->whereNumber('id')
            ->middleware('permission:editar_administradores');

        // Cambiar estado de administrador
        Route::patch(
            '/administradores/{id}/estado',
            [AdministradorController::class, 'cambiarEstado']
        )
            ->whereNumber('id')
            ->middleware('permission:cambiar_estado_administradores');

        // Estadísticas globales
        Route::get(
            '/superadmin/estadisticas/ventas',
            [EstadisticaVentaController::class, 'global']
        );

        // Estadísticas por sucursal
        Route::get(
            '/superadmin/sucursales/{id}/estadisticas/ventas',
            [EstadisticaVentaController::class, 'sucursal']
        )->whereNumber('id');

        // Listar reportes globales
        Route::get(
            '/superadmin/reportes-jornada',
            [ReporteController::class, 'global']
        )->middleware('permission:ver_reportes');

        // Ver reporte global
        Route::get(
            '/superadmin/reportes-jornada/{id}',
            [ReporteController::class, 'detalleGlobal']
        )
            ->whereNumber('id')
            ->middleware('permission:ver_reportes');
    });

    // ADMIN
    Route::middleware('role:ADMIN')->group(function () {

        // Prueba ADMIN
        Route::get('/admin/prueba', function () {
            return response()->json([
                'message' => 'Ruta exclusiva para ADMIN.',
            ]);
        });

        // Listar empleados
        Route::get(
            '/empleados',
            [EmpleadoController::class, 'index']
        )->middleware('permission:ver_empleados');

        // Crear empleado
        Route::post(
            '/empleados',
            [EmpleadoController::class, 'store']
        )->middleware('permission:crear_empleados');

        // Ver empleado
        Route::get(
            '/empleados/{id}',
            [EmpleadoController::class, 'show']
        )
            ->whereNumber('id')
            ->middleware('permission:ver_empleados');

        // Editar empleado
        Route::put(
            '/empleados/{id}',
            [EmpleadoController::class, 'update']
        )
            ->whereNumber('id')
            ->middleware('permission:editar_empleados');

        // Cambiar estado de empleado
        Route::patch(
            '/empleados/{id}/estado',
            [EmpleadoController::class, 'cambiarEstado']
        )
            ->whereNumber('id')
            ->middleware('permission:cambiar_estado_empleados');

        // Listar jornadas
        Route::get(
            '/jornadas',
            [JornadaController::class, 'index']
        )->middleware('permission:ver_jornadas');

        // Listar tipos de carne
        Route::get(
            '/jornadas/tipos-carne',
            [JornadaController::class, 'tiposCarne']
        )->middleware('permission:ver_jornadas');

        // Abrir jornada
        Route::post(
            '/jornadas/abrir',
            [JornadaController::class, 'abrir']
        )->middleware('permission:abrir_jornada');

        // Preparar cierre de jornada
        Route::get(
            '/jornadas/preparar-cierre',
            [JornadaController::class, 'prepararCierre']
        )->middleware('permission:cerrar_jornada');

        // Cerrar jornada
        Route::patch(
            '/jornadas/cerrar',
            [JornadaController::class, 'cerrar']
        )->middleware('permission:cerrar_jornada');

        // Estadísticas del ADMIN
        Route::get(
            '/admin/estadisticas/ventas',
            [EstadisticaVentaController::class, 'admin']
        );

        // Listar cajas
        Route::get(
            '/cajas',
            [CajaController::class, 'index']
        )->middleware('permission:ver_cajas');

        // Listar movimientos de carne
        Route::get(
            '/admin/movimientos-carne',
            [MovimientoCarneController::class, 'index']
        )->middleware('permission:ver_movimientos_carne');

        // Registrar movimiento de carne
        Route::post(
            '/admin/movimientos-carne',
            [MovimientoCarneController::class, 'store']
        )->middleware('permission:registrar_movimientos_carne');

        // Obtener opciones del catálogo
        Route::get(
            '/catalogo/productos/opciones',
            [ProductoVentaController::class, 'opciones']
        )->middleware('permission:ver_catalogo_pedidos');

        // Crear producto
        Route::post(
            '/catalogo/productos',
            [ProductoVentaController::class, 'store']
        )->middleware('permission:crear_catalogo_pedidos');

        // Editar producto
        Route::put(
            '/catalogo/productos/{id}',
            [ProductoVentaController::class, 'update']
        )
            ->whereNumber('id')
            ->middleware('permission:editar_catalogo_pedidos');

        // Eliminar producto
        Route::delete(
            '/catalogo/productos/{id}',
            [ProductoVentaController::class, 'destroy']
        )
            ->whereNumber('id')
            ->middleware('permission:eliminar_catalogo_pedidos');

        // Opciones de compras internas
        Route::get(
            '/admin/compras-internas/opciones',
            [CompraInternaController::class, 'opciones']
        )->middleware('permission:ver_compras_internas');

        // Listar compras internas
        Route::get(
            '/admin/compras-internas',
            [CompraInternaController::class, 'index']
        )->middleware('permission:ver_compras_internas');

        // Registrar compra interna
        Route::post(
            '/admin/compras-internas',
            [CompraInternaController::class, 'store']
        )->middleware('permission:registrar_compras_internas');

        // Agregar dinero a compra
        Route::post(
            '/admin/compras-internas/{id}/dinero-adicional',
            [CompraInternaController::class, 'agregarDinero']
        )
            ->whereNumber('id')
            ->middleware('permission:registrar_compras_internas');

        // Finalizar compra interna
        Route::patch(
            '/admin/compras-internas/{id}/finalizar',
            [CompraInternaController::class, 'finalizar']
        )
            ->whereNumber('id')
            ->middleware('permission:registrar_compras_internas');

        // Anular compra interna
        Route::patch(
            '/admin/compras-internas/{id}/anular',
            [CompraInternaController::class, 'anular']
        )
            ->whereNumber('id')
            ->middleware('permission:registrar_compras_internas');

        // Listar reportes del ADMIN
        Route::get(
            '/admin/reportes-jornada',
            [ReporteController::class, 'admin']
        )->middleware('permission:ver_reportes_jornada');

        // Ver reporte del ADMIN
        Route::get(
            '/admin/reportes-jornada/{id}',
            [ReporteController::class, 'detalleAdmin']
        )
            ->whereNumber('id')
            ->middleware('permission:ver_reportes_jornada');
    });

    // CAJERO
    Route::middleware('role:CAJERO')->group(function () {

        // Prueba CAJERO
        Route::get('/cajero/prueba', function () {
            return response()->json([
                'message' => 'Ruta exclusiva para CAJERO.',
            ]);
        });

        // Obtener caja actual
        Route::get(
            '/cajero/caja/actual',
            [CajaController::class, 'actual']
        )->middleware('permission:ver_cajas');

        // Abrir caja
        Route::post(
            '/cajero/caja/abrir',
            [CajaController::class, 'abrir']
        )->middleware('permission:abrir_caja');

        // Cerrar caja
        Route::patch(
            '/cajero/caja/cerrar',
            [CajaController::class, 'cerrar']
        )->middleware('permission:cerrar_caja');

        // Crear pedido
        Route::post(
            '/pedidos',
            [PedidoController::class, 'store']
        )->middleware('permission:crear_pedidos');

        // Listar pedidos pendientes
        Route::get(
            '/pedidos/pendientes',
            [PedidoController::class, 'pendientes']
        )->middleware('permission:ver_pedidos');

        // Listar pedidos para pago
        Route::get(
            '/pagos/pedidos-pendientes',
            [PagoController::class, 'pedidosPendientes']
        )->middleware('permission:registrar_pagos');

        // Registrar pago
        Route::post(
            '/pedidos/{id}/pagar',
            [PagoController::class, 'pagar']
        )
            ->whereNumber('id')
            ->middleware('permission:registrar_pagos');

        // Listar bebidas
        Route::get(
            '/inventario/bebidas',
            [InventarioController::class, 'bebidas']
        )->middleware('permission:ver_stock_bebidas');

        // Registrar movimiento de bebida
        Route::post(
            '/inventario/movimientos',
            [InventarioController::class, 'registrarMovimiento']
        )->middleware('permission:mover_stock_bebidas');

        // Listar movimientos de bebidas
        Route::get(
            '/inventario/movimientos',
            [InventarioController::class, 'movimientos']
        )->middleware('permission:ver_stock_bebidas');
    });
});