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
        'consumo_chancho_total',
        'consumo_pollo_total',
        'es_pura_carne',
        'tipo_carne_manual',
        'cantidad_carne_manual',
        'unidad_carne_manual',
    ];

    protected $casts = [
        'cantidad' => 'integer',
        'precio_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'consumo_chancho_total' => 'decimal:2',
        'consumo_pollo_total' => 'decimal:2',
        'es_pura_carne' => 'boolean',
        'cantidad_carne_manual' => 'decimal:2',
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