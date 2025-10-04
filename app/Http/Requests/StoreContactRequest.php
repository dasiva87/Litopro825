<?php

namespace App\Http\Requests;

use App\Services\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()
            && $this->user()->company_id !== null
            && $this->user()->can('create', \App\Models\Contact::class);
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['customer', 'supplier', 'both'])],
            'name' => ['required', 'string', 'max:255', 'min:2'],
            'email' => ['required', 'email:rfc,dns', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\+\-\(\)]+$/'],
            'mobile' => ['nullable', 'string', 'max:20', 'regex:/^[0-9\s\+\-\(\)]+$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'tax_id' => ['nullable', 'string', 'max:50'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999.99'],
            'payment_terms' => ['nullable', 'integer', 'min:0', 'max:365'],
            'discount_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
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

    protected function prepareForValidation(): void
    {
        $this->merge(['company_id' => TenantContext::id()]);
        if (!$this->has('is_active')) $this->merge(['is_active' => true]);
        if ($this->has('email')) $this->merge(['email' => strtolower(trim($this->input('email')))]);
    }
}
