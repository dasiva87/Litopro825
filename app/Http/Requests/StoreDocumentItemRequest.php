<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Verificar que el usuario puede modificar el documento
        $document = \App\Models\Document::find($this->document_id);

        return $this->user()
            && $this->user()->company_id !== null
            && $document
            && $document->company_id === TenantContext::id()
            && $this->user()->can('update', $document);
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            // Relación con documento
            'document_id' => [
                'required',
                'integer',
                Rule::exists('documents', 'id')->where('company_id', $tenantId),
            ],

            // Relación polimórfica con itemable (opcional para items personalizados)
            'itemable_type' => [
                'nullable',
                'string',
                Rule::in([
                    'App\\Models\\SimpleItem',
                    'App\\Models\\Product',
                    'App\\Models\\DigitalItem',
                    'App\\Models\\TalonarioItem',
                    'App\\Models\\MagazineItem',
                ]),
            ],
            'itemable_id' => [
                'nullable',
                'integer',
                'required_with:itemable_type',
            ],

            // Datos del item
            'description' => [
                'required',
                'string',
                'max:500',
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999',
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'total_price' => [
                'required',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            // Campos opcionales de cálculo
            'paper_id' => [
                'nullable',
                'integer',
                Rule::exists('papers', 'id')->where('company_id', $tenantId),
            ],
            'printing_machine_id' => [
                'nullable',
                'integer',
                Rule::exists('printing_machines', 'id')->where('company_id', $tenantId),
            ],
            'width' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000',
            ],
            'height' => [
                'nullable',
                'numeric',
                'min:0',
                'max:10000',
            ],
            'pages' => [
                'nullable',
                'integer',
                'min:1',
                'max:10000',
            ],
            'colors_front' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],
            'colors_back' => [
                'nullable',
                'integer',
                'min:0',
                'max:10',
            ],

            // Costos desglosados
            'paper_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'printing_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'cutting_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'design_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'transport_cost' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'other_costs' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            // Margen de ganancia
            'profit_margin' => [
                'nullable',
                'numeric',
                'min:0',
                'max:1000',
            ],

            // Tipo de item
            'item_type' => [
                'nullable',
                'string',
                Rule::in(['simple_item', 'product', 'digital_item', 'talonario_item', 'magazine_item', 'custom_item']),
            ],

            // Configuración JSON
            'item_config' => [
                'nullable',
                'json',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'document_id' => 'documento',
            'itemable_type' => 'tipo de item',
            'itemable_id' => 'item',
            'description' => 'descripción',
            'quantity' => 'cantidad',
            'unit_price' => 'precio unitario',
            'total_price' => 'precio total',
            'paper_id' => 'papel',
            'printing_machine_id' => 'máquina de impresión',
            'width' => 'ancho',
            'height' => 'alto',
            'pages' => 'páginas',
            'colors_front' => 'colores frente',
            'colors_back' => 'colores reverso',
            'profit_margin' => 'margen de ganancia',
            'item_type' => 'tipo de item',
        ];
    }

    public function messages(): array
    {
        return [
            'document_id.exists' => 'El documento seleccionado no existe o no pertenece a tu empresa.',
            'itemable_id.required_with' => 'Debes especificar el ID del item cuando se proporciona el tipo.',
            'quantity.min' => 'La cantidad debe ser mayor a 0.',
            'quantity.max' => 'La cantidad no puede exceder 999,999 unidades.',
            'total_price.min' => 'El precio total debe ser igual o mayor a 0.',
            'profit_margin.max' => 'El margen de ganancia no puede exceder 1000%.',
            'item_type.in' => 'El tipo de item no es válido.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Asegurar que company_id se establezca automáticamente
        $this->merge([
            'company_id' => TenantContext::id(),
        ]);

        // Calcular total_price si no se proporciona
        if ($this->has('quantity') && $this->has('unit_price') && !$this->has('total_price')) {
            $this->merge([
                'total_price' => $this->quantity * $this->unit_price,
            ]);
        }
    }

    /**
     * Validación adicional después de las reglas básicas
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Verificar que el itemable existe y pertenece al tenant (si se proporciona)
            if ($this->itemable_type && $this->itemable_id) {
                $modelClass = $this->itemable_type;

                if (!class_exists($modelClass)) {
                    $validator->errors()->add('itemable_type', 'El tipo de item no existe.');
                    return;
                }

                $item = $modelClass::find($this->itemable_id);

                if (!$item) {
                    $validator->errors()->add('itemable_id', 'El item seleccionado no existe.');
                    return;
                }

                // Verificar que pertenece al tenant actual (si aplica)
                if (isset($item->company_id) && $item->company_id != TenantContext::id()) {
                    $validator->errors()->add('itemable_id', 'El item seleccionado no pertenece a tu empresa.');
                }

                // Validar stock para productos
                if ($modelClass === 'App\\Models\\Product' && method_exists($item, 'hasStock')) {
                    if (!$item->hasStock($this->quantity)) {
                        $validator->errors()->add('quantity', "Stock insuficiente. Disponible: {$item->stock} unidades.");
                    }
                }
            }

            // Validar coherencia de precios
            if ($this->has('quantity') && $this->has('unit_price') && $this->has('total_price')) {
                $calculatedTotal = round($this->quantity * $this->unit_price, 2);
                $providedTotal = round($this->total_price, 2);

                // Permitir diferencia de hasta 0.02 por redondeo
                if (abs($calculatedTotal - $providedTotal) > 0.02) {
                    $validator->errors()->add('total_price', 'El precio total no coincide con cantidad × precio unitario.');
                }
            }
        });
    }
}
