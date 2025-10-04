<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Usuario debe pertenecer a una empresa y tener permiso de crear documentos
        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('create', \App\Models\Document::class);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();

        return [
            // Campos requeridos
            'contact_id' => [
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where('company_id', $tenantId),
            ],
            'document_type_id' => [
                'required',
                'integer',
                'exists:document_types,id',
            ],
            'date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            // Campos opcionales
            'document_number' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('documents', 'document_number')
                    ->where('company_id', $tenantId),
            ],
            'reference' => [
                'nullable',
                'string',
                'max:100',
            ],
            'due_date' => [
                'nullable',
                'date',
                'after_or_equal:date',
            ],
            'valid_until' => [
                'nullable',
                'date',
                'after_or_equal:date',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::in(['draft', 'sent', 'approved', 'rejected', 'cancelled']),
            ],

            // Campos numéricos
            'subtotal' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'discount_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'discount_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'tax_amount' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'tax_percentage' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'total' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            // Notas
            'notes' => [
                'nullable',
                'string',
                'max:5000',
            ],
            'internal_notes' => [
                'nullable',
                'string',
                'max:5000',
            ],

            // Versionado
            'parent_document_id' => [
                'nullable',
                'integer',
                Rule::exists('documents', 'id')->where('company_id', $tenantId),
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'contact_id' => 'cliente',
            'document_type_id' => 'tipo de documento',
            'date' => 'fecha',
            'due_date' => 'fecha de vencimiento',
            'valid_until' => 'válido hasta',
            'subtotal' => 'subtotal',
            'discount_amount' => 'descuento',
            'discount_percentage' => 'porcentaje de descuento',
            'tax_amount' => 'impuesto',
            'tax_percentage' => 'porcentaje de impuesto',
            'total' => 'total',
            'notes' => 'notas',
            'internal_notes' => 'notas internas',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'contact_id.exists' => 'El cliente seleccionado no pertenece a tu empresa.',
            'document_number.unique' => 'Ya existe un documento con este número en tu empresa.',
            'due_date.after_or_equal' => 'La fecha de vencimiento debe ser igual o posterior a la fecha del documento.',
            'valid_until.after_or_equal' => 'La fecha de validez debe ser igual o posterior a la fecha del documento.',
            'parent_document_id.exists' => 'El documento padre no existe o no pertenece a tu empresa.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Asegurar que company_id se establezca automáticamente
        $this->merge([
            'company_id' => TenantContext::id(),
            'user_id' => $this->user()->id,
        ]);

        // Establecer status por defecto si no se proporciona
        if (!$this->has('status')) {
            $this->merge(['status' => 'draft']);
        }
    }
}
