<?php

namespace App\Services;

use App\Enums\FinishingMeasurementUnit;
use App\Models\Finishing;
use App\Models\FinishingRange;
use Illuminate\Support\Collection;

class FinishingCalculatorService
{
    /**
     * Calcular el costo de un acabado según sus parámetros
     */
    public function calculateCost(Finishing $finishing, array $params): float
    {
        return match ($finishing->measurement_unit) {
            FinishingMeasurementUnit::MILLAR => $this->calculateMillarCost(
                $params['quantity'], 
                $finishing->unit_price
            ),
            FinishingMeasurementUnit::RANGO => $this->calculateRangeCost(
                $params['quantity'], 
                $finishing->ranges
            ),
            FinishingMeasurementUnit::UNIDAD => $this->calculateUnitCost(
                $params['quantity'], 
                $finishing->unit_price
            ),
            FinishingMeasurementUnit::TAMAÑO => $this->calculateSizeCost(
                $params['width'], 
                $params['height'], 
                $finishing->unit_price
            ),
        };
    }

    /**
     * Calcular costo por millar
     * Fórmula: ceil(cantidad ÷ 1000) × precio_millar
     */
    public function calculateMillarCost(int $quantity, float $unitPrice): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        $millares = ceil($quantity / 1000);
        
        return (float) ($millares * $unitPrice);
    }

    /**
     * Calcular costo por rango
     * Busca el rango apropiado según la cantidad
     */
    public function calculateRangeCost(int $quantity, Collection|array $ranges): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        // Convertir a Collection si es array
        if (is_array($ranges)) {
            $ranges = collect($ranges);
        }

        // Buscar el rango que contenga la cantidad
        $applicableRange = $ranges->first(function (FinishingRange $range) use ($quantity) {
            return $range->containsQuantity($quantity);
        });

        if (!$applicableRange) {
            throw new \InvalidArgumentException("No se encontró un rango válido para la cantidad: {$quantity}");
        }

        return (float) $applicableRange->range_price;
    }

    /**
     * Calcular costo por unidad
     * Fórmula: cantidad × precio_unitario
     */
    public function calculateUnitCost(int $quantity, float $unitPrice): float
    {
        if ($quantity <= 0) {
            return 0.0;
        }

        return (float) ($quantity * $unitPrice);
    }

    /**
     * Calcular costo por tamaño
     * Fórmula: ancho × alto × precio_unitario
     */
    public function calculateSizeCost(float $width, float $height, float $unitPrice): float
    {
        if ($width <= 0 || $height <= 0) {
            return 0.0;
        }

        $area = $width * $height;
        
        return (float) ($area * $unitPrice);
    }

    /**
     * Validar parámetros según el tipo de medida
     */
    public function validateParams(FinishingMeasurementUnit $measurementUnit, array $params): array
    {
        $errors = [];

        switch ($measurementUnit) {
            case FinishingMeasurementUnit::MILLAR:
            case FinishingMeasurementUnit::RANGO:
            case FinishingMeasurementUnit::UNIDAD:
                if (!isset($params['quantity']) || $params['quantity'] <= 0) {
                    $errors[] = 'La cantidad debe ser mayor a 0';
                }
                break;

            case FinishingMeasurementUnit::TAMAÑO:
                if (!isset($params['width']) || $params['width'] <= 0) {
                    $errors[] = 'El ancho debe ser mayor a 0';
                }
                if (!isset($params['height']) || $params['height'] <= 0) {
                    $errors[] = 'El alto debe ser mayor a 0';
                }
                break;
        }

        return $errors;
    }

    /**
     * Obtener parámetros por defecto según el tipo de medida
     */
    public function getDefaultParams(FinishingMeasurementUnit $measurementUnit): array
    {
        return match ($measurementUnit) {
            FinishingMeasurementUnit::MILLAR,
            FinishingMeasurementUnit::RANGO,
            FinishingMeasurementUnit::UNIDAD => ['quantity' => 1],
            FinishingMeasurementUnit::TAMAÑO => ['width' => 1.0, 'height' => 1.0],
        };
    }

    /**
     * Obtener los campos requeridos según el tipo de medida
     */
    public function getRequiredFields(FinishingMeasurementUnit $measurementUnit): array
    {
        return match ($measurementUnit) {
            FinishingMeasurementUnit::MILLAR,
            FinishingMeasurementUnit::RANGO,
            FinishingMeasurementUnit::UNIDAD => ['quantity'],
            FinishingMeasurementUnit::TAMAÑO => ['width', 'height'],
        };
    }
}