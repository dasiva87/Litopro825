<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use App\Models\Concerns\BelongsToTenant;

class StockMovement extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'stockable_type',
        'stockable_id',
        'type',
        'reason',
        'quantity',
        'previous_stock',
        'new_stock',
        'unit_cost',
        'total_cost',
        'reference_type',
        'reference_id',
        'batch_number',
        'notes',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
        'unit_cost' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stockable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOfReason($query, string $reason)
    {
        return $query->where('reason', $reason);
    }

    public function scopeForStockable($query, $stockable)
    {
        return $query->where('stockable_type', get_class($stockable))
                    ->where('stockable_id', $stockable->id);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // Accessors
    public function getTypeLabel(): string
    {
        return match($this->type) {
            'in' => 'Entrada',
            'out' => 'Salida',
            'adjustment' => 'Ajuste',
            default => 'Desconocido'
        };
    }

    public function getReasonLabel(): string
    {
        return match($this->reason) {
            'initial_stock' => 'Stock Inicial',
            'purchase' => 'Compra',
            'sale' => 'Venta',
            'return' => 'Devolución',
            'damage' => 'Daño/Pérdida',
            'adjustment' => 'Ajuste Manual',
            'production' => 'Producción',
            'transfer' => 'Transferencia',
            default => 'Otro'
        };
    }

    public function getTypeColor(): string
    {
        return match($this->type) {
            'in' => 'success',
            'out' => 'danger',
            'adjustment' => 'warning',
            default => 'gray'
        };
    }

    public function getQuantityWithSign(): string
    {
        $sign = $this->type === 'in' ? '+' : '-';
        return $sign . $this->quantity;
    }

    // Accessors for attributes
    public function getTypeLabelAttribute(): string
    {
        return $this->getTypeLabel();
    }

    public function getReasonLabelAttribute(): string
    {
        return $this->getReasonLabel();
    }

    public function getTypeColorAttribute(): string
    {
        return $this->getTypeColor();
    }

    public function getQuantityWithSignAttribute(): string
    {
        return $this->getQuantityWithSign();
    }

    // Business Methods
    public function isInbound(): bool
    {
        return $this->type === 'in';
    }

    public function isOutbound(): bool
    {
        return $this->type === 'out';
    }

    public function isAdjustment(): bool
    {
        return $this->type === 'adjustment';
    }
}