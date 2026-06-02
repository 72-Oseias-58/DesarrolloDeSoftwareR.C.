<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pantalla_areas', function (Blueprint $table) {
            $table->unsignedInteger('id_pantalla');
            $table->unsignedInteger('id_area');

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->primary(['id_pantalla', 'id_area']);

            $table->foreign('id_pantalla')
                ->references('id_pantalla')
                ->on('pantallas')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_area')
                ->references('id_area')
                ->on('areas_preparacion')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pantalla_areas');
    }
};