<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommercialRequest extends Model
{
    use HasFactory;

    // No usar BelongsToTenant aquí porque maneja relación entre dos empresas

    protected $fillable = [
        'requester_company_id',
        'target_company_id',
        'requested_by_user_id',
        'relationship_type',
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

    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
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

    public function scopeForTarget($query, $companyId)
    {
        return $query->where('target_company_id', $companyId);
    }

    public function scopeFromRequester($query, $companyId)
    {
        return $query->where('requester_company_id', $companyId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('relationship_type', $type);
    }

    // Métodos de estado
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

    // Métodos de negocio
    public function approve(User $user, ?string $responseMessage = null): bool
    {
        $updated = $this->update([
            'status' => 'approved',
            'response_message' => $responseMessage,
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
        ]);

        if ($updated) {
            $this->createRelationship($user);
        }

        return $updated;
    }

    public function reject(User $user, ?string $responseMessage = null): bool
    {
        return $this->update([
            'status' => 'rejected',
            'response_message' => $responseMessage,
            'responded_at' => now(),
            'responded_by_user_id' => $user->id,
        ]);
    }

    /**
     * Crear la relación apropiada según el tipo
     */
    protected function createRelationship(User $approver): void
    {
        if ($this->relationship_type === 'supplier') {
            // Solicitud para ser proveedor → SupplierRelationship
            $existing = SupplierRelationship::where([
                'client_company_id' => $this->requester_company_id,
                'supplier_company_id' => $this->target_company_id,
            ])->first();

            if (!$existing) {
                $relationship = SupplierRelationship::create([
                    'client_company_id' => $this->requester_company_id,
                    'supplier_company_id' => $this->target_company_id,
                    'approved_by_user_id' => $approver->id,
                    'approved_at' => now(),
                    'is_active' => true,
                    'notes' => "Creada desde solicitud comercial - {$this->created_at->format('Y-m-d')}",
                ]);

                // Crear Contact en empresa solicitante
                $this->createSupplierContact($relationship);
            } else {
                $existing->reactivate('Solicitud aprobada');
                $this->createSupplierContact($existing);
            }
        } elseif ($this->relationship_type === 'client') {
            // Solicitud para ser cliente → ClientRelationship
            $existing = ClientRelationship::where([
                'supplier_company_id' => $this->target_company_id,
                'client_company_id' => $this->requester_company_id,
            ])->first();

            if (!$existing) {
                $relationship = ClientRelationship::create([
                    'supplier_company_id' => $this->target_company_id,
                    'client_company_id' => $this->requester_company_id,
                    'approved_by_user_id' => $approver->id,
                    'approved_at' => now(),
                    'is_active' => true,
                    'notes' => "Creada desde solicitud comercial - {$this->created_at->format('Y-m-d')}",
                ]);

                // Crear Contact en empresa objetivo
                $relationship->createLocalContact();
            } else {
                $existing->reactivate('Solicitud aprobada');
                $existing->createLocalContact();
            }
        }
    }

    /**
     * Crear Contact de proveedor en empresa solicitante
     */
    protected function createSupplierContact(SupplierRelationship $relationship): void
    {
        Contact::firstOrCreate([
            'company_id' => $relationship->client_company_id,
            'linked_company_id' => $relationship->supplier_company_id,
        ], [
            'type' => 'supplier',
            'name' => $relationship->supplierCompany->name,
            'email' => $relationship->supplierCompany->email,
            'phone' => $relationship->supplierCompany->phone,
            'address' => $relationship->supplierCompany->address,
            'is_local' => false,
            'is_active' => $relationship->is_active,
            'notes' => 'Proveedor vinculado desde Grafired - ' . now()->format('Y-m-d'),
        ]);
    }

    // Helpers para UI
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

    public function getTypeLabel(): string
    {
        return match ($this->relationship_type) {
            'client' => 'Cliente',
            'supplier' => 'Proveedor',
            default => 'Desconocido',
        };
    }

    public function getTypeIcon(): string
    {
        return match ($this->relationship_type) {
            'client' => 'heroicon-o-users',
            'supplier' => 'heroicon-o-truck',
            default => 'heroicon-o-question-mark-circle',
        };
    }
}