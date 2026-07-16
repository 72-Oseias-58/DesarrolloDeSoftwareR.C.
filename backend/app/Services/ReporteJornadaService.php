<?php

namespace App\Services;

use App\Models\CompraInterna;
use App\Models\Jornada;
use App\Models\Reporte;
use Illuminate\Validation\ValidationException;

class ReporteJornadaService
{
    public function prepararCierre(
    Jornada $jornada
): array {
    $jornada->load([
        'sucursal',
        'cajas.empleado',
    ]);

    $comprasPendientes = CompraInterna::with([
        'empleadoComprador',
        'caja.empleado',
    ])
        ->where(
            'id_jornada',
            $jornada->id_jornada
        )
        ->where('estado', 'PENDIENTE')
        ->orderBy('id_compra_interna')
        ->get();

    if ($comprasPendientes->isNotEmpty()) {
        return [
            'puede_cerrar' => false,

            'motivo_bloqueo' =>
                'COMPRAS_INTERNAS_PENDIENTES',

            'message' =>
                'No se puede cerrar la jornada mientras existan compras internas pendientes.',

            'compras_pendientes' =>
                $comprasPendientes
                    ->map(function ($compra) {
                        return [
                            'id_compra_interna' =>
                                $compra->id_compra_interna,

                            'id_caja' =>
                                $compra->id_caja,

                            'empleado_comprador' =>
                                $compra->empleadoComprador
                                    ?->nombre
                                ?? 'Empleado no disponible',

                            'motivo' =>
                                $compra->motivo,

                            'total_entregado' =>
                                round(
                                    (float) $compra
                                        ->total_entregado,
                                    2
                                ),

                            'fecha_hora_salida' =>
                                optional(
                                    $compra
                                        ->fecha_hora_salida
                                )->format(
                                    'Y-m-d H:i:s'
                                ),
                        ];
                    })
                    ->values()
                    ->all(),

            'cajas_abiertas' => [],
        ];
    }

    $comprasFinalizadas = CompraInterna::query()
        ->where(
            'id_jornada',
            $jornada->id_jornada
        )
        ->where('estado', 'FINALIZADA')
        ->get();

    $cajasAbiertas = $jornada->cajas
        ->where('estado', 'ABIERTA')
        ->map(function ($caja) use (
            $comprasFinalizadas
        ) {
            $gastosCaja = round(
                (float) $comprasFinalizadas
                    ->where(
                        'id_caja',
                        $caja->id_caja
                    )
                    ->sum('total_gastado'),
                2
            );

            $montoInicial = round(
                (float) $caja->monto_inicial,
                2
            );

            $ventasEfectivo = round(
                (float) $caja->total_efectivo,
                2
            );

            $ventasQr = round(
                (float) $caja->total_qr,
                2
            );

            $efectivoAntesGastos = round(
                $montoInicial + $ventasEfectivo,
                2
            );

            $efectivoEstimado = round(
                $efectivoAntesGastos - $gastosCaja,
                2
            );

            return [
                'id_caja' =>
                    $caja->id_caja,

                'id_empleado' =>
                    $caja->id_empleado,

                'cajero' =>
                    $caja->empleado?->nombre
                    ?? 'Cajero no disponible',

                'monto_inicial' =>
                    $montoInicial,

                'ventas_efectivo' =>
                    $ventasEfectivo,

                'ventas_qr' =>
                    $ventasQr,

                'efectivo_antes_gastos' =>
                    $efectivoAntesGastos,

                'gastos_compras_internas' =>
                    $gastosCaja,

                'efectivo_estimado' =>
                    $efectivoEstimado,

                'hora_apertura' =>
                    $caja->hora_apertura,

                'estado' =>
                    $caja->estado,
            ];
        })
        ->values()
        ->all();

    return [
        'puede_cerrar' => true,

        'motivo_bloqueo' => null,

        'message' => count($cajasAbiertas) > 0
            ? 'Existen cajas abiertas. Registra el efectivo físico contado de cada caja.'
            : 'Todas las cajas ya están cerradas. La jornada puede finalizarse.',

        'compras_pendientes' => [],

        'cantidad_cajas_abiertas' =>
            count($cajasAbiertas),

        'cajas_abiertas' =>
            $cajasAbiertas,
    ];
}
    public function generar(
        Jornada $jornada,
        int $idUsuarioCreador,
        array $conteosCajas
    ): Reporte {
        $reporteExistente = Reporte::where(
            'id_jornada',
            $jornada->id_jornada
        )->first();

        if ($reporteExistente) {
            throw ValidationException::withMessages([
                'jornada' => [
                    'Esta jornada ya tiene un reporte generado.',
                ],
            ]);
        }

        $this->validarComprasPendientes($jornada);

        $jornada->load([
            'sucursal',
            'cajas.empleado',
        ]);

        $cajas = $jornada->cajas;

        if ($cajas->isEmpty()) {
            throw ValidationException::withMessages([
                'cajas' => [
                    'La jornada no tiene cajas registradas.',
                ],
            ]);
        }

        $comprasFinalizadas = CompraInterna::with([
            'empleadoComprador',
            'caja.empleado',
        ])
            ->where(
                'id_jornada',
                $jornada->id_jornada
            )
            ->where('estado', 'FINALIZADA')
            ->orderBy('id_compra_interna')
            ->get();

        $conteosPorCaja = collect($conteosCajas)
            ->keyBy('id_caja');

        $resumenCajas = [];

        $totalVentasEfectivo = 0;
        $totalVentasQr = 0;
        $montoInicialTotal = 0;
        $efectivoAntesGastosTotal = 0;
        $totalGastosReales = 0;
        $efectivoEstimadoTotal = 0;
        $efectivoFisicoTotal = 0;
        $diferenciaTotal = 0;

        foreach ($cajas as $caja) {
            $comprasCaja = $comprasFinalizadas
                ->where('id_caja', $caja->id_caja);

            $gastosCaja = round(
                (float) $comprasCaja->sum(
                    'total_gastado'
                ),
                2
            );

            $montoInicial = round(
                (float) $caja->monto_inicial,
                2
            );

            $ventasEfectivo = round(
                (float) $caja->total_efectivo,
                2
            );

            $ventasQr = round(
                (float) $caja->total_qr,
                2
            );

            $efectivoAntesGastos = round(
                $montoInicial + $ventasEfectivo,
                2
            );

            $efectivoEstimado = round(
                $efectivoAntesGastos - $gastosCaja,
                2
            );

            $conteo = $conteosPorCaja->get(
                $caja->id_caja
            );

            if ($caja->estado === 'ABIERTA') {
                if (!$conteo) {
                    throw ValidationException::withMessages([
                        'cajas' => [
                            "Falta registrar el efectivo físico de la caja #{$caja->id_caja}.",
                        ],
                    ]);
                }

                $efectivoFisico = round(
                    (float) (
                        $conteo['monto_fisico'] ?? -1
                    ),
                    2
                );

                if ($efectivoFisico < 0) {
                    throw ValidationException::withMessages([
                        'cajas' => [
                            "El monto físico de la caja #{$caja->id_caja} no es válido.",
                        ],
                    ]);
                }

                $diferencia = round(
                    $efectivoFisico - $efectivoEstimado,
                    2
                );

                $observacion = trim(
                    (string) (
                        $conteo['observacion'] ?? ''
                    )
                );

                if (
                    abs($diferencia) > 0.009
                    && $observacion === ''
                ) {
                    throw ValidationException::withMessages([
                        'cajas' => [
                            "Debe registrar una observación para la diferencia de la caja #{$caja->id_caja}.",
                        ],
                    ]);
                }

                $caja->update([
                    'monto_final' => $efectivoFisico,
                    'diferencia' => $diferencia,
                    'hora_cierre' => now()->format(
                        'H:i:s'
                    ),
                    'estado' => 'CERRADA',
                ]);
            } else {
                $efectivoFisico = round(
                    (float) $caja->monto_final,
                    2
                );

                $diferencia = round(
                    (float) $caja->diferencia,
                    2
                );

                $observacion = trim(
                    (string) (
                        $conteo['observacion'] ?? ''
                    )
                );
            }

            $resumenCajas[] = [
                'id_caja' => $caja->id_caja,

                'id_empleado' => $caja->id_empleado,

                'cajero' => $caja->empleado?->nombre
                    ?? 'Cajero no disponible',

                'monto_inicial' => $montoInicial,

                'ventas_efectivo' => $ventasEfectivo,

                'ventas_qr' => $ventasQr,

                'efectivo_antes_gastos' =>
                    $efectivoAntesGastos,

                'gastos_reales' => $gastosCaja,

                'efectivo_estimado' =>
                    $efectivoEstimado,

                'efectivo_fisico' => $efectivoFisico,

                'diferencia' => $diferencia,

                'estado_diferencia' =>
                    $this->obtenerEstadoDiferencia(
                        $diferencia
                    ),

                'observacion' => $observacion ?: null,

                'hora_apertura' =>
                    $caja->hora_apertura,

                'hora_cierre' =>
                    $caja->hora_cierre,

                'estado' => 'CERRADA',
            ];

            $totalVentasEfectivo += $ventasEfectivo;
            $totalVentasQr += $ventasQr;
            $montoInicialTotal += $montoInicial;

            $efectivoAntesGastosTotal +=
                $efectivoAntesGastos;

            $totalGastosReales += $gastosCaja;

            $efectivoEstimadoTotal +=
                $efectivoEstimado;

            $efectivoFisicoTotal +=
                $efectivoFisico;

            $diferenciaTotal += $diferencia;
        }

        $totalVentasEfectivo = round(
            $totalVentasEfectivo,
            2
        );

        $totalVentasQr = round(
            $totalVentasQr,
            2
        );

        $totalVentas = round(
            $totalVentasEfectivo + $totalVentasQr,
            2
        );

        $montoInicialTotal = round(
            $montoInicialTotal,
            2
        );

        $efectivoAntesGastosTotal = round(
            $efectivoAntesGastosTotal,
            2
        );

        $totalGastosReales = round(
            $totalGastosReales,
            2
        );

        $efectivoEstimadoTotal = round(
            $efectivoEstimadoTotal,
            2
        );

        $efectivoFisicoTotal = round(
            $efectivoFisicoTotal,
            2
        );

        $diferenciaTotal = round(
            $diferenciaTotal,
            2
        );

        $dineroInicialEntregado = round(
            (float) $comprasFinalizadas->sum(
                'monto_entregado_inicial'
            ),
            2
        );

        $dineroAdicionalEntregado = round(
            (float) $comprasFinalizadas->sum(
                'monto_adicional'
            ),
            2
        );

        $dineroTotalEntregado = round(
            (float) $comprasFinalizadas->sum(
                'total_entregado'
            ),
            2
        );

        $totalCambioDevuelto = round(
            (float) $comprasFinalizadas->sum(
                'cambio_devuelto'
            ),
            2
        );

        $resultadoOperativo = round(
            $totalVentas - $totalGastosReales,
            2
        );

        $resumenCompras = $comprasFinalizadas
            ->map(function ($compra) {
                return [
                    'id_compra_interna' =>
                        $compra->id_compra_interna,

                    'id_caja' =>
                        $compra->id_caja,

                    'empleado_comprador' =>
                        $compra->empleadoComprador
                            ?->nombre
                        ?? 'Empleado no disponible',

                    'motivo' =>
                        $compra->motivo,

                    'categoria' =>
                        $compra->categoria,

                    'dinero_inicial' => round(
                        (float) $compra
                            ->monto_entregado_inicial,
                        2
                    ),

                    'dinero_adicional' => round(
                        (float) $compra
                            ->monto_adicional,
                        2
                    ),

                    'total_entregado' => round(
                        (float) $compra
                            ->total_entregado,
                        2
                    ),

                    'total_gastado' => round(
                        (float) $compra
                            ->total_gastado,
                        2
                    ),

                    'cambio_devuelto' => round(
                        (float) $compra
                            ->cambio_devuelto,
                        2
                    ),

                    'productos' =>
                        $compra->productos_comprados
                        ?? [],

                    'fecha_hora_salida' =>
                        optional(
                            $compra->fecha_hora_salida
                        )->format('Y-m-d H:i:s'),

                    'fecha_hora_regreso' =>
                        optional(
                            $compra->fecha_hora_regreso
                        )->format('Y-m-d H:i:s'),
                ];
            })
            ->values()
            ->all();

        return Reporte::create([
            'id_sucursal' =>
                $jornada->id_sucursal,

            'id_jornada' =>
                $jornada->id_jornada,

            'id_user_crea' =>
                $idUsuarioCreador,

            'tipo' =>
                'CIERRE_JORNADA',

            'total_ventas' =>
                $totalVentas,

            'total_efectivo' =>
                $totalVentasEfectivo,

            'total_qr' =>
                $totalVentasQr,

            'monto_inicial_total_cajas' =>
                $montoInicialTotal,

            'efectivo_antes_gastos' =>
                $efectivoAntesGastosTotal,

            'cantidad_compras_internas' =>
                $comprasFinalizadas->count(),

            'dinero_entregado_inicial' =>
                $dineroInicialEntregado,

            'dinero_adicional_entregado' =>
                $dineroAdicionalEntregado,

            'dinero_total_entregado' =>
                $dineroTotalEntregado,

            'total_gastos_reales' =>
                $totalGastosReales,

            'total_cambio_devuelto' =>
                $totalCambioDevuelto,

            'efectivo_estimado_total' =>
                $efectivoEstimadoTotal,

            'efectivo_fisico_total' =>
                $efectivoFisicoTotal,

            'diferencia_total' =>
                $diferenciaTotal,

            'resultado_operativo' =>
                $resultadoOperativo,

            'cantidad_cajas' =>
                $cajas->count(),

            'resumen_cajas' =>
                $resumenCajas,

            'resumen_compras' =>
                $resumenCompras,

            'descripcion' =>
                $this->crearDescripcion(
                    $cajas->count(),
                    $comprasFinalizadas->count(),
                    $diferenciaTotal
                ),

            'fecha' => now(),
        ]);
    }

