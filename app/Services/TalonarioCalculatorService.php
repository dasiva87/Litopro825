<?php

namespace App\Services;

use App\Models\TalonarioItem;

class TalonarioCalculatorService
{
    /**
     * Calcular el precio final completo de un talonario
     */
    public function calculateFinalPricing(TalonarioItem $talonario): TalonarioPricingResult
    {
        // PASO 1: Calcular costo de hojas (SimpleItems)
        $sheetsCost = $this->calculateSheetsTotal($talonario);
        
        // PASO 2: Calcular costo de acabados
        $finishingCost = $this->calculateFinishingCost($talonario);
        
        // PASO 3: Calcular subtotal
        $subtotal = $sheetsCost + $finishingCost + 
                   $talonario->design_value + $talonario->transport_value;
        
        // PASO 4: Aplicar ganancia
        $finalPrice = $subtotal;
        if ($talonario->profit_percentage > 0) {
            $finalPrice = $subtotal * (1 + ($talonario->profit_percentage / 100));
        }

        return new TalonarioPricingResult(
            sheetsCost: $sheetsCost,
            finishingCost: $finishingCost,
            designValue: (float) ($talonario->design_value ?? 0),
            transportValue: (float) ($talonario->transport_value ?? 0),
            totalCost: $subtotal,
            profitPercentage: (float) ($talonario->profit_percentage ?? 0),
            profitAmount: $finalPrice - $subtotal,
            finalPrice: $finalPrice
        );
    }

    /**
     * PASO 1: Calcular costo total de hojas (como páginas de revista)
     */
    public function calculateSheetsTotal(TalonarioItem $talonario): float
    {
        if ($talonario->sheets->count() === 0) {
            return 0;
        }

        $total = 0;
        foreach ($talonario->sheets as $sheet) {
            if ($sheet->simpleItem) {
                // Asegurar que el SimpleItem tenga precios calculados
                $sheet->simpleItem->calculateAll();
                $total += $sheet->simpleItem->final_price;
            }
        }

        return $total;
    }

    /**
     * PASO 2: Calcular costo de acabados específicos
     */
    public function calculateFinishingCost(TalonarioItem $talonario): float
    {
        $total = 0;
        
        foreach ($talonario->finishings as $finishing) {
            $quantity = $this->calculateFinishingQuantity($finishing, $talonario);
            $unitCost = $finishing->pivot->unit_cost ?? $finishing->unit_price ?? 0;
            
            $finishingTotal = $quantity * $unitCost;
            
            // Actualizar el pivot si es necesario
            if ($finishing->pivot->total_cost != $finishingTotal) {
                $talonario->finishings()->updateExistingPivot($finishing->id, [
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'total_cost' => $finishingTotal
                ]);
            }
            
            $total += $finishingTotal;
        }

        return $total;
    }

    /**
     * Calcular cantidad según tipo de acabado
     */
    private function calculateFinishingQuantity($finishing, TalonarioItem $talonario): int
    {
        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
        $totalTalonarios = ceil($totalNumbers / $talonario->numeros_por_talonario);
        
        return match($finishing->measurement_unit->value) {
            'por_numero' => $totalNumbers,         // Numeración: 1000 números
            'por_talonario' => $totalTalonarios,   // Perforación, etc: 40 talonarios  
            'millar' => ceil($totalNumbers / 1000), // Si se factura por millar
            'unidad' => $talonario->quantity,      // Por cantidad solicitada
            default => $talonario->quantity
        };
    }

    /**
     * Validar viabilidad técnica del talonario
     */
    public function validateTechnicalViability(TalonarioItem $talonario): TalonarioValidationResult
    {
        $errors = [];
        $warnings = [];

        // Validaciones dimensionales
        if ($talonario->ancho <= 0 || $talonario->alto <= 0) {
            $errors[] = 'Las dimensiones del talonario deben ser mayores a 0';
        }

        if ($talonario->ancho > 30 || $talonario->alto > 40) {
            $warnings[] = 'Dimensiones muy grandes pueden incrementar costos significativamente';
        }

        // Validaciones de numeración
        if ($talonario->numero_final <= $talonario->numero_inicial) {
            $errors[] = 'El número final debe ser mayor al número inicial';
        }

        $totalNumbers = ($talonario->numero_final - $talonario->numero_inicial) + 1;
        if ($totalNumbers > 100000) {
            $warnings[] = 'Rangos muy grandes requieren planificación especial de numeración';
        }

        // Validaciones de cantidad
        if ($talonario->quantity <= 0) {
            $errors[] = 'La cantidad debe ser mayor a 0';
        }

        if ($talonario->quantity > 1000) {
            $warnings[] = 'Cantidades muy altas requieren planificación especial de producción';
        }

        // Validaciones de hojas
        if ($talonario->sheets->count() === 0) {
            $errors[] = 'El talonario debe tener al menos una hoja';
        }

        if ($talonario->sheets->count() > 5) {
            $warnings[] = 'Más de 5 hojas puede complicar el proceso de armado';
        }

        // Validar números por talonario
        if ($talonario->numeros_por_talonario > 100) {
            $warnings[] = 'Más de 100 números por talonario puede dificultar el manejo';
        }

        // Validaciones de hojas críticas
        $hasOriginal = $talonario->sheets->where('sheet_type', 'original')->count() > 0;
        if (!$hasOriginal) {
            $warnings[] = 'Se recomienda incluir una hoja original';
        }

        // Validaciones de SimpleItems asociados
        foreach ($talonario->sheets as $sheet) {
            if (!$sheet->simpleItem) {
                $errors[] = "La hoja '{$sheet->sheet_type}' (orden {$sheet->sheet_order}) no tiene un SimpleItem asociado";
                continue;
            }

            // Validar dimensiones de hojas vs talonario
            if ($sheet->simpleItem->horizontal_size != $talonario->ancho || 
                $sheet->simpleItem->vertical_size != $talonario->alto) {
                $warnings[] = "La hoja '{$sheet->sheet_type}' tiene dimensiones diferentes al talonario";
            }

            // Validar cantidad de SimpleItems
            $expectedQuantity = $totalNumbers * $talonario->quantity;
            if ($sheet->simpleItem->quantity != $expectedQuantity) {
                $warnings[] = "La hoja '{$sheet->sheet_type}' tiene cantidad incorrecta (esperado: {$expectedQuantity})";
            }
        }

        return new TalonarioValidationResult(
            errors: $errors,
            warnings: $warnings,
            isValid: empty($errors)
        );
    }

