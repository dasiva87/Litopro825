<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Contact extends Model
{
    use SoftDeletes, BelongsToTenant;

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
        'credit_limit',
        'payment_terms',
        'discount_percentage',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'credit_limit' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
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

    // Métodos de negocio
    public function isCustomer(): bool
    {
        return in_array($this->type, ['customer', 'both']);
    }

    public function isSupplier(): bool
    {
        return in_array($this->type, ['supplier', 'both']);
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
}