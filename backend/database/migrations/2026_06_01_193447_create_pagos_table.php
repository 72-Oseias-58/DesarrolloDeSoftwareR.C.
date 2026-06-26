<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->increments('id_pago');

            $table->unsignedInteger('id_pedido');
            $table->unsignedBigInteger('id_user_crea');

            $table->decimal('monto_efectivo', 10, 2)->default(0);
            $table->decimal('monto_qr', 10, 2)->default(0);
            $table->decimal('total_pagado', 10, 2);
            $table->dateTime('fecha');

            $table->dateTime('created_at')
                ->nullable()
                ->useCurrent();

            $table->dateTime('updated_at')
                ->nullable()
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->foreign('id_pedido')
                ->references('id_pedido')
                ->on('pedidos')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user_crea')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique('id_pedido');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};