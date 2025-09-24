<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use Filament\Widgets\Widget;

class StockLevelTrackingWidget extends Widget
{
    protected string $view = 'filament.widgets.stock-level-tracking';

    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = [
        'sm' => 1,
        'md' => 2,
        'lg' => 1,
    ];

    protected ?string $pollingInterval = '60s';

    public function getViewData(): array
    {
        $companyId = auth()->user()->company_id;
        
        // Obtener productos con stock bajo
        $lowStockProducts = Product::where('company_id', $companyId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->orderBy('stock')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'name' => $product->name,
                    'current_stock' => $product->stock,
                    'min_stock' => $product->min_stock ?? 10,
                    'status' => $this->getStockStatus($product->stock, $product->min_stock ?? 10),
                    'percentage' => $this->getStockPercentage($product->stock, $product->min_stock ?? 10),
                ];
            });

        // Obtener papeles con stock bajo
        $lowStockPapers = Paper::where('company_id', $companyId)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 100)
            ->orderBy('stock')
            ->limit(5)
            ->get()
            ->map(function ($paper) {
                return [
                    'name' => $paper->name . ' (' . $paper->size . ')',
                    'current_stock' => $paper->stock,
                    'min_stock' => $paper->min_stock ?? 100,
                    'status' => $this->getStockStatus($paper->stock, $paper->min_stock ?? 100),
                    'percentage' => $this->getStockPercentage($paper->stock, $paper->min_stock ?? 100),
                ];
            });

        $stockItems = $lowStockProducts->concat($lowStockPapers)
            ->sortBy('percentage')
            ->take(8)
            ->values();

        return [
            'stock_items' => $stockItems,
        ];
    }

    private function getStockStatus(int $currentStock, int $minStock): string
    {
        $percentage = $this->getStockPercentage($currentStock, $minStock);
        
        if ($percentage <= 25) {
            return 'critical';
        } elseif ($percentage <= 50) {
            return 'warning';
        } elseif ($percentage <= 75) {
            return 'low';
        }
        
        return 'normal';
    }

    private function getStockPercentage(int $currentStock, int $minStock): float
    {
        if ($minStock <= 0) {
            return 100;
        }
        
        return min(100, ($currentStock / $minStock) * 100);
    }
}