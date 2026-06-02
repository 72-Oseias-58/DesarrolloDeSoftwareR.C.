<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
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
        ]);
    }
}