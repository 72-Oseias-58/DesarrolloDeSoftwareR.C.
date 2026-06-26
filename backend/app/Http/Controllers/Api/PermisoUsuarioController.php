<?php

namespace App\Http\Controllers\Api;

use App\Events\PermissionChanged;
use App\Http\Controllers\Controller;
use App\Models\Permiso;
use App\Models\PermisoUser;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PermisoUsuarioController extends Controller
{
    /**
     * Mostrar los permisos de un usuario.
     */
    public function index($id)
    {
        $usuarioObjetivo = User::with([
            'rol.permisos',
            'empleado.sucursal',
            'permisosPersonalizados.permiso',
        ])->find($id);

        if (!$usuarioObjetivo) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $authUser = Auth::guard('api')->user();

        if (!$this->puedeGestionarUsuario($authUser, $usuarioObjetivo)) {
            return response()->json([
                'message' => 'No tienes permiso para gestionar este usuario.',
            ], 403);
        }

        $permisos = Permiso::orderBy('slug')->get();

        return response()->json([
            'usuario' => [
                'id' => $usuarioObjetivo->id,
                'name' => $usuarioObjetivo->name,
                'usuario' => $usuarioObjetivo->usuario,
                'email' => $usuarioObjetivo->email,

                'role' => [
                    'id_rol' => $usuarioObjetivo->rol?->id_rol,
                    'nombre_rol' => $usuarioObjetivo->rol?->nombre,
                ],

                'empleado' => $usuarioObjetivo->empleado ? [
                    'id_empleado' => $usuarioObjetivo->empleado->id_empleado,
                    'nombre' => $usuarioObjetivo->empleado->nombre,
                    'cargo' => $usuarioObjetivo->empleado->cargo,
                    'estado' => $usuarioObjetivo->empleado->estado,
                    'id_sucursal' => $usuarioObjetivo->empleado->id_sucursal,
                    'sucursal' => $usuarioObjetivo->empleado->sucursal?->nombre,
                ] : null,
            ],

            'permisos_base_rol' => $usuarioObjetivo->rol
                ? $usuarioObjetivo->rol->permisos->pluck('slug')->values()
                : [],

            'permisos_personalizados' => $usuarioObjetivo
                ->permisosPersonalizados
                ->map(function ($item) {
                    return [
                        'slug' => $item->permiso?->slug,
                        'tipo' => $item->tipo,
                    ];
                })
                ->filter(function ($item) {
                    return !empty($item['slug']);
                })
                ->values(),

            'permisos_finales' => $usuarioObjetivo->permisosFinales(),

            'catalogo_permisos' => $permisos,
        ]);
    }

    /**
     * Actualizar permisos personalizados.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'agregados' => 'sometimes|array',
            'agregados.*' => 'string|exists:permisos,slug',

            'quitados' => 'sometimes|array',
            'quitados.*' => 'string|exists:permisos,slug',
        ]);

        $usuarioObjetivo = User::with([
            'rol',
            'empleado.sucursal',
        ])->find($id);

        if (!$usuarioObjetivo) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        $authUser = Auth::guard('api')->user();

        if (!$this->puedeGestionarUsuario($authUser, $usuarioObjetivo)) {
            return response()->json([
                'message' => 'No tienes permiso para modificar permisos de este usuario.',
            ], 403);
        }

        $agregados = array_values(array_unique(
            $request->input('agregados', [])
        ));

        $quitados = array_values(array_unique(
            $request->input('quitados', [])
        ));

        /*
         * Un permiso no puede estar agregado y quitado al mismo tiempo.
         */
        $permisosRepetidos = array_intersect($agregados, $quitados);

        if (!empty($permisosRepetidos)) {
            return response()->json([
                'message' => 'Un permiso no puede agregarse y quitarse al mismo tiempo.',
                'permisos_repetidos' => array_values($permisosRepetidos),
            ], 422);
        }

        DB::transaction(function () use (
            $usuarioObjetivo,
            $agregados,
            $quitados
        ) {
            PermisoUser::where(
                'id_user',
                $usuarioObjetivo->id
            )->delete();

            $permisosSolicitados = Permiso::whereIn(
                'slug',
                array_merge($agregados, $quitados)
            )->get()->keyBy('slug');

            foreach ($agregados as $slug) {
                $permiso = $permisosSolicitados->get($slug);

                if (!$permiso) {
                    continue;
                }

                PermisoUser::create([
                    'id_user' => $usuarioObjetivo->id,
                    'id_permiso' => $permiso->id_permiso,
                    'tipo' => 'AGREGADO',
                ]);
            }

            foreach ($quitados as $slug) {
                $permiso = $permisosSolicitados->get($slug);

                if (!$permiso) {
                    continue;
                }

                PermisoUser::create([
                    'id_user' => $usuarioObjetivo->id,
                    'id_permiso' => $permiso->id_permiso,
                    'tipo' => 'QUITADO',
                ]);
            }
        });

        $usuarioObjetivo->load([
            'rol.permisos',
            'empleado.sucursal',
            'permisosPersonalizados.permiso',
        ]);

        broadcast(new PermissionChanged($usuarioObjetivo));

        return response()->json([
            'message' => 'Permisos personalizados actualizados correctamente.',
            'permisos_finales' => $usuarioObjetivo->permisosFinales(),
        ]);
    }

    /**
     * Cambiar un usuario entre ADMIN y CAJERO.
     *
     * Esta operación solamente puede realizarla el SUPERADMIN.
     */
    public function cambiarRol(Request $request, $id)
    {
        $request->validate([
            'rol' => 'required|string|in:ADMIN,CAJERO',
        ]);

        $nuevoNombreRol = strtoupper(trim($request->rol));

        $usuarioObjetivo = User::with([
            'rol',
            'empleado.sucursal',
        ])->find($id);

        if (!$usuarioObjetivo) {
            return response()->json([
                'message' => 'Usuario no encontrado.',
            ], 404);
        }

        if (!$usuarioObjetivo->empleado) {
            return response()->json([
                'message' => 'El usuario no tiene un registro de empleado asociado.',
            ], 422);
        }

        $authUser = Auth::guard('api')->user();

        if (!$this->puedeCambiarRol(
            $authUser,
            $usuarioObjetivo,
            $nuevoNombreRol
        )) {
            return response()->json([
                'message' => 'No tienes permiso para realizar este cambio de rol.',
            ], 403);
        }

        $rolActual = strtoupper(
            trim($usuarioObjetivo->rol?->nombre ?? '')
        );

        if ($rolActual === $nuevoNombreRol) {
            return response()->json([
                'message' => "El usuario ya tiene el rol {$nuevoNombreRol}.",
            ], 422);
        }

        $nuevoRol = Role::where(
            'nombre',
            $nuevoNombreRol
        )->first();

        if (!$nuevoRol) {
            return response()->json([
                'message' => "No se encontró el rol {$nuevoNombreRol}.",
            ], 404);
        }

        DB::transaction(function () use (
            $usuarioObjetivo,
            $nuevoRol,
            $nuevoNombreRol
        ) {
            /*
             * Cambiar rol en users.
             */
            $usuarioObjetivo->update([
                'id_rol' => $nuevoRol->id_rol,
            ]);

            /*
             * Sincronizar cargo del empleado con el rol.
             */
            $nuevoCargo = $nuevoNombreRol === 'ADMIN'
                ? 'ADMIN'
                : 'CAJERO/A';

            $usuarioObjetivo->empleado->update([
                'cargo' => $nuevoCargo,
            ]);

            /*
             * Los permisos personalizados del rol anterior ya no sirven.
             * El usuario recibirá los permisos base del nuevo rol.
             */
            PermisoUser::where(
                'id_user',
                $usuarioObjetivo->id
            )->delete();
        });

        $usuarioObjetivo->load([
            'rol.permisos',
            'empleado.sucursal',
            'permisosPersonalizados.permiso',
        ]);

        /*
         * Notificar al usuario afectado en tiempo real.
         */
        broadcast(new PermissionChanged($usuarioObjetivo));

        return response()->json([
            'message' => $nuevoNombreRol === 'ADMIN'
                ? 'Cajero ascendido a administrador correctamente.'
                : 'Administrador degradado a cajero correctamente.',

            'usuario' => [
                'id' => $usuarioObjetivo->id,
                'name' => $usuarioObjetivo->name,
                'usuario' => $usuarioObjetivo->usuario,

                'rol' => $usuarioObjetivo->rol?->nombre,

                'empleado' => [
                    'id_empleado' => $usuarioObjetivo->empleado?->id_empleado,
                    'cargo' => $usuarioObjetivo->empleado?->cargo,
                    'id_sucursal' => $usuarioObjetivo->empleado?->id_sucursal,
                    'sucursal' => $usuarioObjetivo
                        ->empleado
                        ?->sucursal
                        ?->nombre,
                ],
            ],

            'nuevo_rol' => $nuevoNombreRol,

            'permisos_finales' => $usuarioObjetivo->permisosFinales(),
        ]);
    }

    /**
     * Determinar si el usuario autenticado puede administrar
     * los permisos del usuario objetivo.
     */
    private function puedeGestionarUsuario(
        $authUser,
        User $usuarioObjetivo
    ): bool {
        if (!$authUser) {
            return false;
        }

        if ((int) $authUser->id === (int) $usuarioObjetivo->id) {
            return false;
        }

        $usuarioAutenticado = User::with([
            'rol',
            'empleado',
        ])->find($authUser->id);

        if (!$usuarioAutenticado) {
            return false;
        }

        $usuarioObjetivo->loadMissing([
            'rol',
            'empleado',
        ]);

        $rolAuth = strtoupper(
            trim($usuarioAutenticado->rol?->nombre ?? '')
        );

        $rolObjetivo = strtoupper(
            trim($usuarioObjetivo->rol?->nombre ?? '')
        );

        /*
         * SUPERADMIN administra permisos de ADMIN.
         */
        if ($rolAuth === 'SUPERADMIN') {
            return $rolObjetivo === 'ADMIN'
                && $usuarioAutenticado
                    ->tienePermiso('gestionar_permisos_admin');
        }

        /*
         * ADMIN administra permisos de CAJEROS
         * únicamente dentro de su propia sucursal.
         */
        if ($rolAuth === 'ADMIN') {
            if ($rolObjetivo !== 'CAJERO') {
                return false;
            }

            if (
                !$usuarioAutenticado->empleado ||
                !$usuarioObjetivo->empleado
            ) {
                return false;
            }

            $mismaSucursal =
                (int) $usuarioAutenticado->empleado->id_sucursal ===
                (int) $usuarioObjetivo->empleado->id_sucursal;

            return $mismaSucursal
                && $usuarioAutenticado
                    ->tienePermiso('gestionar_permisos_cajero');
        }

        return false;
    }

    /**
     * Determinar si puede ascender o degradar a un usuario.
     *
     * Solo SUPERADMIN puede:
     * - ADMIN → CAJERO
     * - CAJERO → ADMIN
     */
    private function puedeCambiarRol(
        $authUser,
        User $usuarioObjetivo,
        string $nuevoRol
    ): bool {
        if (!$authUser) {
            return false;
        }

        if ((int) $authUser->id === (int) $usuarioObjetivo->id) {
            return false;
        }

        $usuarioAutenticado = User::with('rol')
            ->find($authUser->id);

        if (!$usuarioAutenticado) {
            return false;
        }

        $usuarioObjetivo->loadMissing('rol');

        $rolAuth = strtoupper(
            trim($usuarioAutenticado->rol?->nombre ?? '')
        );

        $rolObjetivo = strtoupper(
            trim($usuarioObjetivo->rol?->nombre ?? '')
        );

        $nuevoRol = strtoupper(trim($nuevoRol));

        if ($rolAuth !== 'SUPERADMIN') {
            return false;
        }

        /*
         * SUPERADMIN no puede modificar otro SUPERADMIN.
         */
        if ($rolObjetivo === 'SUPERADMIN') {
            return false;
        }

        /*
         * Degradar ADMIN a CAJERO.
         */
        if (
            $rolObjetivo === 'ADMIN' &&
            $nuevoRol === 'CAJERO'
        ) {
            return $usuarioAutenticado
                ->tienePermiso('degradar_admin_cajero');
        }

        /*
         * Ascender CAJERO a ADMIN.
         */
        if (
            $rolObjetivo === 'CAJERO' &&
            $nuevoRol === 'ADMIN'
        ) {
            return $usuarioAutenticado
                ->tienePermiso('ascender_cajero_admin');
        }

        return false;
    }
}