<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guarnicion extends Model
{
    protected $table = 'guarniciones';

    protected $primaryKey = 'id_guarnicion';

    protected $fillable = [
        'nombre',
    ];

    public function productos()
    {
        return $this->belongsToMany(
            ProductoVenta::class,
            'producto_guarniciones',
            'id_guarnicion',
            'id_producto'
        )->withTimestamps();
    }

    public function detallesPedidos()
    {
        return $this->belongsToMany(
            DetallePedido::class,
            'detalle_pedido_guarniciones',
            'id_guarnicion',
            'id_detalle'
        )->withTimestamps();
    }
}