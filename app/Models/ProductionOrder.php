<?php

namespace App\Models;

use App\Enums\ProductionStatus;
use App\Models\Concerns\BelongsToTenant;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ProductionOrder extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'production_number',
        'supplier_id',
        'supplier_company_id',
        'operator_user_id',
        'status',
        'scheduled_date',
        'started_at',
        'completed_at',
        'total_impressions',
        'total_items',
        'estimated_hours',
        'notes',
        'operator_notes',
        'quality_checked',
        'quality_checked_by',
        'quality_checked_at',
        'email_sent_at',
        'email_sent_by',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'quality_checked_at' => 'datetime',
        'total_impressions' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'quality_checked' => 'boolean',
        'status' => ProductionStatus::class,
        'email_sent_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ProductionOrder $order) {
            if (!$order->company_id) {
                $order->company_id = config('app.current_tenant_id') ?? (auth()->check() ? auth()->user()->company_id : null);
            }

            if (!$order->production_number) {
                $order->production_number = static::generateProductionNumber();
            }
        });

        static::updated(function (ProductionOrder $order) {
            // Auto-update timestamps based on status
            if ($order->isDirty('status')) {
                if ($order->status === ProductionStatus::IN_PROGRESS && !$order->started_at) {
                    $order->started_at = now();
                    $order->saveQuietly();
                } elseif ($order->status === ProductionStatus::COMPLETED && !$order->completed_at) {
                    $order->completed_at = now();
                    $order->saveQuietly();
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function supplierCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'supplier_company_id');
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operator_user_id');
    }

    public function qualityCheckedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'quality_checked_by');
    }

    public function emailSentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'email_sent_by');
    }

    public function documentItems(): BelongsToMany
    {
        return $this->belongsToMany(DocumentItem::class, 'document_item_production_order')
            ->using(\App\Models\DocumentItemProductionOrder::class)
            ->withPivot([
                'id', // ← IMPORTANTE: Incluir el ID del pivot para evitar deduplicación
                'document_item_finishing_id',
                'process_type',
                'finishing_name',
                'process_description',
                'finishing_quantity',
                'finishing_width',
                'finishing_height',
                'finishing_unit',
                'quantity_to_produce',
                'sheets_needed',
                'total_impressions',
                'ink_front_count',
                'ink_back_count',
                'front_back_plate',
                'paper_id',
                'horizontal_size',
                'vertical_size',
                'produced_quantity',
                'rejected_quantity',
                'item_status',
                'production_started_at',
                'production_completed_at',
                'actual_impressions',
                'production_notes',
                'quality_notes',
            ])
            ->withTimestamps();
    }

    /**
     * Relación HasMany a la tabla pivot (para evitar deduplicación en Filament)
     * Usa esto en lugar de documentItems() para mostrar todos los procesos
     */
    public function productionProcesses(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\ProductionOrderProcess::class, 'production_order_id');
    }

    /**
     * Calculate total impressions from all items
     */
    public function recalculateMetrics(): void
    {
        $items = DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->select(
                DB::raw('SUM(total_impressions) as total_impressions'),
                DB::raw('COUNT(*) as total_items')
            )
            ->first();

        $this->total_impressions = $items->total_impressions ?? 0;
        $this->total_items = $items->total_items ?? 0;

        // Note: Estimated hours should be set manually or based on supplier estimates
        // No automatic calculation since we don't have machine capacity data

        $this->save();
    }

    /**
     * Generate consecutive production number with gap filling
     */
    public static function generateProductionNumber(): string
    {
        return DB::transaction(function () {
            $companyId = TenantContext::id() ?? 1;

            // Obtener todos los números existentes con bloqueo
            $existingNumbers = DB::table('production_orders')
                ->where('company_id', $companyId)
                ->whereRaw('production_number REGEXP "^[0-9]{4}$"')
                ->lockForUpdate()
                ->pluck('production_number')
                ->map(fn($num) => (int)$num)
                ->sort()
                ->values()
                ->toArray();

            // Si no hay números existentes, empezar en 1
            if (empty($existingNumbers)) {
                return '0001';
            }

            // Buscar gaps en la secuencia
            $minNumber = min($existingNumbers);
            $maxNumber = max($existingNumbers);
            $nextNumber = null;
            
            // Buscar gap en el rango existente
            for ($i = $minNumber; $i <= $maxNumber; $i++) {
                if (!in_array($i, $existingNumbers)) {
                    $nextNumber = $i;
                    break;
                }
            }
            
            // Si no hay gaps, usar el siguiente después del máximo
            if ($nextNumber === null) {
                $nextNumber = $maxNumber + 1;
            }

            return str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
        });
    }

    /**
     * Change status with validation
     */
    public function changeStatus(ProductionStatus $newStatus, ?string $notes = null): bool
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            return false;
        }

        $this->status = $newStatus;

        if ($notes) {
            $this->notes = ($this->notes ? $this->notes."\n\n" : '')."[".now()->format('Y-m-d H:i')."] {$notes}";
        }

        $this->save();

        return true;
    }

    /**
     * Check if production is in active state
     */
    public function isActive(): bool
    {
        return in_array($this->status, [
            ProductionStatus::SENT,
            ProductionStatus::RECEIVED,
            ProductionStatus::IN_PROGRESS,
            ProductionStatus::ON_HOLD,
        ]);
    }

    /**
     * Check if production can be edited
     */
    public function canBeEdited(): bool
    {
        return in_array($this->status, [
            ProductionStatus::DRAFT,
            ProductionStatus::SENT,
            ProductionStatus::RECEIVED,
        ]);
    }

    /**
     * Check if production can be started
     */
    public function canBeStarted(): bool
    {
        return in_array($this->status, [
            ProductionStatus::DRAFT,
            ProductionStatus::RECEIVED,
        ]);
    }

    /**
     * Check if production can be completed
     */
    public function canBeCompleted(): bool
    {
        return $this->status === ProductionStatus::IN_PROGRESS;
    }

    /**
     * Check if production can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return !in_array($this->status, [ProductionStatus::COMPLETED, ProductionStatus::CANCELLED]);
    }

    /**
     * Get production progress percentage
     */
    public function getProgressPercentage(): int
    {
        if ($this->total_items === 0) {
            return 0;
        }

        $completedItems = DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->where('item_status', 'completed')
            ->count();

        return (int) (($completedItems / $this->total_items) * 100);
    }

    /**
     * Add a single item to production order
     *
     * @param DocumentItem $documentItem
     * @param int|null $quantityToProduce
     * @param string $processType 'printing' or 'finishing'
     * @param string|null $finishingName
     * @param string|null $processDescription
     * @param array $finishingParameters Parámetros adicionales para acabados (document_item_finishing_id, quantity, width, height, unit)
     */
    public function addItem(
        DocumentItem $documentItem,
        ?int $quantityToProduce = null,
        string $processType = 'printing',
        ?string $finishingName = null,
        ?string $processDescription = null,
        array $finishingParameters = []
    ): bool {
        // Check if can be edited
        if (!$this->canBeEdited()) {
            return false;
        }

        // Para acabados, permitir múltiples entries del mismo document_item
        // Para impresión, verificar que no exista ya
        if ($processType === 'printing') {
            $exists = $this->documentItems()
                ->where('document_items.id', $documentItem->id)
                ->wherePivot('process_type', 'printing')
                ->exists();

            if ($exists) {
                return false;
            }
        }

        // Calculate production data
        $calculator = new \App\Services\ProductionCalculatorService();
        $validation = $calculator->canBeProduced($documentItem);

        if (!$validation['valid']) {
            return false;
        }

        $productionData = $calculator->calculateProductionData($documentItem, $quantityToProduce);

        // Add process-specific fields
        $productionData['process_type'] = $processType;
        $productionData['finishing_name'] = $finishingName;
        $productionData['process_description'] = $processDescription;

        // Add finishing parameters if provided
        if (!empty($finishingParameters)) {
            $productionData['document_item_finishing_id'] = $finishingParameters['document_item_finishing_id'] ?? null;
            $productionData['finishing_quantity'] = $finishingParameters['finishing_quantity'] ?? null;
            $productionData['finishing_width'] = $finishingParameters['finishing_width'] ?? null;
            $productionData['finishing_height'] = $finishingParameters['finishing_height'] ?? null;
            $productionData['finishing_unit'] = $finishingParameters['finishing_unit'] ?? null;
        }

        // Attach item with production data
        $this->documentItems()->attach($documentItem->id, $productionData);

        // Recalculate metrics
        $this->recalculateMetrics();

        return true;
    }

    /**
     * Add multiple items to production order
     */
    public function addItems(array $documentItems, array $quantities = []): array
    {
        $results = [
            'added' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($documentItems as $index => $documentItem) {
            if (!$documentItem instanceof DocumentItem) {
                $results['failed']++;
                $results['errors'][] = "Item en posición {$index} no es un DocumentItem válido";
                continue;
            }

            $quantity = $quantities[$documentItem->id] ?? null;

            if ($this->addItem($documentItem, $quantity)) {
                $results['added']++;
            } else {
                $results['failed']++;
                $results['errors'][] = "No se pudo agregar item ID {$documentItem->id}";
            }
        }

        return $results;
    }

    /**
     * Remove an item from production order
     */
    public function removeItem(DocumentItem $documentItem): bool
    {
        if (!$this->canBeEdited()) {
            return false;
        }

        $this->documentItems()->detach($documentItem->id);
        $this->recalculateMetrics();

        return true;
    }

    /**
     * Update item production status
     */
    public function updateItemStatus(DocumentItem $documentItem, string $status, ?array $additionalData = []): bool
    {
        if (!$this->documentItems()->where('document_items.id', $documentItem->id)->exists()) {
            return false;
        }

        $updateData = array_merge(['item_status' => $status], $additionalData);

        DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->where('document_item_id', $documentItem->id)
            ->update($updateData);

        return true;
    }

    /**
     * Get items grouped by status
     */
    public function getItemsByStatus(): array
    {
        $items = DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->select('item_status', DB::raw('COUNT(*) as count'))
            ->groupBy('item_status')
            ->get();

        return $items->pluck('count', 'item_status')->toArray();
    }

    /**
     * Check if all items are completed
     */
    public function allItemsCompleted(): bool
    {
        $totalItems = $this->total_items;
        if ($totalItems === 0) {
            return false;
        }

        $completedItems = DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->where('item_status', 'completed')
            ->count();

        return $completedItems === $totalItems;
    }

    /**
     * Get production efficiency (produced vs rejected)
     */
    public function getEfficiency(): array
    {
        $stats = DB::table('document_item_production_order')
            ->where('production_order_id', $this->id)
            ->select(
                DB::raw('SUM(produced_quantity) as total_produced'),
                DB::raw('SUM(rejected_quantity) as total_rejected'),
                DB::raw('SUM(quantity_to_produce) as total_expected')
            )
            ->first();

        $totalProduced = $stats->total_produced ?? 0;
        $totalRejected = $stats->total_rejected ?? 0;
        $totalExpected = $stats->total_expected ?? 0;

        $validProduced = $totalProduced - $totalRejected;
        $efficiency = $totalProduced > 0 ? ($validProduced / $totalProduced) * 100 : 0;

        return [
            'total_produced' => $totalProduced,
            'total_rejected' => $totalRejected,
            'valid_produced' => $validProduced,
            'total_expected' => $totalExpected,
            'efficiency_percentage' => round($efficiency, 2),
            'fulfillment_percentage' => $totalExpected > 0 ? round(($validProduced / $totalExpected) * 100, 2) : 0,
        ];
    }

    /**
     * Get the supplier name (from Company or Contact)
     */
    public function getSupplierNameAttribute(): string
    {
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->name;
        }

        if ($this->supplier_company_id && $this->supplierCompany) {
            return $this->supplierCompany->name;
        }

        return 'Sin proveedor';
    }

    /**
     * Get the supplier email (from Company or Contact)
     */
    public function getSupplierEmailAttribute(): ?string
    {
        if ($this->supplier_id && $this->supplier) {
            return $this->supplier->email;
        }

        if ($this->supplier_company_id && $this->supplierCompany) {
            return $this->supplierCompany->email;
        }

        return null;
    }
}
