<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class Paper extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'code',
        'name',
        'weight',
        'width',
        'height',
        'cost_per_sheet',
        'price',
        'stock',
        'is_own',
        'supplier_id',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'integer',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'cost_per_sheet' => 'decimal:4',
        'price' => 'decimal:4',
        'stock' => 'integer',
        'is_own' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_id');
    }

    public function documentItems(): HasMany
    {
        return $this->hasMany(DocumentItem::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOwn($query)
    {
        return $query->where('is_own', true);
    }

    public function scopeFromSupplier($query)
    {
        return $query->where('is_own', false);
    }

    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }

    public function scopeLowStock($query, int $threshold = 10)
    {
        return $query->where('stock', '<=', $threshold)->where('stock', '>', 0);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        $name = $this->code . ' - ' . $this->name;
        return $name . ($this->weight ? " {$this->weight}gr" : '');
    }

    public function getAreaAttribute(): float
    {
        return ($this->width && $this->height) ? $this->width * $this->height : 0;
    }

    public function getMarginAttribute(): float
    {
        return $this->cost_per_sheet > 0 ? 
            (($this->price - $this->cost_per_sheet) / $this->cost_per_sheet) * 100 : 0;
    }

    public function getStockStatusAttribute(): string
    {
        if ($this->stock <= 0) return 'out';
        if ($this->stock <= 10) return 'low';
        if ($this->stock <= 50) return 'medium';
        return 'high';
    }

    // Business methods
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    public function isLowStock(int $threshold = 10): bool
    {
        return $this->stock <= $threshold && $this->stock > 0;
    }
}