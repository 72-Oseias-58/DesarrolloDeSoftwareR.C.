<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PHPOpenSourceSaver\JWTAuth\JWTGuard;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('usuario', 'password');

        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        if (!$token = $guard->attempt($credentials)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $user = $guard->user();

        if (!$user) {
            return response()->json([
                'message' => 'Token inválido o usuario no autenticado.'
            ], 401);
        }

        return response()->json([
            'user' => $this->formarUsuarioConPermisos($user)
        ]);
    }

    public function logout()
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $guard->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ]);
    }

    public function refresh()
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $token = $guard->refresh();

        return $this->respondWithToken($token);
    }

    protected function respondWithToken(string $token)
    {
        /** @var JWTGuard $guard */
        $guard = Auth::guard('api');

        $user = $guard->user();

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => $guard->factory()->getTTL() * 60,
            'user' => $this->formarUsuarioConPermisos($user),
        ]);
    }

    private function formarUsuarioConPermisos($user): array
    {
        if (!$user) {
            return [];
        }

        $user->load([
            'rol.permisos',
            'empleado.sucursal',
            'permisosPersonalizados.permiso',
        ]);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'usuario' => $user->usuario,
            'email' => $user->email,

            'role' => [
                'id_rol' => $user->rol?->id_rol,
                'nombre_rol' => $user->rol?->nombre,
            ],

            'empleado' => $user->empleado ? [
                'id_empleado' => $user->empleado->id_empleado,
                'nombre' => $user->empleado->nombre,
                'cargo' => $user->empleado->cargo,
                'estado' => $user->empleado->estado,
                'id_sucursal' => $user->empleado->id_sucursal,
                'sucursal' => $user->empleado->sucursal?->nombre,
            ] : null,

            'permisos' => $user->permisosFinales(),
        ];
    }
}