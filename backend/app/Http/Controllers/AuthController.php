<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'usuario' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('usuario', 'password');

        if (!$token = Auth::guard('api')->attempt($credentials)) {
            return response()->json([
                'message' => 'Usuario o contraseña incorrectos.'
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function me()
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json([
            'message' => 'Token inválido o usuario no autenticado.'
        ], 401);
    }

    return response()->json($user->load('rol'));
}
    public function logout()
    {
        Auth::guard('api')->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.'
        ]);
    }

    public function refresh()
    {
        return $this->respondWithToken(Auth::guard('api')->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => Auth::guard('api')->factory()->getTTL() * 60,
            'user' => Auth::guard('api')->user()->load('rol'),
        ]);
    }
}