<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $primaryKey = 'id_sucursal';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'estado',
    ];
    public function empleados()
{
    return $this->hasMany(
        Empleado::class,
        'id_sucursal',
        'id_sucursal'
    );
}

public function jornadas()
{
    return $this->hasMany(
        Jornada::class,
        'id_sucursal',
        'id_sucursal'
    );
}

public function pedidos()
{
    return $this->hasMany(
        Pedido::class,
        'id_sucursal',
        'id_sucursal'
    );
}
public function reportes()
{
    return $this->hasMany(
        Reporte::class,
        'id_sucursal',
        'id_sucursal'
    );
}
}