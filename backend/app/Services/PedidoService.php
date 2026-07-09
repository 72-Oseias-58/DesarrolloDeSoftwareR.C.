<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\Empleado;
use App\Models\Inventario;
use App\Models\Jornada;
use App\Models\MovimientoInventario;
use App\Models\Pedido;
use App\Models\ProductoVenta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoService
{
    public function __construct(
        private readonly CarneService $carneService
    ) {
    }

    public function crear(
        User $usuario,
        array $datos
    ): Pedido {
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
                    'cajero' => 'El usuario no está asociado a un empleado.',
                ]);
            }

            if (strtoupper((string) $empleado->estado) !== 'ACTIVO') {
                throw ValidationException::withMessages([
                    'cajero' => 'El empleado se encuentra inactivo.',
                ]);
            }

            $jornada = Jornada::query()
                ->where('id_sucursal', $empleado->id_sucursal)
                ->whereDate('fecha', now()->toDateString())
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if (!$jornada) {
                throw ValidationException::withMessages([
                    'jornada' => 'No existe una jornada abierta para hoy.',
                ]);
            }

            $caja = Caja::query()
                ->where('id_jornada', $jornada->id_jornada)
                ->where('id_empleado', $empleado->id_empleado)
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if (!$caja) {
                throw ValidationException::withMessages([
                    'caja' => 'El cajero debe tener una caja abierta.',
                ]);
            }

            $idsProductos = collect($datos['detalles'])
                ->filter(fn ($detalle) => empty($detalle['es_pura_carne']))
                ->pluck('id_producto')
                ->filter(fn ($idProducto) => !is_null($idProducto) && $idProducto !== '')
                ->unique()
                ->values();

            $productos = ProductoVenta::query()
                ->with('guarniciones:id_guarnicion,nombre')
                ->whereIn('id_producto', $idsProductos)
                ->get()
                ->keyBy('id_producto');

            if ($productos->count() !== $idsProductos->count()) {
                throw ValidationException::withMessages([
                    'detalles' => 'Uno o más productos no están disponibles.',
                ]);
            }

            $preparacionCarne = $this->carneService->prepararConsumos(
                $productos,
                $datos['detalles']
            );

            $consumosCarneTotales = $preparacionCarne['totales'];
            $consumosCarnePorDetalle = $preparacionCarne['por_detalle'];

            $detallesPreparados = [];
            $descuentosBebidas = [];
            $totalCentavos = 0;

            foreach ($datos['detalles'] as $indice => $detalle) {
                $esPuraCarne = !empty($detalle['es_pura_carne']);

                if ($esPuraCarne) {
                    $tipoCarneManual = strtoupper(
                        trim((string) ($detalle['tipo_carne_manual'] ?? ''))
                    );

                    $unidadCarneManual = strtoupper(
                        trim((string) ($detalle['unidad_carne_manual'] ?? ''))
                    );

                    $cantidadCarneManual = (float) (
                        $detalle['cantidad_carne_manual'] ?? 0
                    );

                    $precioCentavos = (int) round(
                        (float) ($detalle['precio_unitario'] ?? 0) * 100
                    );

                    if (!in_array($tipoCarneManual, ['CHANCHO', 'POLLO'], true)) {
                        throw ValidationException::withMessages([
                            "detalles.$indice.tipo_carne_manual" =>
                                'El tipo de carne debe ser CHANCHO o POLLO.',
                        ]);
                    }

                    if ($cantidadCarneManual <= 0) {
                        throw ValidationException::withMessages([
                            "detalles.$indice.cantidad_carne_manual" =>
                                'La cantidad de carne debe ser mayor a cero.',
                        ]);
                    }

                    if ($unidadCarneManual === '') {
                        throw ValidationException::withMessages([
                            "detalles.$indice.unidad_carne_manual" =>
                                'Debe indicar la unidad de carne.',
                        ]);
                    }

                    if ($precioCentavos <= 0) {
                        throw ValidationException::withMessages([
                            "detalles.$indice.precio_unitario" =>
                                'El precio de la venta de pura carne debe ser mayor a cero.',
                        ]);
                    }

                    $consumoDetalle = $consumosCarnePorDetalle[$indice] ?? [
                        'consumo_chancho_total' => 0,
                        'consumo_pollo_total' => 0,
                    ];

                    $detallesPreparados[] = [
                        'id_producto' => null,
                        'cantidad' => 1,
                        'precio_unitario' => number_format(
                            $precioCentavos / 100,
                            2,
                            '.',
                            ''
                        ),
                        'subtotal' => number_format(
                            $precioCentavos / 100,
                            2,
                            '.',
                            ''
                        ),
                        'observacion' => $detalle['observacion'] ?? null,
                        'consumo_chancho_total' => number_format(
                            (float) ($consumoDetalle['consumo_chancho_total'] ?? 0),
                            2,
                            '.',
                            ''
                        ),
                        'consumo_pollo_total' => number_format(
                            (float) ($consumoDetalle['consumo_pollo_total'] ?? 0),
                            2,
                            '.',
                            ''
                        ),
                        'es_pura_carne' => true,
                        'tipo_carne_manual' => $tipoCarneManual,
                        'cantidad_carne_manual' => number_format(
                            $cantidadCarneManual,
                            2,
                            '.',
                            ''
                        ),
                        'unidad_carne_manual' => $unidadCarneManual,
                        'guarniciones' => [],
                    ];

                    $totalCentavos += $precioCentavos;

                    continue;
                }

                $producto = $productos->get($detalle['id_producto'] ?? null);

                if (!$producto) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.id_producto" =>
                            'El producto seleccionado no existe.',
                    ]);
                }

                if ($producto->precio === null) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.id_producto" =>
                            "El producto {$producto->nombre} no tiene precio.",
                    ]);
                }

                $precioCentavos = (int) round((float) $producto->precio * 100);

                if ($precioCentavos <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.id_producto" =>
                            "El producto {$producto->nombre} no tiene un precio válido.",
                    ]);
                }

                $cantidad = (int) ($detalle['cantidad'] ?? 0);

                if ($cantidad <= 0) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.cantidad" =>
                            "La cantidad de {$producto->nombre} debe ser mayor a cero.",
                    ]);
                }

                $subtotalCentavos = $precioCentavos * $cantidad;

                $tipoProducto = strtoupper((string) $producto->tipo_producto);

                $prioridadStock = strtoupper(
                    (string) ($producto->prioridad_stock ?? '')
                );

                $usaInventario =
                    $prioridadStock === 'INVENTARIO' ||
                    $tipoProducto === 'BEBIDA';

                $guarnicionesSeleccionadas = array_values(
                    array_unique($detalle['guarniciones'] ?? [])
                );

                $guarnicionesPermitidas = $producto
                    ->guarniciones
                    ->pluck('id_guarnicion')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                if (
                    $tipoProducto === 'PLATO' &&
                    count($guarnicionesPermitidas) > 0 &&
                    count($guarnicionesSeleccionadas) < 3
                ) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.guarniciones" =>
                            "El producto {$producto->nombre} debe tener mínimo 3 guarniciones.",
                    ]);
                }

                foreach ($guarnicionesSeleccionadas as $idGuarnicion) {
                    if (
                        !in_array(
                            (int) $idGuarnicion,
                            $guarnicionesPermitidas,
                            true
                        )
                    ) {
                        throw ValidationException::withMessages([
                            "detalles.$indice.guarniciones" =>
                                "La guarnición seleccionada no corresponde al producto {$producto->nombre}.",
                        ]);
                    }
                }

                if ($usaInventario) {
                    if (!$producto->id_insumo) {
                        throw ValidationException::withMessages([
                            "detalles.$indice.id_producto" =>
                                "El producto {$producto->nombre} no está vinculado a un insumo de inventario.",
                        ]);
                    }

                    $idInsumo = (int) $producto->id_insumo;

                    if (!isset($descuentosBebidas[$idInsumo])) {
                        $descuentosBebidas[$idInsumo] = [
                            'id_insumo' => $idInsumo,
                            'nombre' => $producto->nombre,
                            'cantidad' => 0,
                        ];
                    }

                    $descuentosBebidas[$idInsumo]['cantidad'] += $cantidad;
                }

                $consumoDetalle = $consumosCarnePorDetalle[$indice] ?? [
                    'consumo_chancho_total' => 0,
                    'consumo_pollo_total' => 0,
                ];

                $detallesPreparados[] = [
                    'id_producto' => $producto->id_producto,
                    'cantidad' => $cantidad,
                    'precio_unitario' => number_format(
                        $precioCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'subtotal' => number_format(
                        $subtotalCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'observacion' => $detalle['observacion'] ?? null,
                    'consumo_chancho_total' => number_format(
                        (float) ($consumoDetalle['consumo_chancho_total'] ?? 0),
                        2,
                        '.',
                        ''
                    ),
                    'consumo_pollo_total' => number_format(
                        (float) ($consumoDetalle['consumo_pollo_total'] ?? 0),
                        2,
                        '.',
                        ''
                    ),
                    'es_pura_carne' => false,
                    'tipo_carne_manual' => null,
                    'cantidad_carne_manual' => null,
                    'unidad_carne_manual' => null,
                    'guarniciones' => $guarnicionesSeleccionadas,
                ];

                $totalCentavos += $subtotalCentavos;
            }

            if ($totalCentavos <= 0) {
                throw ValidationException::withMessages([
                    'detalles' => 'El total del pedido debe ser mayor a cero.',
                ]);
            }

            foreach ($descuentosBebidas as $descuento) {
                $inventario = Inventario::query()
                    ->where('id_sucursal', $empleado->id_sucursal)
                    ->where('id_insumo', $descuento['id_insumo'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventario) {
                    throw ValidationException::withMessages([
                        'inventario' =>
                            "No existe inventario para {$descuento['nombre']} en esta sucursal.",
                    ]);
                }

                $stockActualCentavos = (int) round(
                    (float) $inventario->stock_actual * 100
                );

                $cantidadSolicitadaCentavos =
                    (int) $descuento['cantidad'] * 100;

                if ($stockActualCentavos < $cantidadSolicitadaCentavos) {
                    throw ValidationException::withMessages([
                        'inventario' =>
                            "Stock insuficiente para {$descuento['nombre']}. Disponible: "
                            . number_format($stockActualCentavos / 100, 2, '.', '')
                            . '. Solicitado: '
                            . number_format($cantidadSolicitadaCentavos / 100, 2, '.', '')
                            . '.',
                    ]);
                }
            }

            $this->carneService->validarDisponibilidad(
                $jornada,
                $empleado->id_sucursal,
                $consumosCarneTotales
            );

            $codigoPedido = $this->generarCodigo($jornada->id_jornada);

            $pedido = Pedido::create([
                'codigo_pedido' => $codigoPedido,
                'id_sucursal' => $empleado->id_sucursal,
                'id_jornada' => $jornada->id_jornada,
                'id_cajero' => $empleado->id_empleado,
                'fecha' => now(),
                'tipo_consumo' => strtoupper($datos['tipo_consumo']),
                'estado' => 'PENDIENTE',
                'total' => number_format($totalCentavos / 100, 2, '.', ''),
            ]);

            foreach ($detallesPreparados as $detallePreparado) {
                $guarniciones = $detallePreparado['guarniciones'];

                unset($detallePreparado['guarniciones']);

                $detallePedido = $pedido
                    ->detalles()
                    ->create($detallePreparado);

                if (!empty($guarniciones)) {
                    $detallePedido
                        ->guarniciones()
                        ->sync($guarniciones);
                }
            }

            $this->carneService->descontar(
                $jornada,
                $empleado->id_sucursal,
                $consumosCarneTotales
            );

            foreach ($descuentosBebidas as $descuento) {
                $inventario = Inventario::query()
                    ->where('id_sucursal', $empleado->id_sucursal)
                    ->where('id_insumo', $descuento['id_insumo'])
                    ->lockForUpdate()
                    ->first();

                if (!$inventario) {
                    throw ValidationException::withMessages([
                        'inventario' =>
                            "No existe inventario para {$descuento['nombre']} en esta sucursal.",
                    ]);
                }

                $stockAnteriorCentavos = (int) round(
                    (float) $inventario->stock_actual * 100
                );

                $cantidadCentavos = (int) $descuento['cantidad'] * 100;

                if ($stockAnteriorCentavos < $cantidadCentavos) {
                    throw ValidationException::withMessages([
                        'inventario' =>
                            "Stock insuficiente para {$descuento['nombre']}. Disponible: "
                            . number_format($stockAnteriorCentavos / 100, 2, '.', '')
                            . '. Solicitado: '
                            . number_format($cantidadCentavos / 100, 2, '.', '')
                            . '.',
                    ]);
                }

                $stockNuevoCentavos =
                    $stockAnteriorCentavos - $cantidadCentavos;

                $inventario->update([
                    'stock_actual' => number_format(
                        $stockNuevoCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                ]);

                MovimientoInventario::create([
                    'id_sucursal' => $empleado->id_sucursal,
                    'id_jornada' => $jornada->id_jornada,
                    'id_insumo' => $descuento['id_insumo'],
                    'id_user_crea' => $usuario->id,
                    'tipo_movimiento' => 'SALIDA',
                    'motivo' => 'VENTA',
                    'cantidad' => number_format(
                        $cantidadCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'stock_anterior' => number_format(
                        $stockAnteriorCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'stock_nuevo' => number_format(
                        $stockNuevoCentavos / 100,
                        2,
                        '.',
                        ''
                    ),
                    'referencia_tipo' => 'PEDIDO',
                    'referencia_id' => $pedido->id_pedido,
                    'observacion' =>
                        "Salida automática por venta en pedido {$pedido->codigo_pedido}.",
                ]);
            }

            return $pedido->load([
                'sucursal',
                'jornada',
                'cajero',
                'detalles.producto',
                'detalles.guarniciones',
            ]);
        });
    }

    private function generarCodigo(int $idJornada): string
    {
        $codigos = Pedido::query()
            ->where('id_jornada', $idJornada)
            ->lockForUpdate()
            ->pluck('codigo_pedido');

        $ultimoNumero = $codigos
            ->map(function ($codigo) {
                if (preg_match('/^P(\d+)$/i', (string) $codigo, $coincidencias)) {
                    return (int) $coincidencias[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return 'P' . ($ultimoNumero + 1);
    }
}