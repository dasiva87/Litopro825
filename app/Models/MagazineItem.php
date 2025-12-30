<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Services\MagazineCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MagazineItem extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'description',
        'quantity',
        'closed_width',
        'closed_height',
        'binding_type',
        'binding_side',
        'binding_cost',
        'assembly_cost',
        'finishing_cost',
        'transport_value',
        'design_value',
        'profit_percentage',
        'pages_total_cost',
        'total_cost',
        'final_price',
        'binding_options',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'closed_width' => 'decimal:2',
        'closed_height' => 'decimal:2',
        'binding_cost' => 'decimal:2',
        'assembly_cost' => 'decimal:2',
        'finishing_cost' => 'decimal:2',
        'transport_value' => 'decimal:2',
        'design_value' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'pages_total_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'final_price' => 'decimal:2',
        'binding_options' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Agregar acabados a la descripción si están cargados
            $item->appendFinishingsToDescription();

            // Calcular todos los costos
            $item->calculateAll();
        });
    }

    // Relaciones
    public function documentItems(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'itemable');
    }

    public function pages(): HasMany
    {
        return $this->hasMany(MagazinePage::class);
    }

    public function simpleItems()
    {
        return $this->hasManyThrough(
            SimpleItem::class,
            MagazinePage::class,
            'magazine_item_id',
            'id',
            'id',
            'simple_item_id'
        );
    }

    public function finishings(): BelongsToMany
    {
        return $this->belongsToMany(Finishing::class, 'magazine_item_finishings')
            ->withPivot([
                'quantity',
                'unit_cost',
                'total_cost',
                'finishing_options',
                'notes',
            ])
            ->withTimestamps();
    }

    // Métodos de cálculo automático
    public function calculatePagesTotal(): float
    {
        return $this->pages->sum(function ($page) {
            return $page->simpleItem ? $page->simpleItem->final_price * $page->page_quantity : 0;
        });
    }

    public function calculateBindingCost(): float
    {
        if (! $this->binding_type || ! $this->quantity) {
            return 0;
        }

        // Costos base por tipo de encuadernación (por unidad)
        $baseCosts = [
            'grapado' => 500,
            'plegado' => 200,
            'anillado' => 800,
            'cosido' => 1200,
            'caballete' => 600,
            'lomo' => 1500,
            'espiral' => 900,
            'wire_o' => 1000,
            'hotmelt' => 1800,
        ];

        $baseCost = $baseCosts[$this->binding_type] ?? 500;

        // Factor de complejidad basado en el número de páginas
        $totalPages = $this->pages->sum('page_quantity');
        $complexityFactor = 1 + ($totalPages > 50 ? 0.5 : ($totalPages > 20 ? 0.25 : 0));

        return $baseCost * $this->quantity * $complexityFactor;
    }

    public function calculateAssemblyCost(): float
    {
        if (! $this->quantity) {
            return 0;
        }

        // Costo base de armado
        $baseAssemblyCost = 300; // Por revista
        $totalPages = $this->pages->sum('page_quantity');

        // Incremento por complejidad
        $pagesFactor = 1 + ($totalPages * 0.02); // 2% por página adicional

        return $baseAssemblyCost * $this->quantity * $pagesFactor;
    }

    public function calculateFinishingCost(): float
    {
        return $this->finishings->sum('pivot.total_cost');
    }

    public function calculateTotalCost(): float
    {
        return ($this->pages_total_cost ?? 0) +
               ($this->binding_cost ?? 0) +
               ($this->assembly_cost ?? 0) +
               ($this->finishing_cost ?? 0) +
               ($this->design_value ?? 0) +
               ($this->transport_value ?? 0);
    }

    public function calculateFinalPrice(): float
    {
        $totalCost = $this->total_cost ?? 0;

        if (($this->profit_percentage ?? 0) > 0) {
            $totalCost = $totalCost * (1 + ($this->profit_percentage / 100));
        }

        return $totalCost;
    }

    public function calculateAll(): void
    {
        try {
            $calculator = new MagazineCalculatorService;
            $pricingResult = $calculator->calculateFinalPricing($this);

            $this->pages_total_cost = $pricingResult->pagesCost;
            $this->binding_cost = $pricingResult->bindingCost;
            $this->assembly_cost = $pricingResult->assemblyCost;
            $this->finishing_cost = $pricingResult->finishingCost;
            $this->total_cost = $pricingResult->totalCost;
            $this->final_price = $pricingResult->finalPrice;

        } catch (\Exception $e) {
            // Fallback al sistema de cálculo local
            $this->calculateAllLegacy();
        }
    }

    private function calculateAllLegacy(): void
    {
        $this->pages_total_cost = $this->calculatePagesTotal();
        $this->binding_cost = $this->calculateBindingCost();
        $this->assembly_cost = $this->calculateAssemblyCost();
        $this->finishing_cost = $this->calculateFinishingCost();
        $this->total_cost = $this->calculateTotalCost();
        $this->final_price = $this->calculateFinalPrice();
    }

    // Métodos de validación
    public function validateTechnicalViability(): array
    {
        $errors = [];
        $warnings = [];

        // Validar dimensiones
        if ($this->closed_width <= 0 || $this->closed_height <= 0) {
            $errors[] = 'Las dimensiones de la revista deben ser mayores a 0';
        }

        // Validar cantidad
        if ($this->quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }

        // Validar páginas
        if ($this->pages->count() === 0) {
            $errors[] = 'La revista debe tener al menos una página';
        }

        // Validar tipos de página críticos
        $hasPortada = $this->pages->where('page_type', 'portada')->count() > 0;
        if (! $hasPortada) {
            $warnings[] = 'Se recomienda agregar una portada';
        }

        // Validar encuadernación según número de páginas
        $totalPages = $this->pages->sum('page_quantity');
        if ($this->binding_type === 'grapado' && $totalPages > 80) {
            $warnings[] = 'El grapado no es recomendable para más de 80 páginas';
        }

        return [
            'errors' => $errors,
            'warnings' => $warnings,
            'isValid' => empty($errors),
        ];
    }

    // Accessors útiles
    public function getTotalPagesAttribute(): int
    {
        return $this->pages->sum('page_quantity');
    }

    public function getClosedAreaAttribute(): float
    {
        return $this->closed_width * $this->closed_height;
    }

    public function getBindingTypeNameAttribute(): string
    {
        $names = [
            'grapado' => 'Grapado',
            'plegado' => 'Plegado',
            'anillado' => 'Anillado',
            'cosido' => 'Cosido',
            'caballete' => 'Caballete',
            'lomo' => 'Lomo',
            'espiral' => 'Espiral',
            'wire_o' => 'Wire-O',
            'hotmelt' => 'Hot Melt',
        ];

        return $names[$this->binding_type] ?? $this->binding_type;
    }

    public function getBindingSideNameAttribute(): string
    {
        $names = [
            'arriba' => 'Arriba',
            'izquierda' => 'Izquierda',
            'derecha' => 'Derecha',
            'abajo' => 'Abajo',
        ];

        return $names[$this->binding_side] ?? $this->binding_side;
    }

    // Método para obtener el desglose detallado de costos
    public function getDetailedCostBreakdown(): array
    {
        try {
            $calculator = new MagazineCalculatorService;

            return $calculator->getDetailedBreakdown($this);
        } catch (\Exception $e) {
            return [
                'pages_cost' => $this->pages_total_cost,
                'binding_cost' => $this->binding_cost,
                'assembly_cost' => $this->assembly_cost,
                'finishing_cost' => $this->finishing_cost,
                'design_value' => $this->design_value,
                'transport_value' => $this->transport_value,
                'subtotal' => $this->total_cost,
                'profit_percentage' => $this->profit_percentage,
                'profit_amount' => $this->final_price - $this->total_cost,
                'final_price' => $this->final_price,
            ];
        }
    }

    // Métodos helper para gestión de páginas
    public function getNextPageOrder(): int
    {
        return $this->pages()->max('page_order') + 1;
    }

    public function hasPageType(string $pageType): bool
    {
        return $this->pages()->where('page_type', $pageType)->exists();
    }

    public function getPagesTableData(): array
    {
        return $this->pages()->with('simpleItem')->orderBy('page_order')->get()->map(function ($page) {
            return [
                'id' => $page->id,
                'type' => $page->page_type_name,
                'order' => $page->page_order,
                'quantity' => $page->page_quantity,
                'description' => $page->simpleItem ? $page->simpleItem->description : 'Sin SimpleItem',
                'unit_price' => $page->simpleItem ? number_format($page->simpleItem->final_price / $page->simpleItem->quantity, 2) : '0.00',
                'total_cost' => number_format($page->total_cost, 2),
                'simple_item_id' => $page->simple_item_id,
            ];
        })->toArray();
    }

    /**
     * Obtener todos los papeles utilizados en las páginas de la revista
     * Retorna un array con los papeles agrupados por tipo
     */
    public function getPapersUsed(): array
    {
        $papers = [];

        foreach ($this->pages as $page) {
            if ($page->simpleItem && $page->simpleItem->paper) {
                $paperId = $page->simpleItem->paper_id;

                if (! isset($papers[$paperId])) {
                    $papers[$paperId] = [
                        'paper' => $page->simpleItem->paper,
                        'total_sheets' => 0,
                        'pages_using' => [],
                    ];
                }

                // Sumar los pliegos necesarios de este papel
                $papers[$paperId]['total_sheets'] += $page->simpleItem->mounting_quantity ?? 0;
                $papers[$paperId]['pages_using'][] = $page->page_type_name;
            }
        }

        return $papers;
    }

    /**
     * Obtener el proveedor principal de papel (basado en la mayoría de uso)
     */
    public function getMainPaperSupplier(): ?int
    {
        $papers = $this->getPapersUsed();

        if (empty($papers)) {
            return null;
        }

        // Ordenar por cantidad de pliegos (descendente)
        uasort($papers, function ($a, $b) {
            return $b['total_sheets'] <=> $a['total_sheets'];
        });

        // Retornar el supplier del papel más usado
        $mainPaper = reset($papers);

        return $mainPaper['paper']->company_id ?? null;
    }

    /**
     * Obtener el total de pliegos necesarios para toda la revista
     * Suma los mounting_quantity de todos los SimpleItems de las páginas
     */
    public function getTotalSheetsAttribute(): int
    {
        $totalSheets = 0;

        foreach ($this->pages as $page) {
            if ($page->simpleItem) {
                $totalSheets += $page->simpleItem->mounting_quantity ?? 0;
            }
        }

        return $totalSheets;
    }

    /**
     * Obtener el total de pliegos necesarios (alias para compatibilidad)
     */
    public function getMountingQuantityAttribute(): int
    {
        return $this->getTotalSheetsAttribute();
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
