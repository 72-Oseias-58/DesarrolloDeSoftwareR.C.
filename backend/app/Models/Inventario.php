<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';
    protected $primaryKey = 'id_inventario';

    protected $fillable = [
        'id_sucursal',
        'id_insumo',
        'id_user_crea',
        'stock_actual',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function insumo()
    {
        return $this->belongsTo(
            Insumo::class,
            'id_insumo',
            'id_insumo'
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
    public function movimientos()
{
    return $this->hasMany(
        MovimientoInventario::class,
        'id_insumo',
        'id_insumo'
    );
}
}