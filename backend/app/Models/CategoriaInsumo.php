<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaInsumo extends Model
{
    protected $table = 'categorias_insumos';

    protected $primaryKey = 'id_categoria_insumo';

    protected $fillable = [
        'id_sucursal',
        'nombre',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function insumos()
    {
        return $this->hasMany(
            Insumo::class,
            'id_categoria_insumo',
            'id_categoria_insumo'
        );
    }
}