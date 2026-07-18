<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->increments('id_movimiento');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_jornada')->nullable();
            $table->unsignedInteger('id_insumo');
            $table->unsignedBigInteger('id_user_crea');

            $table->string(
                'tipo_movimiento',
                30
            );

            $table->string(
                'motivo',
                50
            );

            $table->decimal(
                'cantidad',
                10,
                2
            );

            $table->decimal(
                'stock_anterior',
                10,
                2
            );

            $table->decimal(
                'stock_nuevo',
                10,
                2
            );

            $table->string(
                'referencia_tipo',
                50
            )->nullable();

            $table->unsignedBigInteger(
                'referencia_id'
            )->nullable();

            $table->string(
                'observacion',
                255
            )->nullable();

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

            $table->foreign('id_jornada')
                ->references('id_jornada')
                ->on('jornadas')
                ->onUpdate('cascade')
                ->onDelete('set null');

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

            $table->index(
                [
                    'id_sucursal',
                    'id_jornada',
                    'id_insumo',
                ],
                'mov_inv_sucursal_jornada_insumo_idx'
            );

            $table->index(
                [
                    'tipo_movimiento',
                    'motivo',
                ],
                'mov_inv_tipo_motivo_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};