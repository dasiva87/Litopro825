<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaperOrderItem extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'paper_order_id',
        'paper_id',
        'marketplace_offer_id',
        'description',
        'quantity',
        'unit_measure',
        'unit_price',
        'total_price',
        'specifications',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'specifications' => 'array',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function paperOrder(): BelongsTo
    {
        return $this->belongsTo(PaperOrder::class);
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function marketplaceOffer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceOffer::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            $item->total_price = $item->quantity * $item->unit_price;
        });

        static::saved(function ($item) {
            // Recalculate order totals when item is saved
            $item->paperOrder->calculateTotals();
        });

        static::deleted(function ($item) {
            // Recalculate order totals when item is deleted
            $item->paperOrder->calculateTotals();
        });
    }
}