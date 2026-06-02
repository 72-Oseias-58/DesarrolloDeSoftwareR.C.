<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_solicitudes', function (Blueprint $table) {
            $table->increments('id_detalle_solicitud');

            $table->unsignedInteger('id_solicitud');
            $table->unsignedInteger('id_insumo');

            $table->integer('cantidad')->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_solicitud')
                ->references('id_solicitud')
                ->on('solicitudes')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_insumo')
                ->references('id_insumo')
                ->on('insumos')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_solicitudes');
    }
};