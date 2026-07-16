<?php

namespace App\Services;

use App\Models\ControlCarneJornada;
use App\Models\Jornada;
use App\Models\MovimientoCarne;
use App\Models\Pedido;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CarneService
{
    public function prepararConsumos(
        Collection $productos,
        array $detalles
    ): array {
        $consumosTotales = [];
        $consumosPorDetalle = [];

        foreach ($detalles as $indice => $detalle) {
            $consumosPorDetalle[$indice] = [
                'consumo_chancho_total' => 0,
                'consumo_pollo_total' => 0,
            ];

            /*
            |--------------------------------------------------------------------------
            | Venta manual de pura carne
            |--------------------------------------------------------------------------
            | El frontend manda:
            | - tipo_carne_manual: CHANCHO / POLLO
            | - cantidad_carne_manual: cantidad ingresada
            | - unidad_carne_manual: COSTILLA, MEDIO_POLLO, CRUZ, etc.
            |
            | El backend debe convertir eso a la unidad base real:
            | - CHANCHO se descuenta en COSTILLAS/HUESOS.
            | - POLLO se descuenta en POLLOS.
            |--------------------------------------------------------------------------
            */

            if (!empty($detalle['es_pura_carne'])) {
                $tipoCarne = strtoupper(
                    trim((string) ($detalle['tipo_carne_manual'] ?? ''))
                );

                $unidadCarne = strtoupper(
                    trim((string) ($detalle['unidad_carne_manual'] ?? ''))
                );

                $cantidadManual = (float) (
                    $detalle['cantidad_carne_manual'] ?? 0
                );

                if (!in_array($tipoCarne, ['CHANCHO', 'POLLO'], true)) {
                    throw ValidationException::withMessages([
                        "detalles.{$indice}.tipo_carne_manual" => [
                            'La venta de pura carne debe ser CHANCHO o POLLO.',
                        ],
                    ]);
                }

                if ($cantidadManual <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.{$indice}.cantidad_carne_manual" => [
                            'La cantidad de pura carne debe ser mayor a cero.',
                        ],
                    ]);
                }

                if ($unidadCarne === '') {
                    throw ValidationException::withMessages([
                        "detalles.{$indice}.unidad_carne_manual" => [
                            'Debe indicar la unidad de carne.',
                        ],
                    ]);
                }

                $consumoReal = $this->convertirUnidadManualAConsumoBase(
                    $tipoCarne,
                    $unidadCarne,
                    $cantidadManual,
                    $indice
                );

                $this->sumarConsumo(
                    $consumosTotales,
                    $tipoCarne,
                    $consumoReal
                );

                if ($tipoCarne === 'CHANCHO') {
                    $consumosPorDetalle[$indice]['consumo_chancho_total'] += $consumoReal;
                }

                if ($tipoCarne === 'POLLO') {
                    $consumosPorDetalle[$indice]['consumo_pollo_total'] += $consumoReal;
                }

                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Producto normal
            |--------------------------------------------------------------------------
            | Los productos con consume_carne = true usan productos_venta.consumos_carne.
            |
            | Ejemplos:
            | Chancho 50 → { CHANCHO: 1.5 }
            | Chancho 70 → { CHANCHO: 2 }
            | Pollo 50 → { POLLO: 0.5 }
            | Mixto → { CHANCHO: 1, POLLO: 0.25 }
            |
            | Charquecán o platos independientes:
            | consume_carne = false / consumos_carne = null
            | No descuentan nada.
            |--------------------------------------------------------------------------
            */

            $producto = $productos->get($detalle['id_producto'] ?? null);

            if (!$producto) {
                continue;
            }

            $cantidad = (int) ($detalle['cantidad'] ?? 0);

            if ($cantidad <= 0) {
                continue;
            }

            $consumeCarne = (bool) $producto->consume_carne;
            $consumosCarne = $producto->consumos_carne ?? [];

            if (!$consumeCarne || empty($consumosCarne)) {
                continue;
            }

            foreach ($consumosCarne as $tipoCarne => $cantidadBase) {
                $tipoCarne = strtoupper(trim((string) $tipoCarne));
                $cantidadBase = (float) $cantidadBase;

                if ($tipoCarne === '' || $cantidadBase <= 0) {
                    continue;
                }

                if (!in_array($tipoCarne, ['CHANCHO', 'POLLO'], true)) {
                    continue;
                }

                $consumoTotal = $cantidadBase * $cantidad;

                $this->sumarConsumo(
                    $consumosTotales,
                    $tipoCarne,
                    $consumoTotal
                );

                if ($tipoCarne === 'CHANCHO') {
                    $consumosPorDetalle[$indice]['consumo_chancho_total'] += $consumoTotal;
                }

                if ($tipoCarne === 'POLLO') {
                    $consumosPorDetalle[$indice]['consumo_pollo_total'] += $consumoTotal;
                }
            }
        }

        return [
            'totales' => $consumosTotales,
            'por_detalle' => $consumosPorDetalle,
        ];
    }

    public function validarDisponibilidad(
        Jornada $jornada,
        int $idSucursal,
        array $consumosTotales
    ): void {
        if (empty($consumosTotales)) {
            return;
        }

        $controles = ControlCarneJornada::query()
            ->with('tipoCarne')
            ->where('id_jornada', $jornada->id_jornada)
            ->where('id_sucursal', $idSucursal)
            ->lockForUpdate()
            ->get()
            ->keyBy(function ($control) {
                return strtoupper((string) $control->tipoCarne?->nombre);
            });

        foreach ($consumosTotales as $tipoCarne => $cantidadNecesaria) {
            $tipoCarne = strtoupper((string) $tipoCarne);
            $cantidadNecesaria = (float) $cantidadNecesaria;

            if ($cantidadNecesaria <= 0) {
                continue;
            }

            $control = $controles->get($tipoCarne);

            if (!$control) {
                throw ValidationException::withMessages([
                    'carne' => [
                        "No existe control de producción para {$tipoCarne} en la jornada actual.",
                    ],
                ]);
            }

            $disponible = (float) $control->cantidad_base_actual;

            if ($disponible < $cantidadNecesaria) {
                throw ValidationException::withMessages([
                    'carne' => [
                        "Producción insuficiente para {$tipoCarne}. Disponible: "
                        . number_format($disponible, 2, '.', '')
                        . " {$control->unidad_base}. Solicitado: "
                        . number_format($cantidadNecesaria, 2, '.', '')
                        . " {$control->unidad_base}.",
                    ],
                ]);
            }
        }
    }

    public function descontar(
    Jornada $jornada,
    int $idSucursal,
    array $consumosTotales,
    User $usuario,
    Pedido $pedido
): void {
    if (empty($consumosTotales)) {
        return;
    }

    $controles = ControlCarneJornada::query()
        ->with('tipoCarne')
        ->where('id_jornada', $jornada->id_jornada)
        ->where('id_sucursal', $idSucursal)
        ->lockForUpdate()
        ->get()
        ->keyBy(function ($control) {
            return strtoupper(
                trim((string) $control->tipoCarne?->nombre)
            );
        });

    foreach ($consumosTotales as $tipoCarne => $cantidadDescontar) {
        $tipoCarne = strtoupper(trim((string) $tipoCarne));
        $cantidadDescontar = round((float) $cantidadDescontar, 2);

        if ($cantidadDescontar <= 0) {
            continue;
        }

        $control = $controles->get($tipoCarne);

        if (!$control) {
            throw ValidationException::withMessages([
                'carne' => [
                    "No existe control de producción para {$tipoCarne} en la jornada actual.",
                ],
            ]);
        }

        $stockActual = round(
            (float) $control->cantidad_base_actual,
            2
        );

        if ($stockActual < $cantidadDescontar) {
            throw ValidationException::withMessages([
                'carne' => [
                    "Producción insuficiente para {$tipoCarne}. Disponible: "
                    . number_format($stockActual, 2, '.', '')
                    . " {$control->unidad_base}. Solicitado: "
                    . number_format($cantidadDescontar, 2, '.', '')
                    . " {$control->unidad_base}.",
                ],
            ]);
        }

        $stockNuevo = round(
            $stockActual - $cantidadDescontar,
            2
        );

        $control->update([
            'cantidad_base_actual' => number_format(
                $stockNuevo,
                2,
                '.',
                ''
            ),
        ]);

        MovimientoCarne::create([
            'id_control_carne' => $control->id_control_carne,
            'id_sucursal' => $idSucursal,
            'id_jornada' => $jornada->id_jornada,
            'id_tipo_carne' => $control->id_tipo_carne,
            'id_user_crea' => $usuario->id,

            'tipo_movimiento' => 'SALIDA',
            'motivo' => 'VENTA',

            'unidad_registrada' => strtoupper(
                (string) $control->unidad_base
            ),

            'cantidad_registrada' => number_format(
                $cantidadDescontar,
                2,
                '.',
                ''
            ),

            'cantidad_base' => number_format(
                $cantidadDescontar,
                2,
                '.',
                ''
            ),

            'unidad_base' => strtoupper(
                (string) $control->unidad_base
            ),

            'cantidad_anterior' => number_format(
                $stockActual,
                2,
                '.',
                ''
            ),

            'cantidad_nueva' => number_format(
                $stockNuevo,
                2,
                '.',
                ''
            ),

            'referencia_tipo' => 'PEDIDO',
            'referencia_id' => $pedido->id_pedido,

            'origen' => null,
            'destino' => 'VENTA',

            'observacion' =>
                "Salida automática por venta del pedido {$pedido->codigo_pedido}.",
        ]);
    }
}

    private function sumarConsumo(
        array &$consumosTotales,
        string $tipoCarne,
        float $cantidad
    ): void {
        $tipoCarne = strtoupper($tipoCarne);

        if (!isset($consumosTotales[$tipoCarne])) {
            $consumosTotales[$tipoCarne] = 0;
        }

        $consumosTotales[$tipoCarne] += $cantidad;
    }

    private function convertirUnidadManualAConsumoBase(
        string $tipoCarne,
        string $unidadCarne,
        float $cantidadManual,
        int $indice
    ): float {
        $tipoCarne = strtoupper($tipoCarne);
        $unidadCarne = strtoupper($unidadCarne);

        /*
        |--------------------------------------------------------------------------
        | CHANCHO
        |--------------------------------------------------------------------------
        | Unidad base del control diario: COSTILLA/HUESO.
        |--------------------------------------------------------------------------
        */

        $conversionChancho = [
    /*
    |--------------------------------------------------------------------------
    | Unidad base real del chancho: MIN_COSTILLA
    |--------------------------------------------------------------------------
    */

    'MEDIA_MIN_COSTILLA' => 0.5,

    'MIN_COSTILLA' => 1,
    'COSTILLA_HUESO' => 1,
    'COSTILLA' => 1,
    'PORCION_CHANCHO' => 1,

    'COSTILLA_Y_MEDIA' => 1.5,
    'DOS_MIN_COSTILLAS' => 2,
    'DOS_COSTILLAS' => 2,

    /*
    |--------------------------------------------------------------------------
    | Unidades grandes
    |--------------------------------------------------------------------------
    */

    'MEDIA_COSTILLA_GRANDE' => 6,

    'COSTILLA_GRANDE' => 12,
    'COSTILLA_ENTERA' => 12,

    'CRUZ_CHANCHO' => 24,
    'CRUZ_ENTERA' => 24,
];

        /*
        |--------------------------------------------------------------------------
        | POLLO
        |--------------------------------------------------------------------------
        | Unidad base del control diario: POLLO ENTERO.
        |--------------------------------------------------------------------------
        */

        $conversionPollo = [
            'CUARTO_POLLO' => 0.25,
            'MEDIO_POLLO' => 0.5,
            'TRES_CUARTOS_POLLO' => 0.75,
            'POLLO_ENTERO' => 1,
            'CRUZ_POLLO' => 2,
            'CRUZ_ENTERA' => 2,
            'PORCION_POLLO' => 0.5,
        ];

        if ($tipoCarne === 'CHANCHO') {
            if (!array_key_exists($unidadCarne, $conversionChancho)) {
                throw ValidationException::withMessages([
                    "detalles.{$indice}.unidad_carne_manual" => [
                        'La unidad seleccionada no corresponde a CHANCHO.',
                    ],
                ]);
            }

            return $cantidadManual * $conversionChancho[$unidadCarne];
        }

        if ($tipoCarne === 'POLLO') {
            if (!array_key_exists($unidadCarne, $conversionPollo)) {
                throw ValidationException::withMessages([
                    "detalles.{$indice}.unidad_carne_manual" => [
                        'La unidad seleccionada no corresponde a POLLO.',
                    ],
                ]);
            }

            return $cantidadManual * $conversionPollo[$unidadCarne];
        }

        throw ValidationException::withMessages([
            "detalles.{$indice}.tipo_carne_manual" => [
                'El tipo de carne debe ser CHANCHO o POLLO.',
            ],
        ]);
    }
}