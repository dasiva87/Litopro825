<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Relations\MorphMany;

trait StockManagement
{
    /**
     * Verificar si hay stock suficiente
     */
    public function hasStock(int $quantity): bool
    {
        return $this->stock >= $quantity;
    }

    /**
     * Reducir stock (para cuando se confirme una venta)
     */
    public function reduceStock(int $quantity, string $reason = 'sale', ?array $movementData = null): bool
    {
        if (!$this->hasStock($quantity)) {
            return false;
        }

        // Usar el servicio de movimientos si está disponible
        if (app()->bound(\App\Services\StockMovementService::class)) {
            $service = app(\App\Services\StockMovementService::class);

            $service->recordOutbound(
                $this,
                $reason,
                $quantity,
                $movementData['unit_cost'] ?? null,
                $movementData['reference_type'] ?? null,
                $movementData['reference_id'] ?? null,
                $movementData['batch_number'] ?? null,
                $movementData['notes'] ?? null,
                $movementData['metadata'] ?? null
            );
        } else {
            // Fallback: actualizar stock directamente
            $this->stock -= $quantity;
            $this->save();
        }

        return true;
    }

    /**
     * Aumentar stock (para devoluciones o nuevas compras)
     */
    public function increaseStock(int $quantity, string $reason = 'purchase', ?array $movementData = null): void
    {
        // Usar el servicio de movimientos si está disponible
        if (app()->bound(\App\Services\StockMovementService::class)) {
            $service = app(\App\Services\StockMovementService::class);

            $service->recordInbound(
                $this,
                $reason,
                $quantity,
                $movementData['unit_cost'] ?? null,
                $movementData['reference_type'] ?? null,
                $movementData['reference_id'] ?? null,
                $movementData['batch_number'] ?? null,
                $movementData['notes'] ?? null,
                $movementData['metadata'] ?? null
            );
        } else {
            // Fallback: actualizar stock directamente
            $this->stock += $quantity;
            $this->save();
        }
    }

    /**
     * Verificar si el stock está por debajo del mínimo
     */
    public function isLowStock(): bool
    {
        $minStock = $this->min_stock ?? 10; // Default 10 si no está definido
        return $this->stock <= $minStock && $this->stock > 0;
    }

    /**
     * Verificar si no hay stock
     */
    public function isOutOfStock(): bool
    {
        return $this->stock <= 0;
    }

    /**
     * Verificar si hay stock disponible
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Obtener el estado del stock
     */
    public function getStockStatus(): string
    {
        if ($this->isOutOfStock()) {
            return 'out_of_stock';
        }

        if ($this->isLowStock()) {
            return 'low_stock';
        }

        if ($this->stock <= 50) {
            return 'medium_stock';
        }

        return 'good_stock';
    }

    /**
     * Obtener la etiqueta del estado del stock
     */
    public function getStockStatusLabel(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'Sin Stock',
            'low_stock' => 'Stock Bajo',
            'medium_stock' => 'Stock Medio',
            'good_stock' => 'Stock Normal',
            default => 'Desconocido'
        };
    }

    /**
     * Obtener el color del estado del stock para UI
     */
    public function getStockStatusColor(): string
    {
        return match($this->getStockStatus()) {
            'out_of_stock' => 'danger',
            'low_stock' => 'warning',
            'medium_stock' => 'info',
            'good_stock' => 'success',
            default => 'gray'
        };
    }

    /**
     * Scope para elementos con stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    /**
     * Scope para elementos con stock bajo
     */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'min_stock')
                    ->where('stock', '>', 0);
    }

    /**
     * Scope para elementos sin stock
     */
    public function scopeOutOfStock($query)
    {
        return $query->where('stock', '<=', 0);
    }

    /**
     * Accessor para stock_status
     */
    public function getStockStatusAttribute(): string
    {
        return $this->getStockStatus();
    }

    /**
     * Accessor para stock_status_label
     */
    public function getStockStatusLabelAttribute(): string
    {
        return $this->getStockStatusLabel();
    }

    /**
     * Accessor para stock_status_color
     */
    public function getStockStatusColorAttribute(): string
    {
        return $this->getStockStatusColor();
    }

    /**
     * Relación polimórfica con movimientos de stock
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(\App\Models\StockMovement::class, 'stockable')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Obtener último movimiento de stock
     */
    public function getLastStockMovement()
    {
        return $this->stockMovements()->first();
    }

    /**
     * Obtener movimientos de stock en un rango de fechas
     */
    public function getStockMovementsInRange($startDate, $endDate)
    {
        return $this->stockMovements()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->get();
    }
}