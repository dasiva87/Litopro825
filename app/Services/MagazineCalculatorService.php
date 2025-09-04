<?php

namespace App\Services;

use App\Models\MagazineItem;

class MagazineCalculatorService
{
    /**
     * Calcular el precio final completo de una revista
     */
    public function calculateFinalPricing(MagazineItem $magazine): MagazinePricingResult
    {
        // PASO 1: Calcular costo de páginas
        $pagesCost = $this->calculatePagesTotal($magazine);
        
        // PASO 2: Calcular costo de encuadernación
        $bindingCost = $this->calculateBindingCost($magazine);
        
        // PASO 3: Calcular costo de armado
        $assemblyCost = $this->calculateAssemblyCost($magazine);
        
        // PASO 4: Calcular costo de acabados
        $finishingCost = $this->calculateFinishingCost($magazine);
        
        // PASO 5: Calcular subtotal
        $subtotal = $pagesCost + $bindingCost + $assemblyCost + $finishingCost + 
                   $magazine->design_value + $magazine->transport_value;
        
        // PASO 6: Aplicar ganancia
        $finalPrice = $subtotal;
        if ($magazine->profit_percentage > 0) {
            $finalPrice = $subtotal * (1 + ($magazine->profit_percentage / 100));
        }

        return new MagazinePricingResult(
            pagesCost: $pagesCost,
            bindingCost: $bindingCost,
            assemblyCost: $assemblyCost,
            finishingCost: $finishingCost,
            designValue: (float) ($magazine->design_value ?? 0),
            transportValue: (float) ($magazine->transport_value ?? 0),
            totalCost: $subtotal,
            profitPercentage: (float) ($magazine->profit_percentage ?? 0),
            profitAmount: $finalPrice - $subtotal,
            finalPrice: $finalPrice
        );
    }

    /**
     * PASO 1: Calcular costo total de páginas
     */
    public function calculatePagesTotal(MagazineItem $magazine): float
    {
        if ($magazine->pages->count() === 0) {
            return 0;
        }

        $total = 0;
        foreach ($magazine->pages as $page) {
            if ($page->simpleItem) {
                // Asegurar que el SimpleItem tenga precios calculados
                $page->simpleItem->calculateAll();
                $total += $page->simpleItem->final_price * $page->page_quantity;
            }
        }

        return $total;
    }

    /**
     * PASO 2: Calcular costo de encuadernación
     */
    public function calculateBindingCost(MagazineItem $magazine): float
    {
        if (!$magazine->binding_type || !$magazine->quantity) {
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

        $baseCost = $baseCosts[$magazine->binding_type] ?? 500;
        
        // Factor de complejidad basado en el número de páginas
        $totalPages = $magazine->pages->sum('page_quantity');
        $complexityFactor = $this->getComplexityFactor($totalPages);
        
        // Factor de posición de encuadernación
        $positionFactor = $this->getBindingPositionFactor($magazine->binding_side);

        return $baseCost * $magazine->quantity * $complexityFactor * $positionFactor;
    }

    /**
     * PASO 3: Calcular costo de armado
     */
    public function calculateAssemblyCost(MagazineItem $magazine): float
    {
        if (!$magazine->quantity) {
            return 0;
        }

        // Costo base de armado por revista
        $baseAssemblyCost = 300;
        $totalPages = $magazine->pages->sum('page_quantity');
        
        // Factor basado en número de páginas (más páginas = más complejidad)
        $pagesFactor = 1 + ($totalPages * 0.02); // 2% por página adicional
        
        // Factor basado en tipos de página diferentes
        $pageTypes = $magazine->pages->pluck('page_type')->unique()->count();
        $varietyFactor = 1 + ($pageTypes * 0.1); // 10% por cada tipo diferente
        
        return $baseAssemblyCost * $magazine->quantity * $pagesFactor * $varietyFactor;
    }

    /**
     * PASO 4: Calcular costo de acabados
     */
    public function calculateFinishingCost(MagazineItem $magazine): float
    {
        $total = 0;
        
        foreach ($magazine->finishings as $finishing) {
            $quantity = $finishing->pivot->quantity ?? $magazine->quantity;
            $unitCost = $finishing->pivot->unit_cost ?? $finishing->cost_per_unit ?? 0;
            
            $finishingTotal = $quantity * $unitCost;
            
            // Actualizar el pivot si es necesario
            if ($finishing->pivot->total_cost != $finishingTotal) {
                $magazine->finishings()->updateExistingPivot($finishing->id, [
                    'total_cost' => $finishingTotal
                ]);
            }
            
            $total += $finishingTotal;
        }

        return $total;
    }

    /**
     * Obtener factor de complejidad basado en número de páginas
     */
    private function getComplexityFactor(int $totalPages): float
    {
        if ($totalPages <= 10) return 1.0;
        if ($totalPages <= 20) return 1.15;
        if ($totalPages <= 50) return 1.25;
        if ($totalPages <= 100) return 1.40;
        return 1.60; // Más de 100 páginas
    }

    /**
     * Obtener factor basado en la posición de encuadernación
     */
    private function getBindingPositionFactor(string $side): float
    {
        $factors = [
            'izquierda' => 1.0,  // Estándar
            'derecha' => 1.05,   // Ligeramente más complejo
            'arriba' => 1.15,    // Más complejo
            'abajo' => 1.10,     // Complejo
        ];

        return $factors[$side] ?? 1.0;
    }

    /**
     * Validar viabilidad técnica de la revista
     */
    public function validateTechnicalViability(MagazineItem $magazine): MagazineValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validaciones dimensionales
        if ($magazine->closed_width <= 0 || $magazine->closed_height <= 0) {
            $errors[] = 'Las dimensiones cerradas deben ser mayores a 0';
        }

        if ($magazine->closed_width > 50 || $magazine->closed_height > 70) {
            $warnings[] = 'Dimensiones muy grandes pueden incrementar costos significativamente';
        }

        // Validaciones de cantidad
        if ($magazine->quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }

        if ($magazine->quantity > 10000) {
            $warnings[] = 'Cantidades muy altas requieren planificación especial de producción';
        }

        // Validaciones de páginas
        if ($magazine->pages->count() === 0) {
            $errors[] = 'La revista debe tener al menos una página';
        }

        $totalPages = $magazine->pages->sum('page_quantity');
        if ($totalPages < 4) {
            $warnings[] = 'Revistas con menos de 4 páginas son poco comunes';
        }

        // Validaciones de encuadernación vs páginas
        $bindingLimits = [
            'grapado' => 80,
            'plegado' => 32,
            'anillado' => 500,
            'cosido' => 300,
            'caballete' => 64,
            'espiral' => 500,
            'wire_o' => 400,
        ];

        $limit = $bindingLimits[$magazine->binding_type] ?? 100;
        if ($totalPages > $limit) {
            $errors[] = "El tipo de encuadernación '{$magazine->binding_type}' no es recomendable para {$totalPages} páginas (máximo recomendado: {$limit})";
        }

        // Validaciones de páginas críticas
        $pageTypes = $magazine->pages->pluck('page_type')->toArray();
        if (!in_array('portada', $pageTypes)) {
            $warnings[] = 'Se recomienda incluir una portada';
        }

        // Validaciones de SimpleItems asociados
        foreach ($magazine->pages as $page) {
            if (!$page->simpleItem) {
                $errors[] = "La página '{$page->page_type}' (orden {$page->page_order}) no tiene un SimpleItem asociado";
                continue;
            }

            $simpleItemValidation = $page->simpleItem->validateTechnicalViability();
            if (!empty($simpleItemValidation)) {
                foreach ($simpleItemValidation as $validation) {
                    if ($validation['type'] === 'error') {
                        $errors[] = "Página '{$page->page_type}': " . $validation['message'];
                    } elseif ($validation['type'] === 'warning') {
                        $warnings[] = "Página '{$page->page_type}': " . $validation['message'];
                    }
                }
            }
        }

        return new MagazineValidationResult(
            errors: $errors,
            warnings: $warnings,
            isValid: empty($errors)
        );
    }

