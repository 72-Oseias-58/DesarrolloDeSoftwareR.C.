<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreaPreparacion extends Model
{
    protected $table = 'areas_preparacion';

    protected $primaryKey = 'id_area';

    public $timestamps = false;

    protected $fillable = [
        'nombre_area',
    ];

    public function pantallas()
    {
        return $this->belongsToMany(
            Pantalla::class,
            'pantalla_areas',
            'id_area',
            'id_pantalla'
        );
    }
}