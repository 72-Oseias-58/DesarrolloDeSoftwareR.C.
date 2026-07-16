<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empleado extends Model
{
    protected $table = 'empleados';

    protected $primaryKey = 'id_empleado';

    protected $fillable = [
        'id_user',
        'id_sucursal',
        'nombre',
        'cargo',
        'estado',
        'fecha_nacimiento',
        'telefono',
        'contacto_referencia',
        'telefono_referencia',
    ];

    public function usuario()
    {
        return $this->belongsTo(
            User::class,
            'id_user',
            'id'
        );
    }

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function cajas(): HasMany
    {
        return $this->hasMany(
            Caja::class,
            'id_empleado',
            'id_empleado'
        );
    }

    public function pedidosComoCajero(): HasMany
    {
        return $this->hasMany(
            Pedido::class,
            'id_cajero',
            'id_empleado'
        );
    }

    public function movimientosCarneRecolectados(): HasMany
    {
        return $this->hasMany(
            MovimientoCarne::class,
            'id_empleado_recolector',
            'id_empleado'
        );
    }
    public function comprasInternasRealizadas()
{
    return $this->hasMany(
        CompraInterna::class,
        'id_empleado_comprador',
        'id_empleado'
    );
}
}