<?php

namespace App\Services;

use App\Models\DigitalItem;

class DigitalItemCalculatorService
{
    /**
     * Calcular precio total por unidad
     */
    public function calculateByUnit(float $quantity, float $unitValue): float
    {
        return $quantity * $unitValue;
    }

    /**
     * Calcular precio total por tamaño (área en metros)
     */
    public function calculateBySize(float $width, float $height, float $unitValue): float
    {
        // Convertir de cm a metros si es necesario
        $widthM = $this->convertToMeters($width);
        $heightM = $this->convertToMeters($height);
        
        $area = $widthM * $heightM;
        return $area * $unitValue;
    }

    /**
     * Calcular precio total para un DigitalItem con parámetros específicos
     */
    public function calculateTotalPrice(DigitalItem $item, array $params): float
    {
        if ($item->pricing_type === 'unit') {
            $quantity = $params['quantity'] ?? 1;
            return $this->calculateByUnit($quantity, $item->unit_value);
        } else { // 'size'
            $width = $params['width'] ?? 0;
            $height = $params['height'] ?? 0;
            $quantity = $params['quantity'] ?? 1;
            
            $pricePerArea = $this->calculateBySize($width, $height, $item->unit_value);
            return $pricePerArea * $quantity;
        }
    }

    /**
     * Calcular margen de ganancia
     */
    public function calculateProfitMargin(float $salePrice, float $purchasePrice): float
    {
        if ($purchasePrice == 0) {
            return 100.0; // Si no hay precio de compra, asumimos 100% ganancia
        }

        return (($salePrice - $purchasePrice) / $purchasePrice) * 100;
    }

    /**
     * Obtener desglose detallado de costos para un item digital
     */
    public function getDetailedBreakdown(DigitalItem $item, array $params): array
    {
        $quantity = $params['quantity'] ?? 1;
        $totalPrice = $this->calculateTotalPrice($item, $params);
        
        $breakdown = [
            'item_code' => $item->code,
            'item_description' => $item->description,
            'pricing_type' => $item->pricing_type,
            'unit_value' => $item->unit_value,
            'quantity' => $quantity,
            'total_price' => $totalPrice,
            'unit_price' => $totalPrice / $quantity,
            'profit_margin' => $item->profit_margin,
            'purchase_price' => $item->purchase_price,
            'sale_price' => $item->sale_price,
            'supplier_type' => $item->supplier_type,
        ];

        // Agregar información específica según el tipo
        if ($item->pricing_type === 'size') {
            $width = $params['width'] ?? 0;
            $height = $params['height'] ?? 0;
            
            $breakdown['dimensions'] = [
                'width' => $width,
                'height' => $height,
                'area_cm2' => $width * $height,
                'area_m2' => $this->convertToMeters($width) * $this->convertToMeters($height),
            ];
        }

        return $breakdown;
    }

    /**
     * Validar parámetros de entrada
     */
    public function validateParameters(object $item, array $params): array
    {
        $errors = [];

        if ($item->pricing_type === 'unit') {
            $quantity = $params['quantity'] ?? 0;
            if ($quantity <= 0) {
                $errors[] = 'La cantidad debe ser mayor a 0 para items por unidad';
            }
        } else { // 'size'
            $width = $params['width'] ?? 0;
            $height = $params['height'] ?? 0;
            $quantity = $params['quantity'] ?? 0;

            if ($width <= 0) {
                $errors[] = 'El ancho debe ser mayor a 0 para items por tamaño';
            }
            if ($height <= 0) {
                $errors[] = 'El alto debe ser mayor a 0 para items por tamaño';
            }
            if ($quantity <= 0) {
                $errors[] = 'La cantidad debe ser mayor a 0';
            }

            // Validaciones adicionales para dimensiones
            if ($width > 500) { // Máximo 5 metros en cm
                $errors[] = 'El ancho no puede ser mayor a 500 cm';
            }
            if ($height > 500) { // Máximo 5 metros en cm
                $errors[] = 'El alto no puede ser mayor a 500 cm';
            }
        }

        return $errors;
    }

    /**
     * Estimar tiempo de producción (opcional, para futuras extensiones)
     */
    public function estimateProductionTime(object $item, array $params): int
    {
        $baseTime = 24; // Horas base

        if ($item->pricing_type === 'size') {
            $width = $params['width'] ?? 0;
            $height = $params['height'] ?? 0;
            $area = $this->convertToMeters($width) * $this->convertToMeters($height);
            
            // Agregar tiempo por área (1 hora por m²)
            $baseTime += ceil($area);
        }

        $quantity = $params['quantity'] ?? 1;
        if ($quantity > 10) {
            // Agregar tiempo por cantidad alta
            $extraUnits = $quantity - 10;
            $baseTime += ceil($extraUnits / 4); // 1 hora por cada 4 unidades extra
        }

        return $baseTime;
    }

    /**
     * Generar opciones de precios para diferentes cantidades (para cotizaciones)
     */
    public function generatePricingOptions(DigitalItem $item, array $baseParams): array
    {
        $options = [];
        $quantities = [1, 5, 10, 25, 50, 100];

        foreach ($quantities as $qty) {
            $params = array_merge($baseParams, ['quantity' => $qty]);
            
            $totalPrice = $this->calculateTotalPrice($item, $params);
            $unitPrice = $totalPrice / $qty;

            $options[] = [
                'quantity' => $qty,
                'total_price' => $totalPrice,
                'unit_price' => $unitPrice,
                'savings_percentage' => $qty > 1 ? $this->calculateSavingsPercentage($unitPrice, $totalPrice / 1) : 0,
            ];
        }

        return $options;
    }

    /**
     * Convertir centímetros a metros
     */
    private function convertToMeters(float $cm): float
    {
        return $cm / 100;
    }

    /**
     * Calcular porcentaje de ahorro
     */
    private function calculateSavingsPercentage(float $unitPrice, float $basePrice): float
    {
        if ($basePrice == 0) return 0;
        
        return (($basePrice - $unitPrice) / $basePrice) * 100;
    }
}