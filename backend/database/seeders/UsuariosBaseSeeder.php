<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosBaseSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'Jhonatan Ibarra',
                'usuario' => 'jhonatan',
                'email' => 'jhonatan@rincon.local',
                'email_verified_at' => null,
                'id_rol' => 1,
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Johan Ibarra',
                'usuario' => 'admin',
                'email' => 'johanIbarra@rincon.local',
                'email_verified_at' => null,
                'id_rol' => 2,
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Bruno Calle',
                'usuario' => 'cajero',
                'email' => 'brunoCalle@rincon.local',
                'email_verified_at' => null,
                'id_rol' => 3,
                'password' => Hash::make('12345678'),
                'remember_token' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        DB::table('empleados')->insert([
            [
                'id_empleado' => 1,
                'id_user' => 2,
                'id_sucursal' => 1,
                'cargo' => 'ADMIN',
                'fecha_nacimiento' => null,
                'telefono' => null,
                'contacto_referencia' => null,
                'telefono_referencia' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id_empleado' => 2,
                'id_user' => 3,
                'id_sucursal' => 1,
                'cargo' => 'CAJERO',
                'fecha_nacimiento' => null,
                'telefono' => null,
                'contacto_referencia' => null,
                'telefono_referencia' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}