<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Contact extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'type',
        'name',
        'contact_person',
        'email',
        'phone',
        'mobile',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'tax_id',
        'website',
        'notes',
        'additional_contacts',
        'credit_limit',
        'payment_terms',
        'discount_percentage',
        'is_active',
        'is_local',
        'linked_company_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_local' => 'boolean',
        'credit_limit' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'additional_contacts' => 'array',
    ];


    // Relaciones geográficas
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Relación con empresa (multi-tenant)
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Relación con empresa vinculada (para contactos Grafired)
    public function linkedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'linked_company_id');
    }

    // Relación con documentos (cotizaciones, órdenes, etc.)
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCustomers($query)
    {
        return $query->whereIn('type', ['customer', 'both']);
    }

    public function scopeSuppliers($query)
    {
        return $query->whereIn('type', ['supplier', 'both']);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeLocal($query)
    {
        return $query->where('is_local', true);
    }

    public function scopeGrafired($query)
    {
        return $query->where('is_local', false)
                     ->whereNotNull('linked_company_id');
    }

    // Métodos de negocio
    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both']);
    }

    public function isSupplier(): bool
    {
        return in_array($this->type, ['supplier', 'both']);
    }

    public function isLocal(): bool
    {
        return $this->is_local === true;
    }

    public function isGrafired(): bool
    {
        return $this->is_local === false && $this->linked_company_id !== null;
    }

    public function getFullAddressAttribute(): string
    {
        $address = $this->address;
        
        if ($this->city) {
            $address .= ', ' . $this->city->name;
        }
        
        if ($this->state) {
            $address .= ', ' . $this->state->name;
        }
        
        if ($this->country) {
            $address .= ', ' . $this->country->name;
        }
        
        return $address;
    }

    public function getCreditAvailable(): float
    {
        if (!$this->credit_limit) {
            return 0;
        }

        // Aquí se calcularía el crédito usado en facturas pendientes
        // Por ahora retornamos el límite completo
        return $this->credit_limit;
    }

    public function syncFromLinkedCompany(): bool
    {
        if (!$this->linkedCompany) {
            return false;
        }

        $company = $this->linkedCompany->fresh([
            'city', 'state', 'country'
        ]);

        // Actualizar datos del contacto desde la empresa
        $this->update([
            'name' => $company->name,
            'email' => $company->email,
            'phone' => $company->phone,
            'address' => $company->address,
            'city_id' => $company->city_id,
            'state_id' => $company->state_id,
            'country_id' => $company->country_id,
            'tax_id' => $company->tax_id,
            'website' => $company->website,
        ]);

        return true;
    }
}