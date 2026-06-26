<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Http\Requests\StorePedidoRequest;
use App\Models\Pedido;
use App\Services\PagoService;
use App\Services\PedidoService;
use Illuminate\Http\JsonResponse;

class PedidoController extends Controller
{
    public function __construct(
        private readonly PedidoService $pedidoService,
        private readonly PagoService $pagoService
    ) {
    }

    public function store(
        StorePedidoRequest $request
    ): JsonResponse {
        $pedido = $this->pedidoService->crear(
            auth('api')->user(),
            $request->validated()
        );

        return response()->json([
            'message' => 'Pedido registrado correctamente.',
            'pedido' => $pedido,
        ], 201);
    }

    public function pendientes(): JsonResponse
    {
        $usuario = auth('api')->user();
        $empleado = $usuario->empleado;

        if (!$empleado) {
            return response()->json([
                'message' =>
                    'El usuario no está asociado a un empleado.',
            ], 422);
        }

        $pedidos = Pedido::query()
            ->with([
                'detalles.producto',
                'detalles.guarniciones',
            ])
            ->where('id_cajero', $empleado->id_empleado)
            ->where('estado', 'PENDIENTE')
            ->whereDoesntHave('pago')
            ->whereDoesntHave('anulacion')
            ->orderByDesc('fecha')
            ->get();

        return response()->json([
            'pedidos' => $pedidos,
        ]);
    }

    public function registrarPago(
        RegistrarPagoRequest $request,
        int $id
    ): JsonResponse {
        $pago = $this->pagoService->registrar(
            auth('api')->user(),
            $id,
            $request->validated()
        );

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'metodo_pago' => $pago->metodo_pago,
            'pago' => $pago,
        ], 201);
    }
}