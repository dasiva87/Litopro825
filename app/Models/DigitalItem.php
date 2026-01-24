<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Services\FinishingCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class DigitalItem extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'description',
        'sale_price',
        'is_own_product',
        'supplier_contact_id',
        'pricing_type',
        'metadata',
        'active',
        'is_public',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'is_own_product' => 'boolean',
        'active' => 'boolean',
        'is_public' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Establecer valores por defecto para campos requeridos
            if (empty($item->description)) {
                $item->description = 'Item digital';
            }
        });

        static::saving(function ($item) {
            // Agregar acabados a la descripción si están cargados
            $item->appendFinishingsToDescription();
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
        return $this->belongsToMany(Finishing::class, 'digital_item_finishing')
            ->withPivot(['quantity', 'width', 'height', 'calculated_cost'])
            ->withTimestamps();
    }

    // Métodos de negocio

    /**
     * Calcular precio total por unidad
     * Formula: cantidad × sale_price
     */
    public function calculateByUnit(int $quantity): float
    {
        return $quantity * $this->sale_price;
    }

    /**
     * Calcular precio total por tamaño (área)
     * Formula: (ancho_cm / 100) × (alto_cm / 100) × sale_price × cantidad
     * Convierte centímetros a metros para el cálculo
     */
    public function calculateBySize(float $width, float $height, int $quantity = 1): float
    {
        // Convertir centímetros a metros
        $widthM = $width / 100;
        $heightM = $height / 100;
        $areaM2 = $widthM * $heightM;

        return $areaM2 * $this->sale_price * $quantity;
    }

    /**
     * Calcular precio total según el tipo y parámetros
     */
    public function calculateTotalPrice(array $params): float
    {
        $quantity = (int) ($params['quantity'] ?? 1);

        if ($this->pricing_type === 'unit') {
            return $this->calculateByUnit($quantity);
        } else { // 'size'
            $width = (float) ($params['width'] ?? 0);
            $height = (float) ($params['height'] ?? 0);

            return $this->calculateBySize($width, $height, $quantity);
        }
    }


    /**
     * Validar si los parámetros son correctos según el tipo
     */
    public function validateParameters(array $params): array
    {
        $errors = [];

        if ($this->pricing_type === 'unit') {
            if (! isset($params['quantity']) || $params['quantity'] <= 0) {
                $errors[] = 'La cantidad debe ser mayor a 0 para items por unidad';
            }
        } else { // 'size'
            if (! isset($params['width']) || $params['width'] <= 0) {
                $errors[] = 'El ancho debe ser mayor a 0 para items por tamaño';
            }
            if (! isset($params['height']) || $params['height'] <= 0) {
                $errors[] = 'El alto debe ser mayor a 0 para items por tamaño';
            }
            if (! isset($params['quantity']) || $params['quantity'] <= 0) {
                $errors[] = 'La cantidad debe ser mayor a 0';
            }
        }

        return $errors;
    }

    // Accessors

    public function getSupplierTypeAttribute(): string
    {
        return $this->is_own_product ? 'Producto Propio' : 'Producto de Terceros';
    }

    public function getPricingTypeNameAttribute(): string
    {
        return $this->pricing_type === 'unit' ? 'Por Unidad' : 'Por Tamaño (m²)';
    }

    public function getFormattedSalePriceAttribute(): string
    {
        if ($this->pricing_type === 'unit') {
            return '$'.number_format($this->sale_price, 2).' por unidad';
        } else {
            return '$'.number_format($this->sale_price, 2).' por m²';
        }
    }

    // Scopes

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeByPricingType($query, string $type)
    {
        return $query->where('pricing_type', $type);
    }

    public function scopeOwnProducts($query)
    {
        return $query->where('is_own_product', true);
    }

    public function scopeThirdPartyProducts($query)
    {
        return $query->where('is_own_product', false);
    }

    public function scopeWithSupplier($query)
    {
        return $query->with('supplier');
    }

    // Métodos estáticos de utilidad

    public static function getPricingTypeOptions(): array
    {
        return [
            'unit' => 'Por Unidad',
            'size' => 'Por Tamaño (m²)',
        ];
    }

    public static function getActiveDescriptions(): array
    {
        return static::active()
            ->pluck('description', 'id')
            ->toArray();
    }

    // Métodos para acabados

    /**
     * Calcular el precio total incluyendo acabados
     */
    public function calculateTotalWithFinishings(array $params): float
    {
        $basePrice = $this->calculateTotalPrice($params);
        $finishingsCost = $this->calculateFinishingsCost();

        return $basePrice + $finishingsCost;
    }

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

        // Verificar si ya existe este acabado para este item digital
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
     * Actualizar un acabado recalculando su costo
     */
    public function updateFinishing(Finishing $finishing, array $params): void
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

        $this->finishings()->updateExistingPivot($finishing->id, $pivotData);
    }

    /**
     * Remover un acabado
     */
    public function removeFinishing(Finishing $finishing): void
    {
        $this->finishings()->detach($finishing->id);
    }

    /**
     * Obtener resumen de acabados aplicados
     */
    public function getFinishingsSummary(): array
    {
        $summary = [];

        foreach ($this->finishings as $finishing) {
            $summary[] = [
                'name' => $finishing->name,
                'measurement_unit' => $finishing->measurement_unit->label(),
                'cost' => $finishing->pivot->calculated_cost,
                'params' => [
                    'quantity' => $finishing->pivot->quantity,
                    'width' => $finishing->pivot->width,
                    'height' => $finishing->pivot->height,
                ],
            ];
        }

        return $summary;
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
            return $this->description ?? '';
        }

        // Buscar "acabados:" y extraer todo lo anterior
        $pos = strpos($fullDescription, ' acabados:');
        if ($pos !== false) {
            return trim(substr($fullDescription, 0, $pos));
        }

        return $fullDescription;
    }
}
