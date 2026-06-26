<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jornada extends Model
{
    protected $table = 'jornadas';

    protected $primaryKey = 'id_jornada';

    protected $fillable = [
        'id_sucursal',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'estado',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function cajas()
    {
        return $this->hasMany(
            Caja::class,
            'id_jornada',
            'id_jornada'
        );
    }

    public function pedidos()
    {
        return $this->hasMany(
            Pedido::class,
            'id_jornada',
            'id_jornada'
        );
    }
}