<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pago extends Model
{
    protected $table = 'pagos';
    protected $primaryKey = 'id_pago';

    protected $fillable = [
        'id_pedido',
        'id_user_crea',
        'monto_efectivo',
        'monto_qr',
        'total_pagado',
        'fecha',
    ];

    protected $casts = [
        'monto_efectivo' => 'decimal:2',
        'monto_qr' => 'decimal:2',
        'total_pagado' => 'decimal:2',
        'fecha' => 'datetime',
    ];

    public function pedido()
    {
        return $this->belongsTo(
            Pedido::class,
            'id_pedido',
            'id_pedido'
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

    public function getMetodoPagoAttribute(): string
    {
        $efectivo = (float) $this->monto_efectivo;
        $qr = (float) $this->monto_qr;

        if ($efectivo > 0 && $qr > 0) {
            return 'MIXTO';
        }

        if ($qr > 0) {
            return 'QR';
        }

        return 'EFECTIVO';
    }
}