<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BebidasInventarioSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Categoría de insumo: Bebidas
        |--------------------------------------------------------------------------
        */

        $idCategoriaInsumoBebida = DB::table('categorias_insumos')
            ->whereRaw('UPPER(nombre) = ?', ['BEBIDAS'])
            ->value('id_categoria_insumo');

        if (!$idCategoriaInsumoBebida) {
            $idCategoriaInsumoBebida = DB::table('categorias_insumos')
                ->insertGetId([
                    'nombre' => 'Bebidas',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Categoría de producto: Bebidas
        |--------------------------------------------------------------------------
        */

        $idCategoriaProductoBebida = DB::table('categorias_productos')
            ->whereRaw('UPPER(nombre) = ?', ['BEBIDAS'])
            ->value('id_categoria_producto');

        if (!$idCategoriaProductoBebida) {
            $idCategoriaProductoBebida = DB::table('categorias_productos')
                ->insertGetId([
                    'nombre' => 'Bebidas',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Usuario creador
        |--------------------------------------------------------------------------
        */

        $idUserCrea = DB::table('users')
            ->orderBy('id')
            ->value('id');

        if (!$idUserCrea) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Sucursales
        |--------------------------------------------------------------------------
        */

        $sucursales = DB::table('sucursales')
            ->select('id_sucursal')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Bebidas base actuales
        |--------------------------------------------------------------------------
        */

        $bebidasBase = [
            [
                'nombre' => 'Coca-Cola 2 litros',
                'descripcion' => 'Refresco Coca-Cola de 2 litros.',
                'precio' => 15.00,
                'unidad_medida' => 'UNIDAD',
            ],
            [
                'nombre' => 'Fanta 500 ml',
                'descripcion' => 'Refresco Fanta de 500 ml.',
                'precio' => 7.00,
                'unidad_medida' => 'UNIDAD',
            ],
        ];

        foreach ($bebidasBase as $bebida) {
            $this->crearORepararBebida(
                $bebida['nombre'],
                $bebida['descripcion'],
                $bebida['precio'],
                $bebida['unidad_medida'],
                $idCategoriaInsumoBebida,
                $idCategoriaProductoBebida,
                $idUserCrea,
                $sucursales
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Reparar bebidas antiguas huérfanas
        |--------------------------------------------------------------------------
        | Caso detectado:
        | - Coca-Cola personal
        | - tipo_producto = BEBIDA
        | - prioridad_stock = INVENTARIO
        | - id_insumo = NULL
        |
        | Toda bebida de productos_venta debe tener:
        | - id_insumo
        | - insumo en categoría Bebidas
        | - inventario por sucursal
        |--------------------------------------------------------------------------
        */

        $bebidasHuerfanas = DB::table('productos_venta')
            ->where('tipo_producto', 'BEBIDA')
            ->where(function ($query) {
                $query->whereNull('id_insumo')
                    ->orWhere('prioridad_stock', '!=', 'INVENTARIO');
            })
            ->get();

        foreach ($bebidasHuerfanas as $producto) {
            $this->crearORepararBebida(
                $producto->nombre,
                $producto->descripcion ?? 'Bebida registrada en catálogo.',
                (float) ($producto->precio ?? 0),
                'UNIDAD',
                $idCategoriaInsumoBebida,
                $idCategoriaProductoBebida,
                $idUserCrea,
                $sucursales,
                $producto->id_producto
            );
        }
    }

    private function crearORepararBebida(
        string $nombre,
        ?string $descripcion,
        float $precio,
        string $unidadMedida,
        int $idCategoriaInsumoBebida,
        int $idCategoriaProductoBebida,
        int $idUserCrea,
        $sucursales,
        ?int $idProductoExistente = null
    ): void {
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Crear o reparar insumo
        |--------------------------------------------------------------------------
        */

        $idInsumo = DB::table('insumos')
            ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nombre)])
            ->value('id_insumo');

        if (!$idInsumo) {
            $idInsumo = DB::table('insumos')
                ->insertGetId([
                    'nombre' => $nombre,
                    'unidad_medida' => $unidadMedida ?: 'UNIDAD',
                    'prioridad_stock' => 'INVENTARIO',
                    'id_categoria_insumo' => $idCategoriaInsumoBebida,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        } else {
            DB::table('insumos')
                ->where('id_insumo', $idInsumo)
                ->update([
                    'unidad_medida' => $unidadMedida ?: 'UNIDAD',
                    'prioridad_stock' => 'INVENTARIO',
                    'id_categoria_insumo' => $idCategoriaInsumoBebida,
                    'updated_at' => $now,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Crear o reparar producto de venta
        |--------------------------------------------------------------------------
        */

        $idProducto = $idProductoExistente;

        if (!$idProducto) {
            $idProducto = DB::table('productos_venta')
                ->whereRaw('UPPER(nombre) = ?', [mb_strtoupper($nombre)])
                ->value('id_producto');
        }

        $datosProducto = [
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio > 0 ? $precio : 1,
            'tipo_producto' => 'BEBIDA',
            'prioridad_stock' => 'INVENTARIO',
            'consume_carne' => false,
            'consumos_carne' => null,
            'id_categoria_producto' => $idCategoriaProductoBebida,
            'id_insumo' => $idInsumo,
            'updated_at' => $now,
        ];

        if (!$idProducto) {
            $datosProducto['created_at'] = $now;

            DB::table('productos_venta')->insert($datosProducto);
        } else {
            DB::table('productos_venta')
                ->where('id_producto', $idProducto)
                ->update($datosProducto);
        }

        /*
        |--------------------------------------------------------------------------
        | Crear inventario por sucursal
        |--------------------------------------------------------------------------
        */

        foreach ($sucursales as $sucursal) {
            $existeInventario = DB::table('inventarios')
                ->where('id_sucursal', $sucursal->id_sucursal)
                ->where('id_insumo', $idInsumo)
                ->exists();

            if (!$existeInventario) {
                DB::table('inventarios')->insert([
                    'id_sucursal' => $sucursal->id_sucursal,
                    'id_insumo' => $idInsumo,
                    'id_user_crea' => $idUserCrea,
                    'stock_actual' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }
}