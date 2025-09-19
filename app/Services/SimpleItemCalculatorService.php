<?php

namespace App\Services;

use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;


class SimpleItemCalculatorService
{
    private CuttingCalculatorService $cuttingCalculator;
    
    public function __construct()
    {
        $this->cuttingCalculator = new CuttingCalculatorService();
    }

    /**
     * PASO 1: Calcular opciones de montaje disponibles
     */
    public function calculateMountingOptions(SimpleItem $item): array
    {
        if (!$item->paper || !$item->printingMachine) {
            return [];
        }

        $options = [];
        $orientations = ['horizontal', 'vertical', 'maximum'];

        // Calcular cantidad total incluyendo sobrante de papel
        $totalQuantityWithWaste = (int) $item->quantity + ($item->sobrante_papel ?? 0);

        foreach ($orientations as $orientation) {
            try {
                $result = $this->cuttingCalculator->calculateCuts(
                    paperWidth: $item->paper->width,
                    paperHeight: $item->paper->height,
                    cutWidth: $item->horizontal_size,
                    cutHeight: $item->vertical_size,
                    desiredCuts: $totalQuantityWithWaste,
                    orientation: $orientation
                );

                $options[] = new MountingOption(
                    orientation: $orientation,
                    cutsPerSheet: $result['cutsPerSheet'],
                    sheetsNeeded: $result['sheetsNeeded'],
                    utilizationPercentage: $result['usedAreaPercentage'],
                    wastePercentage: $result['wastedAreaPercentage'],
                    paperCost: $result['sheetsNeeded'] * $item->paper->cost_per_sheet,
                    cuttingLayout: [
                        'vertical_cuts' => $result['verticalCuts'],
                        'horizontal_cuts' => $result['horizontalCuts']
                    ],
                    rawCalculation: $result
                );

            } catch (\Exception $e) {
                // Skip invalid orientations
                continue;
            }
        }

        // Sort by best utilization
        usort($options, fn($a, $b) => $b->utilizationPercentage <=> $a->utilizationPercentage);

        return $options;
    }

    /**
     * PASO 2: Calcular millares de impresión con redondeo hacia arriba
     */
    public function calculatePrintingMillares(SimpleItem $item, MountingOption $mountingOption): PrintingCalculation
    {
        $totalColors = $item->ink_front_count + $item->ink_back_count;

        // Si es tiro y retiro plancha, ajustar cálculo
        if ($item->front_back_plate) {
            $totalColors = max($item->ink_front_count, $item->ink_back_count);
        }

        // Determinar cantidad a cobrar en impresión según regla de sobrante
        $quantityForPrinting = $mountingOption->sheetsNeeded * $mountingOption->cutsPerSheet;
        $sobrante = $item->sobrante_papel ?? 0;

        // Si el sobrante es mayor a 100, cobrar la cantidad total (original + sobrante)
        if ($sobrante > 100) {
            $quantityForPrinting = $mountingOption->sheetsNeeded * $mountingOption->cutsPerSheet + $sobrante;
        }

        // Fórmula: (Total_colores × Cantidad_para_impresión) ÷ 1000
        $millaresRaw = ($totalColors * $quantityForPrinting) / 1000;
        
        // REGLA: Siempre redondear HACIA ARRIBA
        $millaresFinal = $this->roundUpMillares($millaresRaw);
        
        // Calcular costo
        $printingCost = $millaresFinal * $item->printingMachine->cost_per_impression;
        
        // Agregar costo de alistamiento
        $setupCost = $item->printingMachine->setup_cost ?? 0;
        $totalPrintingCost = $printingCost + $setupCost;

        return new PrintingCalculation(
            totalColors: $totalColors,
            millaresRaw: $millaresRaw,
            millaresFinal: $millaresFinal,
            printingCost: $printingCost,
            setupCost: $setupCost,
            totalCost: $totalPrintingCost,
            frontBackPlate: (bool) ($item->front_back_plate ?? false)
        );
    }

