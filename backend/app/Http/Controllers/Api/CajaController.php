<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\CompraInterna;
use App\Models\Jornada;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CajaController extends Controller
{
    private function obtenerUsuarioConEmpleado(
        Request $request
    ): User {
        $user = User::with('empleado')
            ->find($request->user()->id);

        if (!$user || !$user->empleado) {
            abort(
                403,
                'El usuario no tiene un empleado asociado.'
            );
        }

        if (!$user->empleado->id_sucursal) {
            abort(
                403,
                'El empleado no tiene una sucursal asignada.'
            );
        }

        return $user;
    }

    private function obtenerJornadaAbierta(
        int $idSucursal
    ): ?Jornada {
        return Jornada::query()
            ->where(
                'id_sucursal',
                $idSucursal
            )
            ->whereDate(
                'fecha',
                now()->toDateString()
            )
            ->where('estado', 'ABIERTA')
            ->first();
    }

    public function actual(Request $request)
    {
        $user = $this->obtenerUsuarioConEmpleado(
            $request
        );

        $caja = Caja::with([
            'jornada.sucursal',
            'empleado',
            'usuarioCreador',
        ])
            ->where(
                'id_empleado',
                $user->empleado->id_empleado
            )
            ->where('estado', 'ABIERTA')
            ->latest('id_caja')
            ->first();

        return response()->json([
            'message' => $caja
                ? 'Caja actual encontrada.'
                : 'No existe una caja abierta para este cajero.',

            'caja' => $caja
                ? $this->agregarResumenCaja($caja)
                : null,
        ]);
    }

    public function index(Request $request)
    {
        $user = $this->obtenerUsuarioConEmpleado(
            $request
        );

        $jornada = Jornada::query()
            ->where(
                'id_sucursal',
                $user->empleado->id_sucursal
            )
            ->whereDate(
                'fecha',
                now()->toDateString()
            )
            ->first();

        if (!$jornada) {
            return response()->json([
                'message' =>
                    'No existe jornada registrada para hoy.',

                'cajas' => [],
            ]);
        }

        $cajas = Caja::with([
            'jornada.sucursal',
            'empleado',
            'usuarioCreador',
        ])
            ->where(
                'id_jornada',
                $jornada->id_jornada
            )
            ->orderByDesc('id_caja')
            ->get()
            ->map(function ($caja) {
                return $this->agregarResumenCaja($caja);
            });

        return response()->json([
            'message' => 'Cajas obtenidas correctamente.',
            'jornada' => $jornada,
            'cajas' => $cajas,
        ]);
    }

    public function abrir(Request $request)
    {
        $request->validate([
            'monto_inicial' => [
                'nullable',
                'numeric',
                'min:0',
            ],
        ]);

        $user = $this->obtenerUsuarioConEmpleado(
            $request
        );

        $jornada = $this->obtenerJornadaAbierta(
            $user->empleado->id_sucursal
        );

        if (!$jornada) {
            return response()->json([
                'message' =>
                    'No existe una jornada abierta para abrir caja.',
            ], 409);
        }

        $cajaAbierta = Caja::query()
            ->where(
                'id_empleado',
                $user->empleado->id_empleado
            )
            ->where('estado', 'ABIERTA')
            ->first();

        if ($cajaAbierta) {
            return response()->json([
                'message' => 'Ya tienes una caja abierta.',
                'caja' => $this->agregarResumenCaja(
                    $cajaAbierta
                ),
            ], 409);
        }

        $caja = DB::transaction(function () use (
            $request,
            $user,
            $jornada
        ) {
            return Caja::create([
                'id_jornada' =>
                    $jornada->id_jornada,

                'id_empleado' =>
                    $user->empleado->id_empleado,

                'id_user_crea' =>
                    $user->id,

                'monto_inicial' =>
                    $request->monto_inicial ?? 0,

                'monto_final' => null,
                'total_efectivo' => 0,
                'total_qr' => 0,
                'diferencia' => null,

                'hora_apertura' =>
                    now()->format('H:i:s'),

                'hora_cierre' => null,
                'estado' => 'ABIERTA',
            ]);
        });

        return response()->json([
            'message' => 'Caja abierta correctamente.',

            'caja' => $this->agregarResumenCaja(
                $caja->load([
                    'jornada.sucursal',
                    'empleado',
                    'usuarioCreador',
                ])
            ),
        ], 201);
    }

    public function cerrar(Request $request)
    {
        $request->validate([
            'monto_final' => [
                'required',
                'numeric',
                'min:0',
            ],
        ]);

        $user = $this->obtenerUsuarioConEmpleado(
            $request
        );

        $caja = Caja::query()
            ->where(
                'id_empleado',
                $user->empleado->id_empleado
            )
            ->where('estado', 'ABIERTA')
            ->lockForUpdate()
            ->first();

        if (!$caja) {
            return response()->json([
                'message' =>
                    'No existe una caja abierta para cerrar.',
            ], 404);
        }

        $compraPendiente = CompraInterna::query()
            ->where('id_caja', $caja->id_caja)
            ->where('estado', 'PENDIENTE')
            ->exists();

        if ($compraPendiente) {
            throw ValidationException::withMessages([
                'compras_internas' => [
                    'No puedes cerrar la caja mientras existan compras internas pendientes.',
                ],
            ]);
        }

        $caja = DB::transaction(function () use (
            $request,
            $caja
        ) {
            $caja = Caja::query()
                ->where(
                    'id_caja',
                    $caja->id_caja
                )
                ->lockForUpdate()
                ->firstOrFail();

            $gastosOperativos = $this
                ->obtenerGastosFinalizados(
                    $caja->id_caja
                );

            $montoFinal = round(
                (float) $request->monto_final,
                2
            );

            $efectivoEsperado = round(
                (float) $caja->monto_inicial
                + (float) $caja->total_efectivo
                - $gastosOperativos,
                2
            );

            $diferencia = round(
                $montoFinal - $efectivoEsperado,
                2
            );

            $caja->update([
                'monto_final' => $montoFinal,

                'diferencia' => $diferencia,

                'hora_cierre' =>
                    now()->format('H:i:s'),

                'estado' => 'CERRADA',
            ]);

            return $caja;
        });

        return response()->json([
            'message' => 'Caja cerrada correctamente.',

            'caja' => $this->agregarResumenCaja(
                $caja->load([
                    'jornada.sucursal',
                    'empleado',
                    'usuarioCreador',
                ])
            ),
        ]);
    }

    private function obtenerGastosFinalizados(
        int $idCaja
    ): float {
        return round(
            (float) CompraInterna::query()
                ->where('id_caja', $idCaja)
                ->where('estado', 'FINALIZADA')
                ->sum('total_gastado'),
            2
        );
    }

    private function obtenerDineroPendiente(
        int $idCaja
    ): float {
        return round(
            (float) CompraInterna::query()
                ->where('id_caja', $idCaja)
                ->where('estado', 'PENDIENTE')
                ->sum('total_entregado'),
            2
        );
    }

    private function agregarResumenCaja(
        Caja $caja
    ): Caja {
        $gastosOperativos = $this
            ->obtenerGastosFinalizados(
                $caja->id_caja
            );

        $dineroEnComprasPendientes = $this
            ->obtenerDineroPendiente(
                $caja->id_caja
            );

        $efectivoNetoEsperado = round(
            (float) $caja->monto_inicial
            + (float) $caja->total_efectivo
            - $gastosOperativos,
            2
        );

        $efectivoDisponibleActual = round(
            $efectivoNetoEsperado
            - $dineroEnComprasPendientes,
            2
        );

        $caja->setAttribute(
            'gastos_operativos',
            number_format(
                $gastosOperativos,
                2,
                '.',
                ''
            )
        );

        $caja->setAttribute(
            'dinero_compras_pendientes',
            number_format(
                $dineroEnComprasPendientes,
                2,
                '.',
                ''
            )
        );

        $caja->setAttribute(
            'efectivo_neto_esperado',
            number_format(
                $efectivoNetoEsperado,
                2,
                '.',
                ''
            )
        );

        $caja->setAttribute(
            'efectivo_disponible_actual',
            number_format(
                $efectivoDisponibleActual,
                2,
                '.',
                ''
            )
        );

        return $caja;
    }
}