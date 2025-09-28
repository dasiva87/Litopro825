<?php

namespace App\Filament\Resources\Documents\RelationManagers\Traits;

trait CalculatesProducts
{
    private function calculateProductTotal($get, $set): void
    {
        $productId = $get('product_id');
        $quantity = $get('quantity') ?? 1;
        $profitMargin = $get('profit_margin') ?? 0;

        if (!$productId || $quantity <= 0) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($productId);

        if (!$product) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        // Precio base sin margen
        $baseUnitPrice = $product->sale_price;
        $baseTotal = $baseUnitPrice * $quantity;

        // Aplicar margen de ganancia
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $set('unit_price', round($unitPriceWithMargin, 2));
        $set('total_price', round($totalPriceWithMargin, 2));
    }

    private function recalculateItemTotal($set, $get): void
    {
        $quantity = $get('quantity') ?? 1;
        $unitPrice = $get('unit_price') ?? 0;
        $profitMargin = $get('profit_margin') ?? 0;

        if ($quantity > 0 && $unitPrice > 0) {
            // Precio base sin margen
            $baseTotal = $quantity * $unitPrice;

            // Aplicar margen de ganancia
            $finalTotal = $baseTotal * (1 + ($profitMargin / 100));

            $set('total_price', round($finalTotal, 2));
        } else {
            $set('total_price', 0);
        }
    }
}