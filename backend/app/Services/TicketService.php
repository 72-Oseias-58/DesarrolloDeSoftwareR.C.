<?php

namespace App\Services;

use App\Models\DetallePedido;
use App\Models\Pedido;

class TicketService
{
    public function generar(Pedido $pedido): array
    {
        $pedido->loadMissing([
            'sucursal',
            'cajero',
            'pago',
            'detalles.producto.guarniciones',
            'detalles.guarniciones',
        ]);

        return [
            'ticket_cliente' => $this->generarTicketCliente($pedido),
            'ficha_mesero' => $this->generarFichaMesero($pedido),
        ];
    }

    private function generarTicketCliente(Pedido $pedido): array
    {
        $platosAgrupados = [];
        $puraCarne = [];
        $bebidasAgrupadas = [];

        /*
        |--------------------------------------------------------------------------
        | Procesar detalles del pedido
        |--------------------------------------------------------------------------
        */

        foreach ($pedido->detalles as $detalle) {
            if ($this->esBebida($detalle)) {
                $this->agruparBebida(
                    $bebidasAgrupadas,
                    $detalle
                );

                continue;
            }

            if ((bool) $detalle->es_pura_carne) {
                $puraCarne[] = $this->prepararPuraCarne(
                    $detalle
                );

                continue;
            }

            $this->agruparPlato(
                $platosAgrupados,
                $detalle
            );
        }

        $platos = $this->numerarElementos(
            array_values($platosAgrupados)
        );

        $puraCarne = $this->numerarElementos(
            array_values($puraCarne)
        );

        $bebidas = array_values($bebidasAgrupadas);

        /*
        |--------------------------------------------------------------------------
        | Datos del pago
        |--------------------------------------------------------------------------
        */

        $pago = $pedido->pago;

        $totalPedido = round(
            (float) $pedido->total,
            2
        );

        $dineroRecibido = $pago
            ? round((float) $pago->monto_efectivo, 2)
            : 0;

        $totalPagado = $pago
            ? round((float) $pago->total_pagado, 2)
            : $totalPedido;

        $cambio = max(
            0,
            round($dineroRecibido - $totalPedido, 2)
        );

        return [
            'restaurante' => 'RINCÓN CHAQUEÑO',
            'sucursal' => $pedido->sucursal?->nombre ?? 'Sucursal',
            'codigo_pedido' => $pedido->codigo_pedido,
            'fecha_hora' => $this->fechaBolivia($pedido),
            'cajero' => $this->nombreCajero($pedido),

            'platos' => $platos,
            'pura_carne' => $puraCarne,
            'bebidas' => $bebidas,

            'subtotal_productos' => $totalPedido,
            'total_pagado' => $totalPagado,
            'metodo_pago' => $pago?->metodo_pago ?? 'EFECTIVO',
            'dinero_recibido' => $dineroRecibido,
            'cambio' => $cambio,

            /*
            |--------------------------------------------------------------------------
            | Pie publicitario
            |--------------------------------------------------------------------------
            */

            'mensaje_gracias' =>
                '¡Gracias por compartir el sabor de Rincón Chaqueño!',

            'mensaje_contacto' =>
                'Pedidos y reservas: 12345678',

            'mensaje_publicidad' =>
                'Donde cada plato reúne sabor, familia y tradición.',

            'mensaje_slogan' =>
                '¡Tradición que se saborea!',
        ];
    }

    private function agruparPlato(
        array &$platosAgrupados,
        DetallePedido $detalle
    ): void {
        $guarniciones = $detalle
            ->guarniciones
            ->pluck('nombre')
            ->map(fn ($nombre) => trim((string) $nombre))
            ->filter()
            ->values()
            ->toArray();

        $preparacion = $this->nombrePreparacion(
            $detalle
        );

        $clave = $this->clavePlato(
            $detalle,
            $guarniciones,
            $preparacion
        );

        $cantidad = max(
            1,
            (int) $detalle->cantidad
        );

        $precioUnitario = round(
            (float) $detalle->precio_unitario,
            2
        );

        $subtotalDetalle = round(
            (float) $detalle->subtotal,
            2
        );

        if (!isset($platosAgrupados[$clave])) {
            $platosAgrupados[$clave] = [
                'cantidad' => 0,
                'nombre' => $this->nombreDetalle($detalle),
                'precio_unitario' => $precioUnitario,
                'subtotal' => 0,
                'preparacion' => $preparacion,
                'guarniciones' => $guarniciones,
                'guarniciones_texto' => implode(
                    ' | ',
                    $guarniciones
                ),
                'observacion' => $detalle->observacion,
            ];
        }

        $platosAgrupados[$clave]['cantidad'] += $cantidad;

        $platosAgrupados[$clave]['subtotal'] = round(
            (float) $platosAgrupados[$clave]['subtotal']
            + $subtotalDetalle,
            2
        );
    }

