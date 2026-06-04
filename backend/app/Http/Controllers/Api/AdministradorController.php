<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdministradorController extends Controller
{
    public function index()
    {
        $administradores = User::with(['rol', 'empleado.sucursal'])
            ->where('id_rol', 2)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'administradores' => $administradores
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'usuario' => 'required|string|max:255|unique:users,usuario',
            'email' => 'nullable|email|unique:users,email',
            'password' => 'required|string|min:6',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',
        ]);

        DB::beginTransaction();

        try {
            $user = User::create([
                'name' => $request->name,
                'usuario' => $request->usuario,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'id_rol' => 2,
            ]);

            Empleado::create([
                'id_user' => $user->id,
                'id_sucursal' => $request->id_sucursal,
                'nombre' => $request->name,
                'cargo' => 'ADMIN',
                'estado' => 'ACTIVO',
                'fecha_nacimiento' => $request->fecha_nacimiento,
                'telefono' => $request->telefono,
                'contacto_referencia' => $request->contacto_referencia,
                'telefono_referencia' => $request->telefono_referencia,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Administrador creado correctamente',
                'administrador' => $user->load(['rol', 'empleado.sucursal'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $administrador = User::with(['rol', 'empleado.sucursal'])
            ->where('id_rol', 2)
            ->findOrFail($id);

        return response()->json([
            'administrador' => $administrador
        ]);
    }

    public function update(Request $request, $id)
    {
        $administrador = User::with('empleado')
            ->where('id_rol', 2)
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'usuario' => 'required|string|max:255|unique:users,usuario,' . $administrador->id,
            'email' => 'nullable|email|unique:users,email,' . $administrador->id,
            'password' => 'nullable|string|min:6',
            'id_sucursal' => 'required|exists:sucursales,id_sucursal',
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',
        ]);

        DB::beginTransaction();

        try {
            $administrador->name = $request->name;
            $administrador->usuario = $request->usuario;
            $administrador->email = $request->email;

            if ($request->filled('password')) {
                $administrador->password = Hash::make($request->password);
            }

            $administrador->save();

            Empleado::updateOrCreate(
                ['id_user' => $administrador->id],
                [
                    'id_sucursal' => $request->id_sucursal,
                    'nombre' => $request->name,
                    'cargo' => 'ADMIN',
                    'estado' => $administrador->empleado->estado ?? 'ACTIVO',
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'telefono' => $request->telefono,
                    'contacto_referencia' => $request->contacto_referencia,
                    'telefono_referencia' => $request->telefono_referencia,
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Administrador actualizado correctamente',
                'administrador' => $administrador->load(['rol', 'empleado.sucursal'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar administrador',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cambiarEstado($id)
    {
        $administrador = User::with('empleado')
            ->where('id_rol', 2)
            ->findOrFail($id);

        if (!$administrador->empleado) {
            return response()->json([
                'message' => 'Administrador sin registro de empleado'
            ], 400);
        }

        $administrador->empleado->estado =
            $administrador->empleado->estado === 'ACTIVO'
                ? 'INACTIVO'
                : 'ACTIVO';

        $administrador->empleado->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente',
            'estado' => $administrador->empleado->estado
        ]);
    }
}