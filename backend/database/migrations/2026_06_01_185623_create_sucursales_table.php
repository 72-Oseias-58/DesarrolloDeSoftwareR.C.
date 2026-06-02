<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->bigIncrements('id_sucursal');
            $table->string('nombre', 100);
            $table->string('direccion', 150)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->enum('estado', ['ACTIVA', 'INACTIVA'])->default('ACTIVA');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};