    /**
     * Obtener desglose detallado de costos
     */
    public function getDetailedBreakdown(TalonarioItem $talonario): array
    {
        $result = $this->calculateFinalPricing($talonario);
        
        // Desglose por hojas
        $sheetsBreakdown = [];
        foreach ($talonario->sheets as $sheet) {
            if ($sheet->simpleItem) {
                $sheetsBreakdown[] = [
                    'sheet_type' => $sheet->sheet_type_name,
                    'sheet_order' => $sheet->sheet_order,
                    'paper_color' => $sheet->paper_color_name,
                    'unit_cost' => $sheet->simpleItem->final_price,
                    'total_cost' => $sheet->total_cost,
                    'description' => $sheet->simpleItem->description,
                ];
            }
        }

        // Desglose por acabados
        $finishingsBreakdown = [];
        foreach ($talonario->finishings as $finishing) {
            $finishingsBreakdown[] = [
                'name' => $finishing->name,
                'measurement_unit' => $finishing->measurement_unit->label(),
                'quantity' => $finishing->pivot->quantity,
                'unit_cost' => $finishing->pivot->unit_cost,
                'total_cost' => $finishing->pivot->total_cost,
            ];
        }

        // Métricas del talonario
        $metrics = [
            'total_numbers' => $talonario->total_numbers,
            'total_talonarios' => $talonario->total_talonarios,
            'sheets_per_talonario' => $talonario->sheets_per_talonario,
            'closed_area' => $talonario->closed_area,
            'numbering_range' => $talonario->numbering_range
        ];

        return [
            'metrics' => $metrics,
            'sheets' => [
                'items' => $sheetsBreakdown,
                'total' => $result->sheetsCost,
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
                'unit_price' => $talonario->quantity > 0 ? $result->finalPrice / $talonario->quantity : 0,
            ]
        ];
    }

    /**
     * Generar configuración automática de hojas típica
     */
    public function generateDefaultSheets(TalonarioItem $talonario): array
    {
        return [
            [
                'sheet_type' => 'original',
                'sheet_order' => 1,
                'paper_color' => 'blanco',
                'description' => 'Hoja original del talonario'
            ],
            [
                'sheet_type' => 'copia_1',
                'sheet_order' => 2,
                'paper_color' => 'amarillo',
                'description' => 'Primera copia (cliente)'
            ],
            [
                'sheet_type' => 'copia_2',
                'sheet_order' => 3,
                'paper_color' => 'rosado',
                'description' => 'Segunda copia (archivo)'
            ]
        ];
    }

    /**
     * Sugerir acabados típicos para talonarios
     */
    public function suggestDefaultFinishings(): array
    {
        return [
            'numeracion' => [
                'name' => 'Numeración Consecutiva',
                'measurement_unit' => 'por_numero',
                'recommended' => true,
                'description' => 'Numeración automática consecutiva'
            ],
            'perforacion' => [
                'name' => 'Perforación para Desprendimiento',
                'measurement_unit' => 'por_talonario',
                'recommended' => true,
                'description' => 'Perforación para facilitar desprendimiento'
            ],
            'engomado' => [
                'name' => 'Engomado Superior',
                'measurement_unit' => 'por_talonario',
                'recommended' => false,
                'description' => 'Aplicación de goma en la parte superior'
            ],
            'armado' => [
                'name' => 'Armado en Bloques',
                'measurement_unit' => 'por_talonario',
                'recommended' => true,
                'description' => 'Armado y empaquetado en bloques'
            ]
        ];
    }
}

/**
 * Clase para encapsular el resultado del cálculo de precios
 */
class TalonarioPricingResult
{
    public function __construct(
        public float $sheetsCost,
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
class TalonarioValidationResult
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