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
        'consume_carne',
        'consumos_carne',
        'imagen',
        'id_categoria_producto',
        'id_insumo',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'consume_carne' => 'boolean',
        'consumos_carne' => 'array',
    ];

    protected $appends = [
        'imagen_url',
    ];

    public function getImagenUrlAttribute(): ?string
    {
        if (!$this->imagen) {
            return null;
        }

        return asset('storage/' . $this->imagen);
    }

    public function insumo()
    {
        return $this->belongsTo(
            Insumo::class,
            'id_insumo',
            'id_insumo'
        );
    }

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