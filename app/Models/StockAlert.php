<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;

class StockAlert extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'stockable_type',
        'stockable_id',
        'type',
        'severity',
        'status',
        'current_stock',
        'min_stock',
        'threshold_value',
        'title',
        'message',
        'metadata',
        'triggered_at',
        'acknowledged_at',
        'resolved_at',
        'acknowledged_by',
        'resolved_by',
        'auto_resolvable',
        'expires_at',
    ];

    protected $casts = [
        'current_stock' => 'integer',
        'min_stock' => 'integer',
        'threshold_value' => 'integer',
        'metadata' => 'array',
        'triggered_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'expires_at' => 'datetime',
        'auto_resolvable' => 'boolean',
    ];

    // Relaciones
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    public function acknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUnresolved($query)
    {
        return $query->whereIn('status', ['active', 'acknowledged']);
    }

    public function scopeBySeverity($query, string $severity)
    {
        return $query->where('severity', $severity);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeHigh($query)
    {
        return $query->where('severity', 'high');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    // Accessors
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'low_stock' => 'Stock Bajo',
            'out_of_stock' => 'Sin Stock',
            'critical_low' => 'Stock Crítico',
            'reorder_point' => 'Punto de Reorden',
            'excess_stock' => 'Exceso de Stock',
            'movement_anomaly' => 'Movimiento Anómalo',
            default => 'Desconocido'
        };
    }

    public function getSeverityLabel(): string
    {
        return match($this->severity) {
            'low' => 'Baja',
            'medium' => 'Media',
            'high' => 'Alta',
            'critical' => 'Crítica',
            default => 'Desconocida'
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            'active' => 'Activa',
            'acknowledged' => 'Reconocida',
            'resolved' => 'Resuelta',
            'dismissed' => 'Descartada',
            default => 'Desconocido'
        };
    }

    public function getSeverityColor(): string
    {
        return match($this->severity) {
            'low' => 'info',
            'medium' => 'warning',
            'high' => 'danger',
            'critical' => 'danger',
            default => 'gray'
        };
    }

    public function getStatusColor(): string
    {
        return match($this->status) {
            'active' => 'danger',
            'acknowledged' => 'warning',
            'resolved' => 'success',
            'dismissed' => 'gray',
            default => 'gray'
        };
    }

    public function getAgeDays(): int
    {
        return $this->triggered_at->diffInDays(now());
    }

    public function getAgeHours(): int
    {
        return $this->triggered_at->diffInHours(now());
    }

    // Accessors for attributes
    public function getTypeLabelAttribute(): string
    {
        return $this->getTypeLabel();
    }

    public function getSeverityLabelAttribute(): string
    {
        return $this->getSeverityLabel();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->getStatusLabel();
    }

    public function getSeverityColorAttribute(): string
    {
        return $this->getSeverityColor();
    }

    public function getStatusColorAttribute(): string
    {
        return $this->getStatusColor();
    }

    public function getAgeDaysAttribute(): int
    {
        return $this->getAgeDays();
    }

    public function getAgeHoursAttribute(): int
    {
        return $this->getAgeHours();
    }

    // Business Methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function isAcknowledged(): bool
    {
        return $this->status === 'acknowledged';
    }

    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function acknowledge(?int $userId = null): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        return $this->update([
            'status' => 'acknowledged',
            'acknowledged_at' => now(),
            'acknowledged_by' => $userId ?? auth()->id(),
        ]);
    }

    public function resolve(?int $userId = null): bool
    {
        if (!in_array($this->status, ['active', 'acknowledged'])) {
            return false;
        }

        return $this->update([
            'status' => 'resolved',
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function dismiss(?int $userId = null): bool
    {
        if (!in_array($this->status, ['active', 'acknowledged'])) {
            return false;
        }

        return $this->update([
            'status' => 'dismissed',
            'resolved_at' => now(),
            'resolved_by' => $userId ?? auth()->id(),
        ]);
    }

    public function shouldAutoResolve(): bool
    {
        if (!$this->auto_resolvable || $this->isResolved()) {
            return false;
        }

        // Check if the condition that triggered the alert is still valid
        $currentStock = $this->stockable->stock;

        return match($this->type) {
            'low_stock' => $currentStock > ($this->min_stock ?? 0),
            'out_of_stock' => $currentStock > 0,
            'critical_low' => $currentStock > ($this->threshold_value ?? 0),
            'reorder_point' => $currentStock > ($this->threshold_value ?? 0),
            default => false
        };
    }
}