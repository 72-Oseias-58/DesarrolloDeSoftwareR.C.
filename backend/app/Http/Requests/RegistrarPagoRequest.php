<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarPagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'monto_efectivo' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_efectivo.required' =>
                'Debe indicar el dinero recibido.',

            'monto_efectivo.numeric' =>
                'El dinero recibido debe ser un número válido.',

            'monto_efectivo.min' =>
                'El dinero recibido debe ser mayor a cero.',
        ];
    }
}