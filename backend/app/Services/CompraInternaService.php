<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\CompraInterna;
use App\Models\Empleado;
use App\Models\Jornada;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompraInternaService
{
    public function obtenerSucursalUsuario(User $user): int
    {
        $user->loadMissing('empleado');

        if (!$user->empleado?->id_sucursal) {
            throw ValidationException::withMessages([
                'sucursal' => [
                    'El administrador no tiene una sucursal asignada.',
                ],
            ]);
        }

        return (int) $user->empleado->id_sucursal;
    }

    public function obtenerJornadaAbierta(int $idSucursal): Jornada
    {
        $jornada = Jornada::query()
            ->where('id_sucursal', $idSucursal)
            ->whereDate('fecha', now()->toDateString())
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$jornada) {
            throw ValidationException::withMessages([
                'jornada' => [
                    'No existe una jornada abierta para la sucursal.',
                ],
            ]);
        }

        return $jornada;
    }

    public function registrar(
        User $user,
        array $data
    ): CompraInterna {
        $idSucursal = $this->obtenerSucursalUsuario($user);
        $jornada = $this->obtenerJornadaAbierta($idSucursal);

        return DB::transaction(function () use (
            $user,
            $data,
            $idSucursal,
            $jornada
        ) {
            $caja = $this->obtenerCajaAbierta(
                (int) $data['id_caja'],
                $jornada->id_jornada,
                $idSucursal
            );

            $empleado = $this->obtenerEmpleadoActivo(
                (int) $data['id_empleado_comprador'],
                $idSucursal
            );

            $montoInicial = round(
                (float) $data['monto_entregado_inicial'],
                2
            );

            $this->validarEfectivoDisponible(
                $caja,
                $montoInicial
            );

            $fechaSalida = isset($data['fecha_hora_salida'])
                ? Carbon::parse(
                    $data['fecha_hora_salida'],
                    'America/La_Paz'
                )
                : now('America/La_Paz');

            if ($fechaSalida->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha_hora_salida' => [
                        'La hora de salida no puede estar en el futuro.',
                    ],
                ]);
            }

            return CompraInterna::create([
                'id_sucursal' => $idSucursal,
                'id_jornada' => $jornada->id_jornada,
                'id_caja' => $caja->id_caja,
                'id_empleado_comprador' => $empleado->id_empleado,
                'id_user_autoriza' => $user->id,

                'motivo' => trim($data['motivo']),
                'categoria' => strtoupper($data['categoria']),

                'monto_entregado_inicial' => $montoInicial,
                'monto_adicional' => 0,
                'total_entregado' => $montoInicial,

                'total_gastado' => null,
                'cambio_devuelto' => null,

                'entregas_adicionales' => [],
                'productos_comprados' => [],

                'fecha_hora_salida' => $fechaSalida
                    ->format('Y-m-d H:i:s'),

                'fecha_hora_regreso' => null,
                'estado' => 'PENDIENTE',

                'observacion' => isset($data['observacion'])
                    ? trim($data['observacion'])
                    : null,
            ]);
        });
    }

    public function agregarDinero(
        User $user,
        int $idCompra,
        array $data
    ): CompraInterna {
        $idSucursal = $this->obtenerSucursalUsuario($user);
        $jornada = $this->obtenerJornadaAbierta($idSucursal);

        return DB::transaction(function () use (
            $user,
            $idCompra,
            $data,
            $idSucursal,
            $jornada
        ) {
            $compra = CompraInterna::query()
                ->where('id_compra_interna', $idCompra)
                ->where('id_sucursal', $idSucursal)
                ->where('id_jornada', $jornada->id_jornada)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                throw ValidationException::withMessages([
                    'compra' => [
                        'La compra interna no existe.',
                    ],
                ]);
            }

            if ($compra->estado !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'estado' => [
                        'Solo se puede agregar dinero a una compra pendiente.',
                    ],
                ]);
            }

            $monto = round((float) $data['monto'], 2);

            $caja = $this->obtenerCajaAbierta(
                $compra->id_caja,
                $jornada->id_jornada,
                $idSucursal
            );

            $this->validarEfectivoDisponible(
                $caja,
                $monto,
                $compra->id_compra_interna
            );

            $entregas = $compra->entregas_adicionales ?? [];

            $entregas[] = [
                'monto' => $monto,
                'fecha_hora' => now('America/La_Paz')
                    ->format('Y-m-d H:i:s'),
                'id_user' => $user->id,
                'observacion' => isset($data['observacion'])
                    ? trim($data['observacion'])
                    : null,
            ];

            $montoAdicional = round(
                (float) $compra->monto_adicional + $monto,
                2
            );

            $totalEntregado = round(
                (float) $compra->monto_entregado_inicial
                + $montoAdicional,
                2
            );

            $compra->update([
                'monto_adicional' => $montoAdicional,
                'total_entregado' => $totalEntregado,
                'entregas_adicionales' => $entregas,
            ]);

            return $compra->fresh();
        });
    }

    public function finalizar(
        User $user,
        int $idCompra,
        array $data
    ): CompraInterna {
        $idSucursal = $this->obtenerSucursalUsuario($user);
        $jornada = $this->obtenerJornadaAbierta($idSucursal);

        return DB::transaction(function () use (
            $idCompra,
            $data,
            $idSucursal,
            $jornada
        ) {
            $compra = CompraInterna::query()
                ->where('id_compra_interna', $idCompra)
                ->where('id_sucursal', $idSucursal)
                ->where('id_jornada', $jornada->id_jornada)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                throw ValidationException::withMessages([
                    'compra' => [
                        'La compra interna no existe.',
                    ],
                ]);
            }

            if ($compra->estado !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'estado' => [
                        'La compra ya fue finalizada o anulada.',
                    ],
                ]);
            }

            $productos = collect($data['productos'])
                ->map(function ($producto) {
                    $cantidad = round(
                        (float) $producto['cantidad'],
                        2
                    );

                    $precioUnitario = round(
                        (float) $producto['precio_unitario'],
                        2
                    );

                    return [
                        'producto' => trim($producto['producto']),
                        'cantidad' => $cantidad,
                        'precio_unitario' => $precioUnitario,
                        'subtotal' => round(
                            $cantidad * $precioUnitario,
                            2
                        ),
                    ];
                })
                ->values()
                ->toArray();

            $totalCalculado = round(
                collect($productos)->sum('subtotal'),
                2
            );

            $totalGastado = round(
                (float) $data['total_gastado'],
                2
            );

            if (abs($totalCalculado - $totalGastado) > 0.01) {
                throw ValidationException::withMessages([
                    'total_gastado' => [
                        'El total gastado no coincide con los productos.',
                    ],
                ]);
            }

            $totalEntregado = round(
                (float) $compra->total_entregado,
                2
            );

            if ($totalGastado > $totalEntregado) {
                $faltante = round(
                    $totalGastado - $totalEntregado,
                    2
                );

                throw ValidationException::withMessages([
                    'total_gastado' => [
                        "Faltan Bs {$faltante}. Registra dinero adicional antes de finalizar.",
                    ],
                ]);
            }

            $cambio = round(
                $totalEntregado - $totalGastado,
                2
            );

            $fechaRegreso = isset($data['fecha_hora_regreso'])
                ? Carbon::parse(
                    $data['fecha_hora_regreso'],
                    'America/La_Paz'
                )
                : now('America/La_Paz');

            $fechaSalida = Carbon::parse(
                $compra->fecha_hora_salida
            );

            if ($fechaRegreso->lt($fechaSalida)) {
                throw ValidationException::withMessages([
                    'fecha_hora_regreso' => [
                        'La hora de regreso no puede ser anterior a la salida.',
                    ],
                ]);
            }

            if ($fechaRegreso->isFuture()) {
                throw ValidationException::withMessages([
                    'fecha_hora_regreso' => [
                        'La hora de regreso no puede estar en el futuro.',
                    ],
                ]);
            }

            $compra->update([
                'productos_comprados' => $productos,
                'total_gastado' => $totalGastado,
                'cambio_devuelto' => $cambio,
                'fecha_hora_regreso' => $fechaRegreso
                    ->format('Y-m-d H:i:s'),
                'estado' => 'FINALIZADA',
                'observacion' => isset($data['observacion'])
                    ? trim($data['observacion'])
                    : $compra->observacion,
            ]);

            return $compra->fresh();
        });
    }

    public function anular(
        User $user,
        int $idCompra,
        ?string $observacion
    ): CompraInterna {
        $idSucursal = $this->obtenerSucursalUsuario($user);
        $jornada = $this->obtenerJornadaAbierta($idSucursal);

        return DB::transaction(function () use (
            $idCompra,
            $observacion,
            $idSucursal,
            $jornada
        ) {
            $compra = CompraInterna::query()
                ->where('id_compra_interna', $idCompra)
                ->where('id_sucursal', $idSucursal)
                ->where('id_jornada', $jornada->id_jornada)
                ->lockForUpdate()
                ->first();

            if (!$compra) {
                throw ValidationException::withMessages([
                    'compra' => [
                        'La compra interna no existe.',
                    ],
                ]);
            }

            if ($compra->estado !== 'PENDIENTE') {
                throw ValidationException::withMessages([
                    'estado' => [
                        'Solo se puede anular una compra pendiente.',
                    ],
                ]);
            }

            $compra->update([
                'total_gastado' => 0,
                'cambio_devuelto' => $compra->total_entregado,
                'productos_comprados' => [],
                'fecha_hora_regreso' => now('America/La_Paz')
                    ->format('Y-m-d H:i:s'),
                'estado' => 'ANULADA',
                'observacion' => trim(
                    $observacion ?: 'Compra anulada y dinero devuelto.'
                ),
            ]);

            return $compra->fresh();
        });
    }

    private function obtenerCajaAbierta(
        int $idCaja,
        int $idJornada,
        int $idSucursal
    ): Caja {
        $caja = Caja::query()
            ->where('id_caja', $idCaja)
            ->where('id_jornada', $idJornada)
            ->whereHas('jornada', function ($query) use ($idSucursal) {
                $query->where('id_sucursal', $idSucursal);
            })
            ->where('estado', 'ABIERTA')
            ->lockForUpdate()
            ->first();

        if (!$caja) {
            throw ValidationException::withMessages([
                'id_caja' => [
                    'La caja no existe, está cerrada o pertenece a otra jornada.',
                ],
            ]);
        }

        return $caja;
    }

    private function obtenerEmpleadoActivo(
        int $idEmpleado,
        int $idSucursal
    ): Empleado {
        $empleado = Empleado::query()
            ->where('id_empleado', $idEmpleado)
            ->where('id_sucursal', $idSucursal)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'id_empleado_comprador' => [
                    'El empleado no existe, está inactivo o pertenece a otra sucursal.',
                ],
            ]);
        }

        return $empleado;
    }

    private function validarEfectivoDisponible(
        Caja $caja,
        float $nuevoMonto,
        ?int $ignorarCompra = null
    ): void {
        $pendiente = CompraInterna::query()
            ->where('id_caja', $caja->id_caja)
            ->where('estado', 'PENDIENTE')
            ->when(
                $ignorarCompra,
                fn ($query) => $query->where(
                    'id_compra_interna',
                    '!=',
                    $ignorarCompra
                )
            )
            ->sum('total_entregado');

        $gastosFinalizados = CompraInterna::query()
            ->where('id_caja', $caja->id_caja)
            ->where('estado', 'FINALIZADA')
            ->sum('total_gastado');

        $efectivoDisponible = round(
            (float) $caja->monto_inicial
            + (float) $caja->total_efectivo
            - (float) $pendiente
            - (float) $gastosFinalizados,
            2
        );

        if ($nuevoMonto > $efectivoDisponible) {
            throw ValidationException::withMessages([
                'monto' => [
                    'No existe suficiente efectivo disponible en la caja. '
                    . "Disponible: Bs {$efectivoDisponible}.",
                ],
            ]);
        }
    }
}