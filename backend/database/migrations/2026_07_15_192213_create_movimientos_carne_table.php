<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_carne', function (Blueprint $table) {
            $table->increments('id_movimiento_carne');

            $table->unsignedInteger('id_control_carne');
            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_jornada');
            $table->unsignedInteger('id_tipo_carne');

            /*
             * Usuario responsable del movimiento.
             *
             * En movimientos manuales será el ADMIN.
             * En ventas automáticas será el usuario que originó el pedido
             * o el usuario definido por el servicio.
             */
            $table->unsignedBigInteger('id_user_crea');

            /*
             * Dirección matemática del movimiento.
             */
            $table->enum('tipo_movimiento', [
                'ENTRADA',
                'SALIDA',
            ]);

            /*
             * Razón operativa por la que cambió la carne.
             */
            $table->enum('motivo', [
                'APERTURA',
                'TIENDA_FAMILIAR',
                'VENTA',
                'ANULACION_VENTA',
                'AJUSTE',
                'MERMA',
            ]);

            /*
             * Unidad en la que se recibió, entregó o registró la carne.
             *
             * Pollo:
             * CRUZ_POLLO
             * POLLO
             *
             * Chancho:
             * CRUZ_CHANCHO
             * COSTILLA_GRANDE
             * MIN_COSTILLA
             */
            $table->string('unidad_registrada', 50);

            /*
             * Cantidad escrita o calculada originalmente.
             *
             * Ejemplos:
             * 1 cruz
             * 0.25 pollo
             * 1 CostillaGrande
             * 8 MinCostillas
             */
            $table->decimal('cantidad_registrada', 10, 2);

            /*
             * Cantidad convertida a la unidad base del control.
             *
             * CHANCHO => MIN_COSTILLA
             * POLLO   => POLLO
             */
            $table->decimal('cantidad_base', 10, 2);

            $table->string('unidad_base', 50);

            /*
             * Auditoría del cambio.
             */
            $table->decimal('cantidad_anterior', 10, 2);
            $table->decimal('cantidad_nueva', 10, 2);

            /*
             * Referencia opcional a la operación que originó el movimiento.
             *
             * Ejemplos:
             * PEDIDO
             * ANULACION_PEDIDO
             * APERTURA_JORNADA
             */
            $table->string('referencia_tipo', 50)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();

            /*
             * Estos campos se utilizan principalmente cuando la carne
             * entra o sale mediante la tienda familiar.
             */
            $table->string('origen', 100)->nullable();
            $table->string('destino', 100)->nullable();

            $table->string('observacion', 255)->nullable();

            $table->dateTime('created_at')
                ->nullable()
                ->useCurrent();

            $table->dateTime('updated_at')
                ->nullable()
                ->useCurrent()
                ->useCurrentOnUpdate();

            $table->foreign('id_control_carne')
                ->references('id_control_carne')
                ->on('control_carne_jornada')
                ->onUpdate('cascade');

            $table->foreign('id_sucursal')
                ->references('id_sucursal')
                ->on('sucursales')
                ->onUpdate('cascade');

            $table->foreign('id_jornada')
                ->references('id_jornada')
                ->on('jornadas')
                ->onUpdate('cascade');

            $table->foreign('id_tipo_carne')
                ->references('id_tipo_carne')
                ->on('tipos_carne')
                ->onUpdate('cascade');

            $table->foreign('id_user_crea')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade');

            $table->index(
                ['id_sucursal', 'id_jornada', 'id_tipo_carne'],
                'mov_carne_sucursal_jornada_tipo_idx'
            );

            $table->index(
                ['tipo_movimiento', 'motivo'],
                'mov_carne_tipo_motivo_idx'
            );

            $table->index(
                ['referencia_tipo', 'referencia_id'],
                'mov_carne_referencia_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_carne');
    }
};