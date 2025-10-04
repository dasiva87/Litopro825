<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contact = $this->route('contact');

        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('update', $contact);
    }

    public function rules(): array
    {
        $tenantId = TenantContext::id();
        $contactId = $this->route('contact')->id ?? null;

        return [
            'type' => ['sometimes', 'required', 'string', Rule::in(['customer', 'supplier', 'both'])],
            'name' => ['sometimes', 'required', 'string', 'max:255', 'min:2'],
            'email' => [
                'sometimes',
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('contacts', 'email')
                    ->where('company_id', $tenantId)
                    ->ignore($contactId),
            ],
            'contact_person' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9\s\+\-\(\)]+$/'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:20', 'regex:/^[0-9\s\+\-\(\)]+$/'],
            'address' => ['sometimes', 'nullable', 'string', 'max:500'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'state_id' => ['sometimes', 'nullable', 'integer', 'exists:states,id'],
            'country_id' => ['sometimes', 'nullable', 'integer', 'exists:countries,id'],
            'tax_id' => ['sometimes', 'nullable', 'string', 'max:50'],
            'website' => ['sometimes', 'nullable', 'url:http,https', 'max:255'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'credit_limit' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_terms' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:365'],
            'discount_percentage' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['sometimes', 'nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => 'tipo de contacto',
            'name' => 'nombre',
            'email' => 'correo electrónico',
            'phone' => 'teléfono',
            'mobile' => 'celular',
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Ya existe un contacto con este correo en tu empresa.',
        ];
    }

    protected function prepareForValidation(): void
    {
        // NO sobreescribir company_id en updates - debe mantenerse el original
        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }
    }
}
