<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClientRelationship extends Model
{
    use HasFactory;

    // No usar BelongsToTenant aquí porque maneja relación entre dos empresas

    protected $fillable = [
        'supplier_company_id',
        'client_company_id',
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
    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function clientCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'client_company_id');
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

    public function scopeForSupplier($query, $companyId)
    {
        return $query->where('supplier_company_id', $companyId);
    }

    public function scopeForClient($query, $companyId)
    {
        return $query->where('client_company_id', $companyId);
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

    /**
     * Crear Contact automáticamente al aprobar relación
     */
    public function createLocalContact(): Contact
    {
        return Contact::firstOrCreate([
            'company_id' => $this->supplier_company_id,
            'linked_company_id' => $this->client_company_id,
        ], [
            'type' => 'customer',
            'name' => $this->clientCompany->name,
            'email' => $this->clientCompany->email,
            'phone' => $this->clientCompany->phone,
            'address' => $this->clientCompany->address,
            'is_local' => false,
            'is_active' => $this->is_active,
            'notes' => 'Cliente vinculado desde Grafired - ' . now()->format('Y-m-d'),
        ]);
    }

    /**
     * Sincronizar datos del Contact vinculado
     */
    public function syncLinkedContact(): void
    {
        $contact = Contact::where([
            'company_id' => $this->supplier_company_id,
            'linked_company_id' => $this->client_company_id,
        ])->first();

        if ($contact && $this->clientCompany) {
            $contact->update([
                'name' => $this->clientCompany->name,
                'email' => $this->clientCompany->email,
                'phone' => $this->clientCompany->phone,
                'address' => $this->clientCompany->address,
                'is_active' => $this->is_active,
            ]);
        }
    }
}