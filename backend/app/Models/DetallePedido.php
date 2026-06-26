<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DetallePedido extends Model
{
    protected $table = 'detalle_pedidos';

    protected $primaryKey = 'id_detalle';

    protected $fillable = [
        'id_pedido',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'subtotal',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function pedido()
    {
        return $this->belongsTo(
            Pedido::class,
            'id_pedido',
            'id_pedido'
        );
    }

    public function producto()
    {
        return $this->belongsTo(
            ProductoVenta::class,
            'id_producto',
            'id_producto'
        );
    }

    public function guarniciones()
    {
        return $this->belongsToMany(
            Guarnicion::class,
            'detalle_pedido_guarniciones',
            'id_detalle',
            'id_guarnicion'
        )->withTimestamps();
    }
}