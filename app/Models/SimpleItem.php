<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use App\Services\CuttingCalculatorService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SimpleItem extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'description',
        'base_description', // Descripción manual del usuario
        'quantity',
        'sobrante_papel',
        'horizontal_size',
        'vertical_size',
        'mounting_quantity',
        'paper_cuts_h',
        'paper_cuts_v',
        'ink_front_count',
        'ink_back_count',
        'front_back_plate',
        'design_value',
        'transport_value',
        'rifle_value',
        'cutting_cost',
        'mounting_cost',
        'profit_percentage',
        'paper_id',
        'printing_machine_id',
        'paper_cost',
        'printing_cost',
        'total_cost',
        'final_price',
    ];

    // Descripción base temporal (no se guarda en BD, solo para procesamiento)
    protected $baseDescriptionTemp = null;

    protected $casts = [
        'quantity' => 'decimal:2',
        'sobrante_papel' => 'integer',
        'horizontal_size' => 'decimal:2',
        'vertical_size' => 'decimal:2',
        'paper_cuts_h' => 'decimal:2',
        'paper_cuts_v' => 'decimal:2',
        'mounting_quantity' => 'integer',
        'ink_front_count' => 'integer',
        'ink_back_count' => 'integer',
        'front_back_plate' => 'boolean',
        'design_value' => 'decimal:2',
        'transport_value' => 'decimal:2',
        'rifle_value' => 'decimal:2',
        'profit_percentage' => 'decimal:2',
        'paper_cost' => 'decimal:2',
        'printing_cost' => 'decimal:2',
        'cutting_cost' => 'decimal:2',
        'mounting_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'final_price' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Generar descripción auto-concatenada antes de calcular
            $item->generateAutoDescription();

            $item->calculateAll();
        });
    }

    /**
     * Genera la descripción auto-concatenada basada en los campos del item
     */
    protected function generateAutoDescription(): void
    {
        // Extraer la descripción base (texto antes de "tamaño")
        $baseDescription = $this->extractBaseDescription($this->description);

        // Si hay una descripción base temporal (del formulario), usarla
        if ($this->baseDescriptionTemp) {
            $baseDescription = $this->baseDescriptionTemp;
        }

        // Si no hay descripción base, usar la actual o vacío
        if (!$baseDescription) {
            $baseDescription = $this->description ?? '';
        }

        // Construir la descripción concatenada
        $parts = [$baseDescription];

        // Agregar tamaño si están definidos horizontal_size y vertical_size
        if ($this->horizontal_size && $this->vertical_size) {
            $parts[] = "tamaño {$this->horizontal_size}x{$this->vertical_size}";
        }

        // Agregar impresión (tintas)
        if (isset($this->ink_front_count) && isset($this->ink_back_count)) {
            $parts[] = "impresión {$this->ink_front_count}x{$this->ink_back_count}";
        }

        // Agregar papel si existe la relación
        if ($this->paper_id && $this->paper) {
            $paperName = $this->extractPaperName($this->paper->name);
            $parts[] = "en papel {$paperName}";
        }

        // Unir todas las partes con espacios
        $this->description = trim(implode(' ', array_filter($parts)));
    }

    /**
     * Extrae la descripción base del texto concatenado
     * Ejemplo: "Volantes promocionales tamaño 10x15 impresión 4x0" → "Volantes promocionales"
     */
    protected function extractBaseDescription(?string $fullDescription): ?string
    {
        if (!$fullDescription) {
            return null;
        }

        // Buscar la palabra "tamaño" y extraer todo lo anterior
        $pos = strpos($fullDescription, ' tamaño ');
        if ($pos !== false) {
            return trim(substr($fullDescription, 0, $pos));
        }

        // Si no encuentra "tamaño", retornar la descripción completa
        return $fullDescription;
    }

    /**
     * Extrae el nombre limpio del papel (sin dimensiones)
     * Ejemplo: "Bond 90gr (70x100cm)" → "Bond 90gr"
     */
    protected function extractPaperName(string $paperName): string
    {
        // Eliminar las dimensiones entre paréntesis
        return trim(preg_replace('/\s*\([^)]*\)/', '', $paperName));
    }

    /**
     * Mutator para capturar la descripción base cuando se asigna
     */
    public function setDescriptionAttribute($value): void
    {
        // Si es una nueva instancia o no tiene descripción previa,
        // guardar como descripción base temporal
        if (!$this->exists || !$this->getOriginal('description')) {
            $this->baseDescriptionTemp = $value;
        } else {
            // Si ya existe, extraer la base de la descripción anterior
            $this->baseDescriptionTemp = $this->extractBaseDescription($value);
        }

        // Guardar el valor en el atributo (será regenerado en saving)
        $this->attributes['description'] = $value;
    }

    // Relaciones
    public function documentItems(): MorphMany
    {
        return $this->morphMany(DocumentItem::class, 'itemable');
    }

    public function paper(): BelongsTo
    {
        return $this->belongsTo(Paper::class);
    }

    public function printingMachine(): BelongsTo
    {
        return $this->belongsTo(PrintingMachine::class);
    }

    // Métodos de cálculo automático
    public function calculateMounting(): int
    {
        if (! $this->paper || ! $this->horizontal_size || ! $this->vertical_size) {
            return 0;
        }

        // Usar el servicio de calculadora de cortes
        $calculator = new CuttingCalculatorService;

        try {
            $result = $calculator->calculateCuts(
                paperWidth: $this->paper->width,
                paperHeight: $this->paper->height,
                cutWidth: $this->horizontal_size,
                cutHeight: $this->vertical_size,
                desiredCuts: (int) $this->quantity
            );

            // Retornar la cantidad de pliegos necesarios como montaje
            return $result['sheetsNeeded'] ?? 0;

        } catch (\Exception $e) {
            return 0;
        }
    }

    public function calculatePaperCuts(): array
    {
        if (! $this->paper || ! $this->horizontal_size || ! $this->vertical_size) {
            return ['h' => 0, 'v' => 0];
        }

        // Calcular cuántos cortes entran horizontal y verticalmente
        $cutsH = floor($this->paper->width / $this->horizontal_size);
        $cutsV = floor($this->paper->height / $this->vertical_size);

        // También probar la orientación rotada
        $cutsH_rotated = floor($this->paper->width / $this->vertical_size);
        $cutsV_rotated = floor($this->paper->height / $this->horizontal_size);

        // Elegir la mejor orientación (más aprovechamiento)
        $total1 = $cutsH * $cutsV;
        $total2 = $cutsH_rotated * $cutsV_rotated;

        if ($total2 > $total1) {
            return ['h' => $cutsH_rotated, 'v' => $cutsV_rotated];
        }

        return ['h' => $cutsH, 'v' => $cutsV];
    }

    public function calculatePaperCost(): float
    {
        if (! $this->paper || ! $this->mounting_quantity) {
            return 0;
        }

        return $this->mounting_quantity * $this->paper->cost_per_sheet;
    }

    public function calculatePrintingCost(): float
    {
        if (! $this->printingMachine || ! $this->mounting_quantity) {
            return 0;
        }

        $impressions = $this->mounting_quantity;
        $totalInks = $this->ink_front_count + $this->ink_back_count;

        // Si es tiro y retiro plancha, se cobra solo la mayor cantidad de tintas (frente o respaldo)
        if ($this->front_back_plate) {
            $totalInks = max($this->ink_front_count, $this->ink_back_count);
        }

        // Calcular costo por millar (cost_per_impression es por 1000)
        $costPerImpression = $this->printingMachine->calculateCostForQuantity($impressions);

        // Multiplicar por colores y agregar costo de alistamiento
        return ($costPerImpression * $totalInks) + $this->printingMachine->setup_cost;
    }

    public function calculateMountingCost(): float
    {
        // Costo de montaje básico - se puede hacer más sofisticado
        if ($this->mounting_quantity <= 0) {
            return 0;
        }

        // Costo base por montaje (se puede parametrizar)
        $baseMountingCost = 5000; // $5000 por montaje
        $complexityMultiplier = 1;

        // Aumentar complejidad basado en número de tintas
        if ($this->ink_front_count + $this->ink_back_count > 4) {
            $complexityMultiplier = 1.5;
        }

        return $baseMountingCost * $complexityMultiplier;
    }

    public function calculateTotalCost(): float
    {
        return $this->paper_cost +
               $this->printing_cost +
               $this->cutting_cost +
               $this->mounting_cost +
               $this->design_value +
               $this->transport_value +
               $this->rifle_value;
    }

    public function calculateFinalPrice(): float
    {
        $totalCost = $this->total_cost;

        if ($this->profit_percentage > 0) {
            $totalCost = $totalCost * (1 + ($this->profit_percentage / 100));
        }

        return $totalCost;
    }

    public function calculateAll(): void
    {
        // Usar el nuevo sistema de cálculo avanzado
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService;
            $pricingResult = $calculator->calculateFinalPricing($this);

            // Actualizar campos con los resultados del nuevo calculador
            $this->mounting_quantity = $pricingResult->mountingOption->sheetsNeeded;
            $this->paper_cuts_h = $pricingResult->mountingOption->cuttingLayout['horizontal_cuts'];
            $this->paper_cuts_v = $pricingResult->mountingOption->cuttingLayout['vertical_cuts'];
            $this->paper_cost = $pricingResult->mountingOption->paperCost;
            $this->printing_cost = $pricingResult->printingCalculation->totalCost;

            // Solo actualizar corte y montaje si están en 0 (usar cálculo automático)
            if ($this->cutting_cost == 0) {
                $this->cutting_cost = $pricingResult->additionalCosts->cuttingCost;
            }
            if ($this->mounting_cost == 0) {
                $this->mounting_cost = $pricingResult->additionalCosts->mountingCost;
            }

            $this->total_cost = $pricingResult->subtotal;
            $this->final_price = $pricingResult->finalPrice;

        } catch (\Exception $e) {
            // Fallback al sistema anterior si hay error
            $this->calculateAllLegacy();
        }
    }

    // Mantener el método anterior como fallback
    private function calculateAllLegacy(): void
    {
        $this->mounting_quantity = $this->calculateMounting();

        $cuts = $this->calculatePaperCuts();
        $this->paper_cuts_h = $cuts['h'];
        $this->paper_cuts_v = $cuts['v'];

        $this->paper_cost = $this->calculatePaperCost();
        $this->printing_cost = $this->calculatePrintingCost();
        $this->mounting_cost = $this->calculateMountingCost();
        $this->total_cost = $this->calculateTotalCost();
        $this->final_price = $this->calculateFinalPrice();
    }

    // Método para obtener opciones de montaje disponibles
    public function getMountingOptions(): array
    {
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService;

            return $calculator->calculateMountingOptions($this);
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Obtiene el cálculo de montaje puro (cuántas copias caben por pliego)
     * Usa el nuevo MountingCalculatorService
     *
     * @return array|null [
     *   'horizontal' => [...],
     *   'vertical' => [...],
     *   'maximum' => [...],
     *   'sheets_info' => [...],  (si hay papel y cantidad)
     *   'efficiency' => float     (si hay papel y cantidad)
     * ]
     */
    public function getPureMounting(): ?array
    {
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService;

            return $calculator->calculatePureMounting($this);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Obtiene solo la información del mejor montaje (máximo)
     *
     * @return array|null
     */
    public function getBestMounting(): ?array
    {
        $mounting = $this->getPureMounting();

        return $mounting ? $mounting['maximum'] : null;
    }

    // Método para obtener el desglose detallado de costos
    public function getDetailedCostBreakdown(): array
    {
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService;
            $pricingResult = $calculator->calculateFinalPricing($this);

            return $pricingResult->getFormattedBreakdown();
        } catch (\Exception $e) {
            return [];
        }
    }

    // Método para validar viabilidad técnica
    public function validateTechnicalViability(): array
    {
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService;
            $validation = $calculator->validateTechnicalViability($this);

            return $validation->getAllMessages();
        } catch (\Exception $e) {
            return [['type' => 'error', 'message' => 'Error al validar: '.$e->getMessage()]];
        }
    }

    // Accessors útiles
    public function getTotalInksAttribute(): int
    {
        return $this->ink_front_count + $this->ink_back_count;
    }

    public function getAreaAttribute(): float
    {
        return $this->horizontal_size * $this->vertical_size;
    }

    public function getOptimalOrientationAttribute(): string
    {
        if (! $this->paper) {
            return 'horizontal';
        }

        $cuts = $this->calculatePaperCuts();
        $total = $cuts['h'] * $cuts['v'];

        // Probar orientación rotada
        $cutsH_rotated = floor($this->paper->width / $this->vertical_size);
        $cutsV_rotated = floor($this->paper->height / $this->horizontal_size);
        $total_rotated = $cutsH_rotated * $cutsV_rotated;

        return $total_rotated > $total ? 'vertical' : 'horizontal';
    }
}
