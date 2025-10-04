<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('create', \App\Models\PurchaseOrder::class);
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            // Campos requeridos
            'supplier_company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where(function ($query) {
                    $query->where('type', 'papeleria');
                }),
            ],
            'order_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'expected_delivery_date' => [
                'required',
                'date',
                'after_or_equal:order_date',
            ],

            // Campos opcionales
            'order_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'order_number')
                    ->where('company_id', $tenantId),
            ],
            'status' => [
                'nullable',
                'string',
                Rule::enum(OrderStatus::class),
            ],
            'actual_delivery_date' => [
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],
            'total_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'supplier_company_id' => 'proveedor',
            'order_date' => 'fecha de orden',
            'expected_delivery_date' => 'fecha de entrega esperada',
            'actual_delivery_date' => 'fecha de entrega real',
            'total_amount' => 'monto total',
            'notes' => 'notas',
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_company_id.exists' => 'El proveedor seleccionado debe ser una papelería registrada.',
            'order_number.unique' => 'Ya existe una orden con este número en tu empresa.',
            'expected_delivery_date.after_or_equal' => 'La fecha de entrega esperada debe ser igual o posterior a la fecha de orden.',
            'actual_delivery_date.after_or_equal' => 'La fecha de entrega real debe ser igual o posterior a la fecha de orden.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Asegurar que company_id se establezca automáticamente
        $this->merge([
            'company_id' => TenantContext::id(),
            'created_by' => $this->user()->id,
        ]);

        // Establecer status por defecto si no se proporciona
        if (!$this->has('status')) {
            $this->merge(['status' => OrderStatus::DRAFT->value]);
        }

        // El order_number se genera automáticamente en el modelo si no se proporciona
    }
}
