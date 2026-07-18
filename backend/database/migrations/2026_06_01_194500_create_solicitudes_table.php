<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('solicitudes', function (Blueprint $table) {
            $table->increments('id_solicitud');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_user_solicita');

            $table->string(
                'tipo',
                50
            );

            $table->string(
                'asunto',
                150
            );

            $table->text(
                'descripcion'
            )->nullable();

            /*
             * Guarda varios insumos en solicitudes de reposición.
             */
            $table->json(
                'detalles_inventario'
            )->nullable();

            $table->boolean(
                'visto'
            )->default(false);

            $table->dateTime(
                'visto_en'
            )->nullable();

            $table->unsignedBigInteger(
                'id_user_visto'
            )->nullable();

            $table->dateTime(
                'fecha'
            )->useCurrent();

            $table->dateTime('created_at')
                ->nullable()
                ->useCurrent();

            $table->dateTime('updated_at')
                ->nullable()
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user_solicita')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user_visto')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->index(
                [
                    'id_sucursal',
                    'fecha',
                ],
                'solicitudes_sucursal_fecha_idx'
            );

            $table->index(
                [
                    'visto',
                    'fecha',
                ],
                'solicitudes_visto_fecha_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('solicitudes');
    }
};