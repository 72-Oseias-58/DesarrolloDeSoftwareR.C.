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
                'decimal:0,2',
            ],

            'monto_qr' => [
                'required',
                'numeric',
                'in:0',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'monto_efectivo.required' =>
                'Debe indicar el monto pagado en efectivo.',

            'monto_efectivo.numeric' =>
                'El monto en efectivo debe ser numérico.',

            'monto_efectivo.min' =>
                'El monto en efectivo debe ser mayor a cero.',

            'monto_efectivo.decimal' =>
                'El monto en efectivo puede tener hasta dos decimales.',

            'monto_qr.required' =>
                'Debe enviar el monto QR con valor cero.',

            'monto_qr.numeric' =>
                'El monto QR debe ser numérico.',

            'monto_qr.in' =>
                'Los pagos por QR todavía no están habilitados.',
        ];
    }
}