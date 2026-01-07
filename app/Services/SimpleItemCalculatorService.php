<?php

namespace App\Services;

use App\Models\SimpleItem;
use App\Models\Paper;
use App\Models\PrintingMachine;


class SimpleItemCalculatorService
{
    private CuttingCalculatorService $cuttingCalculator;
    private MountingCalculatorService $mountingCalculator;

    public function __construct()
    {
        $this->cuttingCalculator = new CuttingCalculatorService();
        $this->mountingCalculator = new MountingCalculatorService();
    }

    /**
     * PASO 0: Calcular montaje puro (cuántas copias caben en un pliego)
     * Usa MountingCalculatorService para cálculo genérico
     * Soporta montaje automático (máquina) y manual (papel personalizado)
     */
    public function calculatePureMounting(SimpleItem $item): ?array
    {
        // Determinar dimensiones según tipo de montaje
        $machineWidth = null;
        $machineHeight = null;
        $mountingType = $item->mounting_type ?? 'automatic';

        if ($mountingType === 'custom') {
            // Montaje manual: usar dimensiones del papel personalizado
            if (!$item->custom_paper_width || !$item->custom_paper_height) {
                return null; // No se puede calcular sin dimensiones custom
            }
            $machineWidth = $item->custom_paper_width;
            $machineHeight = $item->custom_paper_height;
        } else {
            // Montaje automático: usar dimensiones de la máquina
            if (!$item->printingMachine) {
                return null;
            }
            $machineWidth = $item->printingMachine->max_width ?? 50.0;
            $machineHeight = $item->printingMachine->max_height ?? 70.0;
        }

        // Calcular montaje usando el nuevo servicio
        // Usar margen configurable del item, o 1.0cm por defecto
        $marginPerSide = $item->margin_per_side ?? 1.0;

        $mounting = $this->mountingCalculator->calculateMounting(
            workWidth: $item->horizontal_size,
            workHeight: $item->vertical_size,
            machineWidth: $machineWidth,
            machineHeight: $machineHeight,
            marginPerSide: $marginPerSide
        );

        // Agregar información del tipo de montaje usado
        $mounting['mounting_type'] = $mountingType;
        $mounting['paper_width'] = $machineWidth;
        $mounting['paper_height'] = $machineHeight;

        // Si necesita pliegos de papel, calcular usando el mejor montaje
        if ($item->paper && $item->quantity > 0) {
            $bestMounting = $mounting['maximum'];

            if ($bestMounting['copies_per_sheet'] > 0) {
                $sheetsInfo = $this->mountingCalculator->calculateRequiredSheets(
                    requiredCopies: $item->quantity + ($item->sobrante_papel ?? 0),
                    copiesPerSheet: $bestMounting['copies_per_sheet']
                );

                $efficiency = $this->mountingCalculator->calculateEfficiency(
                    workWidth: $bestMounting['work_width'],
                    workHeight: $bestMounting['work_height'],
                    copiesPerSheet: $bestMounting['copies_per_sheet'],
                    usableWidth: $machineWidth - 2.0,  // Restar márgenes
                    usableHeight: $machineHeight - 2.0
                );

                $mounting['sheets_info'] = $sheetsInfo;
                $mounting['efficiency'] = $efficiency;
            }
        }

        return $mounting;
    }

