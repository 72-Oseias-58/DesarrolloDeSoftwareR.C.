<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\EstadisticaVentaRequest;
use App\Models\User;
use App\Services\EstadisticaVentaService;
use Illuminate\Http\JsonResponse;

class EstadisticaVentaController extends Controller
{
    public function __construct(
        private readonly EstadisticaVentaService $estadisticaVentaService
    ) {
    }

    /**
     * Estadísticas generales de todas las sucursales.
     *
     * Uso exclusivo del SUPERADMIN.
     */
    public function global(
        EstadisticaVentaRequest $request
    ): JsonResponse {
        $estadisticas = $this->estadisticaVentaService
            ->obtenerGlobal($request->validated());

        return response()->json([
            'message' => 'Estadísticas globales obtenidas correctamente.',
            'data' => $estadisticas,
        ]);
    }

    /**
     * Estadísticas individuales de una sucursal.
     *
     * Uso exclusivo del SUPERADMIN.
     */
    public function sucursal(
        EstadisticaVentaRequest $request,
        int $id
    ): JsonResponse {
        $estadisticas = $this->estadisticaVentaService
            ->obtenerSucursal(
                $id,
                $request->validated()
            );

        return response()->json([
            'message' => 'Estadísticas de la sucursal obtenidas correctamente.',
            'data' => $estadisticas,
        ]);
    }

    /**
     * Estadísticas de la sucursal del ADMIN autenticado.
     *
     * El id de la sucursal se obtiene desde el backend.
     * El ADMIN no puede elegir otra sucursal mediante la URL.
     */
    public function admin(
        EstadisticaVentaRequest $request
    ): JsonResponse {
        $usuarioAutenticado = auth('api')->user();

        if (!$usuarioAutenticado) {
            return response()->json([
                'message' => 'Usuario no autenticado.',
            ], 401);
        }

        $usuario = User::with([
            'rol',
            'empleado.sucursal',
        ])->find($usuarioAutenticado->id);

        if (!$usuario) {
            return response()->json([
                'message' => 'No se encontró el usuario autenticado.',
            ], 404);
        }

        if (!$usuario->empleado) {
            return response()->json([
                'message' => 'El usuario no tiene un empleado asociado.',
            ], 422);
        }

        if (!$usuario->empleado->id_sucursal) {
            return response()->json([
                'message' => 'El empleado no tiene una sucursal asignada.',
            ], 422);
        }

        if (!$usuario->empleado->sucursal) {
            return response()->json([
                'message' => 'La sucursal asignada no existe.',
            ], 404);
        }

        $estadisticas = $this->estadisticaVentaService
            ->obtenerSucursal(
                (int) $usuario->empleado->id_sucursal,
                $request->validated()
            );

        return response()->json([
            'message' => 'Estadísticas de la sucursal obtenidas correctamente.',
            'data' => $estadisticas,
        ]);
    }
}