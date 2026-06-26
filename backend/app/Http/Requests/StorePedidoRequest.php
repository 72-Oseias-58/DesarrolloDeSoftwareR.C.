<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePedidoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_consumo' => [
                'required',
                'string',
                'max:50',
            ],

            'detalles' => [
                'required',
                'array',
                'min:1',
            ],

            'detalles.*.id_producto' => [
                'required',
                'integer',
                'exists:productos_venta,id_producto',
            ],

            'detalles.*.cantidad' => [
                'required',
                'integer',
                'min:1',
                'max:100',
            ],

            'detalles.*.observacion' => [
                'nullable',
                'string',
                'max:255',
            ],

            'detalles.*.guarniciones' => [
                'nullable',
                'array',
            ],

            'detalles.*.guarniciones.*' => [
                'integer',
                'exists:guarniciones,id_guarnicion',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_consumo.required' =>
                'Debe seleccionar el tipo de consumo.',

            'detalles.required' =>
                'Debe agregar productos al pedido.',

            'detalles.min' =>
                'Debe agregar al menos un producto.',

            'detalles.*.id_producto.required' =>
                'Cada detalle debe tener un producto.',

            'detalles.*.id_producto.exists' =>
                'Uno de los productos seleccionados no existe.',

            'detalles.*.cantidad.required' =>
                'Debe indicar la cantidad del producto.',

            'detalles.*.cantidad.min' =>
                'La cantidad debe ser mayor a cero.',

            'detalles.*.guarniciones.*.exists' =>
                'Una de las guarniciones seleccionadas no existe.',
        ];
    }
}