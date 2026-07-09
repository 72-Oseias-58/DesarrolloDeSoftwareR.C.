<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TiposCarneSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tipos_carne')->updateOrInsert(
            ['nombre' => 'CHANCHO'],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('tipos_carne')->updateOrInsert(
            ['nombre' => 'POLLO'],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}