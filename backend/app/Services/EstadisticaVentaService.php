<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EstadisticaVentaService
{
    /**
     * Estadísticas generales de todas las sucursales.
     */
    public function obtenerGlobal(array $filtros): array
    {
        $periodo = $this->resolverPeriodo($filtros);

        $sucursales = DB::table('sucursales')
            ->select([
                'id_sucursal',
                'nombre',
                'estado',
            ])
            ->orderBy('nombre')
            ->get();

        return $this->construirRespuesta(
            sucursales: $sucursales,
            periodo: $periodo
        );
    }

    /**
     * Estadísticas de una sucursal específica.
     */
    public function obtenerSucursal(
        int $idSucursal,
        array $filtros
    ): array {
        $periodo = $this->resolverPeriodo($filtros);

        $sucursal = DB::table('sucursales')
            ->select([
                'id_sucursal',
                'nombre',
                'estado',
            ])
            ->where('id_sucursal', $idSucursal)
            ->first();

        if (!$sucursal) {
            throw ValidationException::withMessages([
                'id_sucursal' => [
                    'La sucursal seleccionada no existe.',
                ],
            ]);
        }

        return $this->construirRespuesta(
            sucursales: collect([$sucursal]),
            periodo: $periodo,
            idSucursal: $idSucursal
        );
    }

    /**
     * Construye una respuesta uniforme para los dashboards.
     */
    private function construirRespuesta(
        Collection $sucursales,
        array $periodo,
        ?int $idSucursal = null
    ): array {
        $categorias = $this->generarCategorias(
            $periodo['desde'],
            $periodo['hasta'],
            $periodo['agrupacion']
        );

        $metricas = $this->obtenerMetricas(
            $periodo['desde'],
            $periodo['hasta'],
            $idSucursal
        );

        $series = $this->obtenerSeries(
            $sucursales,
            $categorias,
            $periodo['desde'],
            $periodo['hasta'],
            $periodo['agrupacion'],
            $idSucursal
        );

        return [
            'periodo' => [
                'tipo' => $periodo['tipo'],
                'desde' => $periodo['desde']->format('Y-m-d H:i:s'),
                'hasta' => $periodo['hasta']->format('Y-m-d H:i:s'),
                'agrupacion' => $periodo['agrupacion'],
            ],

            'metricas' => $metricas,

            'categorias' => array_values($categorias),

            'series' => $series,
        ];
    }

    /**
     * Consulta base de ventas válidas.
     *
     * Una venta válida debe:
     * - tener pedido PAGADO;
     * - tener un pago registrado;
     * - cubrir exactamente el total del pedido;
     * - no encontrarse anulada.
     */
    private function consultaVentasValidas(
        Carbon $desde,
        Carbon $hasta,
        ?int $idSucursal = null
    ): Builder {
        $consulta = DB::table('pagos as pa')
            ->join(
                'pedidos as pe',
                'pe.id_pedido',
                '=',
                'pa.id_pedido'
            )
            ->join(
                'sucursales as su',
                'su.id_sucursal',
                '=',
                'pe.id_sucursal'
            )
            ->where('pe.estado', 'PAGADO')
            ->whereBetween('pa.fecha', [
                $desde->format('Y-m-d H:i:s'),
                $hasta->format('Y-m-d H:i:s'),
            ])
            ->whereColumn(
                'pa.total_pagado',
                '=',
                'pe.total'
            )
            ->whereNotExists(function ($subconsulta) {
                $subconsulta
                    ->selectRaw('1')
                    ->from('anulaciones_pedido as an')
                    ->whereColumn(
                        'an.id_pedido',
                        'pe.id_pedido'
                    );
            });

        if ($idSucursal !== null) {
            $consulta->where(
                'pe.id_sucursal',
                $idSucursal
            );
        }

        return $consulta;
    }

    /**
     * Obtiene las tarjetas de métricas.
     */
    private function obtenerMetricas(
        Carbon $desde,
        Carbon $hasta,
        ?int $idSucursal = null
    ): array {
        $resultado = $this->consultaVentasValidas(
            $desde,
            $hasta,
            $idSucursal
        )
            ->selectRaw(
                '
                COALESCE(SUM(pa.total_pagado), 0) AS total_ventas,
                COALESCE(SUM(pa.monto_efectivo), 0) AS total_efectivo,
                COALESCE(SUM(pa.monto_qr), 0) AS total_qr,
                COUNT(DISTINCT pe.id_pedido) AS cantidad_pedidos
                '
            )
            ->first();

        $totalVentas = round(
            (float) ($resultado->total_ventas ?? 0),
            2
        );

        $cantidadPedidos = (int) (
            $resultado->cantidad_pedidos ?? 0
        );

        $ticketPromedio = $cantidadPedidos > 0
            ? round($totalVentas / $cantidadPedidos, 2)
            : 0;

        return [
            'total_ventas' => $totalVentas,
            'cantidad_pedidos' => $cantidadPedidos,
            'ticket_promedio' => $ticketPromedio,

            'total_efectivo' => round(
                (float) ($resultado->total_efectivo ?? 0),
                2
            ),

            'total_qr' => round(
                (float) ($resultado->total_qr ?? 0),
                2
            ),
        ];
    }

    /**
     * Obtiene una serie independiente por cada sucursal.
     */
    private function obtenerSeries(
        Collection $sucursales,
        array $categorias,
        Carbon $desde,
        Carbon $hasta,
        string $agrupacion,
        ?int $idSucursal = null
    ): array {
        $expresionAgrupacion = $this->expresionSqlAgrupacion(
            $agrupacion
        );

        $filas = $this->consultaVentasValidas(
            $desde,
            $hasta,
            $idSucursal
        )
            ->select([
                'pe.id_sucursal',
                'su.nombre',
            ])
            ->selectRaw(
                "{$expresionAgrupacion} AS categoria"
            )
            ->selectRaw(
                'COALESCE(SUM(pa.total_pagado), 0) AS total'
            )
            ->groupBy([
                'pe.id_sucursal',
                'su.nombre',
                DB::raw($expresionAgrupacion),
            ])
            ->orderBy('pe.id_sucursal')
            ->orderByRaw($expresionAgrupacion)
            ->get();

        $ventasAgrupadas = $filas
            ->groupBy('id_sucursal')
            ->map(function (Collection $ventasSucursal) {
                return $ventasSucursal->mapWithKeys(
                    function ($venta) {
                        return [
                            $venta->categoria => round(
                                (float) $venta->total,
                                2
                            ),
                        ];
                    }
                );
            });

        return $sucursales
            ->map(function ($sucursal) use (
                $categorias,
                $ventasAgrupadas
            ) {
                $datosSucursal = $ventasAgrupadas->get(
                    $sucursal->id_sucursal,
                    collect()
                );

                $datos = collect($categorias)
                    ->map(function ($categoria) use (
                        $datosSucursal
                    ) {
                        return (float) $datosSucursal->get(
                            $categoria,
                            0
                        );
                    })
                    ->values()
                    ->all();

                return [
                    'id_sucursal' => (int) $sucursal->id_sucursal,
                    'nombre' => $sucursal->nombre,
                    'estado' => $sucursal->estado,
                    'datos' => $datos,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Resuelve las fechas y la agrupación según el periodo.
     */
    private function resolverPeriodo(array $filtros): array
    {
        $tipo = $filtros['periodo'] ?? 'hoy';

        return match ($tipo) {
            'hoy' => [
                'tipo' => 'hoy',
                'desde' => now()->startOfDay(),
                'hasta' => now()->endOfDay(),
                'agrupacion' => 'hora',
            ],

            'semana' => [
                'tipo' => 'semana',
                'desde' => now()->startOfWeek(),
                'hasta' => now()->endOfWeek(),
                'agrupacion' => 'dia',
            ],

            'mes' => [
                'tipo' => 'mes',
                'desde' => now()->startOfMonth(),
                'hasta' => now()->endOfMonth(),
                'agrupacion' => 'dia',
            ],

            'anio' => [
                'tipo' => 'anio',
                'desde' => now()->startOfYear(),
                'hasta' => now()->endOfYear(),
                'agrupacion' => 'mes',
            ],

            'personalizado' => $this->resolverPeriodoPersonalizado(
                $filtros
            ),

            default => [
                'tipo' => 'hoy',
                'desde' => now()->startOfDay(),
                'hasta' => now()->endOfDay(),
                'agrupacion' => 'hora',
            ],
        };
    }

    /**
     * Determina la agrupación apropiada para rangos personalizados.
     */
    private function resolverPeriodoPersonalizado(
        array $filtros
    ): array {
        $desde = Carbon::createFromFormat(
            'Y-m-d',
            $filtros['fecha_desde']
        )->startOfDay();

        $hasta = Carbon::createFromFormat(
            'Y-m-d',
            $filtros['fecha_hasta']
        )->endOfDay();

        $dias = $desde->diffInDays($hasta) + 1;

        $agrupacion = match (true) {
            $dias === 1 => 'hora',
            $dias <= 90 => 'dia',
            default => 'mes',
        };

        return [
            'tipo' => 'personalizado',
            'desde' => $desde,
            'hasta' => $hasta,
            'agrupacion' => $agrupacion,
        ];
    }

    /**
     * Genera las categorías completas, incluso cuando no hay ventas.
     */
    private function generarCategorias(
        Carbon $desde,
        Carbon $hasta,
        string $agrupacion
    ): array {
        $categorias = [];

        if ($agrupacion === 'hora') {
            $cursor = $desde->copy()->startOfHour();
            $limite = $hasta->copy()->endOfHour();

            while ($cursor->lte($limite)) {
                $categorias[] = $cursor->format('Y-m-d H:00');
                $cursor->addHour();
            }

            return $categorias;
        }

        if ($agrupacion === 'dia') {
            $cursor = $desde->copy()->startOfDay();
            $limite = $hasta->copy()->startOfDay();

            while ($cursor->lte($limite)) {
                $categorias[] = $cursor->format('Y-m-d');
                $cursor->addDay();
            }

            return $categorias;
        }

        $cursor = $desde->copy()->startOfMonth();
        $limite = $hasta->copy()->startOfMonth();

        while ($cursor->lte($limite)) {
            $categorias[] = $cursor->format('Y-m');
            $cursor->addMonth();
        }

        return $categorias;
    }

    /**
     * Expresión SQL utilizada para agrupar ventas en MySQL.
     */
    private function expresionSqlAgrupacion(
        string $agrupacion
    ): string {
        return match ($agrupacion) {
            'hora' => "DATE_FORMAT(pa.fecha, '%Y-%m-%d %H:00')",
            'dia' => "DATE_FORMAT(pa.fecha, '%Y-%m-%d')",
            'mes' => "DATE_FORMAT(pa.fecha, '%Y-%m')",
            default => "DATE_FORMAT(pa.fecha, '%Y-%m-%d')",
        };
    }
}