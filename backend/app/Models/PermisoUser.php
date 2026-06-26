<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisoUser extends Model
{
    protected $table = 'permiso_user';

    protected $fillable = [
        'id_user',
        'id_permiso',
        'tipo',
    ];

    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_user', 'id');
    }

    public function permiso()
    {
        return $this->belongsTo(Permiso::class, 'id_permiso', 'id_permiso');
    }
}