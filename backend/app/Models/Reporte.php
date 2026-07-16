<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reporte extends Model
{
    protected $table = 'reportes';

    protected $primaryKey = 'id_reporte';

    protected $fillable = [
        'id_sucursal',
        'id_jornada',
        'id_user_crea',
        'tipo',
        'total_ventas',
        'total_efectivo',
        'total_qr',
        'monto_inicial_total_cajas',
        'efectivo_antes_gastos',
        'cantidad_compras_internas',
        'dinero_entregado_inicial',
        'dinero_adicional_entregado',
        'dinero_total_entregado',
        'total_gastos_reales',
        'total_cambio_devuelto',
        'efectivo_estimado_total',
        'efectivo_fisico_total',
        'diferencia_total',
        'resultado_operativo',
        'cantidad_cajas',
        'resumen_cajas',
        'resumen_compras',
        'descripcion',
        'fecha',
    ];

    protected $casts = [
        'total_ventas' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_qr' => 'decimal:2',

        'monto_inicial_total_cajas' => 'decimal:2',
        'efectivo_antes_gastos' => 'decimal:2',

        'cantidad_compras_internas' => 'integer',

        'dinero_entregado_inicial' => 'decimal:2',
        'dinero_adicional_entregado' => 'decimal:2',
        'dinero_total_entregado' => 'decimal:2',

        'total_gastos_reales' => 'decimal:2',
        'total_cambio_devuelto' => 'decimal:2',

        'efectivo_estimado_total' => 'decimal:2',
        'efectivo_fisico_total' => 'decimal:2',
        'diferencia_total' => 'decimal:2',
        'resultado_operativo' => 'decimal:2',

        'cantidad_cajas' => 'integer',

        'resumen_cajas' => 'array',
        'resumen_compras' => 'array',

        'fecha' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function jornada()
    {
        return $this->belongsTo(
            Jornada::class,
            'id_jornada',
            'id_jornada'
        );
    }

    public function usuarioCreador()
    {
        return $this->belongsTo(
            User::class,
            'id_user_crea',
            'id'
        );
    }
}