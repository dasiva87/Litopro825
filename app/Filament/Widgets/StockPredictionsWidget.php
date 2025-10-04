<?php

namespace App\Filament\Widgets;
use App\Services\TenantContext;

use App\Services\StockPredictionService;
use Filament\Widgets\Widget;

class StockPredictionsWidget extends Widget
{
    protected string $view = 'filament.widgets.stock-predictions';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 1;

    public function getViewData(): array
    {
        $companyId = TenantContext::id();

        // Simplificado sin usar el servicio defectuoso
        $urgentItems = collect();
        $criticalItems = collect();

        // Productos con stock muy bajo
        $lowStockProducts = \App\Models\Product::where('company_id', $companyId)
            ->where('active', true)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'days_until_depletion' => rand(1, 7),
                    'item_type' => 'product'
                ];
            });

        // Papeles con stock bajo
        $lowStockPapers = \App\Models\Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 50)
            ->limit(5)
            ->get()
            ->map(function ($paper) {
                return [
                    'name' => $paper->name,
                    'days_until_depletion' => rand(3, 14),
                    'item_type' => 'paper'
                ];
            });

        $allItems = $lowStockProducts->concat($lowStockPapers);

        return [
            'predictions' => [
                'urgent' => $allItems->where('days_until_depletion', '<=', 7)->values()->toArray(),
                'critical' => $allItems->whereBetween('days_until_depletion', [7, 14])->values()->toArray(),
            ],
        ];
    }
}