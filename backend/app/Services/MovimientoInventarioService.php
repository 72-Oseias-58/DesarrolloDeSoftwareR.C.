<?php

namespace App\Services;

use App\Models\Empleado;
use App\Models\Inventario;
use App\Models\Jornada;
use App\Models\MovimientoInventario;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoInventarioService
{
    public function registrarMovimiento(
        User $usuario,
        array $datos
    ): MovimientoInventario {
        return DB::transaction(function () use (
            $usuario,
            $datos
        ) {
            $empleado = Empleado::query()
                ->where('id_user', $usuario->id)
                ->lockForUpdate()
                ->first();

            if (!$empleado) {
                throw ValidationException::withMessages([
                    'empleado' =>
                        'El usuario no está asociado a un empleado.',
                ]);
            }

            if (
                strtoupper((string) $empleado->estado)
                !== 'ACTIVO'
            ) {
                throw ValidationException::withMessages([
                    'empleado' =>
                        'El empleado se encuentra inactivo.',
                ]);
            }

            $jornada = Jornada::query()
                ->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                )
                ->where('estado', 'ABIERTA')
                ->latest('id_jornada')
                ->lockForUpdate()
                ->first();

            if (!$jornada) {
                throw ValidationException::withMessages([
                    'jornada' =>
                        'No existe una jornada abierta en la sucursal.',
                ]);
            }

            $tipoMovimiento = mb_strtoupper(
                trim($datos['tipo_movimiento'])
            );

            $motivo = mb_strtoupper(
                trim($datos['motivo'])
            );

            $this->validarTipoYMotivo(
                $tipoMovimiento,
                $motivo
            );

            $cantidadCentavos = (int) round(
                (float) $datos['cantidad'] * 100
            );

            if ($cantidadCentavos <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' =>
                        'La cantidad debe ser mayor a cero.',
                ]);
            }

            $inventario = Inventario::query()
                ->where(
                    'id_sucursal',
                    $empleado->id_sucursal
                )
                ->where(
                    'id_insumo',
                    $datos['id_insumo']
                )
                ->whereHas(
                    'insumo',
                    function ($query) use ($empleado) {
                        $query->where(
                            'id_sucursal',
                            $empleado->id_sucursal
                        );
                    }
                )
                ->lockForUpdate()
                ->first();

            if (!$inventario) {
                throw ValidationException::withMessages([
                    'inventario' =>
                        'El insumo no pertenece al inventario de la sucursal.',
                ]);
            }

            $stockAnteriorCentavos = (int) round(
                (float) $inventario->stock_actual * 100
            );

            if ($tipoMovimiento === 'ENTRADA') {
                $stockNuevoCentavos =
                    $stockAnteriorCentavos
                    + $cantidadCentavos;
            } else {
                if (
                    $stockAnteriorCentavos
                    < $cantidadCentavos
                ) {
                    throw ValidationException::withMessages([
                        'stock' =>
                            'Stock insuficiente. Disponible: '
                            . $this->formatearCantidad(
                                $stockAnteriorCentavos
                            )
                            . '. Solicitado: '
                            . $this->formatearCantidad(
                                $cantidadCentavos
                            )
                            . '.',
                    ]);
                }

                $stockNuevoCentavos =
                    $stockAnteriorCentavos
                    - $cantidadCentavos;
            }

            $inventario->update([
                'stock_actual' =>
                    $this->formatearCantidad(
                        $stockNuevoCentavos
                    ),
            ]);

            return MovimientoInventario::create([
                'id_sucursal' =>
                    $empleado->id_sucursal,
                'id_jornada' =>
                    $jornada->id_jornada,
                'id_insumo' =>
                    $inventario->id_insumo,
                'id_user_crea' => $usuario->id,
                'tipo_movimiento' =>
                    $tipoMovimiento,
                'motivo' => $motivo,
                'cantidad' =>
                    $this->formatearCantidad(
                        $cantidadCentavos
                    ),
                'stock_anterior' =>
                    $this->formatearCantidad(
                        $stockAnteriorCentavos
                    ),
                'stock_nuevo' =>
                    $this->formatearCantidad(
                        $stockNuevoCentavos
                    ),
                'referencia_tipo' =>
                    $datos['referencia_tipo'] ?? null,
                'referencia_id' =>
                    $datos['referencia_id'] ?? null,
                'observacion' =>
                    $datos['observacion'] ?? null,
            ]);
        });
    }

    public function registrarSalidaVenta(
        User $usuario,
        int $idSucursal,
        int $idJornada,
        int $idInsumo,
        int $idPedido,
        float $cantidad
    ): MovimientoInventario {
        return DB::transaction(function () use (
            $usuario,
            $idSucursal,
            $idJornada,
            $idInsumo,
            $idPedido,
            $cantidad
        ) {
            $cantidadCentavos = (int) round(
                $cantidad * 100
            );

            if ($cantidadCentavos <= 0) {
                throw ValidationException::withMessages([
                    'cantidad' =>
                        'La cantidad debe ser mayor a cero.',
                ]);
            }

            $inventario = Inventario::query()
                ->where(
                    'id_sucursal',
                    $idSucursal
                )
                ->where(
                    'id_insumo',
                    $idInsumo
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
                ->lockForUpdate()
                ->first();

            if (!$inventario) {
                throw ValidationException::withMessages([
                    'inventario' =>
                        'La bebida no pertenece a la sucursal del pedido.',
                ]);
            }

            $stockAnteriorCentavos = (int) round(
                (float) $inventario->stock_actual * 100
            );

            if (
                $stockAnteriorCentavos
                < $cantidadCentavos
            ) {
                throw ValidationException::withMessages([
                    'stock' =>
                        'Stock insuficiente. Disponible: '
                        . $this->formatearCantidad(
                            $stockAnteriorCentavos
                        )
                        . '. Solicitado: '
                        . $this->formatearCantidad(
                            $cantidadCentavos
                        )
                        . '.',
                ]);
            }

            $stockNuevoCentavos =
                $stockAnteriorCentavos
                - $cantidadCentavos;

            $inventario->update([
                'stock_actual' =>
                    $this->formatearCantidad(
                        $stockNuevoCentavos
                    ),
            ]);

            return MovimientoInventario::create([
                'id_sucursal' => $idSucursal,
                'id_jornada' => $idJornada,
                'id_insumo' => $idInsumo,
                'id_user_crea' => $usuario->id,
                'tipo_movimiento' => 'SALIDA',
                'motivo' => 'VENTA',
                'cantidad' =>
                    $this->formatearCantidad(
                        $cantidadCentavos
                    ),
                'stock_anterior' =>
                    $this->formatearCantidad(
                        $stockAnteriorCentavos
                    ),
                'stock_nuevo' =>
                    $this->formatearCantidad(
                        $stockNuevoCentavos
                    ),
                'referencia_tipo' => 'PEDIDO',
                'referencia_id' => $idPedido,
                'observacion' =>
                    'Salida automática por venta de bebida.',
            ]);
        });
    }

    private function validarTipoYMotivo(
        string $tipoMovimiento,
        string $motivo
    ): void {
        $motivosEntrada = [
            'REPOSICION',
            'COMPRA',
            'AJUSTE_POSITIVO',
        ];

        $motivosSalida = [
            'CORTESIA_CLIENTE',
            'CONSUMO_PERSONAL',
            'MERMA',
            'AJUSTE_NEGATIVO',
        ];

        if (
            $tipoMovimiento === 'ENTRADA'
            && in_array(
                $motivo,
                $motivosEntrada,
                true
            )
        ) {
            return;
        }

        if (
            $tipoMovimiento === 'SALIDA'
            && in_array(
                $motivo,
                $motivosSalida,
                true
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'motivo' =>
                'El motivo no corresponde al tipo de movimiento.',
        ]);
    }

    private function formatearCantidad(
        int $centavos
    ): string {
        return number_format(
            $centavos / 100,
            2,
            '.',
            ''
        );
    }
}