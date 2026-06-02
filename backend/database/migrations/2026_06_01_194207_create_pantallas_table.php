<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantallas', function (Blueprint $table) {
            $table->increments('id_pantalla');

            $table->unsignedBigInteger('id_sucursal');

            $table->string('nombre', 100)->nullable();

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantallas');
    }
};