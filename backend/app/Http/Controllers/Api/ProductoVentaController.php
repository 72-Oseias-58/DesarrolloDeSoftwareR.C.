<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Insumo;
use App\Models\Inventario;
use App\Models\MovimientoInventario;
use App\Models\ProductoVenta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProductoVentaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        $idSucursal = $empleado?->id_sucursal;

        $productos = ProductoVenta::query()
            ->select([
                'id_producto',
                'nombre',
                'descripcion',
                'precio',
                'tipo_producto',
                'prioridad_stock',
                'consume_carne',
                'consumos_carne',
                'imagen',
                'id_categoria_producto',
                'id_insumo',
            ])
            ->with([
                'guarniciones:id_guarnicion,nombre',
                'insumo:id_insumo,nombre,unidad_medida,prioridad_stock',
            ])
            ->orderBy('id_categoria_producto')
            ->orderBy('tipo_producto')
            ->orderBy('nombre')
            ->get()
            ->map(function ($producto) use ($idSucursal) {
                $stockActual = null;

                $usaInventario =
                    strtoupper((string) $producto->prioridad_stock) === 'INVENTARIO';

                if (
                    $usaInventario &&
                    $producto->id_insumo &&
                    $idSucursal
                ) {
                    $stockActual = DB::table('inventarios')
                        ->where('id_sucursal', $idSucursal)
                        ->where('id_insumo', $producto->id_insumo)
                        ->value('stock_actual');
                }

                return [
                    'id_producto' => $producto->id_producto,
                    'nombre' => $producto->nombre,
                    'descripcion' => $producto->descripcion,
                    'precio' => $producto->precio,
                    'tipo_producto' => $producto->tipo_producto,
                    'prioridad_stock' => $producto->prioridad_stock,
                    'consume_carne' => (bool) $producto->consume_carne,
                    'consumos_carne' => $producto->consumos_carne,
                    'imagen' => $producto->imagen,
                    'imagen_url' => $producto->imagen_url,
                    'id_categoria_producto' => $producto->id_categoria_producto,
                    'id_insumo' => $producto->id_insumo,
                    'stock_actual' => $stockActual,
                    'agotado' => $usaInventario
                        ? (float) ($stockActual ?? 0) <= 0
                        : false,
                    'insumo' => $producto->insumo,
                    'guarniciones' => $producto->guarniciones,
                ];
            });

        return response()->json([
            'productos' => $productos,
        ]);
    }

    public function opciones(): JsonResponse
    {
        $categorias = DB::table('categorias_productos')
            ->select([
                'id_categoria_producto',
                'nombre',
            ])
            ->orderBy('nombre')
            ->get();

        $guarniciones = DB::table('guarniciones')
            ->select([
                'id_guarnicion',
                'nombre',
            ])
            ->orderBy('nombre')
            ->get();

        $insumos = DB::table('insumos')
            ->select([
                'id_insumo',
                'nombre',
                'unidad_medida',
                'prioridad_stock',
            ])
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'categorias' => $categorias,
            'guarniciones' => $guarniciones,
            'insumos' => $insumos,
            'tipos_producto' => [
                'PLATO',
                'BEBIDA',
                'OTRO',
            ],
            'prioridades_stock' => [
                'SIN_STOCK',
                'INVENTARIO',
                'PRODUCCION_DIARIA',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:productos_venta,nombre',
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0.01',
                'decimal:0,2',
            ],
            'tipo_producto' => [
                'required',
                'string',
                Rule::in(['PLATO', 'BEBIDA', 'OTRO']),
            ],
            'prioridad_stock' => [
                'nullable',
                'string',
                Rule::in(['SIN_STOCK', 'INVENTARIO', 'PRODUCCION_DIARIA']),
            ],
            'consume_carne' => [
                'nullable',
                'boolean',
            ],
            'consumos_carne' => [
                'nullable',
                'array',
            ],
            'consumos_carne.*' => [
                'numeric',
                'min:0.01',
            ],
            'id_categoria_producto' => [
                'required',
                'integer',
                'exists:categorias_productos,id_categoria_producto',
            ],
            'id_insumo' => [
                'nullable',
                'integer',
                'exists:insumos,id_insumo',
            ],
            'guarniciones' => [
                'nullable',
                'array',
            ],
            'guarniciones.*' => [
                'integer',
                'exists:guarniciones,id_guarnicion',
            ],
            'unidad_medida' => [
                'nullable',
                'string',
                'max:50',
            ],
            'stock_inicial' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'imagen' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
        ]);

        return DB::transaction(function () use ($request, $datos) {
            $datosNormalizados = $this->normalizarDatosProducto($datos);

            if ($request->hasFile('imagen')) {
                $datosNormalizados['imagen'] = $request
                    ->file('imagen')
                    ->store('productos', 'public');
            }

            if ($datosNormalizados['tipo_producto'] === 'BEBIDA') {
                $idInsumo = $this->crearInsumoBebidaConInventario(
                    $request,
                    $datosNormalizados['nombre'],
                    $datos['unidad_medida'] ?? 'UNIDAD',
                    (float) ($datos['stock_inicial'] ?? 0)
                );

                $datosNormalizados['id_insumo'] = $idInsumo;
            }

            $producto = ProductoVenta::create([
                'nombre' => $datosNormalizados['nombre'],
                'descripcion' => $datosNormalizados['descripcion'],
                'precio' => $datosNormalizados['precio'],
                'tipo_producto' => $datosNormalizados['tipo_producto'],
                'prioridad_stock' => $datosNormalizados['prioridad_stock'],
                'consume_carne' => $datosNormalizados['consume_carne'],
                'consumos_carne' => $datosNormalizados['consumos_carne'],
                'imagen' => $datosNormalizados['imagen'] ?? null,
                'id_categoria_producto' => $datosNormalizados['id_categoria_producto'],
                'id_insumo' => $datosNormalizados['id_insumo'],
            ]);

            $producto->guarniciones()->sync($datosNormalizados['guarniciones']);

            return response()->json([
                'message' => 'Producto creado correctamente.',
                'producto' => $producto->load([
                    'guarniciones',
                    'insumo',
                ]),
            ], 201);
        });
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $producto = ProductoVenta::query()
            ->where('id_producto', $id)
            ->firstOrFail();

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('productos_venta', 'nombre')
                    ->ignore($producto->id_producto, 'id_producto'),
            ],
            'descripcion' => [
                'nullable',
                'string',
                'max:255',
            ],
            'precio' => [
                'required',
                'numeric',
                'min:0.01',
                'decimal:0,2',
            ],
            'tipo_producto' => [
                'required',
                'string',
                Rule::in(['PLATO', 'BEBIDA', 'OTRO']),
            ],
            'prioridad_stock' => [
                'nullable',
                'string',
                Rule::in(['SIN_STOCK', 'INVENTARIO', 'PRODUCCION_DIARIA']),
            ],
            'consume_carne' => [
                'nullable',
                'boolean',
            ],
            'consumos_carne' => [
                'nullable',
                'array',
            ],
            'consumos_carne.*' => [
                'numeric',
                'min:0.01',
            ],
            'id_categoria_producto' => [
                'required',
                'integer',
                'exists:categorias_productos,id_categoria_producto',
            ],
            'id_insumo' => [
                'nullable',
                'integer',
                'exists:insumos,id_insumo',
            ],
            'guarniciones' => [
                'nullable',
                'array',
            ],
            'guarniciones.*' => [
                'integer',
                'exists:guarniciones,id_guarnicion',
            ],
            'unidad_medida' => [
                'nullable',
                'string',
                'max:50',
            ],
            'stock_inicial' => [
                'nullable',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],
            'imagen' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'eliminar_imagen' => [
                'nullable',
                'boolean',
            ],
        ]);

        return DB::transaction(function () use ($request, $producto, $datos) {
            $datosNormalizados = $this->normalizarDatosProducto($datos);

            $imagen = $producto->imagen;

            if (!empty($datos['eliminar_imagen']) && $imagen) {
                Storage::disk('public')->delete($imagen);
                $imagen = null;
            }

            if ($request->hasFile('imagen')) {
                if ($imagen) {
                    Storage::disk('public')->delete($imagen);
                }

                $imagen = $request
                    ->file('imagen')
                    ->store('productos', 'public');
            }

            if ($datosNormalizados['tipo_producto'] === 'BEBIDA') {
                $idInsumo = $producto->id_insumo;

                if (!$idInsumo) {
                    $idInsumo = $this->crearInsumoBebidaConInventario(
                        $request,
                        $datosNormalizados['nombre'],
                        $datos['unidad_medida'] ?? 'UNIDAD',
                        0
                    );
                } else {
                    Insumo::query()
                        ->where('id_insumo', $idInsumo)
                        ->update([
                            'nombre' => $datosNormalizados['nombre'],
                            'unidad_medida' => trim($datos['unidad_medida'] ?? 'UNIDAD') ?: 'UNIDAD',
                            'prioridad_stock' => 'INVENTARIO',
                        ]);
                }

                $datosNormalizados['id_insumo'] = $idInsumo;
            }

            $producto->update([
                'nombre' => $datosNormalizados['nombre'],
                'descripcion' => $datosNormalizados['descripcion'],
                'precio' => $datosNormalizados['precio'],
                'tipo_producto' => $datosNormalizados['tipo_producto'],
                'prioridad_stock' => $datosNormalizados['prioridad_stock'],
                'consume_carne' => $datosNormalizados['consume_carne'],
                'consumos_carne' => $datosNormalizados['consumos_carne'],
                'imagen' => $imagen,
                'id_categoria_producto' => $datosNormalizados['id_categoria_producto'],
                'id_insumo' => $datosNormalizados['id_insumo'],
            ]);

            $producto->guarniciones()->sync($datosNormalizados['guarniciones']);

            return response()->json([
                'message' => 'Producto actualizado correctamente.',
                'producto' => $producto->fresh([
                    'guarniciones',
                    'insumo',
                ]),
            ]);
        });
    }

    public function destroy(int $id): JsonResponse
    {
        $producto = ProductoVenta::query()
            ->where('id_producto', $id)
            ->firstOrFail();

        if ($producto->detallesPedidos()->exists()) {
            throw ValidationException::withMessages([
                'producto' => [
                    'Este producto ya tiene ventas registradas y no puede eliminarse porque forma parte del historial.',
                ],
            ]);
        }

        if ($producto->imagen) {
            Storage::disk('public')->delete($producto->imagen);
        }

        $producto->guarniciones()->sync([]);
        $producto->delete();

        return response()->json([
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    private function normalizarDatosProducto(array $datos): array
    {
        $tipoProducto = strtoupper((string) $datos['tipo_producto']);

        $prioridadStock = strtoupper((string) (
            $datos['prioridad_stock']
            ?? $this->resolverPrioridadStockPorDefecto($tipoProducto, $datos)
        ));

        $guarniciones = $datos['guarniciones'] ?? [];

        if (!is_array($guarniciones)) {
            $guarniciones = [];
        }

        $guarniciones = array_values(
            array_unique($guarniciones)
        );

        $idInsumo = null;
        $consumeCarne = false;
        $consumosCarne = null;

        if ($tipoProducto === 'BEBIDA') {
            $prioridadStock = 'INVENTARIO';
            $guarniciones = [];
            $consumeCarne = false;
            $consumosCarne = null;
        }

        if ($tipoProducto === 'PLATO' && $prioridadStock === 'SIN_STOCK') {
            $guarniciones = [];
            $idInsumo = null;
            $consumeCarne = false;
            $consumosCarne = null;
        }

        if ($tipoProducto === 'PLATO' && $prioridadStock === 'PRODUCCION_DIARIA') {
            if (count($guarniciones) < 1) {
                throw ValidationException::withMessages([
                    'guarniciones' => [
                        'Un plato con producción diaria debe tener mínimo 1 guarnición disponible.',
                    ],
                ]);
            }

            $consumeCarne = true;

            $consumosCarne = $this->normalizarConsumosCarne(
                $datos['consumos_carne'] ?? null
            );

            if (empty($consumosCarne)) {
                throw ValidationException::withMessages([
                    'consumos_carne' => [
                        'Un plato con producción diaria debe indicar qué carne consume.',
                    ],
                ]);
            }

            $idInsumo = null;
        }

        if ($prioridadStock === 'INVENTARIO' && $tipoProducto !== 'BEBIDA') {
            if (empty($datos['id_insumo'])) {
                throw ValidationException::withMessages([
                    'id_insumo' => [
                        'Un producto con inventario debe estar vinculado a un insumo.',
                    ],
                ]);
            }

            $idInsumo = (int) $datos['id_insumo'];
            $consumeCarne = false;
            $consumosCarne = null;

            if ($tipoProducto !== 'PLATO') {
                $guarniciones = [];
            }
        }

        if ($tipoProducto !== 'PLATO') {
            $guarniciones = [];
        }

        return [
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'precio' => number_format((float) $datos['precio'], 2, '.', ''),
            'tipo_producto' => $tipoProducto,
            'prioridad_stock' => $prioridadStock,
            'consume_carne' => $consumeCarne,
            'consumos_carne' => $consumosCarne,
            'id_categoria_producto' => $datos['id_categoria_producto'],
            'id_insumo' => $idInsumo,
            'guarniciones' => $guarniciones,
        ];
    }

    private function resolverPrioridadStockPorDefecto(
        string $tipoProducto,
        array $datos
    ): string {
        if ($tipoProducto === 'BEBIDA') {
            return 'INVENTARIO';
        }

        if (!empty($datos['consume_carne']) || !empty($datos['consumos_carne'])) {
            return 'PRODUCCION_DIARIA';
        }

        return 'SIN_STOCK';
    }

    private function normalizarConsumosCarne(?array $consumosCarne): ?array
    {
        if (empty($consumosCarne)) {
            return null;
        }

        $normalizados = [];

        foreach ($consumosCarne as $tipoCarne => $cantidad) {
            $tipoCarne = strtoupper(trim((string) $tipoCarne));

            if ($tipoCarne === '') {
                continue;
            }

            $cantidadNormalizada = (float) $cantidad;

            if ($cantidadNormalizada <= 0) {
                continue;
            }

            $normalizados[$tipoCarne] = $cantidadNormalizada;
        }

        return empty($normalizados) ? null : $normalizados;
    }

    private function crearInsumoBebidaConInventario(
        Request $request,
        string $nombreProducto,
        string $unidadMedida,
        float $stockInicial
    ): int {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'usuario' => [
                    'El usuario no está asociado a un empleado.',
                ],
            ]);
        }

        $idCategoriaBebidas = DB::table('categorias_insumos')
            ->whereRaw('UPPER(nombre) = ?', ['BEBIDAS'])
            ->value('id_categoria_insumo');

        if (!$idCategoriaBebidas) {
            throw ValidationException::withMessages([
                'categoria_insumo' => [
                    'No existe la categoría de insumos Bebidas.',
                ],
            ]);
        }

        $insumo = Insumo::create([
            'nombre' => $nombreProducto,
            'unidad_medida' => trim($unidadMedida) ?: 'UNIDAD',
            'prioridad_stock' => 'INVENTARIO',
            'id_categoria_insumo' => $idCategoriaBebidas,
        ]);

        Inventario::create([
            'id_sucursal' => $empleado->id_sucursal,
            'id_insumo' => $insumo->id_insumo,
            'id_user_crea' => $usuario->id,
            'stock_actual' => $stockInicial,
        ]);

        if ($stockInicial > 0) {
            MovimientoInventario::create([
                'id_sucursal' => $empleado->id_sucursal,
                'id_jornada' => null,
                'id_insumo' => $insumo->id_insumo,
                'id_user_crea' => $usuario->id,
                'tipo_movimiento' => 'ENTRADA',
                'motivo' => 'COMPRA',
                'cantidad' => $stockInicial,
                'stock_anterior' => 0,
                'stock_nuevo' => $stockInicial,
                'referencia_tipo' => 'PRODUCTO_VENTA',
                'referencia_id' => null,
                'observacion' => 'Stock inicial registrado desde catálogo de bebidas.',
            ]);
        }

        return $insumo->id_insumo;
    }
}   