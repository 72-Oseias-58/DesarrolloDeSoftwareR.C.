<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RepararConsumosProductosVentaSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Reparar bebidas
        |--------------------------------------------------------------------------
        | Las bebidas usan inventario exacto.
        | No consumen carne.
        |--------------------------------------------------------------------------
        */

        DB::table('productos_venta')
            ->where('tipo_producto', 'BEBIDA')
            ->update([
                'prioridad_stock' => 'INVENTARIO',
                'consume_carne' => false,
                'consumos_carne' => null,
                'updated_at' => now(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Platos de chancho
        |--------------------------------------------------------------------------
        | Unidad base: COSTILLA / HUESO.
        |--------------------------------------------------------------------------
        */

        $this->actualizarProductoPorNombre(
            ['plato de chancho de bs 50', 'chancho bs 50', 'chancho 50'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'CHANCHO' => 1.5,
                ],
            ]
        );

        $this->actualizarProductoPorNombre(
            ['plato de chancho de bs 70', 'chancho bs 70', 'chancho 70'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'CHANCHO' => 2,
                ],
            ]
        );

        $this->actualizarProductoPorNombre(
            ['plato de chancho de bs 90', 'chancho bs 90', 'chancho 90'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'CHANCHO' => 3,
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Plato de pollo
        |--------------------------------------------------------------------------
        | Unidad base: POLLO entero.
        | Plato pollo Bs 50 consume medio pollo.
        |--------------------------------------------------------------------------
        */

        $this->actualizarProductoPorNombre(
            ['plato de pollo de bs 50', 'pollo bs 50', 'pollo 50'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'POLLO' => 0.5,
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Platos mixtos
        |--------------------------------------------------------------------------
        | Mixto Bs 70:
        | - 1 costilla / hueso de chancho
        | - 0.25 pollo
        |
        | Mixto Bs 90:
        | - 2 costillas / huesos de chancho
        | - 0.25 pollo
        |--------------------------------------------------------------------------
        */

        $this->actualizarProductoPorNombre(
            ['plato mixto de bs 70', 'mixto bs 70', 'mixto 70'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'CHANCHO' => 1,
                    'POLLO' => 0.25,
                ],
            ]
        );

        $this->actualizarProductoPorNombre(
            ['plato mixto de bs 90', 'mixto bs 90', 'mixto 90'],
            [
                'prioridad_stock' => 'PRODUCCION_DIARIA',
                'consume_carne' => true,
                'consumos_carne' => [
                    'CHANCHO' => 2,
                    'POLLO' => 0.25,
                ],
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Platos independientes
        |--------------------------------------------------------------------------
        | Charquecán y similares no descuentan chancho ni pollo.
        |--------------------------------------------------------------------------
        */

        $this->actualizarProductoIndependiente(
            ['charquecan', 'charquecán', 'charque']
        );
    }

    private function actualizarProductoPorNombre(
        array $nombresBuscados,
        array $datos
    ): void {
        foreach ($nombresBuscados as $nombreBuscado) {
            $producto = DB::table('productos_venta')
                ->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombreBuscado)])
                ->first();

            if (!$producto) {
                continue;
            }

            DB::table('productos_venta')
                ->where('id_producto', $producto->id_producto)
                ->update([
                    'prioridad_stock' => $datos['prioridad_stock'],
                    'consume_carne' => $datos['consume_carne'],
                    'consumos_carne' => $datos['consumos_carne']
                        ? json_encode($datos['consumos_carne'])
                        : null,
                    'updated_at' => now(),
                ]);

            return;
        }
    }

    private function actualizarProductoIndependiente(array $palabras): void
    {
        foreach ($palabras as $palabra) {
            $productos = DB::table('productos_venta')
                ->where('tipo_producto', 'PLATO')
                ->whereRaw('LOWER(nombre) LIKE ?', ['%' . mb_strtolower($palabra) . '%'])
                ->get();

            foreach ($productos as $producto) {
                DB::table('productos_venta')
                    ->where('id_producto', $producto->id_producto)
                    ->update([
                        'prioridad_stock' => 'SIN_STOCK',
                        'consume_carne' => false,
                        'consumos_carne' => null,
                        'updated_at' => now(),
                    ]);
            }
        }
    }
}