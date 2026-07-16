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
            $table->unsignedInteger('id_jornada');
            $table->unsignedBigInteger('id_user_crea');

            $table->string('tipo', 50)
                ->default('CIERRE_JORNADA');

            $table->decimal('total_ventas', 12, 2)
                ->default(0);

            $table->decimal('total_efectivo', 12, 2)
                ->default(0);

            $table->decimal('total_qr', 12, 2)
                ->default(0);

            $table->decimal(
                'monto_inicial_total_cajas',
                12,
                2
            )->default(0);

            $table->decimal(
                'efectivo_antes_gastos',
                12,
                2
            )->default(0);

            $table->unsignedInteger(
                'cantidad_compras_internas'
            )->default(0);

            $table->decimal(
                'dinero_entregado_inicial',
                12,
                2
            )->default(0);

            $table->decimal(
                'dinero_adicional_entregado',
                12,
                2
            )->default(0);

            $table->decimal(
                'dinero_total_entregado',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_gastos_reales',
                12,
                2
            )->default(0);

            $table->decimal(
                'total_cambio_devuelto',
                12,
                2
            )->default(0);

            $table->decimal(
                'efectivo_estimado_total',
                12,
                2
            )->default(0);

            $table->decimal(
                'efectivo_fisico_total',
                12,
                2
            )->default(0);

            $table->decimal(
                'diferencia_total',
                12,
                2
            )->default(0);

            $table->decimal(
                'resultado_operativo',
                12,
                2
            )->default(0);

            $table->unsignedInteger(
                'cantidad_cajas'
            )->default(0);

            $table->json('resumen_cajas')
                ->nullable();

            $table->json('resumen_compras')
                ->nullable();

            $table->text('descripcion')
                ->nullable();

            $table->dateTime('fecha');

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

            $table->foreign('id_user_crea')
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            $table->unique(
                'id_jornada',
                'reporte_cierre_jornada_unique'
            );

            $table->index(
                [
                    'id_sucursal',
                    'fecha',
                ],
                'reporte_sucursal_fecha_idx'
            );

            $table->index(
                [
                    'tipo',
                    'fecha',
                ],
                'reporte_tipo_fecha_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes');
    }
};