<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedidos', function (Blueprint $table) {
            $table->increments('id_detalle');

            $table->unsignedInteger('id_pedido');

            // Nullable porque la venta manual de pura carne no depende de un producto fijo.
            $table->unsignedInteger('id_producto')->nullable();

            $table->integer('cantidad')->nullable();
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->string('observacion', 255)->nullable();

            // Consumo real de producción descontado por este detalle.
            $table->decimal('consumo_chancho_total', 10, 2)->default(0);
            $table->decimal('consumo_pollo_total', 10, 2)->default(0);

            // Venta manual de pura carne.
            $table->boolean('es_pura_carne')->default(false);
            $table->string('tipo_carne_manual', 50)->nullable();
            $table->decimal('cantidad_carne_manual', 10, 2)->nullable();
            $table->string('unidad_carne_manual', 50)->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_pedido')
                ->references('id_pedido')
                ->on('pedidos')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_producto')
                ->references('id_producto')
                ->on('productos_venta')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedidos');
    }
};