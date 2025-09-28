<?php

namespace App\Services\Documents;

class DocumentItemCalculationService
{
    public static function calculateFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 1;

        if (! $finishingId) {
            $set('calculated_cost', 0);
            return;
        }

        $finishing = \App\Models\Finishing::find($finishingId);
        if (! $finishing) {
            $set('calculated_cost', 0);
            return;
        }

        // Calcular costo basado en el tipo de acabado
        $calculatedCost = match($finishing->calculation_type) {
            'fixed' => $finishing->base_cost,
            'per_unit' => $finishing->base_cost * $quantity,
            'per_area' => static::calculateAreaBasedCost($finishing, $get),
            default => $finishing->base_cost,
        };

        $set('calculated_cost', $calculatedCost);
    }

    public static function calculateSimpleFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 1;
        $width = $get('horizontal_size') ?? 0;
        $height = $get('vertical_size') ?? 0;

        if (! $finishingId) {
            $set('calculated_cost', 0);
            return;
        }

        $finishing = \App\Models\Finishing::find($finishingId);
        if (! $finishing) {
            $set('calculated_cost', 0);
            return;
        }

        $area = ($width * $height) / 10000; // cm² a m²
        $totalArea = $area * $quantity;

        $calculatedCost = match($finishing->calculation_type) {
            'fixed' => $finishing->base_cost,
            'per_unit' => $finishing->base_cost * $quantity,
            'per_area' => $finishing->base_cost * $totalArea,
            default => $finishing->base_cost,
        };

        $set('calculated_cost', $calculatedCost);
    }

    public static function recalculateItemTotal($set, $get): void
    {
        $itemType = $get('item_type');

        switch ($itemType) {
            case 'digital':
                static::recalculateDigitalItemTotal($set, $get);
                break;
            case 'product':
                static::recalculateProductTotal($set, $get);
                break;
            case 'custom':
                static::recalculateCustomItemTotal($set, $get);
                break;
        }
    }

    private static function recalculateDigitalItemTotal($set, $get): void
    {
        $digitalItemId = $get('itemable_id');
        $quantity = $get('quantity') ?? 1;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if (! $digitalItemId) {
            return;
        }

        $digitalItem = \App\Models\DigitalItem::find($digitalItemId);
        if (! $digitalItem) {
            return;
        }

        $basePrice = 0;
        if ($digitalItem->pricing_type === 'unit') {
            $basePrice = $digitalItem->unit_value * $quantity;
        } elseif ($digitalItem->pricing_type === 'size' && $width && $height) {
            $area = ($width * $height) / 10000; // cm² a m²
            $basePrice = $digitalItem->unit_value * $area * $quantity;
        }

        // Agregar costos de acabados
        $finishings = $get('finishings') ?? [];
        $finishingsCost = 0;
        foreach ($finishings as $finishing) {
            $finishingsCost += $finishing['calculated_cost'] ?? 0;
        }

        $totalPrice = $basePrice + $finishingsCost;
        $unitPrice = $quantity > 0 ? $totalPrice / $quantity : 0;

        $set('unit_price', $unitPrice);
        $set('total_price', $totalPrice);
    }

    private static function recalculateProductTotal($set, $get): void
    {
        $productId = $get('itemable_id');
        $quantity = $get('quantity') ?? 1;
        $profitMargin = $get('profit_margin') ?? 0;

        if (! $productId) {
            return;
        }

        $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($productId);
        if (! $product) {
            return;
        }

        $baseTotal = $product->sale_price * $quantity;
        $marginMultiplier = 1 + ($profitMargin / 100);
        $totalWithMargin = $baseTotal * $marginMultiplier;
        $unitPriceWithMargin = $totalWithMargin / $quantity;

        $set('unit_price', $unitPriceWithMargin);
        $set('total_price', $totalWithMargin);
    }

    private static function recalculateCustomItemTotal($set, $get): void
    {
        $quantity = $get('quantity') ?? 1;
        $unitPrice = $get('unit_price') ?? 0;

        $totalPrice = $quantity * $unitPrice;
        $set('total_price', $totalPrice);
    }

    private static function calculateAreaBasedCost($finishing, $get): float
    {
        $width = $get('width') ?? $get('horizontal_size') ?? 0;
        $height = $get('height') ?? $get('vertical_size') ?? 0;
        $quantity = $get('quantity') ?? 1;

        if ($width && $height) {
            $area = ($width * $height) / 10000; // cm² a m²
            return $finishing->base_cost * $area * $quantity;
        }

        return $finishing->base_cost;
    }
}