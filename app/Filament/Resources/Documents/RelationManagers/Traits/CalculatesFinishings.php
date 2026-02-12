<?php

namespace App\Filament\Resources\Documents\RelationManagers\Traits;

trait CalculatesFinishings
{
    private function calculateFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 1;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        \Log::info('calculateFinishingCost called', [
            'finishing_id' => $finishingId,
            'quantity' => $quantity,
            'width' => $width,
            'height' => $height,
        ]);

        if (!$finishingId) {
            $set('calculated_cost', 0);
            return;
        }

        $finishing = \App\Models\Finishing::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($finishingId);

        if (!$finishing) {
            $set('calculated_cost', 0);
            return;
        }

        try {
            $finishingService = app(\App\Services\FinishingCalculatorService::class);

            $params = [
                'quantity' => $quantity,
                'width' => $width,
                'height' => $height,
            ];

            $totalCost = $finishingService->calculateCost($finishing, $params);
            $set('calculated_cost', $totalCost);

        } catch (\Exception $e) {
            \Log::error('Error calculating finishing cost', [
                'finishing_id' => $finishingId,
                'params' => $params ?? [],
                'error' => $e->getMessage(),
            ]);
            $set('calculated_cost', 0);
        }
    }

    private function calculateSimpleFinishingCost($set, $get): void
    {
        $finishingId = $get('finishing_id');
        $quantity = $get('quantity') ?? 1;
        $width = $get('width') ?? 0;
        $height = $get('height') ?? 0;

        if (!$finishingId) {
            $set('calculated_cost', 0);
            return;
        }

        $finishing = \App\Models\Finishing::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($finishingId);

        if (!$finishing) {
            $set('calculated_cost', 0);
            return;
        }

        try {
            $finishingService = app(\App\Services\FinishingCalculatorService::class);

            $params = [
                'quantity' => $quantity,
                'width' => $width > 0 ? $width : null,
                'height' => $height > 0 ? $height : null,
            ];

            $totalCost = $finishingService->calculateCost($finishing, $params);
            $set('calculated_cost', round($totalCost, 2));

        } catch (\Exception $e) {
            \Log::error('Error calculating simple finishing cost', [
                'finishing_id' => $finishingId,
                'params' => $params ?? [],
                'error' => $e->getMessage(),
            ]);
            $set('calculated_cost', 0);
        }
    }

    private function shouldShowSizeFields(?int $finishingId): bool
    {
        if (!$finishingId) {
            return false;
        }

        $finishing = \App\Models\Finishing::withoutGlobalScope(\App\Models\Scopes\TenantScope::class)->find($finishingId);

        return $finishing && $finishing->measurement_unit === \App\Enums\FinishingMeasurementUnit::TAMAÑO;
    }
}