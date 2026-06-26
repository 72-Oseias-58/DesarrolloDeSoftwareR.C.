<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVenta extends Model
{
    protected $table = 'productos_venta';

    protected $primaryKey = 'id_producto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'precio',
        'tipo_producto',
        'prioridad_stock',
        'id_categoria_producto',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
    ];

    public function guarniciones()
    {
        return $this->belongsToMany(
            Guarnicion::class,
            'producto_guarniciones',
            'id_producto',
            'id_guarnicion'
        )->withTimestamps();
    }

    public function detallesPedidos()
    {
        return $this->hasMany(
            DetallePedido::class,
            'id_producto',
            'id_producto'
        );
    }
}