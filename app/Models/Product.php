<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\StockManagement;
use App\Services\FinishingCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes, StockManagement;

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
        'is_public',
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
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($product) {
            // Agregar acabados a la descripción si están cargados
            $product->appendFinishingsToDescription();
        });
    }

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

    /**
     * Obtener URL de imagen 1
     */
    public function getImage1Url(): ?string
    {
        return $this->image_1 ? \Storage::disk('r2')->url($this->image_1) : null;
    }

    /**
     * Obtener URL de imagen 2
     */
    public function getImage2Url(): ?string
    {
        return $this->image_2 ? \Storage::disk('r2')->url($this->image_2) : null;
    }

    /**
     * Obtener URL de imagen 3
     */
    public function getImage3Url(): ?string
    {
        return $this->image_3 ? \Storage::disk('r2')->url($this->image_3) : null;
    }

    /**
     * Obtener URL de la primera imagen disponible
     */
    public function getPrimaryImageUrl(): ?string
    {
        if ($this->image_1) return $this->getImage1Url();
        if ($this->image_2) return $this->getImage2Url();
        if ($this->image_3) return $this->getImage3Url();
        return null;
    }

    /**
     * Obtener array de URLs de todas las imágenes
     */
    public function getImageUrls(): array
    {
        return array_filter([
            $this->getImage1Url(),
            $this->getImage2Url(),
            $this->getImage3Url(),
        ]);
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

    /**
     * Agregar nombres de acabados a la descripción si existen
     */
    protected function appendFinishingsToDescription(): void
    {
        // Solo agregar acabados si la relación está cargada y tiene items
        if (! $this->relationLoaded('finishings') || $this->finishings->isEmpty()) {
            return;
        }

        // Si no hay descripción, no hacer nada
        if (empty($this->description)) {
            return;
        }

        // Extraer descripción base (antes de "acabados:")
        $baseDescription = $this->extractBaseDescription($this->description);

        // Obtener nombres de acabados
        $finishingNames = $this->finishings->pluck('name')->toArray();

        // Reconstruir descripción con acabados
        $this->description = trim($baseDescription.' acabados: '.implode(', ', $finishingNames));
    }

    /**
     * Extraer descripción base (antes de "acabados:")
     */
    protected function extractBaseDescription(?string $fullDescription): string
    {
        if (! $fullDescription) {
            return '';
        }

        // Buscar "acabados:" y extraer todo lo anterior
        $pos = strpos($fullDescription, ' acabados:');
        if ($pos !== false) {
            return trim(substr($fullDescription, 0, $pos));
        }

        return $fullDescription;
    }
}
