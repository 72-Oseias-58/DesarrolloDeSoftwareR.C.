<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caja;
use App\Models\CompraInterna;
use App\Models\User;
use App\Services\CompraInternaService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CompraInternaController extends Controller
{
    public function __construct(
        private readonly CompraInternaService $compraService
    ) {
    }

    public function index(Request $request)
    {
        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $idSucursal = $this->compraService
            ->obtenerSucursalUsuario($user);

        $jornada = $this->compraService
            ->obtenerJornadaAbierta($idSucursal);

        $compras = CompraInterna::query()
            ->with([
                'caja.empleado:id_empleado,nombre,cargo',
                'empleadoComprador:id_empleado,nombre,cargo,estado',
                'usuarioAutoriza:id,name,usuario',
            ])
            ->where('id_sucursal', $idSucursal)
            ->where('id_jornada', $jornada->id_jornada)
            ->when(
                $request->filled('estado'),
                fn ($query) => $query->where(
                    'estado',
                    strtoupper($request->string('estado'))
                )
            )
            ->when(
                $request->filled('categoria'),
                fn ($query) => $query->where(
                    'categoria',
                    strtoupper($request->string('categoria'))
                )
            )
            ->orderByDesc('id_compra_interna')
            ->paginate(20);

        return response()->json([
            'message' => 'Compras internas obtenidas correctamente.',
            'jornada' => $jornada,
            'compras' => $compras,
        ]);
    }

    public function opciones(Request $request)
    {
        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $idSucursal = $this->compraService
            ->obtenerSucursalUsuario($user);

        $jornada = $this->compraService
            ->obtenerJornadaAbierta($idSucursal);

        $cajas = Caja::query()
            ->with([
                'empleado:id_empleado,nombre,cargo',
            ])
            ->where('id_jornada', $jornada->id_jornada)
            ->where('estado', 'ABIERTA')
            ->orderBy('id_caja')
            ->get();

        return response()->json([
            'message' => 'Opciones obtenidas correctamente.',
            'jornada' => $jornada,
            'cajas' => $cajas,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'id_caja' => [
                'required',
                'integer',
                'exists:cajas,id_caja',
            ],

            'id_empleado_comprador' => [
                'required',
                'integer',
                'exists:empleados,id_empleado',
            ],

            'motivo' => [
                'required',
                'string',
                'max:150',
            ],

            'categoria' => [
                'required',
                Rule::in([
                    'GAS',
                    'LIMPIEZA',
                    'COCINA',
                    'TRANSPORTE',
                    'MANTENIMIENTO',
                    'EMERGENCIA',
                    'OTROS',
                ]),
            ],

            'monto_entregado_inicial' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'fecha_hora_salida' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $compra = $this->compraService
            ->registrar($user, $data);

        return response()->json([
            'message' => 'Compra interna iniciada correctamente.',
            'compra' => $compra->load([
                'caja.empleado',
                'empleadoComprador',
                'usuarioAutoriza',
            ]),
        ], 201);
    }

    public function agregarDinero(
        Request $request,
        int $id
    ) {
        $data = $request->validate([
            'monto' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $compra = $this->compraService
            ->agregarDinero($user, $id, $data);

        return response()->json([
            'message' => 'Dinero adicional registrado correctamente.',
            'compra' => $compra->load([
                'caja.empleado',
                'empleadoComprador',
                'usuarioAutoriza',
            ]),
        ]);
    }

    public function finalizar(
        Request $request,
        int $id
    ) {
        $data = $request->validate([
            'productos' => [
                'required',
                'array',
                'min:1',
            ],

            'productos.*.producto' => [
                'required',
                'string',
                'max:150',
            ],

            'productos.*.cantidad' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'productos.*.precio_unitario' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'total_gastado' => [
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
            ],

            'fecha_hora_regreso' => [
                'nullable',
                'date',
                'before_or_equal:now',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ]);

        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $compra = $this->compraService
            ->finalizar($user, $id, $data);

        return response()->json([
            'message' => 'Compra interna finalizada correctamente.',
            'compra' => $compra->load([
                'caja.empleado',
                'empleadoComprador',
                'usuarioAutoriza',
            ]),
        ]);
    }

    public function anular(
        Request $request,
        int $id
    ) {
        $data = $request->validate([
            'observacion' => [
                'required',
                'string',
                'max:1000',
            ],
        ]);

        $user = User::with('empleado')
            ->findOrFail($request->user()->id);

        $compra = $this->compraService
            ->anular(
                $user,
                $id,
                $data['observacion']
            );

        return response()->json([
            'message' => 'Compra interna anulada correctamente.',
            'compra' => $compra->load([
                'caja.empleado',
                'empleadoComprador',
                'usuarioAutoriza',
            ]),
        ]);
    }
}