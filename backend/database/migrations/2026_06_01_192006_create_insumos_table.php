<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insumos', function (Blueprint $table) {
            $table->increments('id_insumo');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_categoria_insumo');

            $table->string('nombre', 100);
            $table->string('unidad_medida', 50);
            $table->string('prioridad_stock', 50);

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

            $table->foreign('id_categoria_insumo')
                ->references('id_categoria_insumo')
                ->on('categorias_insumos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(
                [
                    'id_sucursal',
                    'nombre',
                ],
                'insumos_sucursal_nombre_unique'
            );

            $table->index(
                [
                    'id_sucursal',
                    'id_categoria_insumo',
                ],
                'insumos_sucursal_categoria_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};