<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class EmpleadoController extends Controller
{
    private function sucursalDelAdmin()
    {
        $admin = auth()->user()->load('empleado');

        if (!$admin->empleado) {
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
            'empleados' => $empleados
        ]);
    }

    public function store(Request $request)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',

            'usuario' => 'nullable|string|max:255|unique:users,usuario',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            $user = null;

            if ($request->cargo === 'CAJERO') {
                $request->validate([
                    'usuario' => 'required|string|max:255|unique:users,usuario',
                    'password' => 'required|string|min:6',
                ]);

                $user = User::create([
                    'name' => $request->nombre,
                    'usuario' => $request->usuario,
                    'email' => $request->email,
                    'password' => Hash::make($request->password),
                    'id_rol' => 3,
                ]);
            }

            $empleado = Empleado::create([
                'id_user' => $user?->id,
                'id_sucursal' => $idSucursalAdmin,
                'nombre' => $request->nombre,
                'cargo' => $request->cargo,
                'estado' => 'ACTIVO',
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
                'contacto_referencia' => $request->contacto_referencia,
                'telefono_referencia' => $request->telefono_referencia,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empleado creado correctamente',
                'empleado' => $empleado->load(['usuario.rol', 'sucursal'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear empleado',
                'error' => $e->getMessage()
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
            'empleado' => $empleado
        ]);
    }

    public function update(Request $request, $id)
    {
        $idSucursalAdmin = $this->sucursalDelAdmin();

        $empleado = Empleado::with('usuario')
            ->where('id_sucursal', $idSucursalAdmin)
            ->where('cargo', '!=', 'ADMIN')
            ->findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'cargo' => 'required|string|max:50',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',

            'usuario' => 'nullable|string|max:255|unique:users,usuario,' . $empleado->id_user,
            'email' => 'nullable|email|unique:users,email,' . $empleado->id_user,
            'password' => 'nullable|string|min:6',
        ]);

        DB::beginTransaction();

        try {
            if ($empleado->usuario) {
                $empleado->usuario->name = $request->nombre;
                $empleado->usuario->usuario = $request->usuario;
                $empleado->usuario->email = $request->email;

                if ($request->filled('password')) {
                    $empleado->usuario->password = Hash::make($request->password);
                }

                $empleado->usuario->save();
            }

            $empleado->update([
                'nombre' => $request->nombre,
                'cargo' => $request->cargo,
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
                'contacto_referencia' => $request->contacto_referencia,
                'telefono_referencia' => $request->telefono_referencia,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Empleado actualizado correctamente',
                'empleado' => $empleado->load(['usuario.rol', 'sucursal'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar empleado',
                'error' => $e->getMessage()
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
            'estado' => $empleado->estado
        ]);
    }
}
// cajero