    /**
     * NUEVO PASO 1: Calcular montaje con sistema de cortes integrado
     *
     * TERMINOLOGÍA CORRECTA:
     * - PLIEGO (Paper Sheet): Papel como viene del proveedor (70x100cm)
     * - HOJA (Printing Form): Corte del pliego donde se imprime (50x70cm - tamaño máquina)
     * - COPIA (Copy): Producto final (10x15cm volante)
     *
     * FLUJO: PLIEGO → [divisor] → HOJAS → [mounting] → COPIAS
     */
    public function calculateMountingWithCuts(SimpleItem $item): ?array
    {
        if (!$item->paper || !$item->printingMachine) {
            return null;
        }

        // PASO 1A: Calcular montaje (cuántas COPIAS caben en una HOJA)
        // Usar margen configurable del item, o 1.0cm por defecto
        $marginPerSide = $item->margin_per_side ?? 1.0;

        $mountingResult = $this->mountingCalculator->calculateMounting(
            workWidth: $item->horizontal_size,
            workHeight: $item->vertical_size,
            machineWidth: $item->printingMachine->max_width ?? 50.0,
            machineHeight: $item->printingMachine->max_height ?? 70.0,
            marginPerSide: $marginPerSide
        );

        $copiesPerForm = $mountingResult['maximum']['copies_per_sheet']; // Copias por hoja

        if ($copiesPerForm <= 0) {
            return null; // No cabe ni una copia
        }

        // PASO 1B: Calcular divisor (cuántas HOJAS salen de un PLIEGO)
        $divisorResult = $this->cuttingCalculator->calculateCuts(
            paperWidth: $item->paper->width,
            paperHeight: $item->paper->height,
            cutWidth: $item->printingMachine->max_width ?? 50.0,
            cutHeight: $item->printingMachine->max_height ?? 70.0,
            desiredCuts: 0, // Solo calcular divisor, no pliegos
            orientation: 'maximum'
        );

        $formsPerPaperSheet = $divisorResult['cutsPerSheet']; // Hojas por pliego

        if ($formsPerPaperSheet <= 0) {
            return null; // El tamaño de hoja no cabe en el pliego
        }

        // PASO 1C: Calcular PLIEGOS necesarios
        $totalQuantity = (int) $item->quantity + ($item->sobrante_papel ?? 0);
        $formsNeeded = ceil($totalQuantity / $copiesPerForm); // Hojas necesarias
        $paperSheetsNeeded = ceil($formsNeeded / $formsPerPaperSheet); // Pliegos necesarios

        // PASO 1D: Calcular HOJAS totales a imprimir
        $totalPrintingForms = $paperSheetsNeeded * $formsPerPaperSheet;

        // PASO 1E: Calcular COPIAS producidas
        $totalCopiesProduced = $totalPrintingForms * $copiesPerForm;

        return [
            'mounting' => $mountingResult['maximum'],
            'copies_per_form' => $copiesPerForm,                    // Copias por hoja
            'forms_per_paper_sheet' => $formsPerPaperSheet,         // Hojas por pliego (divisor)
            'forms_needed' => $formsNeeded,                         // Hojas necesarias
            'paper_sheets_needed' => $paperSheetsNeeded,            // Pliegos necesarios
            'printing_forms_needed' => $totalPrintingForms,         // Hojas a imprimir
            'total_copies_produced' => $totalCopiesProduced,        // Copias producidas
            'waste_copies' => $totalCopiesProduced - $totalQuantity,
            'paper_cost' => $paperSheetsNeeded * $item->paper->cost_per_sheet,
            'utilization_percentage' => $divisorResult['usedAreaPercentage'],
            'divisor_layout' => [
                'vertical_cuts' => $divisorResult['verticalCuts'],
                'horizontal_cuts' => $divisorResult['horizontalCuts']
            ],
            'raw_divisor_result' => $divisorResult,

            // LEGACY KEYS (mantener por compatibilidad temporal)
            'copies_per_mounting' => $copiesPerForm,
            'divisor' => $formsPerPaperSheet,
            'impressions_needed' => $formsNeeded,
            'sheets_needed' => $paperSheetsNeeded,
            'total_impressions' => $totalPrintingForms,
        ];
    }

