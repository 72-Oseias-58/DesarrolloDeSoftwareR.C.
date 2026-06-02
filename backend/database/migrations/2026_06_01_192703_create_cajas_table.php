<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->increments('id_caja');

            $table->unsignedInteger('id_jornada');
            $table->unsignedBigInteger('id_empleado');
            $table->unsignedBigInteger('id_user_crea');

            $table->decimal('monto_inicial', 10, 2)->nullable();
            $table->decimal('monto_final', 10, 2)->nullable();
            $table->decimal('total_efectivo', 10, 2)->nullable();
            $table->decimal('total_qr', 10, 2)->nullable();
            $table->decimal('diferencia', 10, 2)->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_jornada')
                ->references('id_jornada')
                ->on('jornadas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_empleado')
                ->references('id_empleado')
                ->on('empleados')
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
        Schema::dropIfExists('cajas');
    }
};