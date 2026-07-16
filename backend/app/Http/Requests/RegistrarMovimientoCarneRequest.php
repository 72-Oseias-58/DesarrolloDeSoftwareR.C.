<?php

namespace App\Http\Requests;

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

            /*
             * Solo movimientos manuales del ADMIN.
             * APERTURA y VENTA serán registrados automáticamente.
             */
            'motivo' => [
                'required',
                Rule::in([
                    'TIENDA_FAMILIAR',
                    'AJUSTE',
                    'MERMA',
                ]),
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

            /*
             * Permite reemplazar la conversión aproximada del chancho.
             *
             * Ejemplo:
             * 1 CostillaGrande normalmente equivale a 12 MinCostillas,
             * pero se contaron realmente 13.
             */
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
            'id_tipo_carne.required' => 'Debe seleccionar el tipo de carne.',
            'id_tipo_carne.exists' => 'El tipo de carne seleccionado no existe.',

            'tipo_movimiento.required' => 'Debe seleccionar entrada o salida.',
            'tipo_movimiento.in' => 'El tipo de movimiento no es válido.',

            'motivo.required' => 'Debe seleccionar el motivo del movimiento.',
            'motivo.in' => 'El motivo seleccionado no es válido.',

            'unidad_registrada.required' => 'Debe seleccionar una unidad.',
            'unidad_registrada.in' => 'La unidad seleccionada no es válida.',

            'cantidad_registrada.required' => 'Debe ingresar una cantidad.',
            'cantidad_registrada.numeric' => 'La cantidad debe ser numérica.',
            'cantidad_registrada.gt' => 'La cantidad debe ser mayor a cero.',

            'cantidad_base_real.numeric' => 'La cantidad real debe ser numérica.',
            'cantidad_base_real.gt' => 'La cantidad real debe ser mayor a cero.',
        ];
    }
}