<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    protected $table = 'solicitudes';

    protected $primaryKey = 'id_solicitud';

    protected $fillable = [
        'id_sucursal',
        'id_user_solicita',
        'tipo',
        'asunto',
        'descripcion',
        'detalles_inventario',
        'visto',
        'visto_en',
        'id_user_visto',
        'fecha',
    ];

    protected $casts = [
        'detalles_inventario' => 'array',
        'visto' => 'boolean',
        'visto_en' => 'datetime',
        'fecha' => 'datetime',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function usuarioSolicitante()
    {
        return $this->belongsTo(
            User::class,
            'id_user_solicita',
            'id'
        );
    }

    public function usuarioVisto()
    {
        return $this->belongsTo(
            User::class,
            'id_user_visto',
            'id'
        );
    }
}