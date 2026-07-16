<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CerrarJornadaRequest;
use App\Models\ControlCarneJornada;
use App\Models\Jornada;
use App\Models\MovimientoCarne;
use App\Models\TipoCarne;
use App\Models\User;
use App\Services\ReporteJornadaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JornadaController extends Controller
{
    public function __construct(
        private readonly ReporteJornadaService $reporteJornadaService
    ) {
    }

    private function obtenerSucursalAdmin(
        Request $request
    ): int {
        $user = User::with('empleado')
            ->find($request->user()->id);

        if (
            !$user
            || !$user->empleado
            || !$user->empleado->id_sucursal
        ) {
            abort(
                403,
                'El usuario ADMIN no tiene una sucursal asignada.'
            );
        }

        return (int) $user->empleado->id_sucursal;
    }

    public function index(
        Request $request
    ) {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $jornadas = Jornada::with([
            'sucursal',
            'controlCarne.tipoCarne',
            'cajas.empleado',
            'reporte',
        ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->orderByDesc('fecha')
            ->paginate(15);

        return response()->json([
            'message' =>
                'Jornadas obtenidas correctamente.',

            'jornadas' =>
                $jornadas,
        ]);
    }

    public function actual(
        Request $request
    ) {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $jornada = Jornada::with([
            'sucursal',
            'controlCarne.tipoCarne',
            'cajas.empleado',
            'reporte',
        ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereDate(
                'fecha',
                now()->toDateString()
            )
            ->first();

        return response()->json([
            'message' => $jornada
                ? 'Jornada actual encontrada.'
                : 'No existe jornada registrada para hoy.',

            'jornada' =>
                $jornada,
        ]);
    }

    public function tiposCarne()
    {
        $tipos = TipoCarne::query()
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'message' =>
                'Tipos de carne obtenidos correctamente.',

            'tipos_carne' =>
                $tipos,
        ]);
    }

    public function abrir(
        Request $request
    ) {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $idUsuario = (int) $request->user()->id;

        $fechaHoy = now()->toDateString();

        $data = $request->validate([
            'carnes' => [
                'required',
                'array',
                'size:2',
            ],

            'carnes.*.id_tipo_carne' => [
                'required',
                'integer',
                'exists:tipos_carne,id_tipo_carne',
            ],

            'carnes.*.cantidad_cruces' => [
                'required',
                'numeric',
                'min:0',
            ],

            'carnes.*.platos_estimados' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'carnes.*.cantidad_base_inicial' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'carnes.*.unidad_base' => [
                'required',
                'string',
                'max:50',
            ],

            'carnes.*.observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $jornadaExistente = Jornada::query()
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereDate(
                'fecha',
                $fechaHoy
            )
            ->first();

        if ($jornadaExistente) {
            return response()->json([
                'message' =>
                    'Ya existe una jornada registrada para hoy.',

                'jornada' =>
                    $jornadaExistente,
            ], 409);
        }

        $idsTiposCarne = collect(
            $data['carnes']
        )
            ->pluck('id_tipo_carne')
            ->map(
                fn ($id) => (int) $id
            )
            ->toArray();

        if (
            count($idsTiposCarne)
            !== count(array_unique($idsTiposCarne))
        ) {
            throw ValidationException::withMessages([
                'carnes' => [
                    'No puedes repetir el mismo tipo de carne.',
                ],
            ]);
        }

        $tiposCarne = TipoCarne::query()
            ->whereIn(
                'id_tipo_carne',
                $idsTiposCarne
            )
            ->get()
            ->keyBy('id_tipo_carne');

        $nombres = $tiposCarne
            ->pluck('nombre')
            ->map(
                fn ($nombre) => strtoupper(
                    trim((string) $nombre)
                )
            )
            ->values()
            ->toArray();

        if (
            !in_array(
                'CHANCHO',
                $nombres,
                true
            )
            || !in_array(
                'POLLO',
                $nombres,
                true
            )
        ) {
            throw ValidationException::withMessages([
                'carnes' => [
                    'Para abrir jornada debes registrar CHANCHO y POLLO.',
                ],
            ]);
        }

        $jornada = DB::transaction(
            function () use (
                $idSucursal,
                $idUsuario,
                $fechaHoy,
                $data,
                $tiposCarne
            ) {
                $jornada = Jornada::create([
                    'id_sucursal' =>
                        $idSucursal,

                    'fecha' =>
                        $fechaHoy,

                    'hora_inicio' =>
                        now()->format('H:i:s'),

                    'hora_fin' =>
                        null,

                    'estado' =>
                        'ABIERTA',
                ]);

                foreach ($data['carnes'] as $carne) {
                    $idTipoCarne = (int)
                        $carne['id_tipo_carne'];

                    $tipoCarne = $tiposCarne->get(
                        $idTipoCarne
                    );

                    if (!$tipoCarne) {
                        throw ValidationException::withMessages([
                            'carnes' => [
                                'Uno de los tipos de carne no existe.',
                            ],
                        ]);
                    }

                    $nombreTipoCarne = strtoupper(
                        trim(
                            (string) $tipoCarne->nombre
                        )
                    );

                    $cantidadCruces = round(
                        (float) $carne[
                            'cantidad_cruces'
                        ],
                        2
                    );

                    $cantidadBaseInicial = round(
                        (float) $carne[
                            'cantidad_base_inicial'
                        ],
                        2
                    );

                    $unidadBase = strtoupper(
                        trim(
                            (string) $carne[
                                'unidad_base'
                            ]
                        )
                    );

                    $unidadRegistrada = match (
                        $nombreTipoCarne
                    ) {
                        'CHANCHO' =>
                            'CRUZ_CHANCHO',

                        'POLLO' =>
                            'CRUZ_POLLO',

                        default =>
                            'CRUZ',
                    };

                    $control =
                        ControlCarneJornada::create([
                            'id_sucursal' =>
                                $idSucursal,

                            'id_jornada' =>
                                $jornada
                                    ->id_jornada,

                            'id_tipo_carne' =>
                                $idTipoCarne,

                            'cantidad_cruces' =>
                                $cantidadCruces,

                            'platos_estimados' =>
                                isset(
                                    $carne[
                                        'platos_estimados'
                                    ]
                                )
                                    ? (int) $carne[
                                        'platos_estimados'
                                    ]
                                    : null,

                            'cantidad_base_inicial' =>
                                number_format(
                                    $cantidadBaseInicial,
                                    2,
                                    '.',
                                    ''
                                ),

                            'cantidad_base_actual' =>
                                number_format(
                                    $cantidadBaseInicial,
                                    2,
                                    '.',
                                    ''
                                ),

                            'unidad_base' =>
                                $unidadBase,

                            'observacion' =>
                                $carne[
                                    'observacion'
                                ] ?? null,
                        ]);

                    MovimientoCarne::create([
                        'id_control_carne' =>
                            $control
                                ->id_control_carne,

                        'id_sucursal' =>
                            $idSucursal,

                        'id_jornada' =>
                            $jornada
                                ->id_jornada,

                        'id_tipo_carne' =>
                            $idTipoCarne,

                        'id_user_crea' =>
                            $idUsuario,

                        'tipo_movimiento' =>
                            'ENTRADA',

                        'motivo' =>
                            'APERTURA',

                        'unidad_registrada' =>
                            $unidadRegistrada,

                        'cantidad_registrada' =>
                            number_format(
                                $cantidadCruces,
                                2,
                                '.',
                                ''
                            ),

                        'cantidad_base' =>
                            number_format(
                                $cantidadBaseInicial,
                                2,
                                '.',
                                ''
                            ),

                        'unidad_base' =>
                            $unidadBase,

                        'cantidad_anterior' =>
                            '0.00',

                        'cantidad_nueva' =>
                            number_format(
                                $cantidadBaseInicial,
                                2,
                                '.',
                                ''
                            ),

                        'referencia_tipo' =>
                            'APERTURA_JORNADA',

                        'referencia_id' =>
                            $jornada
                                ->id_jornada,

                        'origen' =>
                            'APERTURA_JORNADA',

                        'destino' =>
                            null,

                        'observacion' =>
                            $carne['observacion']
                            ?? "Cantidad inicial de {$nombreTipoCarne} registrada al abrir la jornada.",
                    ]);
                }

                return $jornada;
            }
        );

        return response()->json([
            'message' =>
                'Jornada abierta correctamente.',

            'jornada' =>
                $jornada->load([
                    'sucursal',
                    'controlCarne.tipoCarne',
                    'movimientosCarne.tipoCarne',
                    'movimientosCarne.usuarioCreador:id,name,usuario',
                    'cajas.empleado',
                ]),
        ], 201);
    }

    public function prepararCierre(
        Request $request
    ) {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $jornada = Jornada::query()
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereDate(
                'fecha',
                now()->toDateString()
            )
            ->where(
                'estado',
                'ABIERTA'
            )
            ->first();

        if (!$jornada) {
            return response()->json([
                'message' =>
                    'No existe una jornada abierta para cerrar.',
            ], 404);
        }

        $preparacion =
            $this->reporteJornadaService
                ->prepararCierre($jornada);

        return response()->json([
            'message' =>
                $preparacion['message'],

            'jornada' => [
                'id_jornada' =>
                    $jornada->id_jornada,

                'id_sucursal' =>
                    $jornada->id_sucursal,

                'fecha' =>
                    $jornada->fecha,

                'hora_inicio' =>
                    $jornada->hora_inicio,

                'estado' =>
                    $jornada->estado,
            ],

            'cierre' =>
                $preparacion,
        ]);
    }

    public function cerrar(
        CerrarJornadaRequest $request
    ) {
        $idSucursal = $this->obtenerSucursalAdmin(
            $request
        );

        $idUsuario = (int) $request->user()->id;

        $data = $request->validated();

        $resultado = DB::transaction(
            function () use (
                $idSucursal,
                $idUsuario,
                $data
            ) {
                $jornada = Jornada::query()
                    ->where(
                        'id_sucursal',
                        $idSucursal
                    )
                    ->whereDate(
                        'fecha',
                        now()->toDateString()
                    )
                    ->where(
                        'estado',
                        'ABIERTA'
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$jornada) {
                    throw ValidationException::withMessages([
                        'jornada' => [
                            'No existe una jornada abierta para cerrar.',
                        ],
                    ]);
                }

                $preparacion =
                    $this->reporteJornadaService
                        ->prepararCierre($jornada);

                if (
                    !$preparacion['puede_cerrar']
                ) {
                    throw ValidationException::withMessages([
                        'compras_pendientes' => [
                            $preparacion['message'],
                        ],

                        'detalle_compras_pendientes' => [
                            json_encode(
                                $preparacion[
                                    'compras_pendientes'
                                ],
                                JSON_UNESCAPED_UNICODE
                            ),
                        ],
                    ]);
                }

                $idsCajasAbiertas = collect(
                    $preparacion[
                        'cajas_abiertas'
                    ]
                )
                    ->pluck('id_caja')
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->sort()
                    ->values();

                $idsCajasEnviadas = collect(
                    $data['cajas']
                )
                    ->pluck('id_caja')
                    ->map(
                        fn ($id) => (int) $id
                    )
                    ->sort()
                    ->values();

                if (
                    $idsCajasAbiertas->all()
                    !== $idsCajasEnviadas->all()
                ) {
                    throw ValidationException::withMessages([
                        'cajas' => [
                            'Debe registrar el conteo físico de todas las cajas abiertas y únicamente de esas cajas.',
                        ],
                    ]);
                }

                $reporte =
                    $this->reporteJornadaService
                        ->generar(
                            $jornada,
                            $idUsuario,
                            $data['cajas']
                        );

                $jornada->update([
                    'hora_fin' =>
                        now()->format('H:i:s'),

                    'estado' =>
                        'CERRADA',
                ]);

                return [
                    'jornada' =>
                        $jornada->fresh(),

                    'reporte' =>
                        $reporte,
                ];
            }
        );

        return response()->json([
            'message' =>
                'Jornada cerrada y reporte generado correctamente.',

            'jornada' =>
                $resultado['jornada']->load([
                    'sucursal',
                    'controlCarne.tipoCarne',
                    'movimientosCarne.tipoCarne',
                    'movimientosCarne.usuarioCreador:id,name,usuario',
                    'cajas.empleado',
                    'reporte',
                ]),

            'reporte' =>
                $resultado['reporte']->load([
                    'sucursal',
                    'jornada',
                    'usuarioCreador:id,name,usuario',
                ]),
        ]);
    }
}