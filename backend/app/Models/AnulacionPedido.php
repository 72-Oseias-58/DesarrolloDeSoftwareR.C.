<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnulacionPedido extends Model
{
    protected $table = 'anulaciones_pedido';

    protected $primaryKey = 'id_anulacion';

    protected $fillable = [
        'id_pedido',
        'id_user_anula',
        'motivo',
        'fecha',
    ];

    protected $casts = [
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

    public function usuarioAnula()
    {
        return $this->belongsTo(
            User::class,
            'id_user_anula',
            'id'
        );
    }
}