<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Caja extends Model
{
    protected $table = 'cajas';

    protected $primaryKey = 'id_caja';

    protected $fillable = [
        'id_jornada',
        'id_empleado',
        'id_user_crea',
        'monto_inicial',
        'monto_final',
        'total_efectivo',
        'total_qr',
        'diferencia',
        'hora_apertura',
        'hora_cierre',
        'estado',
    ];

    protected $casts = [
        'monto_inicial' => 'decimal:2',
        'monto_final' => 'decimal:2',
        'total_efectivo' => 'decimal:2',
        'total_qr' => 'decimal:2',
        'diferencia' => 'decimal:2',
    ];

    public function jornada()
    {
        return $this->belongsTo(
            Jornada::class,
            'id_jornada',
            'id_jornada'
        );
    }

    public function empleado()
    {
        return $this->belongsTo(
            Empleado::class,
            'id_empleado',
            'id_empleado'
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