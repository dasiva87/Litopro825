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
        // Contadores básicos simplificados
        $totalProducts = Product::forCurrentTenant()->where('active', true)->count();
        $totalPapers = Paper::forCurrentTenant()->where('is_active', true)->count();

        // Stock bajo - simplificado sin usar min_stock
        $lowStockProducts = Product::forCurrentTenant()
            ->where('active', true)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 10)
            ->count();

        $lowStockPapers = Paper::forCurrentTenant()
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 100)
            ->count();

        // Sin stock
        $outOfStockProducts = Product::forCurrentTenant()
            ->where('active', true)
            ->where('stock', '<=', 0)
            ->count();

        $outOfStockPapers = Paper::forCurrentTenant()
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        // Alertas críticas - simplificado sin usar relación
        $criticalAlerts = StockAlert::forCurrentTenant()
            ->where('severity', 'critical')
            ->where('status', 'active')
            ->count();

        return [
            Stat::make('📦 Total Items', $totalProducts + $totalPapers)
                ->description('Productos y papeles activos')
                ->color('primary'),

            Stat::make('⚠️ Stock Bajo', $lowStockProducts + $lowStockPapers)
                ->description('Items bajo nivel mínimo')
                ->color('warning'),

            Stat::make('❌ Sin Stock', $outOfStockProducts + $outOfStockPapers)
                ->description('Items que requieren reposición')
                ->color('danger'),

            Stat::make('🔔 Alertas', $criticalAlerts)
                ->description('Alertas críticas activas')
                ->color('danger'),
        ];
    }

    protected function calculateStockCoverageDays(): int
    {
        $last30Days = now()->subDays(30);

        // Calcular consumo promedio diario
        $totalConsumption = StockMovement::forCurrentTenant()
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->sum('quantity');

        $avgDailyConsumption = $totalConsumption / 30;

        if ($avgDailyConsumption <= 0) {
            return 999; // Stock infinito si no hay consumo
        }

        // Calcular stock total actual
        $totalStock = Product::forCurrentTenant()
            ->where('active', true)
            ->sum('stock') +
            Paper::forCurrentTenant()
            ->where('is_active', true)
            ->sum('stock');

        return (int) ($totalStock / $avgDailyConsumption);
    }
}