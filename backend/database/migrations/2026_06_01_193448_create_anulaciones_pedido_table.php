<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('anulaciones_pedido', function (Blueprint $table) {
            $table->increments('id_anulacion');

            $table->unsignedInteger('id_pedido');
            $table->unsignedBigInteger('id_user_crea');

            $table->string('motivo', 255)->nullable();
            $table->dateTime('fecha')->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

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
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anulaciones_pedido');
    }
};