<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PagoService
{
    public function registrar(
        User $usuario,
        int $idPedido,
        array $datos
    ): Pago {
        return DB::transaction(function () use (
            $usuario,
            $idPedido,
            $datos
        ) {
            $empleado = $usuario->empleado()
                ->lockForUpdate()
                ->first();

            if (!$empleado) {
                throw ValidationException::withMessages([
                    'cajero' =>
                        'El usuario no está asociado a un empleado.',
                ]);
            }

            if (strtoupper((string) $empleado->estado) !== 'ACTIVO') {
                throw ValidationException::withMessages([
                    'cajero' =>
                        'El empleado se encuentra inactivo.',
                ]);
            }

            $pedido = Pedido::query()
                ->where('id_pedido', $idPedido)
                ->where('id_cajero', $empleado->id_empleado)
                ->lockForUpdate()
                ->first();

            if (!$pedido) {
                throw ValidationException::withMessages([
                    'pedido' =>
                        'El pedido no existe o no pertenece al cajero.',
                ]);
            }

            if (strtoupper((string) $pedido->estado) !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'pedido' =>
                        'El pedido ya fue pagado o no está pendiente.',
                ]);
            }

            if ($pedido->anulacion()->exists()) {
                throw ValidationException::withMessages([
                    'pedido' =>
                        'No se puede pagar un pedido anulado.',
                ]);
            }

            if (
                Pago::query()
                    ->where('id_pedido', $pedido->id_pedido)
                    ->exists()
            ) {
                throw ValidationException::withMessages([
                    'pedido' =>
                        'El pedido ya tiene un pago registrado.',
                ]);
            }

            $caja = Caja::query()
                ->where('id_jornada', $pedido->id_jornada)
                ->where('id_empleado', $empleado->id_empleado)
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if (!$caja) {
                throw ValidationException::withMessages([
                    'caja' =>
                        'El cajero debe tener una caja abierta.',
                ]);
            }

            $efectivoCentavos = (int) round(
                (float) $datos['monto_efectivo'] * 100
            );

            $qrCentavos = (int) round(
                (float) $datos['monto_qr'] * 100
            );

            $totalPagadoCentavos =
                $efectivoCentavos + $qrCentavos;

            $totalPedidoCentavos = (int) round(
                (float) $pedido->total * 100
            );

            if ($totalPagadoCentavos <= 0) {
                throw ValidationException::withMessages([
                    'pago' =>
                        'El total pagado debe ser mayor a cero.',
                ]);
            }

            if ($totalPagadoCentavos !== $totalPedidoCentavos) {
                throw ValidationException::withMessages([
                    'pago' =>
                        'La suma del efectivo y QR debe ser exactamente Bs '
                        . number_format(
                            $totalPedidoCentavos / 100,
                            2,
                            '.',
                            ''
                        )
                        . '.',
                ]);
            }

            $pago = Pago::create([
                'id_pedido' => $pedido->id_pedido,
                'id_user_crea' => $usuario->id,
                'monto_efectivo' => $this->formatearMonto(
                    $efectivoCentavos
                ),
                'monto_qr' => $this->formatearMonto(
                    $qrCentavos
                ),
                'total_pagado' => $this->formatearMonto(
                    $totalPagadoCentavos
                ),
                'fecha' => now(),
            ]);

            $totalEfectivoCajaCentavos = (int) round(
                (float) $caja->total_efectivo * 100
            );

            $totalQrCajaCentavos = (int) round(
                (float) $caja->total_qr * 100
            );

            $caja->update([
                'total_efectivo' => $this->formatearMonto(
                    $totalEfectivoCajaCentavos + $efectivoCentavos
                ),
                'total_qr' => $this->formatearMonto(
                    $totalQrCajaCentavos + $qrCentavos
                ),
            ]);

            $pedido->update([
                'estado' => 'PAGADO',
            ]);

            return $pago->load([
                'pedido.detalles.producto',
                'pedido.detalles.guarniciones',
                'usuarioCreador',
            ]);
        });
    }

    private function formatearMonto(int $centavos): string
    {
        return number_format(
            $centavos / 100,
            2,
            '.',
            ''
        );
    }
}