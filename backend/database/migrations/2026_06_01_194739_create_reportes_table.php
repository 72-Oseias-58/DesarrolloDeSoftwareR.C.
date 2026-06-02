<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes', function (Blueprint $table) {
            $table->increments('id_reporte');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedBigInteger('id_user');
            $table->unsignedBigInteger('id_user_crea');

            $table->string('tipo', 50)->nullable();
            $table->text('descripcion')->nullable();
            $table->dateTime('fecha')->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('reportes');
    }
};