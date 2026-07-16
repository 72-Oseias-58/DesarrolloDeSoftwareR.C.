<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarMovimientoCarneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = User::with('empleado')
            ->find($this->user()?->id);

        $idSucursal = $user?->empleado?->id_sucursal;

        return [
            'id_tipo_carne' => [
                'required',
                'integer',
                'exists:tipos_carne,id_tipo_carne',
            ],

            'tipo_movimiento' => [
                'required',
                Rule::in([
                    'ENTRADA',
                    'SALIDA',
                ]),
            ],

            'motivo' => [
                'required',
                Rule::in([
                    'TIENDA_FAMILIAR',
                    'AJUSTE',
                    'MERMA',
                ]),
            ],

            'id_empleado_recolector' => [
                'nullable',
                'required_if:motivo,TIENDA_FAMILIAR',
                'integer',

                Rule::exists(
                    'empleados',
                    'id_empleado'
                )->where(function ($query) use ($idSucursal) {
                    $query->where('estado', 'ACTIVO');

                    if ($idSucursal) {
                        $query->where(
                            'id_sucursal',
                            $idSucursal
                        );
                    }
                }),
            ],

            'fecha_hora_recojo' => [
                'nullable',
                'required_if:motivo,TIENDA_FAMILIAR',
                'date',
                'before_or_equal:now',
            ],

            'unidad_registrada' => [
                'required',
                'string',
                Rule::in([
                    'CRUZ_CHANCHO',
                    'COSTILLA_GRANDE',
                    'MIN_COSTILLA',
                    'CRUZ_POLLO',
                    'POLLO',
                ]),
            ],

            'cantidad_registrada' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'cantidad_base_real' => [
                'nullable',
                'numeric',
                'gt:0',
            ],

            'observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'id_tipo_carne.required' =>
                'Debe seleccionar el tipo de carne.',

            'id_tipo_carne.exists' =>
                'El tipo de carne seleccionado no existe.',

            'tipo_movimiento.required' =>
                'Debe seleccionar entrada o salida.',

            'tipo_movimiento.in' =>
                'El tipo de movimiento no es válido.',

            'motivo.required' =>
                'Debe seleccionar el motivo del movimiento.',

            'motivo.in' =>
                'El motivo seleccionado no es válido.',

            'id_empleado_recolector.required_if' =>
                'Debe seleccionar al empleado que recogió la carne.',

            'id_empleado_recolector.exists' =>
                'El empleado seleccionado no existe, está inactivo o pertenece a otra sucursal.',

            'fecha_hora_recojo.required_if' =>
                'Debe indicar la fecha y hora del recojo.',

            'fecha_hora_recojo.date' =>
                'La fecha y hora del recojo no son válidas.',

            'fecha_hora_recojo.before_or_equal' =>
                'La fecha y hora del recojo no pueden estar en el futuro.',

            'unidad_registrada.required' =>
                'Debe seleccionar una unidad.',

            'unidad_registrada.in' =>
                'La unidad seleccionada no es válida.',

            'cantidad_registrada.required' =>
                'Debe ingresar una cantidad.',

            'cantidad_registrada.numeric' =>
                'La cantidad debe ser numérica.',

            'cantidad_registrada.gt' =>
                'La cantidad debe ser mayor a cero.',

            'cantidad_base_real.numeric' =>
                'La cantidad real debe ser numérica.',

            'cantidad_base_real.gt' =>
                'La cantidad real debe ser mayor a cero.',

            'observacion.max' =>
                'La observación no puede superar los 1000 caracteres.',
        ];
    }
}