<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->increments('id_pedido');

            $table->string('codigo_pedido', 20)->nullable();

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_jornada');
            $table->unsignedBigInteger('id_cajero');

            $table->dateTime('fecha')->nullable();
            $table->string('tipo_consumo', 50)->nullable();
            $table->string('estado', 50)->nullable();
            $table->decimal('total', 10, 2)->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_jornada')
                ->references('id_jornada')
                ->on('jornadas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_cajero')
                ->references('id_empleado')
                ->on('empleados')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(['id_sucursal', 'id_jornada', 'codigo_pedido']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};