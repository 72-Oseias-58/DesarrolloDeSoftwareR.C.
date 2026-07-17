<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $primaryKey = 'id_movimiento';

    protected $fillable = [
        'id_sucursal',
        'id_jornada',
        'id_insumo',
        'id_user_crea',
        'tipo_movimiento',
        'motivo',
        'cantidad', 
        'stock_anterior',
        'stock_nuevo',
        'referencia_tipo',
        'referencia_id',
        'observacion',
    ];

    protected $casts = [
        'cantidad' => 'decimal:2',
        'stock_anterior' => 'decimal:2',
        'stock_nuevo' => 'decimal:2',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function jornada()
    {
        return $this->belongsTo(
            Jornada::class,
            'id_jornada',
            'id_jornada'
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
}