<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CerrarJornadaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('api')->check();
    }

    public function rules(): array
    {
        return [
            'cajas' => [
                'present',
                'array',
            ],

            'cajas.*.id_caja' => [
                'required',
                'integer',
                'distinct',
                'exists:cajas,id_caja',
            ],

            'cajas.*.monto_fisico' => [
                'required',
                'numeric',
                'min:0',
                'decimal:0,2',
            ],

            'cajas.*.observacion' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'cajas.present' =>
                'Debe enviar la información de las cajas.',

            'cajas.array' =>
                'La información de las cajas no es válida.',

            'cajas.*.id_caja.required' =>
                'Debe indicar la caja.',

            'cajas.*.id_caja.distinct' =>
                'No puede enviar la misma caja más de una vez.',

            'cajas.*.id_caja.exists' =>
                'Una de las cajas seleccionadas no existe.',

            'cajas.*.monto_fisico.required' =>
                'Debe registrar el efectivo físico contado.',

            'cajas.*.monto_fisico.min' =>
                'El efectivo físico no puede ser negativo.',

            'cajas.*.observacion.max' =>
                'La observación no puede superar los 1000 caracteres.',
        ];
    }
}