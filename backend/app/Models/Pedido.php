<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pedido extends Model
{
    protected $table = 'pedidos';

    protected $primaryKey = 'id_pedido';

    protected $fillable = [
        'codigo_pedido',
        'id_sucursal',
        'id_jornada',
        'id_cajero',
        'fecha',
        'tipo_consumo',
        'estado',
        'total',
    ];

    protected $casts = [
        'fecha' => 'datetime',
        'total' => 'decimal:2',
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

    public function cajero()
    {
        return $this->belongsTo(
            Empleado::class,
            'id_cajero',
            'id_empleado'
        );
    }

    public function pago()
    {
        return $this->hasOne(
            Pago::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function anulacion()
    {
        return $this->hasOne(
            AnulacionPedido::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function detalles()
    {
        return $this->hasMany(
            DetallePedido::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function movimientosCarne()
    {
        return $this->hasMany(
            MovimientoCarne::class,
            'referencia_id',
            'id_pedido'
        )->where('referencia_tipo', 'PEDIDO');
    }

    public function estaPagado(): bool
    {
        return strtoupper((string) $this->estado) === 'PAGADO';
    }

    public function estaAnulado(): bool
    {
        return $this->anulacion()->exists();
    }
}