    /**
     * PASO 3: Calcular costos adicionales
     */
    public function calculateAdditionalCosts(SimpleItem $item, MountingOption $mountingOption): AdditionalCosts
    {
        // Usar los valores del formulario o calcular si están en 0
        $cuttingCost = ($item->cutting_cost > 0) ? $item->cutting_cost : $this->calculateCuttingCost($mountingOption);
        $mountingCost = ($item->mounting_cost > 0) ? $item->mounting_cost : $this->calculateMountingCost($item, $mountingOption);

        // CTP siempre se calcula automáticamente basado en tintas y máquina
        $ctpCost = $this->calculateCtpCost($item);

        return new AdditionalCosts(
            designCost: $item->design_value ?? 0,
            transportCost: $item->transport_value ?? 0,
            rifleCost: $item->rifle_value ?? 0,
            cuttingCost: $cuttingCost,
            mountingCost: $mountingCost,
            ctpCost: $ctpCost
        );
    }

    /**
     * PASO 4: Calcular precio final con desglose completo
     */
    public function calculateFinalPricing(SimpleItem $item, MountingOption $mountingOption = null): PricingResult
    {
        // Si no se especifica montaje, usar el óptimo
        if (!$mountingOption) {
            $mountingOptions = $this->calculateMountingOptions($item);
            $mountingOption = $mountingOptions[0] ?? null;
            
            if (!$mountingOption) {
                throw new \Exception('No se pudo calcular opciones de montaje válidas');
            }
        }

        // Calcular todos los componentes
        $printingCalc = $this->calculatePrintingMillares($item, $mountingOption);
        $additionalCosts = $this->calculateAdditionalCosts($item, $mountingOption);

        // Sumar costos base
        $subtotal = $mountingOption->paperCost + 
                   $printingCalc->totalCost + 
                   $additionalCosts->getTotalCost();

        // Aplicar margen de ganancia
        $profitAmount = $subtotal * ($item->profit_percentage / 100);
        $finalPrice = $subtotal + $profitAmount;

        return new PricingResult(
            mountingOption: $mountingOption,
            printingCalculation: $printingCalc,
            additionalCosts: $additionalCosts,
            subtotal: $subtotal,
            profitPercentage: $item->profit_percentage,
            profitAmount: $profitAmount,
            finalPrice: $finalPrice,
            unitPrice: $finalPrice / $item->quantity,
            costBreakdown: $this->generateCostBreakdown($mountingOption, $printingCalc, $additionalCosts)
        );
    }

    /**
     * Validar viabilidad técnica
     */
    public function validateTechnicalViability(SimpleItem $item): ValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validar dimensiones vs límites de máquina
        if ($item->printingMachine) {
            $maxWidth = $item->printingMachine->max_width ?? 125;
            $maxHeight = $item->printingMachine->max_height ?? 125;
            
            if ($item->horizontal_size > $maxWidth || $item->vertical_size > $maxHeight) {
                $errors[] = "Las dimensiones del item ({$item->horizontal_size}x{$item->vertical_size}cm) exceden los límites de la máquina ({$maxWidth}x{$maxHeight}cm)";
            }
        }

        // Validar colores vs capacidad de máquina
        if ($item->printingMachine) {
            $maxColors = $item->printingMachine->max_colors ?? 8;
            $totalColors = $item->ink_front_count + $item->ink_back_count;
            
            if ($totalColors > $maxColors) {
                $errors[] = "El número total de tintas ($totalColors) excede la capacidad de la máquina ($maxColors)";
            }
        }

        // Validar disponibilidad de papel
        if ($item->paper && $item->paper->stock !== null) {
            $mountingOptions = $this->calculateMountingOptions($item);
            if (!empty($mountingOptions)) {
                $sheetsNeeded = $mountingOptions[0]->sheetsNeeded;
                if ($sheetsNeeded > $item->paper->stock) {
                    $warnings[] = "Se necesitan {$sheetsNeeded} pliegos pero solo hay {$item->paper->stock} en stock";
                }
            }
        }

