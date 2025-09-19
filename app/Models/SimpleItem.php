<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\CuttingCalculatorService;

class SimpleItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'quantity',
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

    protected $casts = [
        'quantity' => 'decimal:2',
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
            $item->calculateAll();
        });
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
        if (!$this->paper || !$this->horizontal_size || !$this->vertical_size) {
            return 0;
        }

        // Usar el servicio de calculadora de cortes
        $calculator = new CuttingCalculatorService();
        
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
        if (!$this->paper || !$this->horizontal_size || !$this->vertical_size) {
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
        if (!$this->paper || !$this->mounting_quantity) {
            return 0;
        }

        return $this->mounting_quantity * $this->paper->cost_per_sheet;
    }

    public function calculatePrintingCost(): float
    {
        if (!$this->printingMachine || !$this->mounting_quantity) {
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
            $calculator = new \App\Services\SimpleItemCalculatorService();
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
            $calculator = new \App\Services\SimpleItemCalculatorService();
            return $calculator->calculateMountingOptions($this);
        } catch (\Exception $e) {
            return [];
        }
    }

    // Método para obtener el desglose detallado de costos
    public function getDetailedCostBreakdown(): array
    {
        try {
            $calculator = new \App\Services\SimpleItemCalculatorService();
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
            $calculator = new \App\Services\SimpleItemCalculatorService();
            $validation = $calculator->validateTechnicalViability($this);
            return $validation->getAllMessages();
        } catch (\Exception $e) {
            return [['type' => 'error', 'message' => 'Error al validar: ' . $e->getMessage()]];
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
        if (!$this->paper) return 'horizontal';
        
        $cuts = $this->calculatePaperCuts();
        $total = $cuts['h'] * $cuts['v'];
        
        // Probar orientación rotada
        $cutsH_rotated = floor($this->paper->width / $this->vertical_size);
        $cutsV_rotated = floor($this->paper->height / $this->horizontal_size);
        $total_rotated = $cutsH_rotated * $cutsV_rotated;
        
        return $total_rotated > $total ? 'vertical' : 'horizontal';
    }
}
