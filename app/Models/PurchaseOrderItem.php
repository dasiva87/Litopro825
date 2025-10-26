<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    protected $table = 'document_item_purchase_order';

    protected $fillable = [
        'document_item_id',
        'purchase_order_id',
        'paper_id',
        'paper_description',
        'quantity_ordered',
        'sheets_quantity',
        'cut_width',
        'cut_height',
        'unit_price',
        'total_price',
        'status',
        'notes',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:2',
        'sheets_quantity' => 'integer',
        'cut_width' => 'decimal:2',
        'cut_height' => 'decimal:2',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
    ];

    public function documentItem(): BelongsTo
    {
        return $this->belongsTo(DocumentItem::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    /**
     * Obtener el nombre del papel para mostrar
     */
    public function getPaperNameAttribute(): string
    {
        // Prioridad 1: Descripción directa del papel (caso revistas con múltiples papeles)
        if ($this->paper_description) {
            return $this->paper_description;
        }

        // Prioridad 2: Relación directa con Paper
        if ($this->paper_id) {
            $paper = $this->paper;
            if ($paper) {
                return $paper->name;
            }
        }

        // Prioridad 3: Acceder a través de documentItem->itemable
        if (!$this->documentItem) {
            return 'Sin descripción';
        }

        // Cargar itemable si no está cargado
        if (!$this->documentItem->relationLoaded('itemable')) {
            $this->documentItem->load('itemable');
        }

        $itemable = $this->documentItem->itemable;

        if (!$itemable) {
            return $this->documentItem->description ?? 'Sin descripción';
        }

        // Caso SimpleItem (papel simple)
        if ($itemable instanceof \App\Models\SimpleItem) {
            // Cargar paper si no está cargado
            if (!$itemable->relationLoaded('paper')) {
                $itemable->load('paper');
            }

            if ($itemable->paper) {
                return $itemable->paper->name . " ({$itemable->horizontal_size}x{$itemable->vertical_size}cm)";
            }
        }

        // Caso Product (producto)
        if ($itemable instanceof \App\Models\Product) {
            return $itemable->name ?? $this->documentItem->description;
        }

        // Caso TalonarioItem (talonario)
        if ($itemable instanceof \App\Models\TalonarioItem) {
            // TalonarioItem tiene hojas (sheets) que contienen SimpleItems
            // Retornar descripción del talonario
            return $itemable->description ?? $this->documentItem->description ?? 'Talonario';
        }

        // Caso MagazineItem u otros
        return $this->documentItem->description ?? 'Sin descripción';
    }

    /**
     * Obtener el tamaño de corte formateado
     */
    public function getCutSizeAttribute(): ?string
    {
        if (!$this->cut_width || !$this->cut_height) {
            return null;
        }

        return number_format($this->cut_width, 1) . ' x ' . number_format($this->cut_height, 1) . ' cm';
    }
}
