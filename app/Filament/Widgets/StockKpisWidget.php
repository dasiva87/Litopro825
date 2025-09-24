<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockAlert;
use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockKpisWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $companyId = auth()->user()->company_id;

        $totalProducts = Product::where('company_id', $companyId)->where('active', true)->count();
        $totalPapers = Paper::where('company_id', $companyId)->where('is_active', true)->count();

        $lowStockItems = Product::where('company_id', $companyId)
            ->where('active', true)
            ->lowStock()
            ->count() +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->lowStock()
            ->count();

        $outOfStockItems = Product::where('company_id', $companyId)
            ->where('active', true)
            ->outOfStock()
            ->count() +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->outOfStock()
            ->count();

        $criticalAlerts = StockAlert::where('company_id', $companyId)
            ->critical()
            ->active()
            ->count();

        $stockCoverageDays = $this->calculateStockCoverageDays();

        return [
            Stat::make('Total Items', $totalProducts + $totalPapers)
                ->description('Productos y papeles activos')
                ->descriptionIcon('heroicon-o-cube')
                ->color('primary'),

            Stat::make('Stock Bajo', $lowStockItems)
                ->description('Items bajo nivel mínimo')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning'),

            Stat::make('Sin Stock', $outOfStockItems)
                ->description('Items que requieren reposición')
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Alertas Críticas', $criticalAlerts)
                ->description('Alertas que requieren atención')
                ->descriptionIcon('heroicon-o-bell')
                ->color('danger'),

            Stat::make('Cobertura', $stockCoverageDays . ' días')
                ->description('Días estimados de stock')
                ->descriptionIcon('heroicon-o-clock')
                ->color('success'),
        ];
    }

    protected function calculateStockCoverageDays(): int
    {
        $companyId = auth()->user()->company_id;
        $last30Days = now()->subDays(30);

        // Calcular consumo promedio diario
        $totalConsumption = StockMovement::where('company_id', $companyId)
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->sum('quantity');

        $avgDailyConsumption = $totalConsumption / 30;

        if ($avgDailyConsumption <= 0) {
            return 999; // Stock infinito si no hay consumo
        }

        // Calcular stock total actual
        $totalStock = Product::where('company_id', $companyId)
            ->where('active', true)
            ->sum('stock') +
            Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->sum('stock');

        return (int) ($totalStock / $avgDailyConsumption);
    }
}