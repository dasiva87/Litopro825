<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;

class PrintingMachine extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'name',
        'type',
        'max_width',
        'max_height',
        'max_colors',
        'cost_per_impression',
        'costo_ctp',
        'setup_cost',
        'is_own',
        'supplier_id',
        'is_active',
        'is_public',
    ];

    protected $casts = [
        'max_width' => 'decimal:2',
        'max_height' => 'decimal:2',
        'max_colors' => 'integer',
        'cost_per_impression' => 'decimal:4',
        'costo_ctp' => 'decimal:2',
        'setup_cost' => 'decimal:2',
        'is_own' => 'boolean',
        'is_active' => 'boolean',
        'is_public' => 'boolean',
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOwn($query)
    {
        return $query->where('is_own', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeFromSupplier($query)
    {
        return $query->where('is_own', false);
    }

    // Business methods
    public function canPrint($width, $height, $colors): bool
    {
        return $this->max_width >= $width && 
               $this->max_height >= $height && 
               $this->max_colors >= $colors;
    }

    public function calculateCostForQuantity(int $quantity): float
    {
        // El costo está almacenado por millar, calculamos proporcionalmente
        return ($quantity / 1000) * $this->cost_per_impression;
    }

    public function getCostPerUnit(): float
    {
        // Devuelve el costo por unidad individual
        return $this->cost_per_impression / 1000;
    }

    // Accessors
    public function getMaxAreaAttribute(): float
    {
        return ($this->max_width && $this->max_height) ? $this->max_width * $this->max_height : 0;
    }

    public function getCostPerThousandAttribute(): float
    {
        // Alias para claridad - el costo ya está por millar
        return $this->cost_per_impression;
    }
}