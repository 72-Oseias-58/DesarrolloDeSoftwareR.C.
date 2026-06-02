<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('empleados', function (Blueprint $table) {

            $table->id('id_empleado');

            /*
            |--------------------------------------------------------------------------
            | Relación opcional con users
            |--------------------------------------------------------------------------
            | Si el empleado usa el sistema:
            | ADMIN / CAJERO -> tendrá id_user
            |
            | Si NO usa el sistema:
            | cocinero / ayudante / personal -> NULL
            */

            $table->unsignedBigInteger('id_user')
                ->nullable();

            /*
            |--------------------------------------------------------------------------
            | Sucursal
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('id_sucursal');

            /*
            |--------------------------------------------------------------------------
            | Datos laborales
            |--------------------------------------------------------------------------
            */

            $table->string('cargo', 50);

            $table->enum('estado', [
                'ACTIVO',
                'INACTIVO'
            ])->default('ACTIVO');

            /*
            |--------------------------------------------------------------------------
            | Datos personales opcionales
            |--------------------------------------------------------------------------
            */

            $table->date('fecha_nacimiento')
                ->nullable();

            $table->string('telefono', 30)
                ->nullable();

            $table->string('contacto_referencia', 100)
                ->nullable();

            $table->string('telefono_referencia', 30)
                ->nullable();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | Foreign Keys
            |--------------------------------------------------------------------------
            */

            $table->foreign('id_user')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleados');
    }
};