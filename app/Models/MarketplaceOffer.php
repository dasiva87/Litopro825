<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class MarketplaceOffer extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'supplier_contact_id',
        'product_type',
        'product_name',
        'description',
        'specifications',
        'unit_price',
        'currency',
        'minimum_quantity',
        'available_stock',
        'unit_measure',
        'delivery_time_days',
        'delivery_locations',
        'payment_terms',
        'discount_rules',
        'is_active',
        'is_featured',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'specifications' => 'array',
        'unit_price' => 'decimal:2',
        'minimum_quantity' => 'integer',
        'available_stock' => 'integer',
        'delivery_time_days' => 'integer',
        'delivery_locations' => 'array',
        'payment_terms' => 'array',
        'discount_rules' => 'array',
        'metadata' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'expires_at' => 'datetime',
    ];

    // Product types
    const TYPE_PAPER = 'paper';
    const TYPE_INK = 'ink';
    const TYPE_FINISHING = 'finishing';
    const TYPE_EQUIPMENT = 'equipment';
    const TYPE_CONSUMABLES = 'consumables';
    const TYPE_SERVICES = 'services';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_contact_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeByProductType($query, string $type)
    {
        return $query->where('product_type', $type);
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    public function scopeInStock($query)
    {
        return $query->where('available_stock', '>', 0);
    }

    public function scopeByPriceRange($query, float $minPrice = null, float $maxPrice = null)
    {
        if ($minPrice) {
            $query->where('unit_price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('unit_price', '<=', $maxPrice);
        }
        return $query;
    }

    public function calculateTotalPrice(int $quantity): float
    {
        $basePrice = $this->unit_price * $quantity;
        
        // Apply discount rules if any
        if ($this->discount_rules && is_array($this->discount_rules)) {
            foreach ($this->discount_rules as $rule) {
                if ($quantity >= $rule['min_quantity']) {
                    $discountPercent = $rule['discount_percent'] ?? 0;
                    $basePrice = $basePrice * (1 - $discountPercent / 100);
                }
            }
        }
        
        return round($basePrice, 2);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function hasMinimumStock(int $requestedQuantity): bool
    {
        return $this->available_stock >= $requestedQuantity;
    }

    public function meetsMinimumOrder(int $requestedQuantity): bool
    {
        return $requestedQuantity >= $this->minimum_quantity;
    }

    public function getProductTypeLabel(): string
    {
        return match($this->product_type) {
            self::TYPE_PAPER => 'Papel',
            self::TYPE_INK => 'Tintas',
            self::TYPE_FINISHING => 'Acabados',
            self::TYPE_EQUIPMENT => 'Equipos',
            self::TYPE_CONSUMABLES => 'Consumibles',
            self::TYPE_SERVICES => 'Servicios',
            default => 'Producto',
        };
    }

    public static function getProductTypes(): array
    {
        return [
            self::TYPE_PAPER => 'Papel',
            self::TYPE_INK => 'Tintas',
            self::TYPE_FINISHING => 'Acabados',
            self::TYPE_EQUIPMENT => 'Equipos',
            self::TYPE_CONSUMABLES => 'Consumibles',
            self::TYPE_SERVICES => 'Servicios',
        ];
    }
}