<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_pedido_guarniciones', function (Blueprint $table) {
            $table->unsignedInteger('id_detalle');
            $table->unsignedInteger('id_guarnicion');

            $table->dateTime('created_at')->nullable()->useCurrent();
            $table->dateTime('updated_at')->nullable()->useCurrent()->useCurrentOnUpdate();

            $table->primary(['id_detalle', 'id_guarnicion']);

            $table->foreign('id_detalle')
                ->references('id_detalle')
                ->on('detalle_pedidos')
                ->onUpdate('cascade')
                ->onDelete('cascade');

            $table->foreign('id_guarnicion')
                ->references('id_guarnicion')
                ->on('guarniciones')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_pedido_guarniciones');
    }
};