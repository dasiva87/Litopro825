<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MagazinePage extends Model
{
    use HasFactory;

    protected $fillable = [
        'magazine_item_id',
        'simple_item_id',
        'page_type',
        'page_order',
        'page_quantity',
        'page_notes',
    ];

    protected $casts = [
        'page_order' => 'integer',
        'page_quantity' => 'integer',
    ];

    // Relaciones
    public function magazineItem(): BelongsTo
    {
        return $this->belongsTo(MagazineItem::class);
    }

    public function simpleItem(): BelongsTo
    {
        return $this->belongsTo(SimpleItem::class);
    }

    // Accessors
    public function getPageTypeNameAttribute(): string
    {
        $names = [
            'portada' => 'Portada',
            'contraportada' => 'Contraportada',
            'interior' => 'Interior',
            'inserto' => 'Inserto',
            'separador' => 'Separador',
            'anexo' => 'Anexo',
        ];

        return $names[$this->page_type] ?? ucfirst($this->page_type);
    }

    public function getTotalCostAttribute(): float
    {
        if (!$this->simpleItem) {
            return 0;
        }

        return $this->simpleItem->final_price * $this->page_quantity;
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('page_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('page_order');
    }
}