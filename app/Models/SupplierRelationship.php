<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRelationship extends Model
{
    // No usar BelongsToTenant aquí porque maneja relación entre dos empresas

    protected $fillable = [
        'client_company_id',
        'supplier_company_id',
        'approved_by_user_id',
        'approved_at',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relaciones
    public function clientCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'client_company_id');
    }

    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForClient($query, $companyId)
    {
        return $query->where('client_company_id', $companyId);
    }

    public function scopeForSupplier($query, $companyId)
    {
        return $query->where('supplier_company_id', $companyId);
    }

    // Métodos de negocio
    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function deactivate(?string $notes = null): bool
    {
        return $this->update([
            'is_active' => false,
            'notes' => $notes,
        ]);
    }

    public function reactivate(?string $notes = null): bool
    {
        return $this->update([
            'is_active' => true,
            'notes' => $notes,
        ]);
    }
}
