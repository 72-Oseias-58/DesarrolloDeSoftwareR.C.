<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlCarneJornada extends Model
{
    protected $table = 'control_carne_jornada';

    protected $primaryKey = 'id_control_carne';

    protected $fillable = [
        'id_sucursal',
        'id_jornada',
        'id_tipo_carne',
        'cantidad_cruces',
        'platos_estimados',
        'cantidad_base_inicial',
        'cantidad_base_actual',
        'unidad_base',
        'observacion',
    ];

    protected $casts = [
        'cantidad_cruces' => 'integer',
        'platos_estimados' => 'integer',
        'cantidad_base_inicial' => 'decimal:2',
        'cantidad_base_actual' => 'decimal:2',
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

    public function tipoCarne()
    {
        return $this->belongsTo(
            TipoCarne::class,
            'id_tipo_carne',
            'id_tipo_carne'
        );
    }
}