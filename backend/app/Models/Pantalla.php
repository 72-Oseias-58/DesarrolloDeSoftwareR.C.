<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pantalla extends Model
{
    protected $table = 'pantallas';

    protected $primaryKey = 'id_pantalla';

    public $timestamps = false;

    protected $fillable = [
        'id_sucursal',
        'nombre',
        'permite_finalizar',
    ];

    protected $casts = [
        'permite_finalizar' => 'boolean',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function areas()
    {
        return $this->belongsToMany(
            AreaPreparacion::class,
            'pantalla_areas',
            'id_pantalla',
            'id_area'
        );
    }
}