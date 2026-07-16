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
        $platos = [];
        $puraCarne = [];
        $bebidas = [];

        $numeroPlato = 1;
        $numeroPuraCarne = 1;

        foreach ($pedido->detalles as $detalle) {
            if ($this->esBebida($detalle)) {
                $clave = $this->claveBebida($detalle);

                if (!isset($bebidas[$clave])) {
                    $bebidas[$clave] = [
                        'cantidad' => 0,
                        'nombre' => $this->nombreDetalle($detalle),
                        'precio_unitario' => (float) $detalle->precio_unitario,
                        'subtotal' => 0,
                    ];
                }

                $bebidas[$clave]['cantidad'] += (int) $detalle->cantidad;
                $bebidas[$clave]['subtotal'] += (float) $detalle->subtotal;

                continue;
            }

            if ((bool) $detalle->es_pura_carne) {
                $puraCarne[] = [
                    'numero' => $numeroPuraCarne,
                    'nombre' => $this->nombreDetalle($detalle),
                    'precio' => (float) $detalle->precio_unitario,
                    'observacion' => $detalle->observacion,
                ];

                $numeroPuraCarne++;

                continue;
            }

            $cantidad = max(1, (int) $detalle->cantidad);

            for ($i = 1; $i <= $cantidad; $i++) {
                $guarniciones = $detalle
                    ->guarniciones
                    ->pluck('nombre')
                    ->values()
                    ->toArray();

                $platos[] = [
                    'numero' => $numeroPlato,
                    'nombre' => $this->nombreDetalle($detalle),
                    'precio' => (float) $detalle->precio_unitario,
                    'preparacion' => $this->nombrePreparacion($detalle),
                    'guarniciones' => $guarniciones,
                    'guarniciones_texto' => implode(' | ', $guarniciones),
                    'observacion' => $detalle->observacion,
                ];

                $numeroPlato++;
            }
        }

        $pago = $pedido->pago;
        $totalPedido = (float) $pedido->total;
        $dineroRecibido = $pago ? (float) $pago->monto_efectivo : 0;

        return [
            'restaurante' => 'RINCÓN CHAQUEÑO',
            'sucursal' => $pedido->sucursal?->nombre ?? 'Sucursal',
            'codigo_pedido' => $pedido->codigo_pedido,
            'fecha_hora' => $this->fechaBolivia($pedido),
            'cajero' => $this->nombreCajero($pedido),

            'platos' => $platos,
            'pura_carne' => $puraCarne,
            'bebidas' => array_values($bebidas),

            'subtotal_productos' => $totalPedido,
            'total_pagado' => $pago ? (float) $pago->total_pagado : $totalPedido,
            'metodo_pago' => $pago ? $pago->metodo_pago : 'EFECTIVO',
            'dinero_recibido' => $dineroRecibido,
            'cambio' => max(0, $dineroRecibido - $totalPedido),

            'mensaje_revision' => 'Revise que los productos, guarniciones y observaciones sean correctos.',
            'mensaje_conservar' => 'Conserve este ticket hasta recibir su pedido.',
        ];
    }

    private function generarFichaMesero(Pedido $pedido): array
    {
        $cantidadPlatos = 0;
        $cantidadPuraCarne = 0;
        $cantidadBebidas = 0;

        foreach ($pedido->detalles as $detalle) {
            if ($this->esBebida($detalle)) {
                $cantidadBebidas += (int) $detalle->cantidad;
                continue;
            }

            if ((bool) $detalle->es_pura_carne) {
                $cantidadPuraCarne++;
                continue;
            }

            $cantidadPlatos += max(1, (int) $detalle->cantidad);
        }

        return [
            'restaurante' => 'RINCÓN CHAQUEÑO',
            'codigo_pedido' => $pedido->codigo_pedido,
            'cantidad_platos' => $cantidadPlatos,
            'cantidad_pura_carne' => $cantidadPuraCarne,
            'cantidad_bebidas' => $cantidadBebidas,
        ];
    }

    private function esBebida(DetallePedido $detalle): bool
    {
        return strtoupper((string) $detalle->producto?->tipo_producto) === 'BEBIDA';
    }

    private function nombreDetalle(DetallePedido $detalle): string
    {
        if ((bool) $detalle->es_pura_carne) {
            $tipo = strtoupper((string) $detalle->tipo_carne_manual);

            return $tipo === 'POLLO'
                ? 'Pura carne de pollo'
                : 'Pura carne de chancho';
        }

        return $detalle->producto?->nombre ?? 'Producto';
    }

    private function nombrePreparacion(DetallePedido $detalle): string
    {
        $seleccionadas = $detalle
            ->guarniciones
            ->pluck('nombre')
            ->map(fn ($nombre) => $this->normalizar($nombre))
            ->values()
            ->sort()
            ->toArray();

        if (empty($seleccionadas)) {
            return '';
        }

        $permitidas = $detalle
            ->producto
            ?->guarniciones
            ?->pluck('nombre')
            ?->map(fn ($nombre) => $this->normalizar($nombre))
            ?->values()
            ?->toArray() ?? [];

        $mixto = ['ARROZ', 'MOTE'];
        sort($mixto);

        if ($seleccionadas === $mixto) {
            return 'Mixto';
        }

        $arrozCompleto = collect($permitidas)
            ->filter(fn ($nombre) => $nombre !== 'MOTE')
            ->values()
            ->sort()
            ->toArray();

        $moteCompleto = collect($permitidas)
            ->filter(fn ($nombre) => $nombre !== 'ARROZ')
            ->values()
            ->sort()
            ->toArray();

        if (count($arrozCompleto) > 1 && $seleccionadas === $arrozCompleto) {
            return 'Arroz completo';
        }

        if (count($moteCompleto) > 1 && $seleccionadas === $moteCompleto) {
            return 'Mote completo';
        }

        return '';
    }

    private function claveBebida(DetallePedido $detalle): string
    {
        return implode('|', [
            $detalle->id_producto,
            $detalle->precio_unitario,
            $detalle->observacion,
        ]);
    }

    private function fechaBolivia(Pedido $pedido): string
    {
        if (!$pedido->fecha) {
            return now('America/La_Paz')->format('d/m/Y - H:i');
        }

        return $pedido
            ->fecha
            ->timezone('America/La_Paz')
            ->format('d/m/Y - H:i');
    }

    private function nombreCajero(Pedido $pedido): string
    {
        $cajero = $pedido->cajero;

        if (!$cajero) {
            return 'Cajero';
        }

        foreach (['nombre_completo', 'nombres', 'nombre', 'usuario'] as $campo) {
            if (!empty($cajero->{$campo})) {
                return (string) $cajero->{$campo};
            }
        }

        $nombreCompleto = trim(
            ((string) ($cajero->nombre ?? '')) . ' ' .
            ((string) ($cajero->apellido ?? ''))
        );

        return $nombreCompleto !== ''
            ? $nombreCompleto
            : 'Cajero';
    }

    private function normalizar(string $texto): string
    {
        $texto = trim($texto);

        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'A', 'E', 'I', 'O', 'U', 'N'],
            $texto
        );

        return strtoupper($texto);
    }
}