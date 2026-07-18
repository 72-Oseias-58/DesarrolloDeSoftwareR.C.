<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventarios', function (Blueprint $table) {
            $table->increments('id_inventario');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_insumo');
            $table->unsignedBigInteger('id_user_crea');

            $table->decimal(
                'stock_actual',
                10,
                2
            )->default(0);

            $table->decimal(
                'stock_minimo',
                10,
                2
            )->default(0);

            $table->dateTime('created_at')
                ->nullable()
                ->useCurrent();

            $table->dateTime('updated_at')
                ->nullable()
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_insumo')
                ->references('id_insumo')
                ->on('insumos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user_crea')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(
                [
                    'id_sucursal',
                    'id_insumo',
                ],
                'inventarios_sucursal_insumo_unique'
            );

            $table->index(
                [
                    'id_sucursal',
                    'stock_actual',
                    'stock_minimo',
                ],
                'inventarios_alertas_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};