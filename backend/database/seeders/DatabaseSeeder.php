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
            // Roles
            RolesSeeder::class,

            // Permisos
            PermisosSeeder::class,
            PermisosCatalogoSeeder::class,
            PermisosStockBebidasSeeder::class,
            PermisosMovimientosCarneSeeder::class,

            // Datos base
            TiposCarneSeeder::class,
            AreasPreparacionSeeder::class,
            SucursalBaseSeeder::class,

            // Usuarios base
            UsuariosBaseSeeder::class,

            // Catálogo e inventario
            BebidasInventarioSeeder::class,
            CatalogoPedidoSeeder::class,
            RepararConsumosProductosVentaSeeder::class,
            AreasPreparacionSeeder::class,

        ]);
    }
}