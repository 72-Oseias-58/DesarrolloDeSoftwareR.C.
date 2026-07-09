<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('productos_venta', 'consume_carne')) {
                $table->boolean('consume_carne')->default(false)->after('prioridad_stock');
            }
        });

        Schema::table('productos_venta', function (Blueprint $table) {
            if (!Schema::hasColumn('productos_venta', 'consumos_carne')) {
                $table->json('consumos_carne')->nullable()->after('consume_carne');
            }
        });
    }

    public function down(): void
    {
        Schema::table('productos_venta', function (Blueprint $table) {
            if (Schema::hasColumn('productos_venta', 'consumos_carne')) {
                $table->dropColumn('consumos_carne');
            }

            if (Schema::hasColumn('productos_venta', 'consume_carne')) {
                $table->dropColumn('consume_carne');
            }
        });
    }
};