        return new ValidationResult(
            isValid: empty($errors),
            errors: $errors,
            warnings: $warnings
        );
    }

    // Métodos privados de apoyo

    private function roundUpMillares(float $millares): int
    {
        // Redondeo hacia arriba con lógica de negocio
        if ($millares <= 1) {
            return 1; // Mínimo 1 millar
        }

        // Obtener la parte decimal
        $decimalPart = $millares - floor($millares);

        // Solo redondear hacia arriba si el decimal es mayor que 0.1
        if ($decimalPart > 0.1) {
            return (int) ceil($millares);
        } else {
            return (int) floor($millares);
        }
    }

    private function calculateCuttingCost(MountingOption $mountingOption): float
    {
        // Costo base por corte
        $baseCostPerSheet = 50; // $50 por pliego cortado
        
        // Multiplicador por complejidad del corte
        $complexityMultiplier = 1;
        if ($mountingOption->cutsPerSheet > 20) {
            $complexityMultiplier = 1.5; // Cortes más complejos
        }
        
        return $mountingOption->sheetsNeeded * $baseCostPerSheet * $complexityMultiplier;
    }

    private function calculateMountingCost(SimpleItem $item, MountingOption $mountingOption): float
    {
        $baseMountingCost = 8000; // Costo base de montaje

        // Aumentar por número de tintas
        $inkMultiplier = 1 + (($item->ink_front_count + $item->ink_back_count - 1) * 0.25);

        return $baseMountingCost * $inkMultiplier;
    }

    private function calculateCtpCost(SimpleItem $item): float
    {
        // Si no tiene máquina de impresión, no hay costo CTP
        if (!$item->printingMachine) {
            return 0;
        }

        // Calcular cantidad total de tintas considerando front_back_plate
        $totalInks = $item->front_back_plate
            ? max($item->ink_front_count, $item->ink_back_count)
            : ($item->ink_front_count + $item->ink_back_count);

        // Solo las máquinas con costo CTP > 0 cobran este concepto
        $ctpCostPerPlate = $item->printingMachine->costo_ctp ?? 0;

        return $totalInks * $ctpCostPerPlate;
    }

    private function generateCostBreakdown(MountingOption $mountingOption, PrintingCalculation $printing, AdditionalCosts $additional): array
    {
        return [
            'paper' => [
                'description' => 'Papel',
                'quantity' => $mountingOption->sheetsNeeded . ' pliegos',
                'cost' => $mountingOption->paperCost
            ],
            'printing' => [
                'description' => 'Impresión',
                'quantity' => $printing->millaresFinal . ' millares',
                'cost' => $printing->printingCost
            ],
            'setup' => [
                'description' => 'Alistamiento',
                'quantity' => '1 trabajo',
                'cost' => $printing->setupCost
            ],
            'cutting' => [
                'description' => 'Corte',
                'quantity' => $mountingOption->sheetsNeeded . ' pliegos',
                'cost' => $additional->cuttingCost
            ],
            'mounting' => [
                'description' => 'Montaje',
                'quantity' => '1 trabajo',
                'cost' => $additional->mountingCost
            ],
            'design' => [
                'description' => 'Diseño',
                'quantity' => '1 trabajo', 
                'cost' => $additional->designCost
            ],
            'transport' => [
                'description' => 'Transporte',
                'quantity' => '1 envío',
                'cost' => $additional->transportCost
            ],
            'rifle' => [
                'description' => 'Rifle/Doblez',
                'quantity' => '1 proceso',
                'cost' => $additional->rifleCost
            ],
            'ctp' => [
                'description' => 'Planchas CTP',
                'quantity' => '1 juego',
                'cost' => $additional->ctpCost
            ]
        ];
    }
}

// ==================== DATA CLASSES ====================

/**
 * Opción de montaje disponible para un item
 */
class MountingOption
{
    public function __construct(
        public readonly string $orientation,
        public readonly int $cutsPerSheet,
        public readonly int $sheetsNeeded,
        public readonly float $utilizationPercentage,
        public readonly float $wastePercentage,
        public readonly float $paperCost,
        public readonly array $cuttingLayout,
        public readonly array $rawCalculation = []
    ) {}

