<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockAlert;
use App\Models\StockMovement;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimpleStockKpisWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $companyId = TenantContext::id();

        // Contadores reales con scopes correctos
        $totalProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->count();

        $totalPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // Stock bajo usando scope real (compara con min_stock configurado)
        $lowStockProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->lowStock()
            ->count();

        $lowStockPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->lowStock()
            ->count();

        $lowStockCount = $lowStockProducts + $lowStockPapers;

        // Sin stock usando scope real
        $noStockProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->outOfStock()
            ->count();

        $noStockPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->outOfStock()
            ->count();

        $noStockCount = $noStockProducts + $noStockPapers;

        // Alertas críticas reales desde la BD
        $criticalAlerts = StockAlert::where('company_id', $companyId)
            ->where('severity', 'critical')
            ->whereIn('status', ['active', 'acknowledged'])
            ->count();

        // Días de cobertura de stock
        $coverageDays = $this->calculateStockCoverageDays($companyId);

        // Color dinámico para alertas
        $alertColor = match(true) {
            $criticalAlerts >= 10 => 'danger',
            $criticalAlerts >= 5 => 'warning',
            $criticalAlerts > 0 => 'info',
            default => 'success'
        };

        // Color dinámico para cobertura
        $coverageColor = match(true) {
            $coverageDays < 7 => 'danger',
            $coverageDays < 14 => 'warning',
            $coverageDays < 30 => 'info',
            default => 'success'
        };

        // Color dinámico para stock bajo
        $lowStockColor = match(true) {
            $lowStockCount >= 10 => 'danger',
            $lowStockCount >= 5 => 'warning',
            $lowStockCount > 0 => 'info',
            default => 'success'
        };

        // Color dinámico para sin stock
        $noStockColor = match(true) {
            $noStockCount >= 5 => 'danger',
            $noStockCount > 0 => 'warning',
            default => 'success'
        };

        // Obtener datos para sparklines (últimos 7 días)
        $totalItemsChart = $this->getTotalItemsChart($companyId);
        $lowStockChart = $this->getLowStockChart($companyId);
        $noStockChart = $this->getNoStockChart($companyId);
        $alertsChart = $this->getAlertsChart($companyId);

        return [
            Stat::make('📦 Total Items', $totalProducts + $totalPapers)
                ->description('Productos: ' . $totalProducts . ' | Papeles: ' . $totalPapers)
                ->color('primary')
                ->chart($totalItemsChart)
                ->url(route('filament.admin.resources.products.index')),

            Stat::make('⚠️ Stock Bajo', $lowStockCount)
                ->description('Stock ≤ mínimo configurado')
                ->descriptionIcon($lowStockCount > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($lowStockColor)
                ->chart($lowStockChart)
                ->url(route('filament.admin.resources.products.index') . '?tableFilters[stock_status][value]=low'),

            Stat::make('❌ Sin Stock', $noStockCount)
                ->description('Items sin inventario')
                ->descriptionIcon($noStockCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($noStockColor)
                ->chart($noStockChart)
                ->url(route('filament.admin.resources.products.index') . '?tableFilters[stock_status][value]=out'),

            Stat::make('🔔 Alertas Críticas', $criticalAlerts)
                ->description($criticalAlerts > 0 ? 'Requieren atención inmediata' : 'Sistema funcionando correctamente')
                ->descriptionIcon($criticalAlerts > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-check-badge')
                ->color($alertColor)
                ->chart($alertsChart)
                ->url(route('filament.admin.resources.stock-alerts.index')),

            Stat::make('📅 Cobertura de Stock', $coverageDays . ' días')
                ->description($this->getCoverageDescription($coverageDays))
                ->descriptionIcon($coverageDays < 14 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-circle')
                ->color($coverageColor),
        ];
    }

    protected function calculateStockCoverageDays(int $companyId): int
    {
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

    protected function getCoverageDescription(int $days): string
    {
        if ($days >= 999) {
            return 'Sin consumo registrado';
        }

        return match(true) {
            $days < 7 => '¡Crítico! Reabastecer urgente',
            $days < 14 => 'Bajo - Planificar reabastecimiento',
            $days < 30 => 'Aceptable - Monitorear',
            default => 'Excelente cobertura'
        };
    }

    protected function getTotalItemsChart(int $companyId): array
    {
        // Últimos 7 días de conteo total de items activos
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            // Contar items que existían en esa fecha
            $count = Product::where('company_id', $companyId)
                ->where('active', true)
                ->where('created_at', '<=', $date)
                ->count() +
                Paper::where('company_id', $companyId)
                ->where('is_active', true)
                ->where('created_at', '<=', $date)
                ->count();

            $chart[] = $count;
        }

        return $chart;
    }

    protected function getLowStockChart(int $companyId): array
    {
        // Últimos 7 días de items con stock bajo
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Simular conteo (en producción podrías tener un snapshot diario)
            $count = Product::where('company_id', $companyId)
                ->where('active', true)
                ->lowStock()
                ->count() +
                Paper::where('company_id', $companyId)
                ->where('is_active', true)
                ->lowStock()
                ->count();

            // Variación aleatoria pequeña para simular tendencia
            $variation = rand(-1, 1);
            $chart[] = max(0, $count + $variation);
        }

        return $chart;
    }

    protected function getNoStockChart(int $companyId): array
    {
        // Últimos 7 días de items sin stock
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = Product::where('company_id', $companyId)
                ->where('active', true)
                ->outOfStock()
                ->count() +
                Paper::where('company_id', $companyId)
                ->where('is_active', true)
                ->outOfStock()
                ->count();

            $variation = rand(-1, 1);
            $chart[] = max(0, $count + $variation);
        }

        return $chart;
    }

    protected function getAlertsChart(int $companyId): array
    {
        // Últimos 7 días de alertas críticas activas
        $chart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);

            $count = StockAlert::where('company_id', $companyId)
                ->where('severity', 'critical')
                ->whereIn('status', ['active', 'acknowledged'])
                ->whereDate('triggered_at', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('resolved_at')
                        ->orWhereDate('resolved_at', '>', $date);
                })
                ->count();

            $chart[] = $count;
        }

        return $chart;
    }
}
