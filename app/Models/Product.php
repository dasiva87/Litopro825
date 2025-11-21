<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\StockManagement;
use App\Services\FinishingCalculatorService;

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
        'image_1',
        'image_2',
        'image_3',
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

    public function finishings(): BelongsToMany
    {
        return $this->belongsToMany(Finishing::class, 'product_finishing')
            ->withPivot(['quantity', 'width', 'height', 'calculated_cost'])
            ->withTimestamps();
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

    /**
     * Obtener todas las imágenes del producto como array
     */
    public function getImagesAttribute(): array
    {
        return array_filter([
            $this->image_1,
            $this->image_2,
            $this->image_3,
        ]);
    }

    /**
     * Obtener la primera imagen o una imagen por defecto
     */
    public function getPrimaryImageAttribute(): ?string
    {
        return $this->image_1 ?? $this->image_2 ?? $this->image_3 ?? null;
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

    // Métodos para acabados

    /**
     * Calcular el costo total de todos los acabados
     */
    public function calculateFinishingsCost(): float
    {
        $total = 0.0;

        foreach ($this->finishings as $finishing) {
            $total += (float) $finishing->pivot->calculated_cost;
        }

        return $total;
    }

    /**
     * Agregar un acabado calculando su costo automáticamente
     */
    public function addFinishing(Finishing $finishing, array $params): void
    {
        $calculatorService = app(FinishingCalculatorService::class);
        $calculatedCost = $calculatorService->calculateCost($finishing, $params);

        $pivotData = [
            'calculated_cost' => $calculatedCost,
        ];

        // Agregar parámetros específicos según el tipo de medida
        if (isset($params['quantity'])) {
            $pivotData['quantity'] = $params['quantity'];
        }
        if (isset($params['width'])) {
            $pivotData['width'] = $params['width'];
        }
        if (isset($params['height'])) {
            $pivotData['height'] = $params['height'];
        }

        // Verificar si ya existe este acabado para este producto
        $existingFinishing = $this->finishings()->where('finishing_id', $finishing->id)->first();

        if ($existingFinishing) {
            // Si existe, actualizar la cantidad y el costo
            $newQuantity = $existingFinishing->pivot->quantity + ($params['quantity'] ?? 1);
            $newCalculatedCost = $calculatorService->calculateCost($finishing, array_merge($params, ['quantity' => $newQuantity]));

            $updateData = array_merge($pivotData, [
                'quantity' => $newQuantity,
                'calculated_cost' => $newCalculatedCost,
            ]);

            $this->finishings()->updateExistingPivot($finishing->id, $updateData);
        } else {
            // Si no existe, crear nuevo registro
            $this->finishings()->attach($finishing->id, $pivotData);
        }
    }

    /**
     * Verificar si tiene acabados
     */
    public function hasFinishings(): bool
    {
        return $this->finishings()->exists();
    }
}