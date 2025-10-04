<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $document = $this->route('document');

        // Verificar que el usuario puede actualizar el documento
        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('update', $document);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $documentId = $this->route('document')->id ?? null;

        return [
            // Campos opcionales en update (pueden no enviarse)
            'contact_id' => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('contacts', 'id')->where('company_id', $tenantId),
            ],
            'document_type_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:document_types,id',
            ],
            'date' => [
                'sometimes',
                'required',
                'date',
            ],

            // Campos opcionales
            'document_number' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                Rule::unique('documents', 'document_number')
                    ->where('company_id', $tenantId)
                    ->ignore($documentId),
            ],
            'reference' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
            ],
            'due_date' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:date',
            ],
            'valid_until' => [
                'sometimes',
                'nullable',
                'date',
                'after_or_equal:date',
            ],
            'status' => [
                'sometimes',
                'nullable',
                'string',
                Rule::in(['draft', 'sent', 'approved', 'rejected', 'cancelled']),
            ],

            // Campos numéricos
            'subtotal' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'discount_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'discount_percentage' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'tax_amount' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'tax_percentage' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],
            'total' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],

            // Notas
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],
            'internal_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:5000',
            ],

            // Versionado
            'parent_document_id' => [
                'sometimes',
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
        // NO sobreescribir company_id en updates - debe mantenerse el original
        // NO sobreescribir user_id - mantener el creador original
    }
}
