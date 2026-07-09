<?php

namespace App\Services;

use App\Models\ControlCarneJornada;
use App\Models\Jornada;
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

            if (!empty($detalle['es_pura_carne'])) {
                $tipoCarne = strtoupper((string) ($detalle['tipo_carne_manual'] ?? ''));
                $cantidadManual = (float) ($detalle['cantidad_carne_manual'] ?? 0);

                if ($tipoCarne === '' || $cantidadManual <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.{$indice}.cantidad_carne_manual" => [
                            'La venta de pura carne debe indicar tipo de carne y cantidad válida.',
                        ],
                    ]);
                }

                if (!isset($consumosTotales[$tipoCarne])) {
                    $consumosTotales[$tipoCarne] = 0;
                }

                $consumosTotales[$tipoCarne] += $cantidadManual;

                if ($tipoCarne === 'CHANCHO') {
                    $consumosPorDetalle[$indice]['consumo_chancho_total'] += $cantidadManual;
                }

                if ($tipoCarne === 'POLLO') {
                    $consumosPorDetalle[$indice]['consumo_pollo_total'] += $cantidadManual;
                }

                continue;
            }

            $producto = $productos->get($detalle['id_producto'] ?? null);

            if (!$producto) {
                continue;
            }

            $cantidad = (int) $detalle['cantidad'];
            $consumeCarne = (bool) $producto->consume_carne;
            $consumosCarne = $producto->consumos_carne ?? [];

            if (!$consumeCarne || empty($consumosCarne)) {
                continue;
            }

            foreach ($consumosCarne as $tipoCarne => $cantidadBase) {
                $tipoCarne = strtoupper((string) $tipoCarne);
                $consumoTotal = (float) $cantidadBase * $cantidad;

                if (!isset($consumosTotales[$tipoCarne])) {
                    $consumosTotales[$tipoCarne] = 0;
                }

                $consumosTotales[$tipoCarne] += $consumoTotal;

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
            ->keyBy(fn ($control) => strtoupper($control->tipoCarne->nombre));

        foreach ($consumosTotales as $tipoCarne => $cantidadNecesaria) {
            $tipoCarne = strtoupper((string) $tipoCarne);

            $control = $controles->get($tipoCarne);

            if (!$control) {
                throw ValidationException::withMessages([
                    'carne' => "No existe control de producción para {$tipoCarne} en la jornada actual.",
                ]);
            }

            $disponible = (float) $control->cantidad_base_actual;

            if ($disponible < (float) $cantidadNecesaria) {
                throw ValidationException::withMessages([
                    'carne' =>
                        "Producción insuficiente para {$tipoCarne}. Disponible: "
                        . number_format($disponible, 2, '.', '')
                        . " {$control->unidad_base}. Solicitado: "
                        . number_format((float) $cantidadNecesaria, 2, '.', '')
                        . " {$control->unidad_base}.",
                ]);
            }
        }
    }

    public function descontar(
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
            ->keyBy(fn ($control) => strtoupper($control->tipoCarne->nombre));

        foreach ($consumosTotales as $tipoCarne => $cantidadDescontar) {
            $tipoCarne = strtoupper((string) $tipoCarne);

            $control = $controles->get($tipoCarne);

            if (!$control) {
                throw ValidationException::withMessages([
                    'carne' => "No existe control de producción para {$tipoCarne} en la jornada actual.",
                ]);
            }

            $stockActual = (float) $control->cantidad_base_actual;

            if ($stockActual < (float) $cantidadDescontar) {
                throw ValidationException::withMessages([
                    'carne' =>
                        "Producción insuficiente para {$tipoCarne}. Disponible: "
                        . number_format($stockActual, 2, '.', '')
                        . " {$control->unidad_base}. Solicitado: "
                        . number_format((float) $cantidadDescontar, 2, '.', '')
                        . " {$control->unidad_base}.",
                ]);
            }

            $stockNuevo = $stockActual - (float) $cantidadDescontar;

            $control->update([
                'cantidad_base_actual' => number_format($stockNuevo, 2, '.', ''),
            ]);
        }
    }
}