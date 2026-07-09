<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolesSeeder::class,

            PermisosSeeder::class,
            PermisosCatalogoSeeder::class,
            PermisosStockBebidasSeeder::class,

            TiposCarneSeeder::class,
            AreasPreparacionSeeder::class,

            SucursalBaseSeeder::class,
            UsuariosBaseSeeder::class,

            BebidasInventarioSeeder::class,
            CatalogoPedidoSeeder::class,
        ]);
    }
}