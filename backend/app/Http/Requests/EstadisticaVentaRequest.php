<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EstadisticaVentaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'periodo' => strtolower(
                trim((string) $this->input('periodo', 'hoy'))
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'periodo' => [
                'required',
                'string',
                Rule::in([
                    'hoy',
                    'semana',
                    'mes',
                    'anio',
                    'personalizado',
                ]),
            ],

            'fecha_desde' => [
                'nullable',
                'required_if:periodo,personalizado',
                'date_format:Y-m-d',
            ],

            'fecha_hasta' => [
                'nullable',
                'required_if:periodo,personalizado',
                'date_format:Y-m-d',
                'after_or_equal:fecha_desde',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'periodo.required' =>
                'Debe seleccionar un periodo.',

            'periodo.in' =>
                'El periodo debe ser hoy, semana, mes, anio o personalizado.',

            'fecha_desde.required_if' =>
                'La fecha inicial es obligatoria para un periodo personalizado.',

            'fecha_desde.date_format' =>
                'La fecha inicial debe tener el formato YYYY-MM-DD.',

            'fecha_hasta.required_if' =>
                'La fecha final es obligatoria para un periodo personalizado.',

            'fecha_hasta.date_format' =>
                'La fecha final debe tener el formato YYYY-MM-DD.',

            'fecha_hasta.after_or_equal' =>
                'La fecha final no puede ser anterior a la fecha inicial.',
        ];
    }
}