<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $table = 'insumos';

    protected $primaryKey = 'id_insumo';

    protected $fillable = [
        'nombre',
        'unidad_medida',
        'prioridad_stock',
        'id_categoria_insumo',
    ];

    public function categoria()
    {
        return $this->belongsTo(
            CategoriaInsumo::class,
            'id_categoria_insumo',
            'id_categoria_insumo'
        );
    }

    public function inventarios()
    {
        return $this->hasMany(
            Inventario::class,
            'id_insumo',
            'id_insumo'
        );
    }

    public function productosVenta()
    {
        return $this->hasMany(
            ProductoVenta::class,
            'id_insumo',
            'id_insumo'
        );
    }
}