<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreasPreparacionSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            [
                'nombre_area' => 'Guarniciones',
            ],
            [
                'nombre_area' => 'Carne',
            ],
            [
                'nombre_area' => 'Bebidas',
            ],
        ];

        foreach ($areas as $area) {
            DB::table('areas_preparacion')->updateOrInsert(
                [
                    'nombre_area' => $area['nombre_area'],
                ],
                [
                    'nombre_area' => $area['nombre_area'],
                ]
            );
        }
    }
}