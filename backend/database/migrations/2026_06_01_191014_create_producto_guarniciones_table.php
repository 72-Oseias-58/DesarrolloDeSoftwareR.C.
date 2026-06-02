<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producto_guarniciones', function (Blueprint $table) {
            $table->unsignedInteger('id_producto');
            $table->unsignedInteger('id_guarnicion');

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->primary(['id_producto', 'id_guarnicion']);

            $table->foreign('id_producto')
                ->references('id_producto')
                ->on('productos_venta')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_guarnicion')
                ->references('id_guarnicion')
                ->on('guarniciones')
                ->onUpdate('cascade')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_guarniciones');
    }
};