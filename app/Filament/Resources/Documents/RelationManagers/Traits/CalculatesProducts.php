<?php

namespace App\Filament\Resources\Documents\RelationManagers\Traits;

trait CalculatesProducts
{
    public function calculateProductTotalWithFinishings($get, $set): void
    {
        $productId = $get('product_id');
        $quantity = $get('quantity') ?? 1;
        $profitMargin = $get('profit_margin') ?? 0;
        $finishingsData = $get('finishings_data') ?? [];

        if (!$productId || $quantity <= 0) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->with('finishings')
            ->find($productId);

        if (!$product) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        // Debug log (se puede ver en las herramientas de desarrollo del navegador)
        \Log::info('ProductCalculation', [
            'product_id' => $productId,
            'quantity' => $quantity,
            'profit_margin' => $profitMargin,
            'finishings_from_form' => count($finishingsData),
            'finishings_from_product' => $product->finishings->count(),
        ]);

        // Precio base del producto
        $baseUnitPrice = $product->sale_price;

        // Calcular costo de acabados
        $finishingsCostTotal = 0;

        if (!empty($finishingsData)) {
            // Usar acabados personalizados del formulario
            $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);

            foreach ($finishingsData as $finishingData) {
                if (empty($finishingData['finishing_id'])) {
                    continue;
                }

                $finishing = \App\Models\Finishing::find($finishingData['finishing_id']);
                if (!$finishing) {
                    continue;
                }

                $params = [];
                switch ($finishing->measurement_unit->value) {
                    case 'millar':
                    case 'rango':
                    case 'unidad':
                        $params = ['quantity' => $finishingData['quantity'] ?? $quantity];
                        break;
                    case 'tamaño':
                        $params = [
                            'width' => $finishingData['width'] ?? 0,
                            'height' => $finishingData['height'] ?? 0,
                        ];
                        break;
                }

                $cost = $finishingCalculator->calculateCost($finishing, $params);
                $finishingsCostTotal += $cost;
            }
        } elseif ($product->finishings->isNotEmpty()) {
            // Usar acabados del producto
            $finishingCalculator = app(\App\Services\FinishingCalculatorService::class);

            foreach ($product->finishings as $finishing) {
                $params = [];

                switch ($finishing->measurement_unit->value) {
                    case 'millar':
                    case 'rango':
                    case 'unidad':
                        $params = ['quantity' => $quantity];
                        break;
                    case 'tamaño':
                        $params = [
                            'width' => $finishing->pivot->width ?? 0,
                            'height' => $finishing->pivot->height ?? 0,
                        ];
                        break;
                }

                $cost = $finishingCalculator->calculateCost($finishing, $params);
                $finishingsCostTotal += $cost;
            }
        }

        // Total base (producto × cantidad + acabados)
        $baseTotal = ($baseUnitPrice * $quantity) + $finishingsCostTotal;

        // Aplicar margen de ganancia
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $set('unit_price', round($unitPriceWithMargin, 2));
        $set('total_price', round($totalPriceWithMargin, 2));
    }

    public function calculateProductTotal($get, $set): void
    {
        $productId = $get('product_id');
        $quantity = $get('quantity') ?? 1;
        $profitMargin = $get('profit_margin') ?? 0;

        if (!$productId || $quantity <= 0) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        $product = \App\Models\Product::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)
            ->with('finishings')
            ->find($productId);

        if (!$product) {
            $set('unit_price', 0);
            $set('total_price', 0);
            return;
        }

        // Precio base del producto (sin acabados, ya que se calculan en handleCreate)
        $baseUnitPrice = $product->sale_price;
        $baseTotal = $baseUnitPrice * $quantity;

        // Aplicar margen de ganancia
        $totalPriceWithMargin = $baseTotal * (1 + ($profitMargin / 100));
        $unitPriceWithMargin = $totalPriceWithMargin / $quantity;

        $set('unit_price', round($unitPriceWithMargin, 2));
        $set('total_price', round($totalPriceWithMargin, 2));
    }

    public function recalculateItemTotal($set, $get): void
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