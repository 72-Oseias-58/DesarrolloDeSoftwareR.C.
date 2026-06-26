<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdministradorController extends Controller
{
    /**
     * Listar únicamente administradores.
     */
    public function index()
    {
        $administradores = User::with([
            'rol',
            'empleado.sucursal',
        ])
            ->whereHas('rol', function ($query) {
                $query->where('nombre', 'ADMIN');
            })
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'administradores' => $administradores,
        ]);
    }

    /**
     * Listar usuarios que el SUPERADMIN puede ascender o degradar.
     *
     * Incluye:
     * - ADMIN
     * - CAJERO
     */
    public function usuariosGestionables()
    {
        $usuarios = User::with([
            'rol',
            'empleado.sucursal',
        ])
            ->whereHas('rol', function ($query) {
                $query->whereIn('nombre', [
                    'ADMIN',
                    'CAJERO',
                ]);
            })
            ->whereHas('empleado')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'usuarios' => $usuarios,
        ]);
    }

    /**
     * Crear un administrador.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',

            'usuario' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'usuario'),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email'),
            ],

            'password' => 'required|string|min:6',

            'id_sucursal' => [
                'required',
                'exists:sucursales,id_sucursal',
            ],

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
                'email' => $request->email ?: null,
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
                'message' => 'Administrador creado correctamente.',
                'administrador' => $user->load([
                    'rol',
                    'empleado.sucursal',
                ]),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al crear administrador.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mostrar un administrador.
     */
    public function show($id)
    {
        $administrador = User::with([
            'rol',
            'empleado.sucursal',
        ])
            ->whereHas('rol', function ($query) {
                $query->where('nombre', 'ADMIN');
            })
            ->findOrFail($id);

        return response()->json([
            'administrador' => $administrador,
        ]);
    }

    /**
     * Actualizar un administrador.
     */
    public function update(Request $request, $id)
    {
        $administrador = User::with('empleado')
            ->whereHas('rol', function ($query) {
                $query->where('nombre', 'ADMIN');
            })
            ->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',

            'usuario' => [
                'required',
                'string',
                'max:255',
                Rule::unique('users', 'usuario')
                    ->ignore($administrador->id),
            ],

            'email' => [
                'nullable',
                'email',
                Rule::unique('users', 'email')
                    ->ignore($administrador->id),
            ],

            'password' => 'nullable|string|min:6',

            'id_sucursal' => [
                'required',
                'exists:sucursales,id_sucursal',
            ],

            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:30',
            'contacto_referencia' => 'nullable|string|max:100',
            'telefono_referencia' => 'nullable|string|max:30',
        ]);

        DB::beginTransaction();

        try {
            $administrador->name = $request->name;
            $administrador->usuario = $request->usuario;
            $administrador->email = $request->email ?: null;

            if ($request->filled('password')) {
                $administrador->password = Hash::make(
                    $request->password
                );
            }

            $administrador->save();

            Empleado::updateOrCreate(
                [
                    'id_user' => $administrador->id,
                ],
                [
                    'id_sucursal' => $request->id_sucursal,
                    'nombre' => $request->name,
                    'cargo' => 'ADMIN',
                    'estado' => $administrador->empleado?->estado ?? 'ACTIVO',
                    'fecha_nacimiento' => $request->fecha_nacimiento,
                    'telefono' => $request->telefono,
                    'contacto_referencia' => $request->contacto_referencia,
                    'telefono_referencia' => $request->telefono_referencia,
                ]
            );

            DB::commit();

            return response()->json([
                'message' => 'Administrador actualizado correctamente.',
                'administrador' => $administrador->load([
                    'rol',
                    'empleado.sucursal',
                ]),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al actualizar administrador.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Activar o desactivar un administrador.
     */
    public function cambiarEstado($id)
    {
        $administrador = User::with('empleado')
            ->whereHas('rol', function ($query) {
                $query->where('nombre', 'ADMIN');
            })
            ->findOrFail($id);

        if (!$administrador->empleado) {
            return response()->json([
                'message' => 'El administrador no tiene un registro de empleado.',
            ], 422);
        }

        $administrador->empleado->estado =
            $administrador->empleado->estado === 'ACTIVO'
                ? 'INACTIVO'
                : 'ACTIVO';

        $administrador->empleado->save();

        return response()->json([
            'message' => 'Estado actualizado correctamente.',
            'estado' => $administrador->empleado->estado,
        ]);
    }
}