    private function agruparBebida(
        array &$bebidasAgrupadas,
        DetallePedido $detalle
    ): void {
        $clave = $this->claveBebida(
            $detalle
        );

        $cantidad = max(
            1,
            (int) $detalle->cantidad
        );

        $precioUnitario = round(
            (float) $detalle->precio_unitario,
            2
        );

        $subtotalDetalle = round(
            (float) $detalle->subtotal,
            2
        );

        if (!isset($bebidasAgrupadas[$clave])) {
            $bebidasAgrupadas[$clave] = [
                'cantidad' => 0,
                'nombre' => $this->nombreDetalle($detalle),
                'precio_unitario' => $precioUnitario,
                'subtotal' => 0,
                'observacion' => $detalle->observacion,
            ];
        }

        $bebidasAgrupadas[$clave]['cantidad'] += $cantidad;

        $bebidasAgrupadas[$clave]['subtotal'] = round(
            (float) $bebidasAgrupadas[$clave]['subtotal']
            + $subtotalDetalle,
            2
        );
    }

    private function prepararPuraCarne(
        DetallePedido $detalle
    ): array {
        return [
            'cantidad' => max(
                1,
                (int) $detalle->cantidad
            ),

            'nombre' => $this->nombreDetalle($detalle),

            'precio_unitario' => round(
                (float) $detalle->precio_unitario,
                2
            ),

            'subtotal' => round(
                (float) $detalle->subtotal,
                2
            ),

            /*
             * Se mantiene también "precio" para no romper
             * el frontend actual mientras se actualiza.
             */
            'precio' => round(
                (float) $detalle->subtotal,
                2
            ),

            'tipo_carne_manual' =>
                $detalle->tipo_carne_manual,

            'cantidad_carne_manual' =>
                $detalle->cantidad_carne_manual,

            'unidad_carne_manual' =>
                $detalle->unidad_carne_manual,

            'observacion' => $detalle->observacion,
        ];
    }

    private function numerarElementos(
        array $elementos
    ): array {
        return collect($elementos)
            ->values()
            ->map(function (
                array $elemento,
                int $indice
            ) {
                $elemento['numero'] = $indice + 1;

                return $elemento;
            })
            ->all();
    }

    private function generarFichaMesero(
        Pedido $pedido
    ): array {
        $cantidadPlatos = 0;
        $cantidadPuraCarne = 0;
        $cantidadBebidas = 0;

        foreach ($pedido->detalles as $detalle) {
            $cantidad = max(
                1,
                (int) $detalle->cantidad
            );

            if ($this->esBebida($detalle)) {
                $cantidadBebidas += $cantidad;

                continue;
            }

            if ((bool) $detalle->es_pura_carne) {
                $cantidadPuraCarne += $cantidad;

                continue;
            }

            $cantidadPlatos += $cantidad;
        }

        return [
            'restaurante' => 'RINCÓN CHAQUEÑO',
            'codigo_pedido' => $pedido->codigo_pedido,
            'cantidad_platos' => $cantidadPlatos,
            'cantidad_pura_carne' => $cantidadPuraCarne,
            'cantidad_bebidas' => $cantidadBebidas,
        ];
    }

    private function esBebida(
        DetallePedido $detalle
    ): bool {
        return strtoupper(
            trim(
                (string) $detalle
                    ->producto
                    ?->tipo_producto
            )
        ) === 'BEBIDA';
    }

    private function nombreDetalle(
        DetallePedido $detalle
    ): string {
        if ((bool) $detalle->es_pura_carne) {
            $tipo = strtoupper(
                trim(
                    (string) $detalle->tipo_carne_manual
                )
            );

            return $tipo === 'POLLO'
                ? 'Pura carne de pollo'
                : 'Pura carne de chancho';
        }

        return $detalle->producto?->nombre
            ?? 'Producto';
    }

