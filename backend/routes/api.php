<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SucursalController;
use App\Http\Controllers\Api\AdministradorController;
use App\Http\Controllers\Api\EmpleadoController;
use App\Http\Controllers\Api\PermisoUsuarioController;
use App\Http\Controllers\Api\EstadisticaVentaController;
use App\Http\Controllers\Api\JornadaController;
use App\Http\Controllers\Api\CajaController;
use App\Http\Controllers\Api\ProductoVentaController;
use App\Http\Controllers\Api\PedidoController;
use App\Http\Controllers\Api\InventarioController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {

    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Permisos usuario
    Route::get('/usuarios/{id}/permisos', [PermisoUsuarioController::class, 'index']);
    Route::put('/usuarios/{id}/permisos', [PermisoUsuarioController::class, 'update']);

    // Pruebas permisos
    Route::get('/permiso/prueba-sucursales', function () {
        return response()->json(['message' => 'Tienes permiso para ver sucursales.']);
    })->middleware('permission:ver_sucursales');

    Route::get('/permiso/prueba-empleados', function () {
        return response()->json(['message' => 'Tienes permiso para ver empleados.']);
    })->middleware('permission:ver_empleados');

    // Superadmin
    Route::middleware('role:SUPERADMIN')->group(function () {

        Route::get('/superadmin/prueba', function () {
            return response()->json(['message' => 'Ruta exclusiva para SUPERADMIN']);
        });

        Route::patch('/usuarios/{id}/rol', [PermisoUsuarioController::class, 'cambiarRol']);

        Route::get('/sucursales', [SucursalController::class, 'index'])
            ->middleware('permission:ver_sucursales');

        Route::post('/sucursales', [SucursalController::class, 'store'])
            ->middleware('permission:crear_sucursales');

        Route::get('/sucursales/{id}', [SucursalController::class, 'show'])
            ->middleware('permission:ver_sucursales');

        Route::put('/sucursales/{id}', [SucursalController::class, 'update'])
            ->middleware('permission:editar_sucursales');

        Route::patch('/sucursales/{id}/estado', [SucursalController::class, 'cambiarEstado'])
            ->middleware('permission:cambiar_estado_sucursales');

        Route::get('/administradores', [AdministradorController::class, 'index'])
            ->middleware('permission:ver_administradores');

        Route::get('/superadmin/usuarios-gestionables', [AdministradorController::class, 'usuariosGestionables'])
            ->middleware('permission:ver_administradores');

        Route::post('/administradores', [AdministradorController::class, 'store'])
            ->middleware('permission:crear_administradores');

        Route::get('/administradores/{id}', [AdministradorController::class, 'show'])
            ->middleware('permission:ver_administradores');

        Route::put('/administradores/{id}', [AdministradorController::class, 'update'])
            ->middleware('permission:editar_administradores');

        Route::patch('/administradores/{id}/estado', [AdministradorController::class, 'cambiarEstado'])
            ->middleware('permission:cambiar_estado_administradores');

        Route::get('/superadmin/estadisticas/ventas', [EstadisticaVentaController::class, 'global']);

        Route::get('/superadmin/sucursales/{id}/estadisticas/ventas', [EstadisticaVentaController::class, 'sucursal'])
            ->whereNumber('id');
    });

    // Admin
    Route::middleware('role:ADMIN')->group(function () {

        Route::get('/admin/prueba', function () {
            return response()->json(['message' => 'Ruta exclusiva para ADMIN']);
        });

        Route::get('/empleados', [EmpleadoController::class, 'index'])
            ->middleware('permission:ver_empleados');

        Route::post('/empleados', [EmpleadoController::class, 'store'])
            ->middleware('permission:crear_empleados');

        Route::get('/empleados/{id}', [EmpleadoController::class, 'show'])
            ->middleware('permission:ver_empleados');

        Route::put('/empleados/{id}', [EmpleadoController::class, 'update'])
            ->middleware('permission:editar_empleados');

        Route::patch('/empleados/{id}/estado', [EmpleadoController::class, 'cambiarEstado'])
            ->middleware('permission:cambiar_estado_empleados');

        Route::get('/jornadas', [JornadaController::class, 'index'])
            ->middleware('permission:ver_jornadas');

        Route::get('/jornadas/actual', [JornadaController::class, 'actual'])
            ->middleware('permission:ver_jornadas');

        Route::get('/tipos-carne', [JornadaController::class, 'tiposCarne'])
            ->middleware('permission:ver_jornadas');

        Route::post('/jornadas/abrir', [JornadaController::class, 'abrir'])
            ->middleware('permission:abrir_jornada');

        Route::patch('/jornadas/cerrar', [JornadaController::class, 'cerrar'])
            ->middleware('permission:cerrar_jornada');

        Route::get('/admin/estadisticas/ventas', [EstadisticaVentaController::class, 'admin']);

        Route::get('/cajas', [CajaController::class, 'index'])
            ->middleware('permission:ver_cajas');
    });

    // Cajero
    Route::middleware('role:CAJERO')->group(function () {

        Route::get('/cajero/prueba', function () {
            return response()->json(['message' => 'Ruta exclusiva para CAJERO']);
        });

        Route::get('/cajero/caja/actual', [CajaController::class, 'actual'])
            ->middleware('permission:ver_cajas');

        Route::post('/cajero/caja/abrir', [CajaController::class, 'abrir'])
            ->middleware('permission:abrir_caja');

        Route::patch('/cajero/caja/cerrar', [CajaController::class, 'cerrar'])
            ->middleware('permission:cerrar_caja');

        Route::get('/productos-venta', [ProductoVentaController::class, 'index'])
            ->middleware('permission:crear_pedidos');

        Route::get('/catalogo/productos/opciones', [ProductoVentaController::class, 'opciones'])
            ->middleware('permission:ver_catalogo_pedidos');

        Route::post('/catalogo/productos', [ProductoVentaController::class, 'store'])
            ->middleware('permission:ver_catalogo_pedidos');

        Route::put('/catalogo/productos/{id}', [ProductoVentaController::class, 'update'])
            ->whereNumber('id')
            ->middleware('permission:editar_catalogo_pedidos');

        Route::delete('/catalogo/productos/{id}', [ProductoVentaController::class, 'destroy'])
            ->whereNumber('id')
            ->middleware('permission:eliminar_catalogo_pedidos');

        Route::post('/pedidos', [PedidoController::class, 'store'])
            ->middleware('permission:crear_pedidos');

        Route::get('/pedidos/pendientes', [PedidoController::class, 'pendientes'])
            ->middleware('permission:ver_pedidos');

        Route::post('/pedidos/{id}/pago', [PedidoController::class, 'registrarPago'])
            ->whereNumber('id')
            ->middleware('permission:registrar_pagos');

        Route::get('/inventario/bebidas', [InventarioController::class, 'bebidas'])
            ->middleware('permission:ver_stock_bebidas');

        Route::post('/inventario/movimientos', [InventarioController::class, 'registrarMovimiento'])
            ->middleware('permission:mover_stock_bebidas');

        Route::get('/inventario/movimientos', [InventarioController::class, 'movimientos'])
            ->middleware('permission:ver_stock_bebidas');
    });
});