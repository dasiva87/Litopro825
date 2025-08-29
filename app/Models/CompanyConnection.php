<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyConnection extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'connected_company_id',
        'connection_type',
        'status',
        'requested_by_user_id',
        'approved_by_user_id',
        'connection_metadata',
        'approved_at',
    ];

    protected $casts = [
        'connection_metadata' => 'array',
        'approved_at' => 'datetime',
    ];

    // Connection types
    const TYPE_PARTNER = 'partner';           // Socio comercial
    const TYPE_SUPPLIER = 'supplier';         // Proveedor
    const TYPE_CLIENT = 'client';             // Cliente
    const TYPE_COLLABORATOR = 'collaborator'; // Colaborador
    const TYPE_NETWORK = 'network';           // Red profesional

    // Connection statuses
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function connectedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'connected_company_id');
    }

    public function requestedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('connection_type', $type);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId)
                    ->orWhere('connected_company_id', $companyId);
    }

    public function approve(User $user): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED,
            'approved_by_user_id' => $user->id,
            'approved_at' => now(),
        ]);

        // Create reciprocal connection if it doesn't exist
        if (!self::where('company_id', $this->connected_company_id)
                ->where('connected_company_id', $this->company_id)
                ->exists()) {
            
            self::create([
                'company_id' => $this->connected_company_id,
                'connected_company_id' => $this->company_id,
                'connection_type' => $this->getReciprocalType(),
                'status' => self::STATUS_APPROVED,
                'requested_by_user_id' => $user->id,
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ]);
        }
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }

    public function block(): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
    }

    private function getReciprocalType(): string
    {
        return match($this->connection_type) {
            self::TYPE_SUPPLIER => self::TYPE_CLIENT,
            self::TYPE_CLIENT => self::TYPE_SUPPLIER,
            default => $this->connection_type,
        };
    }

    public function getConnectionTypeLabel(): string
    {
        return match($this->connection_type) {
            self::TYPE_PARTNER => 'Socio Comercial',
            self::TYPE_SUPPLIER => 'Proveedor',
            self::TYPE_CLIENT => 'Cliente',
            self::TYPE_COLLABORATOR => 'Colaborador',
            self::TYPE_NETWORK => 'Red Profesional',
            default => 'Conexión',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_BLOCKED => 'Bloqueado',
            default => 'Desconocido',
        };
    }

    public static function getConnectionTypes(): array
    {
        return [
            self::TYPE_PARTNER => 'Socio Comercial',
            self::TYPE_SUPPLIER => 'Proveedor',
            self::TYPE_CLIENT => 'Cliente',
            self::TYPE_COLLABORATOR => 'Colaborador',
            self::TYPE_NETWORK => 'Red Profesional',
        ];
    }

    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobado',
            self::STATUS_REJECTED => 'Rechazado',
            self::STATUS_BLOCKED => 'Bloqueado',
        ];
    }
}