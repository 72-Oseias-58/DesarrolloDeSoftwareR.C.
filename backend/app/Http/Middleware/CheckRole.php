<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'message' => 'Token inválido o usuario no autenticado.'
            ], 401);
        }

        $rolNombre = $user->rol?->nombre;

        if (!$rolNombre || !in_array($rolNombre, $roles)) {
            return response()->json([
                'message' => 'No tienes permiso para acceder a esta ruta.'
            ], 403);
        }

        return $next($request);
    }
}