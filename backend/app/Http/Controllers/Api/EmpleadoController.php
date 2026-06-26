<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\PermisoUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class EmpleadoController extends Controller
{
    private function normalizarTexto(?string $texto): string
    {
        return strtoupper(trim($texto ?? ''));
    }

    private function esCargoCajero(?string $cargo): bool
    {
        return str_contains($this->normalizarTexto($cargo), 'CAJERO');
    }

    private function sucursalDelAdmin()
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser) {
            abort(401, 'Usuario no autenticado.');
        }

        $admin = User::with('empleado')->find($authUser->id);

        if (!$admin || !$admin->empleado) {
            abort(403, 'El administrador no tiene sucursal asignada.');
        }

        return $admin->empleado->id_sucursal;
    }

    public function index()
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $empleados = Empleado::with(['usuario.rol', 'sucursal'])
            ->where('id_sucursal', $idSucursalAdmin)
            ->where('cargo', '!=', 'ADMIN')
            ->orderBy('id_empleado', 'desc')
            ->get();

        return response()->json([
            'empleados' => $empleados,
        ]);
    }

    public function store(Request $request)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();
        $esCajero = $this->esCargoCajero($request->cargo);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',

            'usuario' => [
                $esCajero ? 'required' : 'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'usuario'),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                $esCajero ? 'required' : 'nullable',
                'string',
                'min:6',
            ],
        ]);

        DB::beginTransaction();

        try {
            $user = null;

            if ($esCajero) {
                $user = User::create([
                    'name' => $request->nombre,
                    'usuario' => $request->usuario,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'id_rol' => 3, // CAJERO
                ]);
            }

            $empleado = Empleado::create([
                'id_user' => $user?->id,
                'id_sucursal' => $idSucursalAdmin,
                'nombre' => $request->nombre,
                'cargo' => $this->normalizarTexto($request->cargo),
                'estado' => 'ACTIVO',
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
                'contacto_referencia' => $request->contacto_referencia,
                'telefono_referencia' => $request->telefono_referencia,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empleado creado correctamente',
                'empleado' => $empleado->load(['usuario.rol', 'sucursal']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear empleado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $empleado = Empleado::with(['usuario.rol', 'sucursal'])
            ->where('id_sucursal', $idSucursalAdmin)
            ->where('cargo', '!=', 'ADMIN')
            ->findOrFail($id);

        return response()->json([
            'empleado' => $empleado,
        ]);
    }

    public function update(Request $request, $id)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $empleado = Empleado::with('usuario')
            ->where('id_sucursal', $idSucursalAdmin)
            ->where('cargo', '!=', 'ADMIN')
            ->findOrFail($id);

        $esCajero = $this->esCargoCajero($request->cargo);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',

            'usuario' => [
                $esCajero ? 'required' : 'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'usuario')->ignore($empleado->id_user),
            ],
            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')->ignore($empleado->id_user),
            ],
            'password' => [
                $esCajero && !$empleado->id_user ? 'required' : 'nullable',
                'string',
                'min:6',
            ],
        ]);

        DB::beginTransaction();

        try {
            if ($esCajero) {
                if ($empleado->usuario) {
                    $empleado->usuario->update([
                        'name' => $request->nombre,
                        'usuario' => $request->usuario,
                        'email' => $request->email,
                        'id_rol' => 3, // CAJERO
                    ]);

                    if ($request->filled('password')) {
                        $empleado->usuario->update([
                            'password' => Hash::make($request->password),
                        ]);
                    }
                } else {
                    $user = User::create([
                        'name' => $request->nombre,
                        'usuario' => $request->usuario,
                        'email' => $request->email,
                        'password' => Hash::make($request->password),
                        'id_rol' => 3, // CAJERO
                    ]);

                    $empleado->id_user = $user->id;
                    $empleado->save();
                }
            }

            if (!$esCajero) {
                if ($empleado->usuario) {
                    PermisoUser::where('id_user', $empleado->usuario->id)->delete();

                    $usuarioAnterior = $empleado->usuario;

                    $empleado->id_user = null;
                    $empleado->save();

                    $usuarioAnterior->delete();
                }
            }

            $empleado->update([
                'nombre' => $request->nombre,
                'cargo' => $this->normalizarTexto($request->cargo),
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
                'contacto_referencia' => $request->contacto_referencia,
                'telefono_referencia' => $request->telefono_referencia,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empleado actualizado correctamente',
                'empleado' => $empleado->load(['usuario.rol', 'sucursal']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar empleado',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cambiarEstado($id)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $empleado = Empleado::where('id_sucursal', $idSucursalAdmin)
            ->where('cargo', '!=', 'ADMIN')
            ->findOrFail($id);

        $empleado->estado = $empleado->estado === 'ACTIVO'
            ? 'INACTIVO'
            : 'ACTIVO';

        $empleado->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado' => $empleado->estado,
        ]);
    }
}