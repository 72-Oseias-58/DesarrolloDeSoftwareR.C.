<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos_venta', function (Blueprint $table) {
            $table->increments('id_producto');

            $table->string('nombre', 100)->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('precio', 10, 2)->nullable();
            $table->string('tipo_producto', 50)->nullable();
            $table->string('prioridad_stock', 50)->nullable();

            $table->unsignedInteger('id_categoria_producto');

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_categoria_producto')
                ->references('id_categoria_producto')
                ->on('categorias_productos')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos_venta');
    }
};