<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            $table->unsignedBigInteger('id_sucursal')
                ->nullable()
                ->after('id_producto');

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index(
                [
                    'id_sucursal',
                    'tipo_producto',
                ],
                'productos_venta_sucursal_tipo_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            $table->dropForeign([
                'id_sucursal',
            ]);

            $table->dropIndex(
                'productos_venta_sucursal_tipo_idx'
            );

            $table->dropColumn('id_sucursal');
        });
    }
};