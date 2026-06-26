<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Jornada;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JornadaController extends Controller
{
    private function obtenerSucursalAdmin(Request $request): int
    {
        $user = User::with('empleado')->find($request->user()->id);

        if (!$user || !$user->empleado || !$user->empleado->id_sucursal) {
            abort(403, 'El usuario ADMIN no tiene una sucursal asignada.');
        }

        return (int) $user->empleado->id_sucursal;
    }

    public function index(Request $request)
    {
        $idSucursal = $this->obtenerSucursalAdmin($request);

        $jornadas = Jornada::with('sucursal')
            ->where('id_sucursal', $idSucursal)
            ->orderByDesc('fecha')
            ->paginate(15);

        return response()->json([
            'message' => 'Jornadas obtenidas correctamente.',
            'jornadas' => $jornadas,
        ]);
    }

    public function actual(Request $request)
    {
        $idSucursal = $this->obtenerSucursalAdmin($request);

        $jornada = Jornada::with('sucursal')
            ->where('id_sucursal', $idSucursal)
            ->whereDate('fecha', now()->toDateString())
            ->first();

        return response()->json([
            'message' => $jornada
                ? 'Jornada actual encontrada.'
                : 'No existe jornada registrada para hoy.',
            'jornada' => $jornada,
        ]);
    }

    public function abrir(Request $request)
    {
        $idSucursal = $this->obtenerSucursalAdmin($request);
        $fechaHoy = now()->toDateString();

        $jornadaExistente = Jornada::where('id_sucursal', $idSucursal)
            ->whereDate('fecha', $fechaHoy)
            ->first();

        if ($jornadaExistente) {
            return response()->json([
                'message' => 'Ya existe una jornada registrada para hoy.',
                'jornada' => $jornadaExistente,
            ], 409);
        }

        $jornada = DB::transaction(function () use ($idSucursal, $fechaHoy) {
            return Jornada::create([
                'id_sucursal' => $idSucursal,
                'fecha' => $fechaHoy,
                'hora_inicio' => now()->format('H:i:s'),
                'hora_fin' => null,
                'estado' => 'ABIERTA',
            ]);
        });

        return response()->json([
            'message' => 'Jornada abierta correctamente.',
            'jornada' => $jornada->load('sucursal'),
        ], 201);
    }

    public function cerrar(Request $request)
    {
        $idSucursal = $this->obtenerSucursalAdmin($request);

        $jornada = Jornada::where('id_sucursal', $idSucursal)
            ->whereDate('fecha', now()->toDateString())
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$jornada) {
            return response()->json([
                'message' => 'No existe una jornada abierta para cerrar.',
            ], 404);
        }

        $jornada = DB::transaction(function () use ($jornada) {
            $jornada->update([
                'hora_fin' => now()->format('H:i:s'),
                'estado' => 'CERRADA',
            ]);

            return $jornada;
        });

        return response()->json([
            'message' => 'Jornada cerrada correctamente.',
            'jornada' => $jornada->load('sucursal'),
        ]);
    }
}