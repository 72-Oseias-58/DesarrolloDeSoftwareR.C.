<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            $table->unsignedInteger('id_insumo')
                ->nullable()
                ->after('id_categoria_producto');

            $table->foreign('id_insumo')
                ->references('id_insumo')
                ->on('insumos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->index(['tipo_producto', 'id_insumo']);
        });
    }

    public function down(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            $table->dropForeign(['id_insumo']);
            $table->dropIndex(['tipo_producto', 'id_insumo']);
            $table->dropColumn('id_insumo');
        });
    }
};