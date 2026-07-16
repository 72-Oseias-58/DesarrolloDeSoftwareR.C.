<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarPagoRequest;
use App\Models\Caja;
use App\Models\Empleado;
use App\Models\Jornada;
use App\Models\Pago;
use App\Models\Pedido;
use App\Services\TicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoController extends Controller
{
    public function __construct(
        private readonly TicketService $ticketService
    ) {
    }

    public function pedidosPendientes(Request $request)
    {
        $empleado = $this->obtenerEmpleadoActivo($request);

        $jornada = $this->obtenerJornadaAbierta($empleado->id_sucursal);

        $pedidos = Pedido::query()
            ->with([
                'sucursal',
                'cajero',
                'detalles.producto',
                'detalles.guarniciones',
            ])
            ->where('id_sucursal', $empleado->id_sucursal)
            ->where('id_jornada', $jornada->id_jornada)
            ->where('estado', 'PENDIENTE')
            ->orderBy('id_pedido')
            ->get();

        return response()->json([
            'message' => 'Pedidos pendientes obtenidos correctamente.',
            'pedidos' => $pedidos,
        ]);
    }

    public function pagar(
        RegistrarPagoRequest $request,
        int $idPedido
    ) {
        $usuario = $request->user('api');
        $empleado = $this->obtenerEmpleadoActivo($request);

        $resultado = DB::transaction(function () use (
            $request,
            $usuario,
            $empleado,
            $idPedido
        ) {
            $jornada = $this->obtenerJornadaAbierta(
                $empleado->id_sucursal,
                true
            );

            $this->validarCajaAbierta(
                $jornada->id_jornada,
                $empleado->id_empleado
            );

            $pedido = Pedido::query()
                ->where('id_pedido', $idPedido)
                ->where('id_sucursal', $empleado->id_sucursal)
                ->where('id_jornada', $jornada->id_jornada)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                throw ValidationException::withMessages([
                    'pedido' => [
                        'El pedido no existe o no pertenece a la jornada actual.',
                    ],
                ]);
            }

            if (strtoupper((string) $pedido->estado) !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'pedido' => [
                        'Solo se pueden pagar pedidos pendientes.',
                    ],
                ]);
            }

            $pagoExistente = Pago::query()
                ->where('id_pedido', $pedido->id_pedido)
                ->lockForUpdate()
                ->first();

            if ($pagoExistente) {
                throw ValidationException::withMessages([
                    'pedido' => [
                        'Este pedido ya tiene un pago registrado.',
                    ],
                ]);
            }

            $totalPedidoCentavos = (int) round(
                (float) $pedido->total * 100
            );

            $montoEfectivoCentavos = (int) round(
                (float) $request->input('monto_efectivo') * 100
            );

            if ($montoEfectivoCentavos < $totalPedidoCentavos) {
                throw ValidationException::withMessages([
                    'monto_efectivo' => [
                        'El efectivo recibido no cubre el total del pedido.',
                    ],
                ]);
            }

            $pago = Pago::create([
                'id_pedido' => $pedido->id_pedido,
                'id_user_crea' => $usuario->id,
                'monto_efectivo' => number_format(
                    $montoEfectivoCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'monto_qr' => '0.00',
                'total_pagado' => number_format(
                    $totalPedidoCentavos / 100,
                    2,
                    '.',
                    ''
                ),
                'fecha' => now(),
            ]);

            $pedido->update([
                'estado' => 'PAGADO',
            ]);

            $pedido = Pedido::query()
                ->with([
                    'sucursal',
                    'jornada',
                    'cajero',
                    'pago',
                    'detalles.producto',
                    'detalles.guarniciones',
                ])
                ->where('id_pedido', $pedido->id_pedido)
                ->first();

            $tickets = $this->ticketService->generar($pedido);

            return [
                'pedido' => $pedido,
                'pago' => $pago,
                'ticket_cliente' => $tickets['ticket_cliente'],
                'ficha_mesero' => $tickets['ficha_mesero'],
            ];
        });

        return response()->json([
            'message' => 'Pago registrado correctamente.',
            'pedido' => $resultado['pedido'],
            'pago' => $resultado['pago'],
            'ticket_cliente' => $resultado['ticket_cliente'],
            'ficha_mesero' => $resultado['ficha_mesero'],
        ], 201);
    }

    private function obtenerEmpleadoActivo(Request $request): Empleado
    {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado' => [
                    'El usuario no está asociado a un empleado.',
                ],
            ]);
        }

        if (strtoupper((string) $empleado->estado) !== 'ACTIVO') {
            throw ValidationException::withMessages([
                'empleado' => [
                    'El empleado se encuentra inactivo.',
                ],
            ]);
        }

        if (!$empleado->id_sucursal) {
            throw ValidationException::withMessages([
                'sucursal' => [
                    'El empleado no tiene sucursal asignada.',
                ],
            ]);
        }

        return $empleado;
    }

    private function obtenerJornadaAbierta(
        int $idSucursal,
        bool $bloquear = false
    ): Jornada {
        $query = Jornada::query()
            ->where('id_sucursal', $idSucursal)
            ->whereDate('fecha', now()->toDateString())
            ->where('estado', 'ABIERTA');

        if ($bloquear) {
            $query->lockForUpdate();
        }

        $jornada = $query->first();

        if (!$jornada) {
            throw ValidationException::withMessages([
                'jornada' => [
                    'No existe una jornada abierta para hoy.',
                ],
            ]);
        }

        return $jornada;
    }

    private function validarCajaAbierta(
        int $idJornada,
        int $idEmpleado
    ): void {
        $caja = Caja::query()
            ->where('id_jornada', $idJornada)
            ->where('id_empleado', $idEmpleado)
            ->where('estado', 'ABIERTA')
            ->lockForUpdate()
            ->first();

        if (!$caja) {
            throw ValidationException::withMessages([
                'caja' => [
                    'El cajero debe tener una caja abierta para registrar pagos.',
                ],
            ]);
        }
    }
}