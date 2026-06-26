<?php

namespace App\Services;

use App\Models\Caja;
use App\Models\DetallePedido;
use App\Models\Empleado;
use App\Models\Jornada;
use App\Models\Pedido;
use App\Models\ProductoVenta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PedidoService
{
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

            $jornada = Jornada::query()
                ->where('id_sucursal', $empleado->id_sucursal)
                ->whereDate('fecha', now()->toDateString())
                ->where('estado', 'ABIERTA')
                ->lockForUpdate()
                ->first();

            if (!$jornada) {
                throw ValidationException::withMessages([
                    'jornada' =>
                        'No existe una jornada abierta para hoy.',
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
                    'caja' =>
                        'El cajero debe tener una caja abierta.',
                ]);
            }

            $idsProductos = collect($datos['detalles'])
                ->pluck('id_producto')
                ->unique()
                ->values();

            $productos = ProductoVenta::query()
                ->with('guarniciones:id_guarnicion,nombre')
                ->whereIn('id_producto', $idsProductos)
                ->get()
                ->keyBy('id_producto');

            if ($productos->count() !== $idsProductos->count()) {
                throw ValidationException::withMessages([
                    'detalles' =>
                        'Uno o más productos no están disponibles.',
                ]);
            }

            $detallesPreparados = [];
            $totalCentavos = 0;

            foreach ($datos['detalles'] as $indice => $detalle) {
                $producto = $productos->get(
                    $detalle['id_producto']
                );

                if ($producto->precio === null) {
                    throw ValidationException::withMessages([
                        "detalles.$indice.id_producto" =>
                            "El producto {$producto->nombre} no tiene precio.",
                    ]);
                }

                $cantidad = (int) $detalle['cantidad'];

                $precioCentavos = (int) round(
                    (float) $producto->precio * 100
                );

                $subtotalCentavos =
                    $precioCentavos * $cantidad;

                $guarnicionesSeleccionadas = array_values(
                    array_unique(
                        $detalle['guarniciones'] ?? []
                    )
                );

                $guarnicionesPermitidas = $producto
                    ->guarniciones
                    ->pluck('id_guarnicion')
                    ->map(fn ($id) => (int) $id)
                    ->all();

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
                    'observacion' =>
                        $detalle['observacion'] ?? null,
                    'guarniciones' =>
                        $guarnicionesSeleccionadas,
                ];

                $totalCentavos += $subtotalCentavos;
            }

            if ($totalCentavos <= 0) {
                throw ValidationException::withMessages([
                    'detalles' =>
                        'El total del pedido debe ser mayor a cero.',
                ]);
            }

            $codigoPedido = $this->generarCodigo(
                $jornada->id_jornada
            );

            $pedido = Pedido::create([
                'codigo_pedido' => $codigoPedido,
                'id_sucursal' => $empleado->id_sucursal,
                'id_jornada' => $jornada->id_jornada,
                'id_cajero' => $empleado->id_empleado,
                'fecha' => now(),
                'tipo_consumo' => strtoupper(
                    $datos['tipo_consumo']
                ),
                'estado' => 'PENDIENTE',
                'total' => number_format(
                    $totalCentavos / 100,
                    2,
                    '.',
                    ''
                ),
            ]);

            foreach ($detallesPreparados as $detallePreparado) {
                $guarniciones =
                    $detallePreparado['guarniciones'];

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

            return $pedido->load([
                'sucursal',
                'jornada',
                'cajero',
                'detalles.producto',
                'detalles.guarniciones',
            ]);
        });
    }

    private function generarCodigo(
        int $idJornada
    ): string {
        $codigos = Pedido::query()
            ->where('id_jornada', $idJornada)
            ->lockForUpdate()
            ->pluck('codigo_pedido');

        $ultimoNumero = $codigos
            ->map(function ($codigo) {
                if (
                    preg_match(
                        '/^P(\d+)$/i',
                        (string) $codigo,
                        $coincidencias
                    )
                ) {
                    return (int) $coincidencias[1];
                }

                return 0;
            })
            ->max() ?? 0;

        return 'P' . ($ultimoNumero + 1);
    }
}