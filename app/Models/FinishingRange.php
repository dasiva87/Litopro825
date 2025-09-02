<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinishingRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'finishing_id',
        'min_quantity',
        'max_quantity',
        'range_price',
        'sort_order',
    ];

    protected $casts = [
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'range_price' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Relación con Finishing
     */
    public function finishing(): BelongsTo
    {
        return $this->belongsTo(Finishing::class);
    }

    /**
     * Scope para ordenar por sort_order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('min_quantity');
    }

    /**
     * Verificar si una cantidad está en este rango
     */
    public function containsQuantity(int $quantity): bool
    {
        if ($this->max_quantity === null) {
            return $quantity >= $this->min_quantity;
        }

        return $quantity >= $this->min_quantity && $quantity <= $this->max_quantity;
    }

    /**
     * Obtener descripción del rango
     */
    public function getRangeDescriptionAttribute(): string
    {
        if ($this->max_quantity === null) {
            return "{$this->min_quantity}+";
        }

        if ($this->min_quantity === $this->max_quantity) {
            return (string) $this->min_quantity;
        }

        return "{$this->min_quantity} - {$this->max_quantity}";
    }
}
