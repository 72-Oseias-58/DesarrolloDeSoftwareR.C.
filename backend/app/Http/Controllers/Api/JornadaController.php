<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ControlCarneJornada;
use App\Models\Jornada;
use App\Models\TipoCarne;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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

        $jornadas = Jornada::with(['sucursal', 'controlCarne.tipoCarne'])
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

        $jornada = Jornada::with(['sucursal', 'controlCarne.tipoCarne'])
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

    public function tiposCarne()
    {
        $tipos = TipoCarne::orderBy('nombre')->get();

        return response()->json([
            'message' => 'Tipos de carne obtenidos correctamente.',
            'tipos_carne' => $tipos,
        ]);
    }

    public function abrir(Request $request)
    {
        $idSucursal = $this->obtenerSucursalAdmin($request);
        $fechaHoy = now()->toDateString();

        $data = $request->validate([
            'carnes' => ['required', 'array', 'size:2'],

            'carnes.*.id_tipo_carne' => ['required', 'integer', 'exists:tipos_carne,id_tipo_carne'],
            'carnes.*.cantidad_cruces' => ['required', 'numeric', 'min:0'],
            'carnes.*.platos_estimados' => ['nullable', 'numeric', 'min:0'],
            'carnes.*.cantidad_base_inicial' => ['required', 'numeric', 'min:0.01'],
            'carnes.*.unidad_base' => ['required', 'string', 'max:50'],
            'carnes.*.observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $jornadaExistente = Jornada::where('id_sucursal', $idSucursal)
            ->whereDate('fecha', $fechaHoy)
            ->first();

        if ($jornadaExistente) {
            return response()->json([
                'message' => 'Ya existe una jornada registrada para hoy.',
                'jornada' => $jornadaExistente,
            ], 409);
        }

        $idsTiposCarne = collect($data['carnes'])->pluck('id_tipo_carne')->toArray();

        if (count($idsTiposCarne) !== count(array_unique($idsTiposCarne))) {
            throw ValidationException::withMessages([
                'carnes' => ['No puedes repetir el mismo tipo de carne.'],
            ]);
        }

        $tiposCarne = TipoCarne::whereIn('id_tipo_carne', $idsTiposCarne)
            ->pluck('nombre', 'id_tipo_carne');

        $nombres = $tiposCarne->map(fn ($nombre) => strtoupper($nombre))->values()->toArray();

        if (!in_array('CHANCHO', $nombres) || !in_array('POLLO', $nombres)) {
            throw ValidationException::withMessages([
                'carnes' => ['Para abrir jornada debes registrar CHANCHO y POLLO.'],
            ]);
        }

        $jornada = DB::transaction(function () use ($idSucursal, $fechaHoy, $data) {
            $jornada = Jornada::create([
                'id_sucursal' => $idSucursal,
                'fecha' => $fechaHoy,
                'hora_inicio' => now()->format('H:i:s'),
                'hora_fin' => null,
                'estado' => 'ABIERTA',
            ]);

            foreach ($data['carnes'] as $carne) {
                ControlCarneJornada::create([
                    'id_sucursal' => $idSucursal,
                    'id_jornada' => $jornada->id_jornada,
                    'id_tipo_carne' => $carne['id_tipo_carne'],
                    'cantidad_cruces' => $carne['cantidad_cruces'],
                    'platos_estimados' => $carne['platos_estimados'] ?? null,
                    'cantidad_base_inicial' => $carne['cantidad_base_inicial'],
                    'cantidad_base_actual' => $carne['cantidad_base_inicial'],
                    'unidad_base' => $carne['unidad_base'],
                    'observacion' => $carne['observacion'] ?? null,
                ]);
            }

            return $jornada;
        });

        return response()->json([
            'message' => 'Jornada abierta correctamente.',
            'jornada' => $jornada->load(['sucursal', 'controlCarne.tipoCarne']),
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
            'jornada' => $jornada->load(['sucursal', 'controlCarne.tipoCarne']),
        ]);
    }
}