    /**
     * Obtener desglose detallado de costos
     */
    public function getDetailedBreakdown(MagazineItem $magazine): array
    {
        $result = $this->calculateFinalPricing($magazine);
        
        // Desglose por páginas
        $pagesBreakdown = [];
        foreach ($magazine->pages as $page) {
            if ($page->simpleItem) {
                $pagesBreakdown[] = [
                    'page_type' => $page->page_type_name,
                    'page_order' => $page->page_order,
                    'quantity' => $page->page_quantity,
                    'unit_cost' => $page->simpleItem->final_price,
                    'total_cost' => $page->total_cost,
                    'description' => $page->simpleItem->description,
                ];
            }
        }

        // Desglose por acabados
        $finishingsBreakdown = [];
        foreach ($magazine->finishings as $finishing) {
            $finishingsBreakdown[] = [
                'name' => $finishing->name,
                'quantity' => $finishing->pivot->quantity,
                'unit_cost' => $finishing->pivot->unit_cost,
                'total_cost' => $finishing->pivot->total_cost,
            ];
        }

        return [
            'pages' => [
                'items' => $pagesBreakdown,
                'total' => $result->pagesCost,
            ],
            'binding' => [
                'type' => $magazine->binding_type_name,
                'side' => $magazine->binding_side_name,
                'total' => $result->bindingCost,
            ],
            'assembly' => [
                'total_pages' => $magazine->total_pages,
                'total' => $result->assemblyCost,
            ],
            'finishings' => [
                'items' => $finishingsBreakdown,
                'total' => $result->finishingCost,
            ],
            'additional_costs' => [
                'design' => $result->designValue,
                'transport' => $result->transportValue,
            ],
            'summary' => [
                'subtotal' => $result->totalCost,
                'profit_percentage' => $result->profitPercentage,
                'profit_amount' => $result->profitAmount,
                'final_price' => $result->finalPrice,
                'unit_price' => $magazine->quantity > 0 ? $result->finalPrice / $magazine->quantity : 0,
            ]
        ];
    }
}

/**
 * Clase para encapsular el resultado del cálculo de precios
 */
class MagazinePricingResult
{
    public function __construct(
        public float $pagesCost,
        public float $bindingCost,
        public float $assemblyCost,
        public float $finishingCost,
        public float $designValue,
        public float $transportValue,
        public float $totalCost,
        public float $profitPercentage,
        public float $profitAmount,
        public float $finalPrice
    ) {}
}

/**
 * Clase para encapsular el resultado de la validación
 */
class MagazineValidationResult
{
    public function __construct(
        public array $errors,
        public array $warnings,
        public bool $isValid
    ) {}

    public function getAllMessages(): array
    {
        $messages = [];
        
        foreach ($this->errors as $error) {
            $messages[] = ['type' => 'error', 'message' => $error];
        }
        
        foreach ($this->warnings as $warning) {
            $messages[] = ['type' => 'warning', 'message' => $warning];
        }
        
        return $messages;
    }
}