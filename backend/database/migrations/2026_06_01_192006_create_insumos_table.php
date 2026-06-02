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

            $table->string('nombre', 100)->nullable();
            $table->string('unidad_medida', 50)->nullable();
            $table->string('prioridad_stock', 50)->nullable();

            $table->unsignedInteger('id_categoria_insumo');

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_categoria_insumo')
                ->references('id_categoria_insumo')
                ->on('categorias_insumos')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('insumos');
    }
};