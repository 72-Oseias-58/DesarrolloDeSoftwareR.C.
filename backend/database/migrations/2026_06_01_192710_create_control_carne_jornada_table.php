<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('control_carne_jornada', function (Blueprint $table) {
            $table->increments('id_control_carne');

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_jornada');
            $table->unsignedInteger('id_tipo_carne');

            $table->integer('cantidad_cruces')->nullable();
            $table->integer('platos_estimados')->nullable();

            $table->decimal('cantidad_base_inicial', 10, 2)->default(0);
            $table->decimal('cantidad_base_actual', 10, 2)->default(0);
            $table->string('unidad_base', 50)->nullable();
            $table->text('observacion')->nullable();

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

            $table->foreign('id_tipo_carne')
                ->references('id_tipo_carne')
                ->on('tipos_carne')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(['id_jornada', 'id_tipo_carne']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('control_carne_jornada');
    }
};