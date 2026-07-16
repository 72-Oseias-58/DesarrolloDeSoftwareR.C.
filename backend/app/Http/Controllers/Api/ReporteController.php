<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reporte;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReporteController extends Controller
{
    // Obtiene la sucursal del ADMIN autenticado
    private function obtenerSucursalAdmin(): int
    {
        $usuarioAutenticado = auth('api')->user();

        if (!$usuarioAutenticado) {
            abort(
                401,
                'Usuario no autenticado.'
            );
        }

        $user = User::with('empleado')
            ->find($usuarioAutenticado->id);

        if (!$user) {
            abort(
                404,
                'El usuario autenticado no existe.'
            );
        }

        if (
            !$user->empleado
            || !$user->empleado->id_sucursal
        ) {
            abort(
                403,
                'El usuario ADMIN no tiene una sucursal asignada.'
            );
        }

        return (int) $user->empleado->id_sucursal;
    }

    // Lista los reportes del ADMIN
    public function admin(
        Request $request
    ): JsonResponse {
        $idSucursal = $this->obtenerSucursalAdmin();

        $data = $request->validate([
            'fecha_desde' => [
                'nullable',
                'date',
            ],

            'fecha_hasta' => [
                'nullable',
                'date',
                'after_or_equal:fecha_desde',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $reportes = Reporte::query()
            ->with([
                'sucursal',
                'jornada',
                'usuarioCreador:id,name,usuario',
            ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->where(
                'tipo',
                'CIERRE_JORNADA'
            )
            ->when(
                !empty($data['fecha_desde']),
                function ($query) use ($data) {
                    $query->whereDate(
                        'fecha',
                        '>=',
                        $data['fecha_desde']
                    );
                }
            )
            ->when(
                !empty($data['fecha_hasta']),
                function ($query) use ($data) {
                    $query->whereDate(
                        'fecha',
                        '<=',
                        $data['fecha_hasta']
                    );
                }
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id_reporte')
            ->paginate(
                $data['per_page'] ?? 15
            );

        return response()->json([
            'message' =>
                'Reportes de la sucursal obtenidos correctamente.',

            'reportes' =>
                $reportes,
        ]);
    }

    // Muestra un reporte del ADMIN
    public function detalleAdmin(
        int $id
    ): JsonResponse {
        $idSucursal = $this->obtenerSucursalAdmin();

        $reporte = Reporte::query()
            ->with([
                'sucursal',
                'jornada',
                'usuarioCreador:id,name,usuario',
            ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->where(
                'tipo',
                'CIERRE_JORNADA'
            )
            ->where(
                'id_reporte',
                $id
            )
            ->first();

        if (!$reporte) {
            return response()->json([
                'message' =>
                    'El reporte solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'message' =>
                'Reporte obtenido correctamente.',

            'reporte' =>
                $reporte,
        ]);
    }

    // Lista los reportes del SUPERADMIN
    public function global(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'id_sucursal' => [
                'nullable',
                'integer',
                'exists:sucursales,id_sucursal',
            ],

            'fecha_desde' => [
                'nullable',
                'date',
            ],

            'fecha_hasta' => [
                'nullable',
                'date',
                'after_or_equal:fecha_desde',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $reportes = Reporte::query()
            ->with([
                'sucursal',
                'jornada',
                'usuarioCreador:id,name,usuario',
            ])
            ->where(
                'tipo',
                'CIERRE_JORNADA'
            )
            ->when(
                !empty($data['id_sucursal']),
                function ($query) use ($data) {
                    $query->where(
                        'id_sucursal',
                        $data['id_sucursal']
                    );
                }
            )
            ->when(
                !empty($data['fecha_desde']),
                function ($query) use ($data) {
                    $query->whereDate(
                        'fecha',
                        '>=',
                        $data['fecha_desde']
                    );
                }
            )
            ->when(
                !empty($data['fecha_hasta']),
                function ($query) use ($data) {
                    $query->whereDate(
                        'fecha',
                        '<=',
                        $data['fecha_hasta']
                    );
                }
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id_reporte')
            ->paginate(
                $data['per_page'] ?? 15
            );

        return response()->json([
            'message' =>
                'Reportes de cierre obtenidos correctamente.',

            'reportes' =>
                $reportes,
        ]);
    }

    // Muestra un reporte del SUPERADMIN
    public function detalleGlobal(
        int $id
    ): JsonResponse {
        $reporte = Reporte::query()
            ->with([
                'sucursal',
                'jornada',
                'usuarioCreador:id,name,usuario',
            ])
            ->where(
                'tipo',
                'CIERRE_JORNADA'
            )
            ->where(
                'id_reporte',
                $id
            )
            ->first();

        if (!$reporte) {
            return response()->json([
                'message' =>
                    'El reporte solicitado no existe.',
            ], 404);
        }

        return response()->json([
            'message' =>
                'Reporte obtenido correctamente.',

            'reporte' =>
                $reporte,
        ]);
    }
}