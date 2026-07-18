<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Inventario;
use App\Models\Solicitud;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SolicitudController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Obtener sucursal del ADMIN
    |--------------------------------------------------------------------------
    */

    private function obtenerSucursalAdmin(
        Request $request
    ): int {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (
            !$empleado
            || !$empleado->id_sucursal
        ) {
            abort(
                403,
                'El usuario ADMIN no tiene una sucursal asignada.'
            );
        }

        if (
            strtoupper((string) $empleado->estado)
            !== 'ACTIVO'
        ) {
            abort(
                403,
                'El empleado ADMIN se encuentra inactivo.'
            );
        }

        return (int) $empleado->id_sucursal;
    }

    /*
    |--------------------------------------------------------------------------
    | Listar solicitudes del ADMIN
    |--------------------------------------------------------------------------
    */

    public function adminIndex(
        Request $request
    ): JsonResponse {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $datos = $request->validate([
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $solicitudes = Solicitud::query()
            ->with([
                'usuarioSolicitante:id,name,usuario',
                'usuarioVisto:id,name,usuario',
            ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->orderByDesc('fecha')
            ->orderByDesc('id_solicitud')
            ->paginate(
                $datos['per_page'] ?? 15
            );

        return response()->json([
            'solicitudes' => $solicitudes,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Opciones para crear una solicitud
    |--------------------------------------------------------------------------
    |
    | Devuelve todos los insumos del inventario del ADMIN.
    | El buscador se encuentra en el q-select del frontend.
    |
    */

    public function opciones(
        Request $request
    ): JsonResponse {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $inventarios = Inventario::query()
            ->with([
                'insumo.categoria',
            ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereHas(
                'insumo',
                function ($query) use ($idSucursal) {
                    $query->where(
                        'id_sucursal',
                        $idSucursal
                    );
                }
            )
            ->get()
            ->sortBy(function ($inventario) {
                return mb_strtolower(
                    $inventario->insumo?->nombre
                    ?? ''
                );
            })
            ->values()
            ->map(function ($inventario) {
                $stockActual = (float) (
                    $inventario->stock_actual ?? 0
                );

                $stockMinimo = (float) (
                    $inventario->stock_minimo ?? 0
                );

                $cantidadSugerida = max(
                    $stockMinimo - $stockActual,
                    0
                );

                return [
                    'id_inventario' =>
                        $inventario->id_inventario,

                    'id_insumo' =>
                        $inventario->id_insumo,

                    'nombre' =>
                        $inventario->insumo?->nombre,

                    'unidad_medida' =>
                        $inventario
                            ->insumo
                            ?->unidad_medida,

                    'categoria' =>
                        $inventario
                            ->insumo
                            ?->categoria
                            ?->nombre,

                    'stock_actual' =>
                        $stockActual,

                    'stock_minimo' =>
                        $stockMinimo,

                    'cantidad_sugerida' =>
                        $cantidadSugerida,

                    'requiere_reposicion' =>
                        $stockActual <= $stockMinimo,
                ];
            });

        return response()->json([
            'tipos' => [
                [
                    'label' =>
                        'Reposición de inventario',

                    'value' =>
                        'REPOSICION_INVENTARIO',
                ],
                [
                    'label' =>
                        'Creación de recurso',

                    'value' =>
                        'CREACION_RECURSO',
                ],
                [
                    'label' =>
                        'Modificación de recurso',

                    'value' =>
                        'MODIFICACION_RECURSO',
                ],
                [
                    'label' =>
                        'Otro requerimiento',

                    'value' =>
                        'OTRO_REQUERIMIENTO',
                ],
            ],

            // Todos los inventarios para el buscador del ADMIN
            'inventarios' => $inventarios,

            // Insumos con stock bajo o agotado
            'recomendados' => $inventarios
                ->where(
                    'requiere_reposicion',
                    true
                )
                ->values(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Crear solicitud
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ): JsonResponse {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $datos = $request->validate([
            'tipo' => [
                'required',
                'string',
                Rule::in([
                    'REPOSICION_INVENTARIO',
                    'CREACION_RECURSO',
                    'MODIFICACION_RECURSO',
                    'OTRO_REQUERIMIENTO',
                ]),
            ],

            'asunto' => [
                'required',
                'string',
                'max:150',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'detalles_inventario' => [
                Rule::requiredIf(
                    $request->input('tipo')
                    === 'REPOSICION_INVENTARIO'
                ),
                'nullable',
                'array',
                'min:1',
            ],

            'detalles_inventario.*.id_insumo' => [
                'required_with:detalles_inventario',
                'integer',
            ],

            'detalles_inventario.*.cantidad_solicitada' => [
                'required_with:detalles_inventario',
                'numeric',
                'min:0.01',
                'decimal:0,2',
            ],
        ]);

        $detallesInventario = null;

        if (
            $datos['tipo']
            === 'REPOSICION_INVENTARIO'
        ) {
            $idsInsumos = collect(
                $datos['detalles_inventario']
            )
                ->pluck('id_insumo');

            if (
                $idsInsumos->count()
                !== $idsInsumos->unique()->count()
            ) {
                return response()->json([
                    'message' =>
                        'No puedes agregar el mismo insumo más de una vez.',
                ], 422);
            }

            $detallesInventario = [];

            foreach (
                $datos['detalles_inventario']
                as $detalle
            ) {
                $inventario = Inventario::query()
                    ->with([
                        'insumo.categoria',
                    ])
                    ->where(
                        'id_sucursal',
                        $idSucursal
                    )
                    ->where(
                        'id_insumo',
                        $detalle['id_insumo']
                    )
                    ->whereHas(
                        'insumo',
                        function ($query) use (
                            $idSucursal
                        ) {
                            $query->where(
                                'id_sucursal',
                                $idSucursal
                            );
                        }
                    )
                    ->first();

                if (!$inventario) {
                    return response()->json([
                        'message' =>
                            'Uno de los insumos seleccionados no pertenece al inventario de la sucursal.',
                    ], 422);
                }

                $detallesInventario[] = [
                    'id_insumo' =>
                        $inventario->id_insumo,

                    'nombre' =>
                        $inventario
                            ->insumo
                            ->nombre,

                    'unidad_medida' =>
                        $inventario
                            ->insumo
                            ->unidad_medida,

                    'categoria' =>
                        $inventario
                            ->insumo
                            ->categoria
                            ?->nombre,

                    'stock_actual' =>
                        (float) $inventario
                            ->stock_actual,

                    'stock_minimo' =>
                        (float) $inventario
                            ->stock_minimo,

                    'cantidad_solicitada' =>
                        (float) $detalle[
                            'cantidad_solicitada'
                        ],
                ];
            }
        }

        $solicitud = DB::transaction(
            function () use (
                $request,
                $datos,
                $idSucursal,
                $detallesInventario
            ) {
                return Solicitud::create([
                    'id_sucursal' =>
                        $idSucursal,

                    'id_user_solicita' =>
                        $request
                            ->user('api')
                            ->id,

                    'tipo' =>
                        $datos['tipo'],

                    'asunto' =>
                        trim($datos['asunto']),

                    'descripcion' =>
                        !empty(
                            trim(
                                (string) (
                                    $datos[
                                        'descripcion'
                                    ] ?? ''
                                )
                            )
                        )
                            ? trim(
                                $datos['descripcion']
                            )
                            : null,

                    'detalles_inventario' =>
                        $detallesInventario,

                    'visto' =>
                        false,

                    'visto_en' =>
                        null,

                    'id_user_visto' =>
                        null,

                    'fecha' =>
                        now(),
                ]);
            }
        );

        return response()->json([
            'message' =>
                'Solicitud enviada correctamente.',

            'solicitud' =>
                $solicitud->load([
                    'sucursal',
                    'usuarioSolicitante:id,name,usuario',
                ]),
        ], 201);
    }

    /*
    |--------------------------------------------------------------------------
    | Listar solicitudes para el SUPERADMIN
    |--------------------------------------------------------------------------
    */

    public function superadminIndex(
        Request $request
    ): JsonResponse {
        $datos = $request->validate([
            'id_sucursal' => [
                'nullable',
                'integer',
                'exists:sucursales,id_sucursal',
            ],

            'visto' => [
                'nullable',
                Rule::in([
                    '0',
                    '1',
                ]),
            ],

            'tipo' => [
                'nullable',
                'string',
                Rule::in([
                    'REPOSICION_INVENTARIO',
                    'CREACION_RECURSO',
                    'MODIFICACION_RECURSO',
                    'OTRO_REQUERIMIENTO',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $solicitudes = Solicitud::query()
            ->with([
                'sucursal:id_sucursal,nombre',
                'usuarioSolicitante:id,name,usuario',
                'usuarioVisto:id,name,usuario',
            ])
            ->when(
                isset($datos['id_sucursal']),
                function ($query) use ($datos) {
                    $query->where(
                        'id_sucursal',
                        $datos['id_sucursal']
                    );
                }
            )
            ->when(
                isset($datos['visto']),
                function ($query) use ($datos) {
                    $query->where(
                        'visto',
                        (bool) $datos['visto']
                    );
                }
            )
            ->when(
                !empty($datos['tipo']),
                function ($query) use ($datos) {
                    $query->where(
                        'tipo',
                        $datos['tipo']
                    );
                }
            )
            ->orderBy('visto')
            ->orderByDesc('fecha')
            ->orderByDesc('id_solicitud')
            ->paginate(
                $datos['per_page'] ?? 15
            );

        return response()->json([
            'solicitudes' => $solicitudes,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Abrir solicitud y marcarla como vista
    |--------------------------------------------------------------------------
    */

    public function detalleSuperadmin(
        Request $request,
        int $id
    ): JsonResponse {
        $solicitud = DB::transaction(
            function () use ($request, $id) {
                $solicitud = Solicitud::query()
                    ->where(
                        'id_solicitud',
                        $id
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$solicitud) {
                    abort(
                        404,
                        'La solicitud no existe.'
                    );
                }

                if (!$solicitud->visto) {
                    $solicitud->update([
                        'visto' =>
                            true,

                        'visto_en' =>
                            now(),

                        'id_user_visto' =>
                            $request
                                ->user('api')
                                ->id,
                    ]);
                }

                return $solicitud;
            }
        );

        return response()->json([
            'message' =>
                'Solicitud obtenida correctamente.',

            'solicitud' =>
                $solicitud->load([
                    'sucursal',
                    'usuarioSolicitante:id,name,usuario',
                    'usuarioVisto:id,name,usuario',
                ]),
        ]);
    }
}