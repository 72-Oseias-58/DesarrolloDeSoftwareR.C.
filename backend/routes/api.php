<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\SucursalController;
use App\Http\Controllers\Api\AdministradorController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('role:SUPERADMIN')->group(function () {
        Route::get('/superadmin/prueba', function () {
            return response()->json([
                'message' => 'Ruta exclusiva para SUPERADMIN'
            ]);
        });

        // crud para sucursales 
        Route::get('/sucursales', [SucursalController::class, 'index']);
        Route::post('/sucursales', [SucursalController::class, 'store']);
        Route::get('/sucursales/{id}', [SucursalController::class, 'show']);
        Route::put('/sucursales/{id}', [SucursalController::class, 'update']);
        Route::patch('/sucursales/{id}/estado', [SucursalController::class, 'cambiarEstado']);

        Route::middleware(['auth:api', 'role:SUPERADMIN'])->group(function () {
            Route::get('/administradores', [AdministradorController::class, 'index']);
            Route::post('/administradores', [AdministradorController::class, 'store']);
            Route::get('/administradores/{id}', [AdministradorController::class, 'show']);
            Route::put('/administradores/{id}', [AdministradorController::class, 'update']);
            Route::patch('/administradores/{id}/estado', [AdministradorController::class, 'cambiarEstado']);
        });
    });

    Route::middleware('role:ADMIN')->get('/admin/prueba', function () {
        return response()->json([
            'message' => 'Ruta exclusiva para ADMIN'
        ]);
    });

    Route::middleware('role:CAJERO')->get('/cajero/prueba', function () {
        return response()->json([
            'message' => 'Ruta exclusiva para CAJERO'
        ]);
    });
});