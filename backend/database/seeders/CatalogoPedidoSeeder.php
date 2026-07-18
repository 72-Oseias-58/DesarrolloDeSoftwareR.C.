<?php

namespace Database\Seeders;

use App\Models\Guarnicion;
use App\Models\ProductoVenta;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogoPedidoSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        /*
        |--------------------------------------------------------------------------
        | Categoría global de productos: Platos
        |--------------------------------------------------------------------------
        */

        $categoriaPlatos = DB::table('categorias_productos')
            ->whereRaw('UPPER(nombre) = ?', ['PLATOS'])
            ->value('id_categoria_producto');

        if (!$categoriaPlatos) {
            $categoriaPlatos = DB::table('categorias_productos')
                ->insertGetId([
                    'nombre' => 'Platos',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Guarniciones disponibles
        |--------------------------------------------------------------------------
        */

        $mote = Guarnicion::firstOrCreate([
            'nombre' => 'Mote',
        ]);

        $arroz = Guarnicion::firstOrCreate([
            'nombre' => 'Arroz',
        ]);

        $papa = Guarnicion::firstOrCreate([
            'nombre' => 'Papa',
        ]);

        $yuca = Guarnicion::firstOrCreate([
            'nombre' => 'Yuca',
        ]);

        $postre = Guarnicion::firstOrCreate([
            'nombre' => 'Postre',
        ]);

        $choclo = Guarnicion::firstOrCreate([
            'nombre' => 'Choclo',
        ]);

        $guarnicionesPlato = [
            $mote->id_guarnicion,
            $arroz->id_guarnicion,
            $papa->id_guarnicion,
            $yuca->id_guarnicion,
            $postre->id_guarnicion,
            $choclo->id_guarnicion,
        ];

        /*
        |--------------------------------------------------------------------------
        | Crear catálogo independiente por sucursal
        |--------------------------------------------------------------------------
        */

        $sucursales = DB::table('sucursales')
            ->select('id_sucursal')
            ->get();

        foreach ($sucursales as $sucursal) {
            $idSucursal = (int) $sucursal->id_sucursal;

            $plato50 = ProductoVenta::updateOrCreate(
                [
                    'id_sucursal' => $idSucursal,
                    'nombre' => 'Plato de chancho de Bs 50',
                ],
                [
                    'descripcion' =>
                        'Plato con 1.5 costillas/huesos de chancho y guarniciones seleccionables.',

                    'precio' => 50.00,

                    'tipo_producto' => 'PLATO',

                    'prioridad_stock' =>
                        'PRODUCCION_DIARIA',

                    'id_categoria_producto' =>
                        $categoriaPlatos,

                    'id_insumo' => null,

                    'consume_carne' => true,

                    'consumos_carne' => [
                        'CHANCHO' => 1.5,
                    ],
                ]
            );

            $plato70 = ProductoVenta::updateOrCreate(
                [
                    'id_sucursal' => $idSucursal,
                    'nombre' => 'Plato de chancho de Bs 70',
                ],
                [
                    'descripcion' =>
                        'Plato con 2 costillas/huesos de chancho y guarniciones seleccionables.',

                    'precio' => 70.00,

                    'tipo_producto' => 'PLATO',

                    'prioridad_stock' =>
                        'PRODUCCION_DIARIA',

                    'id_categoria_producto' =>
                        $categoriaPlatos,

                    'id_insumo' => null,

                    'consume_carne' => true,

                    'consumos_carne' => [
                        'CHANCHO' => 2,
                    ],
                ]
            );

            $mixto90 = ProductoVenta::updateOrCreate(
                [
                    'id_sucursal' => $idSucursal,
                    'nombre' => 'Plato mixto de Bs 90',
                ],
                [
                    'descripcion' =>
                        'Plato mixto grande con chancho, pollo y guarniciones seleccionables.',

                    'precio' => 90.00,

                    'tipo_producto' => 'PLATO',

                    'prioridad_stock' =>
                        'PRODUCCION_DIARIA',

                    'id_categoria_producto' =>
                        $categoriaPlatos,

                    'id_insumo' => null,

                    'consume_carne' => true,

                    'consumos_carne' => [
                        'CHANCHO' => 2,
                        'POLLO' => 1,
                    ],
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Relacionar guarniciones
            |--------------------------------------------------------------------------
            */

            $plato50->guarniciones()
                ->sync($guarnicionesPlato);

            $plato70->guarniciones()
                ->sync($guarnicionesPlato);

            $mixto90->guarniciones()
                ->sync($guarnicionesPlato);
        }
    }
}