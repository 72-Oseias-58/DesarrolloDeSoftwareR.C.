<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificacion_destinatarios', function (Blueprint $table) {
            $table->increments('id_destinatario');

            $table->unsignedInteger('id_notificacion');

            $table->unsignedBigInteger('id_user')->nullable();
            $table->unsignedBigInteger('id_rol')->nullable();
            $table->unsignedBigInteger('id_sucursal')->nullable();

            $table->boolean('leido')->nullable()->default(false);

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_notificacion')
                ->references('id_notificacion')
                ->on('notificaciones')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_rol')
                ->references('id_rol')
                ->on('roles')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_destinatarios');
    }
};