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
            $table->unsignedInteger('id_producto');

            $table->integer('cantidad')->nullable();
            $table->decimal('precio_unitario', 10, 2)->nullable();
            $table->decimal('subtotal', 10, 2)->nullable();
            $table->string('observacion', 255)->nullable();

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