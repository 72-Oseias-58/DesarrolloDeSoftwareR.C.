<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegistrarMovimientoCarneRequest;
use App\Models\MovimientoCarne;
use App\Models\User;
use App\Services\MovimientoCarneService;
use Illuminate\Http\Request;

class MovimientoCarneController extends Controller
{
    public function __construct(
        private readonly MovimientoCarneService $movimientoCarneService
    ) {
    }

    public function index(Request $request)
    {
        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $idSucursal = $this
            ->movimientoCarneService
            ->obtenerSucursalUsuario($user);

        $jornada = $this
            ->movimientoCarneService
            ->obtenerJornadaAbierta($idSucursal);

        $movimientos = MovimientoCarne::query()
            ->with([
                'tipoCarne',
                'usuarioCreador:id,name,usuario',
                'empleadoRecolector:id_empleado,nombre,cargo,estado',
            ])
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->where(
                'id_jornada',
                $jornada->id_jornada
            )
            ->when(
                $request->filled('id_tipo_carne'),
                fn ($query) => $query->where(
                    'id_tipo_carne',
                    $request->integer(
                        'id_tipo_carne'
                    )
                )
            )
            ->when(
                $request->filled('tipo_movimiento'),
                fn ($query) => $query->where(
                    'tipo_movimiento',
                    strtoupper(
                        $request
                            ->string('tipo_movimiento')
                            ->toString()
                    )
                )
            )
            ->when(
                $request->filled('motivo'),
                fn ($query) => $query->where(
                    'motivo',
                    strtoupper(
                        $request
                            ->string('motivo')
                            ->toString()
                    )
                )
            )
            ->orderByDesc(
                'id_movimiento_carne'
            )
            ->paginate(20);

        return response()->json([
            'message' =>
                'Movimientos de carne obtenidos correctamente.',

            'jornada' => [
                'id_jornada' =>
                    $jornada->id_jornada,

                'fecha' =>
                    $jornada->fecha,

                'estado' =>
                    $jornada->estado,
            ],

            'movimientos' =>
                $movimientos,
        ]);
    }

    public function store(
        RegistrarMovimientoCarneRequest $request
    ) {
        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $movimiento = $this
            ->movimientoCarneService
            ->registrarManual(
                $user,
                $request->validated()
            );

        return response()->json([
            'message' =>
                'Movimiento de carne registrado correctamente.',

            'movimiento' =>
                $movimiento->load([
                    'tipoCarne',
                    'controlCarne',
                    'usuarioCreador:id,name,usuario',
                    'empleadoRecolector:id_empleado,nombre,cargo,estado',
                ]),
        ], 201);
    }
}