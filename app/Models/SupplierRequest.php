<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierRequest extends Model
{
    // No usar BelongsToTenant aquí porque maneja relación entre dos empresas

    protected $fillable = [
        'requester_company_id',
        'supplier_company_id',
        'requested_by_user_id',
        'status',
        'message',
        'response_message',
        'responded_at',
        'responded_by_user_id',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    // Relaciones
    public function requesterCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'requester_company_id');
    }

    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function respondedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by_user_id');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    public function scopeForSupplier($query, $companyId)
    {
        return $query->where('supplier_company_id', $companyId);
    }

    public function scopeFromRequester($query, $companyId)
    {
        return $query->where('requester_company_id', $companyId);
    }

    // Métodos de negocio
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function approve(User $user, ?string $responseMessage = null): bool
    {
        $updated = $this->update([
            'status' => 'approved',
            'response_message' => $responseMessage,
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
        ]);

        if ($updated) {
            // Buscar relación existente
            $existingRelation = SupplierRelationship::where('client_company_id', $this->requester_company_id)
                ->where('supplier_company_id', $this->supplier_company_id)
                ->first();

            if (!$existingRelation) {
                // Crear nueva relación de proveedor
                SupplierRelationship::create([
                    'client_company_id' => $this->requester_company_id,
                    'supplier_company_id' => $this->supplier_company_id,
                    'approved_by_user_id' => $user->id,
                    'approved_at' => now(),
                    'is_active' => true,
                ]);
            } else {
                // Reactivar relación existente
                $existingRelation->reactivate('Solicitud aprobada');
            }
        }

        return $updated;
    }

    public function reject(User $user, ?string $responseMessage = null): bool
    {
        $updated = $this->update([
            'status' => 'rejected',
            'response_message' => $responseMessage,
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
        ]);

        if ($updated) {
            // Buscar y desactivar relación existente
            $existingRelation = SupplierRelationship::where('client_company_id', $this->requester_company_id)
                ->where('supplier_company_id', $this->supplier_company_id)
                ->first();

            if ($existingRelation && $existingRelation->is_active) {
                $existingRelation->deactivate('Solicitud rechazada');
            }
        }

        return $updated;
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            default => 'Desconocido',
        };
    }
}