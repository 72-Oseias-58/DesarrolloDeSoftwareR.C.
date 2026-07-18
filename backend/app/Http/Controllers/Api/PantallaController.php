<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AreaPreparacion;
use App\Models\Empleado;
use App\Models\Pantalla;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PantallaController extends Controller
{
    /**
     * Devuelve las áreas disponibles y las pantallas
     * configuradas en la sucursal del ADMIN.
     */
    public function index(Request $request): JsonResponse
    {
        $empleado = $this->obtenerEmpleadoActivo($request);

        $pantallas = Pantalla::query()
            ->with([
                'areas:id_area,nombre_area',
            ])
            ->where('id_sucursal', $empleado->id_sucursal)
            ->orderBy('id_pantalla')
            ->get();

        $areas = AreaPreparacion::query()
            ->select([
                'id_area',
                'nombre_area',
            ])
            ->orderBy('id_area')
            ->get();

        return response()->json([
            'message' => 'Configuración de pantallas obtenida correctamente.',
            'pantallas' => $pantallas,
            'areas' => $areas,
        ]);
    }

    /**
     * Devuelve solamente el catálogo de áreas.
     */
    public function opciones(Request $request): JsonResponse
    {
        $this->obtenerEmpleadoActivo($request);

        $areas = AreaPreparacion::query()
            ->select([
                'id_area',
                'nombre_area',
            ])
            ->orderBy('id_area')
            ->get();

        return response()->json([
            'areas' => $areas,
        ]);
    }

    /**
     * Registra una pantalla para la sucursal del ADMIN.
     */
    public function store(Request $request): JsonResponse
    {
        $empleado = $this->obtenerEmpleadoActivo($request);

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pantallas', 'nombre')
                    ->where(function ($query) use ($empleado) {
                        return $query->where(
                            'id_sucursal',
                            $empleado->id_sucursal
                        );
                    }),
            ],

            'permite_finalizar' => [
                'required',
                'boolean',
            ],

            'areas' => [
                'required',
                'array',
                'min:1',
            ],

            'areas.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('areas_preparacion', 'id_area'),
            ],
        ], [
            'nombre.required' =>
                'Debe escribir el nombre de la pantalla.',

            'nombre.unique' =>
                'Ya existe una pantalla con ese nombre en la sucursal.',

            'areas.required' =>
                'Debe seleccionar al menos un área.',

            'areas.min' =>
                'Debe seleccionar al menos un área.',

            'areas.*.distinct' =>
                'No puede repetir la misma área.',
        ]);

        $pantalla = DB::transaction(function () use (
            $datos,
            $empleado
        ) {
            /*
             * Solo una pantalla de la sucursal tendrá el botón
             * "Finalizar pedido".
             *
             * Si el ADMIN marca una nueva pantalla como táctil,
             * se desactiva la anterior automáticamente.
             */
            if ((bool) $datos['permite_finalizar']) {
                Pantalla::query()
                    ->where(
                        'id_sucursal',
                        $empleado->id_sucursal
                    )
                    ->update([
                        'permite_finalizar' => false,
                    ]);
            }

            $pantalla = Pantalla::create([
                'id_sucursal' => $empleado->id_sucursal,
                'nombre' => trim($datos['nombre']),
                'permite_finalizar' =>
                    (bool) $datos['permite_finalizar'],
            ]);

            $pantalla->areas()->sync(
                collect($datos['areas'])
                    ->map(fn ($idArea) => (int) $idArea)
                    ->unique()
                    ->values()
                    ->all()
            );

            return $pantalla->load([
                'areas:id_area,nombre_area',
            ]);
        });

        return response()->json([
            'message' => 'Pantalla registrada correctamente.',
            'pantalla' => $pantalla,
        ], 201);
    }

    /**
     * Actualiza el nombre, las áreas y la capacidad
     * de finalizar pedidos.
     */
    public function update(
        Request $request,
        int $idPantalla
    ): JsonResponse {
        $empleado = $this->obtenerEmpleadoActivo($request);

        $pantalla = Pantalla::query()
            ->where('id_pantalla', $idPantalla)
            ->where('id_sucursal', $empleado->id_sucursal)
            ->first();

        if (!$pantalla) {
            throw ValidationException::withMessages([
                'pantalla' => [
                    'La pantalla no existe o no pertenece a su sucursal.',
                ],
            ]);
        }

        $datos = $request->validate([
            'nombre' => [
                'required',
                'string',
                'max:100',
                Rule::unique('pantallas', 'nombre')
                    ->where(function ($query) use ($empleado) {
                        return $query->where(
                            'id_sucursal',
                            $empleado->id_sucursal
                        );
                    })
                    ->ignore(
                        $pantalla->id_pantalla,
                        'id_pantalla'
                    ),
            ],

            'permite_finalizar' => [
                'required',
                'boolean',
            ],

            'areas' => [
                'required',
                'array',
                'min:1',
            ],

            'areas.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('areas_preparacion', 'id_area'),
            ],
        ]);

        $pantalla = DB::transaction(function () use (
            $datos,
            $empleado,
            $pantalla
        ) {
            if ((bool) $datos['permite_finalizar']) {
                Pantalla::query()
                    ->where(
                        'id_sucursal',
                        $empleado->id_sucursal
                    )
                    ->where(
                        'id_pantalla',
                        '!=',
                        $pantalla->id_pantalla
                    )
                    ->update([
                        'permite_finalizar' => false,
                    ]);
            }

            $pantalla->update([
                'nombre' => trim($datos['nombre']),
                'permite_finalizar' =>
                    (bool) $datos['permite_finalizar'],
            ]);

            $pantalla->areas()->sync(
                collect($datos['areas'])
                    ->map(fn ($idArea) => (int) $idArea)
                    ->unique()
                    ->values()
                    ->all()
            );

            return $pantalla->fresh()->load([
                'areas:id_area,nombre_area',
            ]);
        });

        return response()->json([
            'message' => 'Pantalla actualizada correctamente.',
            'pantalla' => $pantalla,
        ]);
    }

    /**
     * Elimina una pantalla y sus asignaciones de áreas.
     */
    public function destroy(
        Request $request,
        int $idPantalla
    ): JsonResponse {
        $empleado = $this->obtenerEmpleadoActivo($request);

        $pantalla = Pantalla::query()
            ->where('id_pantalla', $idPantalla)
            ->where('id_sucursal', $empleado->id_sucursal)
            ->first();

        if (!$pantalla) {
            throw ValidationException::withMessages([
                'pantalla' => [
                    'La pantalla no existe o no pertenece a su sucursal.',
                ],
            ]);
        }

        DB::transaction(function () use ($pantalla) {
            $pantalla->areas()->detach();
            $pantalla->delete();
        });

        return response()->json([
            'message' => 'Pantalla eliminada correctamente.',
        ]);
    }

    private function obtenerEmpleadoActivo(
        Request $request
    ): Empleado {
        $usuario = $request->user('api');

        $empleado = Empleado::query()
            ->where('id_user', $usuario->id)
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'empleado' => [
                    'El usuario no está asociado a un empleado.',
                ],
            ]);
        }

        if (
            strtoupper((string) $empleado->estado)
            !== 'ACTIVO'
        ) {
            throw ValidationException::withMessages([
                'empleado' => [
                    'El empleado se encuentra inactivo.',
                ],
            ]);
        }

        if (!$empleado->id_sucursal) {
            throw ValidationException::withMessages([
                'sucursal' => [
                    'El administrador no tiene una sucursal asignada.',
                ],
            ]);
        }

        return $empleado;
    }
}