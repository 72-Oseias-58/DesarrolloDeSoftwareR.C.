<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BebidasInventarioSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Categoría de insumo: Bebidas
        |--------------------------------------------------------------------------
        */

        $idCategoriaBebida = DB::table('categorias_insumos')
            ->where('nombre', 'Bebidas')
            ->value('id_categoria_insumo');

        if (!$idCategoriaBebida) {
            $idCategoriaBebida = DB::table('categorias_insumos')
                ->insertGetId([
                    'nombre' => 'Bebidas',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Insumos de bebidas
        |--------------------------------------------------------------------------
        */

        $bebidas = [
            [
                'nombre' => 'Coca-Cola personal',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'ALTA',
            ],
            [
                'nombre' => 'Coca-Cola popular',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'ALTA',
            ],
            [
                'nombre' => 'Coca-Cola 1/2 litro',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'ALTA',
            ],
            [
                'nombre' => 'Coca-Cola 3 litros',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'ALTA',
            ],
            [
                'nombre' => 'Sprite personal',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'MEDIA',
            ],
            [
                'nombre' => 'Fanta personal',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'MEDIA',
            ],
        ];

        $idUserCrea = DB::table('users')
            ->orderBy('id')
            ->value('id');

        $sucursales = DB::table('sucursales')
            ->select('id_sucursal')
            ->get();

        foreach ($bebidas as $bebida) {
            $idInsumo = DB::table('insumos')
                ->where('nombre', $bebida['nombre'])
                ->value('id_insumo');

            if (!$idInsumo) {
                $idInsumo = DB::table('insumos')
                    ->insertGetId([
                        'nombre' => $bebida['nombre'],
                        'unidad_medida' => $bebida['unidad_medida'],
                        'prioridad_stock' => $bebida['prioridad_stock'],
                        'id_categoria_insumo' => $idCategoriaBebida,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Inventario inicial por sucursal
            |--------------------------------------------------------------------------
            | Stock inicia en 0. Luego el ADMIN hará el conteo real.
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
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}