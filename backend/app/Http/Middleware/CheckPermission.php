<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permiso): Response
    {
        $authUser = Auth::guard('api')->user();

        if (!$authUser) {
            return response()->json([
                'message' => 'No autenticado.'
            ], 401);
        }

        $user = User::find($authUser->id);

        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado.'
            ], 404);
        }

        if (!$user->tienePermiso($permiso)) {
            return response()->json([
                'message' => 'No tienes permiso para realizar esta acción.',
                'permiso_requerido' => $permiso,
            ], 403);
        }

        return $next($request);
    }
}