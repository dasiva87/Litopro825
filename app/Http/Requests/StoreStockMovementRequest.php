<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('create', \App\Models\StockMovement::class);
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            // Relación polimórfica con stockable (Product, Paper, SimpleItem, etc.)
            'stockable_type' => [
                'required',
                'string',
                Rule::in([
                    'App\\Models\\Product',
                    'App\\Models\\Paper',
                    'App\\Models\\SimpleItem',
                    'App\\Models\\DigitalItem',
                ]),
            ],
            'stockable_id' => [
                'required',
                'integer',
                'min:1',
            ],

            // Tipo de movimiento
            'type' => [
                'required',
                'string',
                Rule::in(['entry', 'exit', 'adjustment', 'transfer']),
            ],

            // Cantidad
            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999',
            ],

            // Campos opcionales
            'reason' => [
                'nullable',
                'string',
                Rule::in(['purchase', 'sale', 'production', 'return', 'loss', 'adjustment', 'initial', 'other']),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'total_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'stockable_type' => 'tipo de item',
            'stockable_id' => 'item',
            'type' => 'tipo de movimiento',
            'quantity' => 'cantidad',
            'reason' => 'razón',
            'reference' => 'referencia',
            'notes' => 'notas',
            'unit_cost' => 'costo unitario',
            'total_cost' => 'costo total',
        ];
    }

    public function messages(): array
    {
        return [
            'stockable_type.in' => 'El tipo de item no es válido.',
            'type.in' => 'El tipo de movimiento debe ser: entrada, salida, ajuste o transferencia.',
            'reason.in' => 'La razón del movimiento no es válida.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'quantity.max' => 'La cantidad no puede exceder 999,999 unidades.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Asegurar que company_id se establezca automáticamente
        $this->merge([
            'company_id' => TenantContext::id(),
            'user_id' => $this->user()->id,
        ]);

        // Calcular total_cost si no se proporciona
        if ($this->has('quantity') && $this->has('unit_cost') && !$this->has('total_cost')) {
            $this->merge([
                'total_cost' => $this->quantity * $this->unit_cost,
            ]);
        }
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar que el stockable existe y pertenece al tenant
            if ($this->stockable_type && $this->stockable_id) {
                $modelClass = $this->stockable_type;

                if (!class_exists($modelClass)) {
                    $validator->errors()->add('stockable_type', 'El tipo de item no existe.');
                    return;
                }

                $item = $modelClass::find($this->stockable_id);

                if (!$item) {
                    $validator->errors()->add('stockable_id', 'El item seleccionado no existe.');
                    return;
                }

                // Verificar que pertenece al tenant actual
                if (isset($item->company_id) && $item->company_id != TenantContext::id()) {
                    $validator->errors()->add('stockable_id', 'El item seleccionado no pertenece a tu empresa.');
                }

                // Validar stock suficiente para salidas
                if ($this->type === 'exit' && method_exists($item, 'hasStock')) {
                    if (!$item->hasStock($this->quantity)) {
                        $validator->errors()->add('quantity', 'No hay stock suficiente para realizar esta salida.');
                    }
                }
            }
        });
    }
}
