<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InsumosBaseSeeder extends Seeder
{
    public function run(): void
    {
        // Categorías
        $categorias = [
            'Alimentos',
            'Bebidas',
            'Limpieza',
            'Materiales',
            'Combustible',
        ];

        foreach ($categorias as $nombre) {
            DB::table('categorias_insumos')
                ->updateOrInsert(
                    ['nombre' => $nombre],
                    [
                        'nombre' => $nombre,
                        'updated_at' => now(),
                    ]
                );
        }

        $idAlimentos = DB::table('categorias_insumos')
            ->where('nombre', 'Alimentos')
            ->value('id_categoria_insumo');

        $idMateriales = DB::table('categorias_insumos')
            ->where('nombre', 'Materiales')
            ->value('id_categoria_insumo');

        $idCombustible = DB::table('categorias_insumos')
            ->where('nombre', 'Combustible')
            ->value('id_categoria_insumo');

        // Insumos principales
        $insumos = [
            [
                'nombre' => 'Arroz',
                'unidad_medida' => 'KG',
                'prioridad_stock' => 'ALTA',
                'id_categoria_insumo' => $idAlimentos,
            ],
            [
                'nombre' => 'Papa',
                'unidad_medida' => 'KG',
                'prioridad_stock' => 'ALTA',
                'id_categoria_insumo' => $idAlimentos,
            ],
            [
                'nombre' => 'Aceite',
                'unidad_medida' => 'L',
                'prioridad_stock' => 'ALTA',
                'id_categoria_insumo' => $idAlimentos,
            ],
            [
                'nombre' => 'Gas',
                'unidad_medida' => 'BALON',
                'prioridad_stock' => 'ALTA',
                'id_categoria_insumo' => $idCombustible,
            ],
            [
                'nombre' => 'Servilletas',
                'unidad_medida' => 'PAQUETE',
                'prioridad_stock' => 'MEDIA',
                'id_categoria_insumo' => $idMateriales,
            ],
            [
                'nombre' => 'Envases',
                'unidad_medida' => 'UNIDAD',
                'prioridad_stock' => 'MEDIA',
                'id_categoria_insumo' => $idMateriales,
            ],
        ];

        foreach ($insumos as $insumo) {
            DB::table('insumos')
                ->updateOrInsert(
                    ['nombre' => $insumo['nombre']],
                    [
                        ...$insumo,
                        'updated_at' => now(),
                    ]
                );
        }
    }
}