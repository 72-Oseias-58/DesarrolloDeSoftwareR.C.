<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id_rol' => 1,
                'nombre' => 'SUPERADMIN',
            ],
            [
                'id_rol' => 2,
                'nombre' => 'ADMIN',
            ],
            [
                'id_rol' => 3,
                'nombre' => 'CAJERO',
            ],
        ];

        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(
                [
                    'id_rol' => $rol['id_rol'],
                ],
                [
                    'nombre' => $rol['nombre'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}