    private function nombrePreparacion(
        DetallePedido $detalle
    ): string {
        $seleccionadas = $detalle
            ->guarniciones
            ->pluck('nombre')
            ->map(
                fn ($nombre) =>
                    $this->normalizar(
                        (string) $nombre
                    )
            )
            ->filter()
            ->sort()
            ->values()
            ->toArray();

        if (empty($seleccionadas)) {
            return '';
        }

        $permitidas = $detalle
            ->producto
            ?->guarniciones
            ?->pluck('nombre')
            ?->map(
                fn ($nombre) =>
                    $this->normalizar(
                        (string) $nombre
                    )
            )
            ?->filter()
            ?->values()
            ?->toArray() ?? [];

        $mixto = [
            'ARROZ',
            'MOTE',
        ];

        sort($mixto);

        if ($seleccionadas === $mixto) {
            return 'Mixto';
        }

        $arrozCompleto = collect($permitidas)
            ->filter(
                fn ($nombre) =>
                    $nombre !== 'MOTE'
            )
            ->sort()
            ->values()
            ->toArray();

        $moteCompleto = collect($permitidas)
            ->filter(
                fn ($nombre) =>
                    $nombre !== 'ARROZ'
            )
            ->sort()
            ->values()
            ->toArray();

        if (
            count($arrozCompleto) > 1 &&
            $seleccionadas === $arrozCompleto
        ) {
            return 'Arroz completo';
        }

        if (
            count($moteCompleto) > 1 &&
            $seleccionadas === $moteCompleto
        ) {
            return 'Mote completo';
        }

        return '';
    }

    private function clavePlato(
        DetallePedido $detalle,
        array $guarniciones,
        string $preparacion
    ): string {
        $guarnicionesNormalizadas = collect(
            $guarniciones
        )
            ->map(
                fn ($nombre) =>
                    $this->normalizar(
                        (string) $nombre
                    )
            )
            ->sort()
            ->values()
            ->implode('|');

        return implode('::', [
            (string) $detalle->id_producto,

            number_format(
                (float) $detalle->precio_unitario,
                2,
                '.',
                ''
            ),

            $this->normalizar($preparacion),

            $guarnicionesNormalizadas,

            $this->normalizar(
                (string) ($detalle->observacion ?? '')
            ),
        ]);
    }

    private function claveBebida(
        DetallePedido $detalle
    ): string {
        return implode('::', [
            (string) $detalle->id_producto,

            number_format(
                (float) $detalle->precio_unitario,
                2,
                '.',
                ''
            ),

            $this->normalizar(
                (string) ($detalle->observacion ?? '')
            ),
        ]);
    }

    private function fechaBolivia(
        Pedido $pedido
    ): string {
        if (!$pedido->fecha) {
            return now('America/La_Paz')
                ->format('d/m/Y - H:i');
        }

        return $pedido
            ->fecha
            ->timezone('America/La_Paz')
            ->format('d/m/Y - H:i');
    }

    private function nombreCajero(
        Pedido $pedido
    ): string {
        $cajero = $pedido->cajero;

        if (!$cajero) {
            return 'Cajero';
        }

        foreach (
            [
                'nombre_completo',
                'nombres',
                'nombre',
                'usuario',
            ] as $campo
        ) {
            if (!empty($cajero->{$campo})) {
                return (string) $cajero->{$campo};
            }
        }

        $nombreCompleto = trim(
            ((string) ($cajero->nombre ?? ''))
            . ' '
            . ((string) ($cajero->apellido ?? ''))
        );

        return $nombreCompleto !== ''
            ? $nombreCompleto
            : 'Cajero';
    }

    private function normalizar(
        string $texto
    ): string {
        $texto = trim($texto);

        $texto = str_replace(
            [
                'á',
                'é',
                'í',
                'ó',
                'ú',
                'ñ',
                'Á',
                'É',
                'Í',
                'Ó',
                'Ú',
                'Ñ',
            ],
            [
                'a',
                'e',
                'i',
                'o',
                'u',
                'n',
                'A',
                'E',
                'I',
                'O',
                'U',
                'N',
            ],
            $texto
        );

        return strtoupper($texto);
    }
}