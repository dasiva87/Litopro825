<?php

namespace App\Http\Requests;

use App\Enums\OrderStatus;
use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $purchaseOrder = $this->route('purchase_order');

        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('update', $purchaseOrder);
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $purchaseOrderId = $this->route('purchase_order')->id ?? null;

        return [
            // Campos opcionales en update (pueden no enviarse)
            'supplier_company_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('companies', 'id')->where(function ($query) {
                    $query->where('type', 'papeleria');
                }),
            ],
            'order_date' => [
                'sometimes',
                'required',
                'date',
            ],
            'expected_delivery_date' => [
                'sometimes',
                'required',
                'date',
                'after_or_equal:order_date',
            ],

            // Campos opcionales
            'order_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('purchase_orders', 'order_number')
                    ->where('company_id', $tenantId)
                    ->ignore($purchaseOrderId),
            ],
            'status' => [
                'sometimes',
                'nullable',
                'string',
                Rule::enum(OrderStatus::class),
            ],
            'actual_delivery_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:order_date',
            ],
            'total_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'approved_by' => [
                'sometimes',
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'approved_at' => [
                'sometimes',
                'nullable',
                'date',
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
            'approved_by' => 'aprobador',
            'approved_at' => 'fecha de aprobación',
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
        // NO sobreescribir company_id en updates - debe mantenerse el original
        // NO sobreescribir created_by - mantener el creador original
    }
}
