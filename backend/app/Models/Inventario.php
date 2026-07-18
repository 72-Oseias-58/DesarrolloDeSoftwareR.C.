<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $table = 'inventarios';

    protected $primaryKey = 'id_inventario';

    protected $fillable = [
        'id_sucursal',
        'id_insumo',
        'id_user_crea',
        'stock_actual',
        'stock_minimo',
    ];

    protected $casts = [
        'stock_actual' => 'decimal:2',
        'stock_minimo' => 'decimal:2',
    ];

    protected $appends = [
        'estado_stock',
        'cantidad_faltante',
    ];

    public function sucursal()
    {
        return $this->belongsTo(
            Sucursal::class,
            'id_sucursal',
            'id_sucursal'
        );
    }

    public function insumo()
    {
        return $this->belongsTo(
            Insumo::class,
            'id_insumo',
            'id_insumo'
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

    public function movimientos()
    {
        return $this->hasMany(
            MovimientoInventario::class,
            'id_insumo',
            'id_insumo'
        )->whereColumn(
            'movimientos_inventario.id_sucursal',
            'inventarios.id_sucursal'
        );
    }

    public function getEstadoStockAttribute(): string
    {
        $stockActual = (float) $this->stock_actual;
        $stockMinimo = (float) $this->stock_minimo;

        if ($stockActual <= 0) {
            return 'AGOTADO';
        }

        if ($stockActual <= $stockMinimo) {
            return 'STOCK_BAJO';
        }

        return 'NORMAL';
    }

    public function getCantidadFaltanteAttribute(): string
    {
        $cantidad = max(
            (float) $this->stock_minimo
            - (float) $this->stock_actual,
            0
        );

        return number_format(
            $cantidad,
            2,
            '.',
            ''
        );
    }
}