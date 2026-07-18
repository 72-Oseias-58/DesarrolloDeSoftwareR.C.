<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CategoriaInsumo;
use App\Models\Empleado;
use App\Models\Insumo;
use App\Models\Inventario;
use App\Models\Jornada;
use App\Models\MovimientoInventario;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class InventarioController extends Controller
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientoService
    ) {
    }

    // Obtiene el empleado autenticado
    private function obtenerEmpleado(Request $request): Empleado
    {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado' => 'El usuario no está asociado a un empleado.',
            ]);
        }

        if (strtoupper((string) $empleado->estado) !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'empleado' => 'El empleado se encuentra inactivo.',
            ]);
        }

        return $empleado;
    }

    // Lista el inventario del ADMIN
    public function index(Request $request): JsonResponse
    {
        $empleado = $this->obtenerEmpleado($request);

        $datos = $request->validate([
            'buscar' => [
                'nullable',
                'string',
                'max:100',
            ],
            'alerta' => [
                'nullable',
                Rule::in([
                    'AGOTADO',
                    'STOCK_BAJO',
                    'NORMAL',
                ]),
            ],
            'categoria' => [
                'nullable',
                'string',
                'max:100',
            ],
        ]);

        $inventarios = Inventario::query()
            ->with([
                'insumo.categoria',
                'usuarioCreador:id,name,usuario',
            ])
            ->where('id_sucursal', $empleado->id_sucursal)
            ->whereHas('insumo', function ($query) use ($empleado) {
                $query->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                );
            })
            ->when(
                !empty($datos['buscar']),
                function ($query) use ($datos) {
                    $buscar = trim($datos['buscar']);

                    $query->whereHas(
                        'insumo',
                        function ($subQuery) use ($buscar) {
                            $subQuery->where(
                                'nombre',
                                'like',
                                '%' . $buscar . '%'
                            );
                        }
                    );
                }
            )
            ->when(
                !empty($datos['categoria']),
                function ($query) use ($datos) {
                    $categoria = trim($datos['categoria']);

                    $query->whereHas(
                        'insumo.categoria',
                        function ($subQuery) use ($categoria) {
                            $subQuery->where(
                                'nombre',
                                'like',
                                '%' . $categoria . '%'
                            );
                        }
                    );
                }
            )
            ->when(
                ($datos['alerta'] ?? null) === 'AGOTADO',
                function ($query) {
                    $query->where('stock_actual', '<=', 0);
                }
            )
            ->when(
                ($datos['alerta'] ?? null) === 'STOCK_BAJO',
                function ($query) {
                    $query
                        ->where('stock_actual', '>', 0)
                        ->whereColumn(
                            'stock_actual',
                            '<=',
                            'stock_minimo'
                        );
                }
            )
            ->when(
                ($datos['alerta'] ?? null) === 'NORMAL',
                function ($query) {
                    $query->whereColumn(
                        'stock_actual',
                        '>',
                        'stock_minimo'
                    );
                }
            )
            ->orderByDesc('id_inventario')
            ->get();

        return response()->json([
            'inventarios' => $inventarios,
            'resumen' => [
                'total' => $inventarios->count(),
                'agotados' => $inventarios
                    ->where('estado_stock', 'AGOTADO')
                    ->count(),
                'stock_bajo' => $inventarios
                    ->where('estado_stock', 'STOCK_BAJO')
                    ->count(),
                'normales' => $inventarios
                    ->where('estado_stock', 'NORMAL')
                    ->count(),
            ],
        ]);
    }

    // Crea categoría, insumo e inventario
    public function store(Request $request): JsonResponse
    {
        $empleado = $this->obtenerEmpleado($request);
        $usuario = $request->user('api');

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
            ],
            'unidad_medida' => [
                'required',
                'string',
                'max:50',
            ],
            'categoria' => [
                'required',
                'string',
                'max:100',
            ],
            'prioridad_stock' => [
                'required',
                'string',
                'max:50',
            ],
            'stock_actual' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'stock_minimo' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $nombre = $this->normalizarNombre(
            $datos['nombre']
        );

        $categoriaNombre = $this->normalizarNombre(
            $datos['categoria']
        );

        $unidadMedida = mb_strtoupper(
            trim($datos['unidad_medida'])
        );

        $prioridadStock = mb_strtoupper(
            trim($datos['prioridad_stock'])
        );

        $inventario = DB::transaction(function () use (
            $empleado,
            $usuario,
            $datos,
            $nombre,
            $categoriaNombre,
            $unidadMedida,
            $prioridadStock
        ) {
            $categoria = CategoriaInsumo::query()
                ->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                )
                ->whereRaw(
                    'UPPER(nombre) = ?',
                    [mb_strtoupper($categoriaNombre)]
                )
                ->lockForUpdate()
                ->first();

            if (!$categoria) {
                $categoria = CategoriaInsumo::create([
                    'id_sucursal' => $empleado->id_sucursal,
                    'nombre' => $categoriaNombre,
                ]);
            }

            $insumoExistente = Insumo::query()
                ->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                )
                ->whereRaw(
                    'UPPER(nombre) = ?',
                    [mb_strtoupper($nombre)]
                )
                ->lockForUpdate()
                ->first();

            if ($insumoExistente) {
                throw ValidationException::withMessages([
                    'nombre' =>
                        'Ya existe un insumo con ese nombre en la sucursal.',
                ]);
            }

            $insumo = Insumo::create([
                'id_sucursal' => $empleado->id_sucursal,
                'id_categoria_insumo' =>
                    $categoria->id_categoria_insumo,
                'nombre' => $nombre,
                'unidad_medida' => $unidadMedida,
                'prioridad_stock' => $prioridadStock,
            ]);

            $inventario = Inventario::create([
                'id_sucursal' => $empleado->id_sucursal,
                'id_insumo' => $insumo->id_insumo,
                'id_user_crea' => $usuario->id,
                'stock_actual' => $datos['stock_actual'],
                'stock_minimo' => $datos['stock_minimo'],
            ]);

            $stockInicial = (float) $datos['stock_actual'];

            if ($stockInicial > 0) {
                $jornada = Jornada::query()
                    ->where(
                        'id_sucursal',
                        $empleado->id_sucursal
                    )
                    ->where('estado', 'ABIERTA')
                    ->latest('id_jornada')
                    ->first();

                MovimientoInventario::create([
                    'id_sucursal' => $empleado->id_sucursal,
                    'id_jornada' =>
                        $jornada?->id_jornada,
                    'id_insumo' => $insumo->id_insumo,
                    'id_user_crea' => $usuario->id,
                    'tipo_movimiento' => 'ENTRADA',
                    'motivo' => 'STOCK_INICIAL',
                    'cantidad' => number_format(
                        $stockInicial,
                        2,
                        '.',
                        ''
                    ),
                    'stock_anterior' => '0.00',
                    'stock_nuevo' => number_format(
                        $stockInicial,
                        2,
                        '.',
                        ''
                    ),
                    'referencia_tipo' => 'INVENTARIO',
                    'referencia_id' =>
                        $inventario->id_inventario,
                    'observacion' =>
                        $datos['observacion']
                        ?? 'Registro inicial del inventario.',
                ]);
            }

            return $inventario;
        });

        return response()->json([
            'message' =>
                'Insumo agregado al inventario correctamente.',
            'inventario' => $inventario->load([
                'insumo.categoria',
                'usuarioCreador:id,name,usuario',
            ]),
        ], 201);
    }

    // Registra una entrada o salida
    public function registrarMovimiento(
        Request $request
    ): JsonResponse {
        $empleado = $this->obtenerEmpleado($request);

        $datos = $request->validate([
            'id_insumo' => [
                'required',
                'integer',
                Rule::exists(
                    'insumos',
                    'id_insumo'
                )->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                ),
            ],
            'tipo_movimiento' => [
                'required',
                'string',
                Rule::in([
                    'ENTRADA',
                    'SALIDA',
                ]),
            ],
            'motivo' => [
                'required',
                'string',
                Rule::in([
                    'REPOSICION',
                    'COMPRA',
                    'AJUSTE_POSITIVO',
                    'CORTESIA_CLIENTE',
                    'CONSUMO_PERSONAL',
                    'MERMA',
                    'AJUSTE_NEGATIVO',
                ]),
            ],
            'cantidad' => [
                'required',
                'numeric',
                'min:0.01',
                'decimal:0,2',
            ],
            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $movimiento = $this
            ->movimientoService
            ->registrarMovimiento(
                $request->user('api'),
                $datos
            );

        return response()->json([
            'message' =>
                'Movimiento de inventario registrado correctamente.',
            'movimiento' => $movimiento->load([
                'insumo.categoria',
                'usuarioCreador:id,name,usuario',
                'jornada',
            ]),
        ], 201);
    }

    // Lista movimientos de la sucursal
    public function movimientos(
        Request $request
    ): JsonResponse {
        $empleado = $this->obtenerEmpleado($request);

        $datos = $request->validate([
            'id_insumo' => [
                'nullable',
                'integer',
            ],
            'tipo_movimiento' => [
                'nullable',
                Rule::in([
                    'ENTRADA',
                    'SALIDA',
                ]),
            ],
            'per_page' => [
                'nullable',
                'integer',
                'min:5',
                'max:100',
            ],
        ]);

        $movimientos = MovimientoInventario::query()
            ->with([
                'insumo.categoria',
                'usuarioCreador:id,name,usuario',
                'jornada:id_jornada,fecha,estado',
            ])
            ->where(
                'id_sucursal',
                $empleado->id_sucursal
            )
            ->when(
                !empty($datos['id_insumo']),
                function ($query) use ($datos) {
                    $query->where(
                        'id_insumo',
                        $datos['id_insumo']
                    );
                }
            )
            ->when(
                !empty($datos['tipo_movimiento']),
                function ($query) use ($datos) {
                    $query->where(
                        'tipo_movimiento',
                        $datos['tipo_movimiento']
                    );
                }
            )
            ->latest('id_movimiento')
            ->paginate(
                $datos['per_page'] ?? 50
            );

        return response()->json([
            'movimientos' => $movimientos,
        ]);
    }

    // Lista bebidas para el CAJERO
    public function bebidas(
        Request $request
    ): JsonResponse {
        $empleado = $this->obtenerEmpleado($request);

        $bebidas = Inventario::query()
            ->with([
                'insumo.categoria',
            ])
            ->where(
                'id_sucursal',
                $empleado->id_sucursal
            )
            ->whereHas(
                'insumo',
                function ($query) use ($empleado) {
                    $query
                        ->where(
                            'id_sucursal',
                            $empleado->id_sucursal
                        )
                        ->whereHas(
                            'categoria',
                            function ($subQuery) use ($empleado) {
                                $subQuery
                                    ->where(
                                        'id_sucursal',
                                        $empleado->id_sucursal
                                    )
                                    ->whereRaw(
                                        'UPPER(nombre) = ?',
                                        ['BEBIDAS']
                                    );
                            }
                        );
                }
            )
            ->orderBy('id_insumo')
            ->get();

        return response()->json([
            'bebidas' => $bebidas,
        ]);
    }

    private function normalizarNombre(string $valor): string
    {
        $valor = trim($valor);
        $valor = preg_replace('/\s+/', ' ', $valor);

        return mb_convert_case(
            $valor,
            MB_CASE_TITLE,
            'UTF-8'
        );
    }
}