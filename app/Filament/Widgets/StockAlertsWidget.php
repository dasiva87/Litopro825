<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 10;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Productos críticos
        $criticalProducts = Product::forCurrentTenant()
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // Productos bajo stock
        $lowStockProducts = Product::forCurrentTenant()
            ->where('active', true)
            ->whereColumn('stock', '>', 'min_stock')
            ->whereRaw('stock <= (min_stock * 1.5)')
            ->count();

        // Papeles críticos
        $criticalPapers = Paper::forCurrentTenant()
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // Costo estimado de reposición
        $restockCost = Product::forCurrentTenant()
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get()
            ->sum(function ($product) {
                $neededQuantity = max(0, $product->min_stock - $product->stock + 10);
                return $neededQuantity * $product->purchase_price;
            });

        return [
            Stat::make('🚨 Stock Crítico', number_format($criticalProducts))
                ->description($criticalProducts > 0 ? 'Productos bajo mínimo' : 'Todo en orden')
                ->descriptionIcon($criticalProducts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($criticalProducts > 0 ? 'danger' : 'success')
                ->chart($this->getCriticalTrend()),

            Stat::make('⚠️ Stock Bajo', number_format($lowStockProducts))
                ->description('Próximos a mínimo')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('📄 Papeles Críticos', number_format($criticalPapers))
                ->description('Pliegos bajo mínimo')
                ->descriptionIcon('heroicon-m-document')
                ->color($criticalPapers > 0 ? 'danger' : 'success'),

            Stat::make('💰 Costo Reposición', '$' . number_format($restockCost, 0))
                ->description('Inversión estimada')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }

    private function getCriticalTrend(): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $count = Product::forCurrentTenant()
                ->where('active', true)
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('updated_at', '<=', $date->endOfDay())
                ->count();

            $trend[] = $count;
        }

        return $trend;
    }
}