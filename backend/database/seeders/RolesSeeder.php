<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'id_rol' => 1,
                'nombre' => 'SUPERADMIN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_rol' => 2,
                'nombre' => 'ADMIN',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_rol' => 3,
                'nombre' => 'CAJERO',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}