<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoCarne extends Model
{
    protected $table = 'tipos_carne';

    protected $primaryKey = 'id_tipo_carne';

    protected $fillable = [
        'nombre',
    ];

    public function controlesCarne()
    {
        return $this->hasMany(
            ControlCarneJornada::class,
            'id_tipo_carne',
            'id_tipo_carne'
        );
    }

    public function movimientosCarne()
    {
        return $this->hasMany(
            MovimientoCarne::class,
            'id_tipo_carne',
            'id_tipo_carne'
        );
    }
}