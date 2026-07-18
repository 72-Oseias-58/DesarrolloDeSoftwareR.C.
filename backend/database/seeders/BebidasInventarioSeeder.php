<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BebidasInventarioSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $idUserCrea = DB::table('users')
            ->orderBy('id')
            ->value('id');

        if (!$idUserCrea) {
            return;
        }

        $idCategoriaProductoBebida = DB::table(
            'categorias_productos'
        )
            ->whereRaw(
                'UPPER(nombre) = ?',
                ['BEBIDAS']
            )
            ->value('id_categoria_producto');

        if (!$idCategoriaProductoBebida) {
            $idCategoriaProductoBebida = DB::table(
                'categorias_productos'
            )->insertGetId([
                'nombre' => 'Bebidas',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $sucursales = DB::table('sucursales')
            ->select('id_sucursal')
            ->get();

        $bebidasBase = [
            [
                'nombre' => 'Coca-Cola 2 litros',
                'descripcion' =>
                    'Refresco Coca-Cola de 2 litros.',
                'precio' => 15.00,
                'unidad_medida' => 'UNIDAD',
            ],
            [
                'nombre' => 'Fanta 500 ml',
                'descripcion' =>
                    'Refresco Fanta de 500 ml.',
                'precio' => 7.00,
                'unidad_medida' => 'UNIDAD',
            ],
        ];

        foreach ($sucursales as $sucursal) {
            $idSucursal = (int) $sucursal->id_sucursal;

            $idCategoriaInsumo = DB::table(
                'categorias_insumos'
            )
                ->where(
                    'id_sucursal',
                    $idSucursal
                )
                ->whereRaw(
                    'UPPER(nombre) = ?',
                    ['BEBIDAS']
                )
                ->value('id_categoria_insumo');

            if (!$idCategoriaInsumo) {
                $idCategoriaInsumo = DB::table(
                    'categorias_insumos'
                )->insertGetId([
                    'id_sucursal' => $idSucursal,
                    'nombre' => 'Bebidas',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            foreach ($bebidasBase as $bebida) {
                $this->crearBebidaSucursal(
                    $idSucursal,
                    $bebida,
                    $idCategoriaInsumo,
                    $idCategoriaProductoBebida,
                    $idUserCrea
                );
            }
        }
    }

    private function crearBebidaSucursal(
        int $idSucursal,
        array $bebida,
        int $idCategoriaInsumo,
        int $idCategoriaProducto,
        int $idUserCrea
    ): void {
        $now = now();

        $idInsumo = DB::table('insumos')
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereRaw(
                'UPPER(nombre) = ?',
                [mb_strtoupper($bebida['nombre'])]
            )
            ->value('id_insumo');

        if (!$idInsumo) {
            $idInsumo = DB::table('insumos')
                ->insertGetId([
                    'id_sucursal' => $idSucursal,
                    'id_categoria_insumo' =>
                        $idCategoriaInsumo,
                    'nombre' => $bebida['nombre'],
                    'unidad_medida' =>
                        $bebida['unidad_medida'],
                    'prioridad_stock' => 'INVENTARIO',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        DB::table('inventarios')->updateOrInsert(
            [
                'id_sucursal' => $idSucursal,
                'id_insumo' => $idInsumo,
            ],
            [
                'id_user_crea' => $idUserCrea,
                'stock_actual' => 0,
                'stock_minimo' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $idProducto = DB::table('productos_venta')
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereRaw(
                'UPPER(nombre) = ?',
                [mb_strtoupper($bebida['nombre'])]
            )
            ->value('id_producto');

        $datosProducto = [
            'id_sucursal' => $idSucursal,
            'nombre' => $bebida['nombre'],
            'descripcion' => $bebida['descripcion'],
            'precio' => $bebida['precio'],
            'tipo_producto' => 'BEBIDA',
            'prioridad_stock' => 'INVENTARIO',
            'consume_carne' => false,
            'consumos_carne' => null,
            'id_categoria_producto' =>
                $idCategoriaProducto,
            'id_insumo' => $idInsumo,
            'updated_at' => $now,
        ];

        if (!$idProducto) {
            $datosProducto['created_at'] = $now;

            DB::table('productos_venta')
                ->insert($datosProducto);

            return;
        }

        DB::table('productos_venta')
            ->where(
                'id_producto',
                $idProducto
            )
            ->update($datosProducto);
    }
}