<?php

namespace App\Services;

use App\Models\ControlCarneJornada;
use App\Models\Empleado;
use App\Models\Jornada;
use App\Models\MovimientoCarne;
use App\Models\TipoCarne;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovimientoCarneService
{
    public function obtenerSucursalUsuario(User $user): int
    {
        $user->loadMissing('empleado');

        if (!$user->empleado || !$user->empleado->id_sucursal) {
            throw ValidationException::withMessages([
                'sucursal' => [
                    'El usuario ADMIN no tiene una sucursal asignada.',
                ],
            ]);
        }

        return (int) $user->empleado->id_sucursal;
    }

    public function obtenerJornadaAbierta(int $idSucursal): Jornada
    {
        $jornada = Jornada::query()
            ->where('id_sucursal', $idSucursal)
            ->whereDate('fecha', now()->toDateString())
            ->where('estado', 'ABIERTA')
            ->first();

        if (!$jornada) {
            throw ValidationException::withMessages([
                'jornada' => [
                    'No existe una jornada abierta para la sucursal.',
                ],
            ]);
        }

        return $jornada;
    }

    public function registrarManual(
        User $user,
        array $data
    ): MovimientoCarne {
        $idSucursal = $this->obtenerSucursalUsuario($user);

        $jornada = $this->obtenerJornadaAbierta(
            $idSucursal
        );

        return DB::transaction(function () use (
            $user,
            $data,
            $idSucursal,
            $jornada
        ) {
            $tipoCarne = TipoCarne::query()
                ->whereKey($data['id_tipo_carne'])
                ->firstOrFail();

            $nombreCarne = strtoupper(
                trim($tipoCarne->nombre)
            );

            if (
                !in_array(
                    $nombreCarne,
                    ['CHANCHO', 'POLLO'],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'id_tipo_carne' => [
                        'Solo se permiten movimientos de CHANCHO o POLLO.',
                    ],
                ]);
            }

            $control = ControlCarneJornada::query()
                ->where('id_sucursal', $idSucursal)
                ->where(
                    'id_jornada',
                    $jornada->id_jornada
                )
                ->where(
                    'id_tipo_carne',
                    $tipoCarne->id_tipo_carne
                )
                ->lockForUpdate()
                ->first();

            if (!$control) {
                throw ValidationException::withMessages([
                    'carne' => [
                        "No existe control de {$nombreCarne} para la jornada actual.",
                    ],
                ]);
            }

            $tipoMovimiento = strtoupper(
                $data['tipo_movimiento']
            );

            $motivo = strtoupper(
                $data['motivo']
            );

            $unidadRegistrada = strtoupper(
                $data['unidad_registrada']
            );

            $cantidadRegistrada = (float)
                $data['cantidad_registrada'];

            $this->validarMovimientoManual(
                $tipoMovimiento,
                $motivo,
                $data['observacion'] ?? null
            );

            $datosRecojo = $this->obtenerDatosRecojo(
                $data,
                $motivo,
                $idSucursal
            );

            $cantidadBase = $this->convertirCantidadBase(
                $nombreCarne,
                $unidadRegistrada,
                $cantidadRegistrada,
                isset($data['cantidad_base_real'])
                    ? (float) $data['cantidad_base_real']
                    : null
            );

            $cantidadAnterior = (float)
                $control->cantidad_base_actual;

            if ($tipoMovimiento === 'ENTRADA') {
                $cantidadNueva =
                    $cantidadAnterior + $cantidadBase;
            } else {
                if ($cantidadAnterior < $cantidadBase) {
                    throw ValidationException::withMessages([
                        'cantidad_registrada' => [
                            "No existe suficiente {$nombreCarne}. "
                            . 'Disponible: '
                            . number_format(
                                $cantidadAnterior,
                                2,
                                '.',
                                ''
                            )
                            . " {$control->unidad_base}. "
                            . 'Solicitado: '
                            . number_format(
                                $cantidadBase,
                                2,
                                '.',
                                ''
                            )
                            . " {$control->unidad_base}.",
                        ],
                    ]);
                }

                $cantidadNueva =
                    $cantidadAnterior - $cantidadBase;
            }

            $control->update([
                'cantidad_base_actual' => number_format(
                    $cantidadNueva,
                    2,
                    '.',
                    ''
                ),
            ]);

            return MovimientoCarne::create([
                'id_control_carne' =>
                    $control->id_control_carne,

                'id_sucursal' =>
                    $idSucursal,

                'id_jornada' =>
                    $jornada->id_jornada,

                'id_tipo_carne' =>
                    $tipoCarne->id_tipo_carne,

                'id_user_crea' =>
                    $user->id,

                'id_empleado_recolector' =>
                    $datosRecojo['id_empleado_recolector'],

                'fecha_hora_recojo' =>
                    $datosRecojo['fecha_hora_recojo'],

                'tipo_movimiento' =>
                    $tipoMovimiento,

                'motivo' =>
                    $motivo,

                'unidad_registrada' =>
                    $unidadRegistrada,

                'cantidad_registrada' =>
                    number_format(
                        $cantidadRegistrada,
                        2,
                        '.',
                        ''
                    ),

                'cantidad_base' =>
                    number_format(
                        $cantidadBase,
                        2,
                        '.',
                        ''
                    ),

                'unidad_base' =>
                    $control->unidad_base,

                'cantidad_anterior' =>
                    number_format(
                        $cantidadAnterior,
                        2,
                        '.',
                        ''
                    ),

                'cantidad_nueva' =>
                    number_format(
                        $cantidadNueva,
                        2,
                        '.',
                        ''
                    ),

                'referencia_tipo' => null,
                'referencia_id' => null,

                'origen' =>
                    $tipoMovimiento === 'ENTRADA'
                    && $motivo === 'TIENDA_FAMILIAR'
                        ? 'TIENDA_FAMILIAR'
                        : null,

                'destino' =>
                    $tipoMovimiento === 'SALIDA'
                    && $motivo === 'TIENDA_FAMILIAR'
                        ? 'TIENDA_FAMILIAR'
                        : null,

                'observacion' =>
                    isset($data['observacion'])
                        ? trim((string) $data['observacion'])
                        : null,
            ]);
        });
    }

    private function obtenerDatosRecojo(
        array $data,
        string $motivo,
        int $idSucursal
    ): array {
        if ($motivo !== 'TIENDA_FAMILIAR') {
            return [
                'id_empleado_recolector' => null,
                'fecha_hora_recojo' => null,
            ];
        }

        $idEmpleado = (int) (
            $data['id_empleado_recolector'] ?? 0
        );

        if ($idEmpleado <= 0) {
            throw ValidationException::withMessages([
                'id_empleado_recolector' => [
                    'Debe seleccionar al empleado que recogió la carne.',
                ],
            ]);
        }

        $empleado = Empleado::query()
            ->whereKey($idEmpleado)
            ->where('id_sucursal', $idSucursal)
            ->where('estado', 'ACTIVO')
            ->first();

        if (!$empleado) {
            throw ValidationException::withMessages([
                'id_empleado_recolector' => [
                    'El empleado no existe, está inactivo o pertenece a otra sucursal.',
                ],
            ]);
        }

        if (empty($data['fecha_hora_recojo'])) {
            throw ValidationException::withMessages([
                'fecha_hora_recojo' => [
                    'Debe indicar la fecha y hora del recojo.',
                ],
            ]);
        }

        $fechaHoraRecojo = Carbon::parse(
            $data['fecha_hora_recojo'],
            'America/La_Paz'
        );

        if ($fechaHoraRecojo->isFuture()) {
            throw ValidationException::withMessages([
                'fecha_hora_recojo' => [
                    'La fecha y hora del recojo no pueden estar en el futuro.',
                ],
            ]);
        }

        return [
            'id_empleado_recolector' =>
                $empleado->id_empleado,

            'fecha_hora_recojo' =>
                $fechaHoraRecojo->format(
                    'Y-m-d H:i:s'
                ),
        ];
    }

    private function validarMovimientoManual(
        string $tipoMovimiento,
        string $motivo,
        ?string $observacion
    ): void {
        if (
            $motivo === 'MERMA'
            && $tipoMovimiento !== 'SALIDA'
        ) {
            throw ValidationException::withMessages([
                'tipo_movimiento' => [
                    'Una merma solamente puede registrarse como SALIDA.',
                ],
            ]);
        }

        if (
            in_array(
                $motivo,
                ['AJUSTE', 'MERMA'],
                true
            )
            && empty(trim((string) $observacion))
        ) {
            throw ValidationException::withMessages([
                'observacion' => [
                    'Debe explicar el motivo del ajuste o la merma.',
                ],
            ]);
        }
    }

    private function convertirCantidadBase(
        string $tipoCarne,
        string $unidad,
        float $cantidad,
        ?float $cantidadBaseReal = null
    ): float {
        if ($cantidad <= 0) {
            throw ValidationException::withMessages([
                'cantidad_registrada' => [
                    'La cantidad debe ser mayor a cero.',
                ],
            ]);
        }

        if ($tipoCarne === 'CHANCHO') {
            $conversiones = [
                'CRUZ_CHANCHO' => 24,
                'COSTILLA_GRANDE' => 12,
                'MIN_COSTILLA' => 1,
            ];

            if (
                !array_key_exists(
                    $unidad,
                    $conversiones
                )
            ) {
                throw ValidationException::withMessages([
                    'unidad_registrada' => [
                        'La unidad seleccionada no corresponde a CHANCHO.',
                    ],
                ]);
            }

            if ($cantidadBaseReal !== null) {
                return round(
                    $cantidadBaseReal,
                    2
                );
            }

            return round(
                $cantidad * $conversiones[$unidad],
                2
            );
        }

        if ($tipoCarne === 'POLLO') {
            $conversiones = [
                'CRUZ_POLLO' => 2,
                'POLLO' => 1,
            ];

            if (
                !array_key_exists(
                    $unidad,
                    $conversiones
                )
            ) {
                throw ValidationException::withMessages([
                    'unidad_registrada' => [
                        'La unidad seleccionada no corresponde a POLLO.',
                    ],
                ]);
            }

            if ($cantidadBaseReal !== null) {
                throw ValidationException::withMessages([
                    'cantidad_base_real' => [
                        'La cantidad real solamente se utiliza para CHANCHO.',
                    ],
                ]);
            }

            return round(
                $cantidad * $conversiones[$unidad],
                2
            );
        }

        throw ValidationException::withMessages([
            'id_tipo_carne' => [
                'El tipo de carne debe ser CHANCHO o POLLO.',
            ],
        ]);
    }
}