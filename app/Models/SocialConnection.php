<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialConnection extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',          // Empresa que envía la solicitud
        'requester_user_id',   // Usuario que solicita
        'target_company_id',   // Empresa objetivo
        'target_user_id',      // Usuario objetivo (opcional)
        'connection_type',
        'status',
        'message',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Connection types
    const TYPE_PARTNERSHIP = 'partnership';      // Asociación comercial
    const TYPE_SUPPLIER = 'supplier';            // Relación proveedor
    const TYPE_CLIENT = 'client';                // Relación cliente
    const TYPE_COLLABORATION = 'collaboration';   // Colaboración técnica
    const TYPE_REFERRAL = 'referral';            // Referencias mutuas

    // Status types
    const STATUS_PENDING = 'pending';
    const STATUS_ACCEPTED = 'accepted';
    const STATUS_REJECTED = 'rejected';
    const STATUS_BLOCKED = 'blocked';

    public function requestingCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_user_id');
    }

    public function targetCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'target_company_id');
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('connection_type', $type);
    }

    public function scopeForCompany($query, int $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)
              ->orWhere('target_company_id', $companyId);
        });
    }

    public function getConnectionTypeLabel(): string
    {
        return match($this->connection_type) {
            self::TYPE_PARTNERSHIP => 'Asociación Comercial',
            self::TYPE_SUPPLIER => 'Relación de Proveedor',
            self::TYPE_CLIENT => 'Relación de Cliente',
            self::TYPE_COLLABORATION => 'Colaboración Técnica',
            self::TYPE_REFERRAL => 'Referencias Mutuas',
            default => 'Conexión',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'Pendiente',
            self::STATUS_ACCEPTED => 'Aceptada',
            self::STATUS_REJECTED => 'Rechazada',
            self::STATUS_BLOCKED => 'Bloqueada',
            default => 'Sin estado',
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'warning',
            self::STATUS_ACCEPTED => 'success',
            self::STATUS_REJECTED => 'danger',
            self::STATUS_BLOCKED => 'secondary',
            default => 'gray',
        };
    }

    public static function getConnectionTypes(): array
    {
        return [
            self::TYPE_PARTNERSHIP => 'Asociación Comercial',
            self::TYPE_SUPPLIER => 'Relación de Proveedor',
            self::TYPE_CLIENT => 'Relación de Cliente',
            self::TYPE_COLLABORATION => 'Colaboración Técnica',
            self::TYPE_REFERRAL => 'Referencias Mutuas',
        ];
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function accept(): void
    {
        $this->update(['status' => self::STATUS_ACCEPTED]);
    }

    public function reject(): void
    {
        $this->update(['status' => self::STATUS_REJECTED]);
    }
}