    private function validarComprasPendientes(
        Jornada $jornada
    ): void {
        $comprasPendientes = CompraInterna::with([
            'empleadoComprador',
            'caja',
        ])
            ->where(
                'id_jornada',
                $jornada->id_jornada
            )
            ->where('estado', 'PENDIENTE')
            ->get();

        if ($comprasPendientes->isEmpty()) {
            return;
        }

        $detalle = $comprasPendientes
            ->map(function ($compra) {
                $empleado = $compra
                    ->empleadoComprador?->nombre
                    ?? 'Empleado no disponible';

                return [
                    'id_compra_interna' =>
                        $compra->id_compra_interna,

                    'empleado' => $empleado,

                    'motivo' => $compra->motivo,

                    'total_entregado' =>
                        $compra->total_entregado,

                    'id_caja' =>
                        $compra->id_caja,
                ];
            })
            ->values()
            ->all();

        throw ValidationException::withMessages([
            'compras_pendientes' => [
                'No se puede cerrar la jornada mientras existan compras internas pendientes.',
            ],
            'detalle_compras_pendientes' => [
                json_encode($detalle),
            ],
        ]);
    }

    private function obtenerEstadoDiferencia(
        float $diferencia
    ): string {
        if ($diferencia < -0.009) {
            return 'FALTANTE';
        }

        if ($diferencia > 0.009) {
            return 'SOBRANTE';
        }

        return 'CUADRA';
    }

    private function crearDescripcion(
        int $cantidadCajas,
        int $cantidadCompras,
        float $diferenciaTotal
    ): string {
        $estadoDiferencia =
            $this->obtenerEstadoDiferencia(
                $diferenciaTotal
            );

        return implode(' ', [
            'La jornada fue cerrada correctamente.',
            "Se cerraron {$cantidadCajas} cajas.",
            "Se registraron {$cantidadCompras} compras internas finalizadas.",
            "Estado general de caja: {$estadoDiferencia}.",
        ]);
    }
}