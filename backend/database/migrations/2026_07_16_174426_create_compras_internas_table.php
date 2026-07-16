<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras_internas', function (Blueprint $table) {
            $table->increments('id_compra_interna');

            /*
            |--------------------------------------------------------------------------
            | Relaciones principales
            |--------------------------------------------------------------------------
            */

            $table->unsignedBigInteger('id_sucursal');
            $table->unsignedInteger('id_jornada');
            $table->unsignedInteger('id_caja');

            /*
             * Empleado enviado a realizar la compra.
             */
            $table->unsignedBigInteger('id_empleado_comprador');

            /*
             * Usuario ADMIN que autorizó y registró la compra.
             */
            $table->unsignedBigInteger('id_user_autoriza');

            /*
            |--------------------------------------------------------------------------
            | Información general
            |--------------------------------------------------------------------------
            */

            $table->string('motivo', 150);

            $table->enum('categoria', [
                'GAS',
                'LIMPIEZA',
                'COCINA',
                'TRANSPORTE',
                'MANTENIMIENTO',
                'EMERGENCIA',
                'OTROS',
            ])->default('OTROS');

            /*
            |--------------------------------------------------------------------------
            | Dinero entregado
            |--------------------------------------------------------------------------
            */

            /*
             * Primer monto entregado al empleado.
             *
             * Ejemplo: Bs 50.
             */
            $table->decimal(
                'monto_entregado_inicial',
                10,
                2
            );
            $table->decimal(
                'monto_adicional',
                10,
                2
            )->default(0);
            $table->decimal(
                'total_entregado',
                10,
                2
            );
            $table->decimal(
                'total_gastado',
                10,
                2
            )->nullable();
            $table->decimal(
                'cambio_devuelto',
                10,
                2
            )->nullable();
            $table->json('entregas_adicionales')
                ->nullable();

            $table->json('productos_comprados')
                ->nullable();


            $table->dateTime('fecha_hora_salida');

            $table->dateTime('fecha_hora_regreso')
                ->nullable();


            $table->enum('estado', [
                'PENDIENTE',
                'FINALIZADA',
                'ANULADA',
            ])->default('PENDIENTE');

            $table->string('observacion', 1000)
                ->nullable();


            $table->dateTime('created_at')
                ->nullable()
                ->useCurrent();

            $table->dateTime('updated_at')
                ->nullable()
                ->useCurrent()
                ->useCurrentOnUpdate();
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

            $table->foreign('id_caja')
                ->references('id_caja')
                ->on('cajas')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_empleado_comprador')
                ->references('id_empleado')
                ->on('empleados')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->foreign('id_user_autoriza')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');
            $table->index(
                [
                    'id_sucursal',
                    'id_jornada',
                    'estado',
                ],
                'compra_interna_sucursal_jornada_estado_idx'
            );

            $table->index(
                [
                    'id_caja',
                    'estado',
                ],
                'compra_interna_caja_estado_idx'
            );

            $table->index(
                [
                    'id_empleado_comprador',
                    'fecha_hora_salida',
                ],
                'compra_interna_empleado_salida_idx'
            );
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('compras_internas');
    }
};