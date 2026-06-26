<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ProductoVenta;
use Illuminate\Http\JsonResponse;

class ProductoVentaController extends Controller
{
    public function index(): JsonResponse
    {
        $productos = ProductoVenta::query()
            ->select([
                'id_producto',
                'nombre',
                'descripcion',
                'precio',
                'tipo_producto',
                'prioridad_stock',
                'id_categoria_producto',
            ])
            ->with([
                'guarniciones:id_guarnicion,nombre',
            ])
            ->orderBy('id_categoria_producto')
            ->orderBy('nombre')
            ->get()
            ->map(function (ProductoVenta $producto) {
                return [
                    'id_producto' => $producto->id_producto,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion,
                    'precio' => $producto->precio,
                    'tipo_producto' => $producto->tipo_producto,
                    'prioridad_stock' => $producto->prioridad_stock,
                    'id_categoria_producto' => $producto->id_categoria_producto,
                    'guarniciones' => $producto->guarniciones->map(
                        fn ($guarnicion) => [
                            'id_guarnicion' => $guarnicion->id_guarnicion,
                            'nombre' => $guarnicion->nombre,
                        ]
                    )->values(),
                ];
            });

        return response()->json([
            'productos' => $productos,
        ]);
    }
}