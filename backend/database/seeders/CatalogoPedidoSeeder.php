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
        $categoriaPlatos = DB::table('categorias_productos')
            ->where('nombre', 'Platos')
            ->value('id_categoria_producto');

        if (!$categoriaPlatos) {
            $categoriaPlatos = DB::table('categorias_productos')
                ->insertGetId([
                    'nombre' => 'Platos',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
        }

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

        $plato50 = ProductoVenta::updateOrCreate(
            [
                'nombre' => 'Plato de chancho de Bs 50',
            ],
            [
                'descripcion' =>
                    'Plato con media costilla de chancho y guarniciones seleccionables.',
                'precio' => 50.00,
                'tipo_producto' => 'PLATO',
                'prioridad_stock' => 'ALTA',
                'id_categoria_producto' => $categoriaPlatos,
            ]
        );

        $plato70 = ProductoVenta::updateOrCreate(
            [
                'nombre' => 'Plato de chancho de Bs 70',
            ],
            [
                'descripcion' =>
                    'Plato con dos costillas de chancho y guarniciones seleccionables.',
                'precio' => 70.00,
                'tipo_producto' => 'PLATO',
                'prioridad_stock' => 'ALTA',
                'id_categoria_producto' => $categoriaPlatos,
            ]
        );

        $guarniciones = [
            $mote->id_guarnicion,
            $arroz->id_guarnicion,
            $papa->id_guarnicion,
            $yuca->id_guarnicion,
            $postre->id_guarnicion,
            $choclo->id_guarnicion,
        ];

        $plato50->guarniciones()->sync($guarniciones);
        $plato70->guarniciones()->sync($guarniciones);
    }
}