    public function getEfficiencyRating(): string
    {
        if ($this->utilizationPercentage >= 90) return 'Excelente';
        if ($this->utilizationPercentage >= 80) return 'Muy Bueno';
        if ($this->utilizationPercentage >= 70) return 'Bueno';
        if ($this->utilizationPercentage >= 60) return 'Regular';
        return 'Bajo';
    }

    public function getDescription(): string
    {
        return ucfirst($this->orientation) . " - {$this->cutsPerSheet} cortes/pliego ({$this->utilizationPercentage}% aprovechamiento)";
    }
}

/**
 * Cálculo detallado de millares de impresión
 */
class PrintingCalculation
{
    public function __construct(
        public readonly int $totalColors,
        public readonly float $millaresRaw,
        public readonly int $millaresFinal,
        public readonly float $printingCost,
        public readonly float $setupCost,
        public readonly float $totalCost,
        public readonly bool $frontBackPlate = false
    ) {}

    public function getColorDescription(): string
    {
        if ($this->frontBackPlate) {
            return "{$this->totalColors} colores (tiro y retiro plancha)";
        }
        return "{$this->totalColors} colores total";
    }
}

/**
 * Costos adicionales del trabajo
 */
class AdditionalCosts
{
    public function __construct(
        public readonly float $designCost = 0,
        public readonly float $transportCost = 0,
        public readonly float $rifleCost = 0,
        public readonly float $cuttingCost = 0,
        public readonly float $mountingCost = 0,
        public readonly float $ctpCost = 0
    ) {}

    public function getTotalCost(): float
    {
        return $this->designCost +
               $this->transportCost +
               $this->rifleCost +
               $this->cuttingCost +
               $this->mountingCost +
               $this->ctpCost;
    }

    public function getBreakdown(): array
    {
        return [
            'design' => $this->designCost,
            'transport' => $this->transportCost,
            'rifle' => $this->rifleCost,
            'cutting' => $this->cuttingCost,
            'mounting' => $this->mountingCost,
            'ctp' => $this->ctpCost
        ];
    }
}

/**
 * Resultado completo del pricing con todos los detalles
 */
class PricingResult
{
    public function __construct(
        public readonly MountingOption $mountingOption,
        public readonly PrintingCalculation $printingCalculation,
        public readonly AdditionalCosts $additionalCosts,
        public readonly float $subtotal,
        public readonly float $profitPercentage,
        public readonly float $profitAmount,
        public readonly float $finalPrice,
        public readonly float $unitPrice,
        public readonly array $costBreakdown
    ) {}

    public function getSummary(): array
    {
        return [
            'cuts_per_sheet' => $this->mountingOption->cutsPerSheet,
            'sheets_needed' => $this->mountingOption->sheetsNeeded,
            'utilization' => $this->mountingOption->utilizationPercentage,
            'millares' => $this->printingCalculation->millaresFinal,
            'subtotal' => $this->subtotal,
            'profit_amount' => $this->profitAmount,
            'final_price' => $this->finalPrice,
            'unit_price' => $this->unitPrice
        ];
    }

    public function getFormattedBreakdown(): array
    {
        $formatted = [];
        foreach ($this->costBreakdown as $key => $item) {
            if ($item['cost'] > 0) {
                $formatted[$key] = [
                    'description' => $item['description'],
                    'detail' => $item['quantity'],
                    'cost' => '$' . number_format($item['cost'], 2)
                ];
            }
        }
        return $formatted;
    }
}

/**
 * Resultado de validaciones técnicas
 */
class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = [],
        public readonly array $warnings = []
    ) {}

    public function hasWarnings(): bool
    {
        return !empty($this->warnings);
    }

    public function getAllMessages(): array
    {
        return array_merge(
            array_map(fn($error) => ['type' => 'error', 'message' => $error], $this->errors),
            array_map(fn($warning) => ['type' => 'warning', 'message' => $warning], $this->warnings)
        );
    }
}