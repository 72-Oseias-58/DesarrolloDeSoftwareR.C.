<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permiso extends Model
{
    protected $table = 'permisos';
    protected $primaryKey = 'id_permiso';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
    ];

    public function roles()
    {
        return $this->belongsToMany(
            Role::class,
            'permiso_rol',
            'id_permiso',
            'id_rol'
        );
    }
}