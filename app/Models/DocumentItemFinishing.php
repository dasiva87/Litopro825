<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentItemFinishing extends Model
{
    protected $fillable = [
        'document_item_id',
        'finishing_name',
        'quantity',
        'is_double_sided',
        'unit_price',
        'total_price',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'is_double_sided' => 'boolean',
        'unit_price' => 'decimal:4',
        'total_price' => 'decimal:2',
    ];

    public function documentItem(): BelongsTo
    {
        return $this->belongsTo(DocumentItem::class);
    }
}
