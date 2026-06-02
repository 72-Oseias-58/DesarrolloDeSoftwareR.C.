<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alertas_stock', function (Blueprint $table) {
            $table->increments('id_alerta');

            $table->unsignedInteger('id_inventario');

            $table->string('tipo_alerta', 50)->nullable();
            $table->string('mensaje', 255)->nullable();
            $table->string('prioridad', 50)->nullable();
            $table->dateTime('fecha')->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_inventario')
                ->references('id_inventario')
                ->on('inventarios')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alertas_stock');
    }
};