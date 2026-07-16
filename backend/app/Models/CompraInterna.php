<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompraInterna extends Model
{
    protected $table = 'compras_internas';

    protected $primaryKey = 'id_compra_interna';

    protected $fillable = [
        'id_sucursal',
        'id_jornada',
        'id_caja',
        'id_empleado_comprador',
        'id_user_autoriza',
        'motivo',
        'categoria',
        'monto_entregado_inicial',
        'monto_adicional',
        'total_entregado',
        'total_gastado',
        'cambio_devuelto',
        'entregas_adicionales',
        'productos_comprados',
        'fecha_hora_salida',
        'fecha_hora_regreso',
        'estado',
        'observacion',
    ];

    protected $casts = [
        'monto_entregado_inicial' => 'decimal:2',
        'monto_adicional' => 'decimal:2',
        'total_entregado' => 'decimal:2',
        'total_gastado' => 'decimal:2',
        'cambio_devuelto' => 'decimal:2',
        'entregas_adicionales' => 'array',
        'productos_comprados' => 'array',
        'fecha_hora_salida' => 'datetime',
        'fecha_hora_regreso' => 'datetime',
    ];

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(
            Jornada::class,
            'id_jornada',
            'id_jornada'
        );
    }

    public function caja(): BelongsTo
    {
        return $this->belongsTo(
            Caja::class,
            'id_caja',
            'id_caja'
        );
    }

    public function empleadoComprador(): BelongsTo
    {
        return $this->belongsTo(
            Empleado::class,
            'id_empleado_comprador',
            'id_empleado'
        );
    }

    public function usuarioAutoriza(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_user_autoriza',
            'id'
        );
    }
}