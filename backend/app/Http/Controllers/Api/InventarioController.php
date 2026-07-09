<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class InventarioController extends Controller
{
    public function __construct(
        private readonly MovimientoInventarioService $movimientoService
    ) {
    }

    public function bebidas(Request $request): JsonResponse
    {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            return response()->json([
                'message' => 'El usuario no está asociado a un empleado.',
            ], 422);
        }

        $bebidas = Inventario::query()
            ->with([
                'insumo:id_insumo,nombre,unidad_medida,prioridad_stock,id_categoria_insumo',
            ])
            ->where('id_sucursal', $empleado->id_sucursal)
            ->whereHas('insumo', function ($query) {
                $query->whereHas('categoria', function ($subQuery) {
                    $subQuery->where('nombre', 'Bebidas');
                });
            })
            ->orderBy('id_insumo')
            ->get();

        return response()->json([
            'bebidas' => $bebidas,
        ]);
    }

    public function registrarMovimiento(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'id_insumo' => [
                'required',
                'integer',
                'exists:insumos,id_insumo',
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

        $movimiento = $this->movimientoService
            ->registrarMovimiento(
                $request->user('api'),
                $datos
            );

        return response()->json([
            'message' => 'Movimiento de inventario registrado correctamente.',
            'movimiento' => $movimiento->load([
                'sucursal',
                'jornada',
                'insumo',
                'usuarioCreador',
            ]),
        ], 201);
    }

    public function movimientos(Request $request): JsonResponse
    {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            return response()->json([
                'message' => 'El usuario no está asociado a un empleado.',
            ], 422);
        }

        $movimientos = MovimientoInventario::query()
            ->with([
                'insumo:id_insumo,nombre,unidad_medida',
                'usuarioCreador:id,name,usuario',
            ])
            ->where('id_sucursal', $empleado->id_sucursal)
            ->latest('id_movimiento')
            ->limit(50)
            ->get();

        return response()->json([
            'movimientos' => $movimientos,
        ]);
    }
}