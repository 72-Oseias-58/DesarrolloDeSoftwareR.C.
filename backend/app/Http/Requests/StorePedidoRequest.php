<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
                'nullable',
                'integer',
                'exists:productos_venta,id_producto',
            ],

            'detalles.*.cantidad' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],

            'detalles.*.precio_unitario' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:99999.99',
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

            'detalles.*.es_pura_carne' => [
                'nullable',
                'boolean',
            ],

            'detalles.*.tipo_carne_manual' => [
                'nullable',
                'string',
                'max:50',
            ],

            'detalles.*.cantidad_carne_manual' => [
                'nullable',
                'numeric',
                'min:0.01',
                'max:99999.99',
            ],

            'detalles.*.unidad_carne_manual' => [
                'nullable',
                'string',
                'max:50',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            foreach ($this->input('detalles', []) as $indice => $detalle) {
                $esPuraCarne = !empty($detalle['es_pura_carne']);

                if ($esPuraCarne) {
                    $this->validarDetallePuraCarne(
                        $validator,
                        $indice,
                        $detalle
                    );

                    continue;
                }

                $this->validarDetalleProductoNormal(
                    $validator,
                    $indice,
                    $detalle
                );
            }
        });
    }

    private function validarDetalleProductoNormal(
        Validator $validator,
        int $indice,
        array $detalle
    ): void {
        if (empty($detalle['id_producto'])) {
            $validator->errors()->add(
                "detalles.{$indice}.id_producto",
                'Cada detalle normal debe tener un producto.'
            );
        }

        if (empty($detalle['cantidad'])) {
            $validator->errors()->add(
                "detalles.{$indice}.cantidad",
                'Debe indicar la cantidad del producto.'
            );
        }

        if (!empty($detalle['tipo_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.tipo_carne_manual",
                'El tipo de carne manual solo se usa en venta de pura carne.'
            );
        }

        if (!empty($detalle['cantidad_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.cantidad_carne_manual",
                'La cantidad de carne manual solo se usa en venta de pura carne.'
            );
        }

        if (!empty($detalle['unidad_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.unidad_carne_manual",
                'La unidad de carne manual solo se usa en venta de pura carne.'
            );
        }
    }

    private function validarDetallePuraCarne(
        Validator $validator,
        int $indice,
        array $detalle
    ): void {
        if (!empty($detalle['id_producto'])) {
            $validator->errors()->add(
                "detalles.{$indice}.id_producto",
                'La venta de pura carne no debe enviar id_producto.'
            );
        }

        if (!empty($detalle['cantidad'])) {
            $validator->errors()->add(
                "detalles.{$indice}.cantidad",
                'La venta de pura carne no usa cantidad de producto.'
            );
        }

        if (empty($detalle['precio_unitario'])) {
            $validator->errors()->add(
                "detalles.{$indice}.precio_unitario",
                'La venta de pura carne debe tener precio unitario.'
            );
        }

        if (empty($detalle['tipo_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.tipo_carne_manual",
                'Debe indicar el tipo de carne.'
            );
        }

        if (
            !empty($detalle['tipo_carne_manual']) &&
            !in_array(strtoupper($detalle['tipo_carne_manual']), ['CHANCHO', 'POLLO'], true)
        ) {
            $validator->errors()->add(
                "detalles.{$indice}.tipo_carne_manual",
                'El tipo de carne debe ser CHANCHO o POLLO.'
            );
        }

        if (empty($detalle['cantidad_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.cantidad_carne_manual",
                'Debe indicar la cantidad de carne a descontar.'
            );
        }

        if (empty($detalle['unidad_carne_manual'])) {
            $validator->errors()->add(
                "detalles.{$indice}.unidad_carne_manual",
                'Debe indicar la unidad de carne.'
            );
        }

        if (!empty($detalle['guarniciones'])) {
            $validator->errors()->add(
                "detalles.{$indice}.guarniciones",
                'La venta de pura carne no debe tener guarniciones.'
            );
        }
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

            'detalles.*.id_producto.exists' =>
                'Uno de los productos seleccionados no existe.',

            'detalles.*.cantidad.min' =>
                'La cantidad debe ser mayor a cero.',

            'detalles.*.cantidad.max' =>
                'La cantidad máxima permitida por producto es 100.',

            'detalles.*.precio_unitario.min' =>
                'El precio debe ser mayor a cero.',

            'detalles.*.guarniciones.*.exists' =>
                'Una de las guarniciones seleccionadas no existe.',

            'detalles.*.cantidad_carne_manual.min' =>
                'La cantidad de carne debe ser mayor a cero.',
        ];
    }
}