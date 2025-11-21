<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Representa un proceso individual en una orden de producción
 * (Puede ser impresión o acabado)
 *
 * Este modelo mapea directamente a la tabla pivot document_item_production_order
 * pero se usa como un modelo normal para evitar problemas de deduplicación
 */
class ProductionOrderProcess extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'document_item_production_order';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'production_order_id',
        'document_item_id',
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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'finishing_quantity' => 'decimal:2',
        'finishing_width' => 'decimal:2',
        'finishing_height' => 'decimal:2',
        'quantity_to_produce' => 'integer',
        'sheets_needed' => 'integer',
        'total_impressions' => 'decimal:2',
        'ink_front_count' => 'integer',
        'ink_back_count' => 'integer',
        'front_back_plate' => 'boolean',
        'horizontal_size' => 'decimal:2',
        'vertical_size' => 'decimal:2',
        'produced_quantity' => 'integer',
        'rejected_quantity' => 'integer',
        'actual_impressions' => 'decimal:2',
        'production_started_at' => 'datetime',
        'production_completed_at' => 'datetime',
    ];

    /**
     * Get the production order that owns this process.
     */
    public function productionOrder(): BelongsTo
    {
        return $this->belongsTo(ProductionOrder::class);
    }

    /**
     * Get the document item associated with this process.
     */
    public function documentItem(): BelongsTo
    {
        return $this->belongsTo(DocumentItem::class);
    }

    /**
     * Get the paper used (if applicable).
     */
    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    /**
     * Scope to filter by process type.
     */
    public function scopePrinting($query)
    {
        return $query->where('process_type', 'printing');
    }

    /**
     * Scope to filter by finishing processes.
     */
    public function scopeFinishing($query)
    {
        return $query->where('process_type', 'finishing');
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('item_status', $status);
    }
}
