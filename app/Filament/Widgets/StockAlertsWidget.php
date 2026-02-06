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

        return [
            Stat::make('Stock Critico', number_format($criticalProducts))
                ->description($criticalProducts > 0 ? 'Productos bajo minimo' : 'Todo en orden')
                ->descriptionIcon($criticalProducts > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($criticalProducts > 0 ? 'danger' : 'success')
                ->chart($this->getCriticalTrend()),

            Stat::make('Stock Bajo', number_format($lowStockProducts))
                ->description('Proximos a minimo')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color($lowStockProducts > 0 ? 'warning' : 'success'),

            Stat::make('Papeles Criticos', number_format($criticalPapers))
                ->description('Pliegos bajo minimo')
                ->descriptionIcon('heroicon-m-document')
                ->color($criticalPapers > 0 ? 'danger' : 'success'),
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