    /**
     * PASO 1 LEGACY: Calcular opciones de montaje disponibles
     * (Mantiene compatibilidad con código existente usando CuttingCalculatorService)
     * Soporta montaje automático y manual
     */
    public function calculateMountingOptions(SimpleItem $item): array
    {
        if (!$item->paper) {
            return [];
        }

        // Determinar dimensiones del papel según tipo de montaje
        $paperWidth = $item->paper->width;
        $paperHeight = $item->paper->height;
        $mountingType = $item->mounting_type ?? 'automatic';

        // Si es montaje custom, usar dimensiones personalizadas del papel
        if ($mountingType === 'custom' && $item->custom_paper_width && $item->custom_paper_height) {
            $paperWidth = $item->custom_paper_width;
            $paperHeight = $item->custom_paper_height;
        }

        $options = [];
        $orientations = ['horizontal', 'vertical', 'maximum'];

        // Calcular cantidad total incluyendo sobrante de papel
        $totalQuantityWithWaste = (int) $item->quantity + ($item->sobrante_papel ?? 0);

        foreach ($orientations as $orientation) {
            try {
                $result = $this->cuttingCalculator->calculateCuts(
                    paperWidth: $paperWidth,
                    paperHeight: $paperHeight,
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
     * PASO 2 NUEVO: Calcular millares de impresión basado en IMPRESIONES (no pliegos)
     * Fórmula: Millares = (Impresiones × Colores) ÷ 1000
     */
    public function calculatePrintingMillaresNew(SimpleItem $item, array $mountingWithCuts): PrintingCalculation
    {
        // Calcular total de colores
        if ($item->front_back_plate) {
            $totalColors = max($item->ink_front_count, $item->ink_back_count);
        } else {
            $totalColors = $item->ink_front_count + $item->ink_back_count;
        }

        // Obtener impresiones totales del nuevo cálculo
        $totalImpressions = $mountingWithCuts['total_impressions'];

        // Determinar cantidad a cobrar en impresión según regla de sobrante
        $quantityForPrinting = $totalImpressions;
        $sobrante = $item->sobrante_papel ?? 0;

        // Si el sobrante es mayor a 100, cobrar la cantidad total (original + sobrante)
        if ($sobrante > 100) {
            $quantityForPrinting = $totalImpressions + ceil($sobrante / $mountingWithCuts['copies_per_mounting']);
        }

        // Fórmula NUEVA: Impresiones ÷ 1000
        $millaresRaw = $quantityForPrinting / 1000;

        // REGLA: Siempre redondear HACIA ARRIBA si son más de 100 ejemplares de más
        $millaresFinal = $this->roundUpMillares($millaresRaw) * $totalColors;

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
     * PASO 2 LEGACY: Calcular millares de impresión con redondeo hacia arriba
     * (Mantiene compatibilidad con código existente)
     */
    public function calculatePrintingMillares(SimpleItem $item, MountingOption $mountingOption): PrintingCalculation
    {

        // Si es tiro y retiro plancha, ajustar cálculo
        if ($item->front_back_plate) {
            $totalColors = max($item->ink_front_count, $item->ink_back_count);
        } else {
            $totalColors = $item->ink_front_count + $item->ink_back_count;
        }

        // Determinar cantidad a cobrar en impresión según regla de sobrante
        $quantityForPrinting = $mountingOption->sheetsNeeded * $mountingOption->cutsPerSheet;
        $sobrante = $item->sobrante_papel ?? 0;

        // Si el sobrante es mayor a 100, cobrar la cantidad total (original + sobrante)
        if ($sobrante > 100) {
            $quantityForPrinting = $mountingOption->sheetsNeeded * $mountingOption->cutsPerSheet + $sobrante;
        }

        // Fórmula: (Total_colores × Cantidad_para_impresión) ÷ 1000
        $millaresRaw = ($quantityForPrinting) / 1000;

        // REGLA: Siempre redondear HACIA ARRIBA si son mas de 100 ejemplares de más
        $millaresFinal = $this->roundUpMillares($millaresRaw) * $totalColors;

        // Calcular costo
        $printingCost = $millaresFinal  * $item->printingMachine->cost_per_impression;

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

        // Calcular acabados si existen
        $finishingsCost = $this->calculateFinishingsCost($item);

        return new AdditionalCosts(
            designCost: $item->design_value ?? 0,
            transportCost: $item->transport_value ?? 0,
            rifleCost: $item->rifle_value ?? 0,
            cuttingCost: $cuttingCost,
            mountingCost: $mountingCost,
            ctpCost: $ctpCost,
            finishingsCost: $finishingsCost
        );
    }

    /**
     * PASO 4 NUEVO: Calcular precio final con sistema de montaje y cortes integrado
     */
    public function calculateFinalPricingNew(SimpleItem $item): ?PricingResult
    {
        // Usar el nuevo sistema de cálculo
        $mountingWithCuts = $this->calculateMountingWithCuts($item);

        if (!$mountingWithCuts) {
            return null;
        }

        // Calcular millares con el nuevo método
        $printingCalc = $this->calculatePrintingMillaresNew($item, $mountingWithCuts);

        // Calcular costos adicionales (usar pliegos del nuevo cálculo)
        $cuttingCost = ($item->cutting_cost > 0)
            ? $item->cutting_cost
            : $this->calculateCuttingCostFromSheets($mountingWithCuts['paper_sheets_needed']);

        $mountingCost = ($item->mounting_cost > 0)
            ? $item->mounting_cost
            : $this->calculateMountingCost($item, null);

        $ctpCost = $this->calculateCtpCost($item);

        // Calcular acabados si existen
        $finishingsCost = $this->calculateFinishingsCost($item);

        $additionalCosts = new AdditionalCosts(
            designCost: $item->design_value ?? 0,
            transportCost: $item->transport_value ?? 0,
            rifleCost: $item->rifle_value ?? 0,
            cuttingCost: $cuttingCost,
            mountingCost: $mountingCost,
            ctpCost: $ctpCost,
            finishingsCost: $finishingsCost
        );

        // Sumar costos base
        $subtotal = $mountingWithCuts['paper_cost'] +
                   $printingCalc->totalCost +
                   $additionalCosts->getTotalCost();

        // Aplicar margen de ganancia
        $profitAmount = $subtotal * ($item->profit_percentage / 100);
        $finalPrice = $subtotal + $profitAmount;

        // Crear MountingOption compatible para el resultado
        $mountingOption = new MountingOption(
            orientation: 'maximum',
            cutsPerSheet: $mountingWithCuts['copies_per_form'],
            sheetsNeeded: $mountingWithCuts['paper_sheets_needed'],
            utilizationPercentage: $mountingWithCuts['utilization_percentage'],
            wastePercentage: 100 - $mountingWithCuts['utilization_percentage'],
            paperCost: $mountingWithCuts['paper_cost'],
            cuttingLayout: $mountingWithCuts['divisor_layout'],
            rawCalculation: $mountingWithCuts,
            // NUEVAS PROPIEDADES CON TERMINOLOGÍA CORRECTA
            copiesPerForm: $mountingWithCuts['copies_per_form'],
            formsPerPaperSheet: $mountingWithCuts['forms_per_paper_sheet'],
            paperSheetsNeeded: $mountingWithCuts['paper_sheets_needed'],
            printingFormsNeeded: $mountingWithCuts['printing_forms_needed']
        );

        return new PricingResult(
            mountingOption: $mountingOption,
            printingCalculation: $printingCalc,
            additionalCosts: $additionalCosts,
            subtotal: $subtotal,
            profitPercentage: $item->profit_percentage,
            profitAmount: $profitAmount,
            finalPrice: $finalPrice,
            unitPrice: $finalPrice / $item->quantity,
            costBreakdown: $this->generateCostBreakdownNew($mountingWithCuts, $printingCalc, $additionalCosts)
        );
    }

    /**
     * PASO 4 LEGACY: Calcular precio final con desglose completo
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
        if ($millares < 1) {
            return 1; // Mínimo 1 millar
        }

        // Obtener la parte decimal
        $decimalPart = $millares - intval($millares);

        // Solo redondear hacia arriba si el decimal es mayor que 1
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

    private function calculateCuttingCostFromSheets(int $sheetsNeeded): float
    {
        // Costo base por corte
        $baseCostPerSheet = 50; // $50 por pliego cortado

        return $sheetsNeeded * $baseCostPerSheet;
    }

    private function calculateMountingCost(SimpleItem $item, ?MountingOption $mountingOption): float
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

    /**
     * Calcular el costo total de todos los acabados del SimpleItem
     */
    private function calculateFinishingsCost(SimpleItem $item): float
    {
        if (!$item->relationLoaded('finishings') || $item->finishings->isEmpty()) {
            return 0;
        }

        $total = 0;
        $finishingCalculator = new FinishingCalculatorService();

        foreach ($item->finishings as $finishing) {
            $params = $this->buildFinishingParams($item, $finishing);

            try {
                $cost = $finishingCalculator->calculateCost($finishing, $params);
                $total += $cost;
            } catch (\Exception $e) {
                // Si hay error en el cálculo, continuar con el siguiente
                continue;
            }
        }

        return $total;
    }

    /**
     * Construir parámetros para el cálculo de un acabado según su tipo
     */
    private function buildFinishingParams(SimpleItem $item, \App\Models\Finishing $finishing): array
    {
        return match($finishing->measurement_unit) {
            \App\Enums\FinishingMeasurementUnit::MILLAR,
            \App\Enums\FinishingMeasurementUnit::RANGO,
            \App\Enums\FinishingMeasurementUnit::UNIDAD => [
                'quantity' => (int) $item->quantity
            ],
            \App\Enums\FinishingMeasurementUnit::TAMAÑO => [
                'width' => (float) $item->horizontal_size,
                'height' => (float) $item->vertical_size
            ],
            default => []
        };
    }

    private function generateCostBreakdownNew(array $mountingWithCuts, PrintingCalculation $printing, AdditionalCosts $additional): array
    {
        return [
            'paper' => [
                'description' => 'Papel',
                'quantity' => $mountingWithCuts['paper_sheets_needed'] . ' pliegos',
                'cost' => $mountingWithCuts['paper_cost']
            ],
            'printing' => [
                'description' => 'Impresión',
                'quantity' => $printing->millaresFinal . ' millares (' . $mountingWithCuts['printing_forms_needed'] . ' hojas)',
                'cost' => $printing->printingCost
            ],
            'setup' => [
                'description' => 'Alistamiento',
                'quantity' => '1 trabajo',
                'cost' => $printing->setupCost
            ],
            'cutting' => [
                'description' => 'Corte',
                'quantity' => $mountingWithCuts['paper_sheets_needed'] . ' pliegos',
                'cost' => $additional->cuttingCost
            ],
            'mounting' => [
                'description' => 'Montaje',
                'quantity' => $mountingWithCuts['copies_per_form'] . ' copias/hoja × ' . $mountingWithCuts['forms_per_paper_sheet'] . ' hojas/pliego',
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
            ],
            'finishings' => [
                'description' => 'Acabados',
                'quantity' => 'Varios',
                'cost' => $additional->finishingsCost
            ]
        ];
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
            ],
            'finishings' => [
                'description' => 'Acabados',
                'quantity' => 'Varios',
                'cost' => $additional->finishingsCost
            ]
        ];
    }
}

// ==================== DATA CLASSES ====================

/**
 * Opción de montaje disponible para un item
 *
 * TERMINOLOGÍA CORRECTA:
 * - copiesPerForm: Copias que caben en una hoja
 * - formsPerPaperSheet: Hojas que salen de un pliego (divisor)
 * - paperSheetsNeeded: Pliegos necesarios
 * - printingFormsNeeded: Hojas a imprimir
 */
class MountingOption
{
    public function __construct(
        public readonly string $orientation,
        public readonly int $cutsPerSheet,              // LEGACY: usar copiesPerForm
        public readonly int $sheetsNeeded,              // LEGACY: usar paperSheetsNeeded
        public readonly float $utilizationPercentage,
        public readonly float $wastePercentage,
        public readonly float $paperCost,
        public readonly array $cuttingLayout,
        public readonly array $rawCalculation = [],
        // NUEVAS PROPIEDADES CON TERMINOLOGÍA CORRECTA
        public readonly ?int $copiesPerForm = null,
        public readonly ?int $formsPerPaperSheet = null,
        public readonly ?int $paperSheetsNeeded = null,
        public readonly ?int $printingFormsNeeded = null,
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
        public readonly float $ctpCost = 0,
        public readonly float $finishingsCost = 0
    ) {}

    public function getTotalCost(): float
    {
        return $this->designCost +
               $this->transportCost +
               $this->rifleCost +
               $this->cuttingCost +
               $this->mountingCost +
               $this->ctpCost +
               $this->finishingsCost;
    }

    public function getBreakdown(): array
    {
        return [
            'design' => $this->designCost,
            'transport' => $this->transportCost,
            'rifle' => $this->rifleCost,
            'cutting' => $this->cuttingCost,
            'mounting' => $this->mountingCost,
            'ctp' => $this->ctpCost,
            'finishings' => $this->finishingsCost
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