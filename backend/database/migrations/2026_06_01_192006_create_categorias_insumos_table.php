<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_insumos', function (Blueprint $table) {
            $table->increments('id_categoria_insumo');

            $table->unsignedBigInteger('id_sucursal');

            $table->string('nombre', 100);

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

            $table->unique(
                [
                    'id_sucursal',
                    'nombre',
                ],
                'categorias_insumos_sucursal_nombre_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_insumos');
    }
};