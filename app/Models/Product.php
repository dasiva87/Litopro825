<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\StockManagement;

class Product extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant, StockManagement;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'code',
        'purchase_price',
        'sale_price',
        'is_own_product',
        'supplier_contact_id',
        'stock',
        'min_stock',
        'active',
        'metadata',
    ];

    protected $casts = [
        'purchase_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'is_own_product' => 'boolean',
        'stock' => 'integer',
        'min_stock' => 'integer',
        'active' => 'boolean',
        'metadata' => 'array',
    ];

    // Relaciones
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'supplier_contact_id');
    }

    public function documentItems(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'itemable');
    }

    // Métodos de negocio

    /**
     * Calcular precio total para una cantidad específica
     */
    public function calculateTotalPrice(int $quantity): float
    {
        return $this->sale_price * $quantity;
    }

    /**
     * Obtener el margen de ganancia
     */
    public function getProfitMargin(): float
    {
        if ($this->purchase_price == 0) {
            return 100; // Si no hay precio de compra, asumimos 100% ganancia
        }

        return (($this->sale_price - $this->purchase_price) / $this->purchase_price) * 100;
    }

    /**
     * Obtener la ganancia por unidad
     */
    public function getProfitPerUnit(): float
    {
        return $this->sale_price - $this->purchase_price;
    }

    // Accessors

    public function getSupplierTypeAttribute(): string
    {
        return $this->is_own_product ? 'Producto Propio' : 'Producto de Terceros';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOwnProducts($query)
    {
        return $query->where('is_own_product', true);
    }

    public function scopeThirdPartyProducts($query)
    {
        return $query->where('is_own_product', false);
    }
}