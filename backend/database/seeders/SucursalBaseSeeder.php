<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SucursalBaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('sucursales')->insert([
            'id_sucursal' => 1,
            'nombre' => 'Sucursal Central',
            'direccion' => 'Zona Ventilla',
            'telefono' => '11223344',
            'estado' => 'ACTIVA',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}