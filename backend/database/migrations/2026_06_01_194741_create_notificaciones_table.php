<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->increments('id_notificacion');

            $table->string('tipo_notificacion', 50)->nullable();
            $table->string('titulo', 100)->nullable();
            $table->string('mensaje', 255)->nullable();

            $table->dateTime('fecha')->nullable();
            $table->date('fecha_evento')->nullable();

            $table->unsignedInteger('id_solicitud')->nullable();
            $table->unsignedInteger('id_reporte')->nullable();
            $table->unsignedBigInteger('id_empleado')->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_solicitud')
                ->references('id_solicitud')
                ->on('solicitudes')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_reporte')
                ->references('id_reporte')
                ->on('reportes')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_empleado')
                ->references('id_empleado')
                ->on('empleados')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};