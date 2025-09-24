<?php

namespace App\Services;

use App\Models\StockMovement;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Registrar un movimiento de stock
     */
    public function recordMovement(
        Model $stockable,
        string $type,
        string $reason,
        int $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $batchNumber = null,
        ?string $notes = null,
        ?array $metadata = null
    ): StockMovement {
        return DB::transaction(function () use (
            $stockable, $type, $reason, $quantity, $unitCost,
            $referenceType, $referenceId, $batchNumber, $notes, $metadata
        ) {
            // Obtener stock anterior
            $previousStock = $stockable->stock;

            // Calcular nuevo stock
            $stockChange = match($type) {
                'in' => $quantity,
                'out' => -$quantity,
                'adjustment' => $quantity, // quantity puede ser positivo o negativo
                default => 0
            };

            $newStock = $previousStock + $stockChange;

            // Calcular costo total si se proporciona costo unitario
            $totalCost = $unitCost ? $unitCost * abs($quantity) : null;

            // Crear el movimiento
            $movement = StockMovement::create([
                'company_id' => $stockable->company_id,
                'stockable_type' => get_class($stockable),
                'stockable_id' => $stockable->id,
                'type' => $type,
                'reason' => $reason,
                'quantity' => abs($quantity),
                'previous_stock' => $previousStock,
                'new_stock' => $newStock,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'batch_number' => $batchNumber,
                'notes' => $notes,
                'metadata' => $metadata,
                'user_id' => Auth::id(),
            ]);

            // Actualizar el stock del item
            $stockable->update(['stock' => $newStock]);

            return $movement;
        });
    }

    /**
     * Registrar entrada de stock (compras, devoluciones, etc.)
     */
    public function recordInbound(
        Model $stockable,
        string $reason,
        int $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $batchNumber = null,
        ?string $notes = null,
        ?array $metadata = null
    ): StockMovement {
        return $this->recordMovement(
            $stockable, 'in', $reason, $quantity, $unitCost,
            $referenceType, $referenceId, $batchNumber, $notes, $metadata
        );
    }

    /**
     * Registrar salida de stock (ventas, consumos, etc.)
     */
    public function recordOutbound(
        Model $stockable,
        string $reason,
        int $quantity,
        ?float $unitCost = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $batchNumber = null,
        ?string $notes = null,
        ?array $metadata = null
    ): StockMovement {
        return $this->recordMovement(
            $stockable, 'out', $reason, $quantity, $unitCost,
            $referenceType, $referenceId, $batchNumber, $notes, $metadata
        );
    }

    /**
     * Registrar ajuste de stock (correcciones, inventarios, etc.)
     */
    public function recordAdjustment(
        Model $stockable,
        int $newStock,
        string $reason = 'adjustment',
        ?string $notes = null,
        ?array $metadata = null
    ): StockMovement {
        $currentStock = $stockable->stock;
        $adjustment = $newStock - $currentStock;

        return $this->recordMovement(
            $stockable, 'adjustment', $reason, $adjustment, null,
            null, null, null, $notes, $metadata
        );
    }

    /**
     * Registrar movimiento de venta
     */
    public function recordSale(
        Model $stockable,
        int $quantity,
        ?int $documentId = null,
        ?string $notes = null
    ): StockMovement {
        return $this->recordOutbound(
            $stockable,
            'sale',
            $quantity,
            null,
            'App\Models\Document',
            $documentId,
            null,
            $notes
        );
    }

    /**
     * Registrar movimiento de compra
     */
    public function recordPurchase(
        Model $stockable,
        int $quantity,
        ?float $unitCost = null,
        ?int $purchaseId = null,
        ?string $batchNumber = null,
        ?string $notes = null
    ): StockMovement {
        return $this->recordInbound(
            $stockable,
            'purchase',
            $quantity,
            $unitCost,
            'App\Models\Purchase',
            $purchaseId,
            $batchNumber,
            $notes
        );
    }

    /**
     * Registrar stock inicial
     */
    public function recordInitialStock(
        Model $stockable,
        int $quantity,
        ?float $unitCost = null,
        ?string $notes = null
    ): StockMovement {
        return $this->recordInbound(
            $stockable,
            'initial_stock',
            $quantity,
            $unitCost,
            null,
            null,
            null,
            $notes
        );
    }

    /**
     * Obtener resumen de movimientos por período
     */
    public function getMovementSummary(
        Model $stockable,
        string $startDate,
        string $endDate
    ): array {
        $movements = $stockable->stockMovements()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $summary = [
            'total_movements' => $movements->count(),
            'total_inbound' => $movements->where('type', 'in')->sum('quantity'),
            'total_outbound' => $movements->where('type', 'out')->sum('quantity'),
            'total_adjustments' => $movements->where('type', 'adjustment')->count(),
            'net_change' => 0,
            'by_reason' => [],
            'by_type' => [
                'in' => $movements->where('type', 'in')->count(),
                'out' => $movements->where('type', 'out')->count(),
                'adjustment' => $movements->where('type', 'adjustment')->count(),
            ]
        ];

        // Calcular cambio neto
        $inbound = $movements->where('type', 'in')->sum('quantity');
        $outbound = $movements->where('type', 'out')->sum('quantity');
        $adjustments = $movements->where('type', 'adjustment')->sum(function ($movement) {
            return $movement->new_stock - $movement->previous_stock;
        });

        $summary['net_change'] = $inbound - $outbound + $adjustments;

        // Agrupar por razón
        $summary['by_reason'] = $movements->groupBy('reason')->map(function ($items, $reason) {
            return [
                'count' => $items->count(),
                'total_quantity' => $items->sum('quantity'),
                'total_cost' => $items->sum('total_cost'),
            ];
        })->toArray();

        return $summary;
    }

    /**
     * Validar si el movimiento es válido
     */
    public function validateMovement(
        Model $stockable,
        string $type,
        int $quantity
    ): array {
        $errors = [];

        // Validar cantidad
        if ($quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }

        // Validar stock suficiente para salidas
        if ($type === 'out' && !$stockable->hasStock($quantity)) {
            $errors[] = "Stock insuficiente. Disponible: {$stockable->stock}, Requerido: {$quantity}";
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}