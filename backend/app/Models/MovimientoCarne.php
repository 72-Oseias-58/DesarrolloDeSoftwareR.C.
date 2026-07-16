<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoCarne extends Model
{
    protected $table = 'movimientos_carne';

    protected $primaryKey = 'id_movimiento_carne';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'id_control_carne',
        'id_sucursal',
        'id_jornada',
        'id_tipo_carne',
        'id_user_crea',
        'tipo_movimiento',
        'motivo',
        'unidad_registrada',
        'cantidad_registrada',
        'cantidad_base',
        'unidad_base',
        'cantidad_anterior',
        'cantidad_nueva',
        'referencia_tipo',
        'referencia_id',
        'origen',
        'destino',
        'observacion',
    ];

    protected $casts = [
        'cantidad_registrada' => 'decimal:2',
        'cantidad_base' => 'decimal:2',
        'cantidad_anterior' => 'decimal:2',
        'cantidad_nueva' => 'decimal:2',
        'referencia_id' => 'integer',
    ];

    public function controlCarne(): BelongsTo
    {
        return $this->belongsTo(
            ControlCarneJornada::class,
            'id_control_carne',
            'id_control_carne'
        );
    }

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

    public function tipoCarne(): BelongsTo
    {
        return $this->belongsTo(
            TipoCarne::class,
            'id_tipo_carne',
            'id_tipo_carne'
        );
    }

    public function usuarioCreador(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'id_user_crea',
            'id'
        );
    }

    public function pedido(): BelongsTo
    {
        return $this->belongsTo(
            Pedido::class,
            'referencia_id',
            'id_pedido'
        )->where('referencia_tipo', 'PEDIDO');
    }
}