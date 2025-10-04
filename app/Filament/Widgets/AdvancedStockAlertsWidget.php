<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockAlert;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdvancedStockAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '120s';

    protected function getStats(): array
    {
        // Stats de stock general
        $stockStats = $this->getStockStatsByType();

        // Alertas activas
        $activeAlerts = StockAlert::forCurrentTenant()
            ->active()
            ->count();

        $criticalAlerts = StockAlert::forCurrentTenant()
            ->active()
            ->critical()
            ->count();

        // Valor de inventario
        $inventoryValue = $this->getInventoryValueMetrics();

        // Items con movimiento
        $topMovingCount = $this->getTopMovingItemsCount();

        return [
            Stat::make('📦 Total Items', number_format($stockStats['combined']['total']))
                ->description('Productos y papeles activos')
                ->descriptionIcon('heroicon-m-cube')
                ->color('info')
                ->chart($this->getStockTrend()),

            Stat::make('✅ En Stock', number_format($stockStats['combined']['in_stock']))
                ->description('Items con stock suficiente')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('⚠️ Stock Bajo', number_format($stockStats['combined']['low_stock']))
                ->description('Próximos a reposición')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('🚨 Sin Stock', number_format($stockStats['combined']['out_of_stock']))
                ->description('Reposición urgente')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('🔔 Alertas Activas', number_format($activeAlerts))
                ->description($criticalAlerts . ' críticas')
                ->descriptionIcon($criticalAlerts > 0 ? 'heroicon-m-bell-alert' : 'heroicon-m-bell')
                ->color($criticalAlerts > 0 ? 'danger' : 'warning'),

            Stat::make('💰 Valor Inventario', '$' . number_format($inventoryValue['total_inventory_value'], 0))
                ->description($inventoryValue['risk_percentage'] . '% en riesgo')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('info'),
        ];
    }

    private function getStockTrend(): array
    {
        $trend = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();

            $stockCount = Product::forCurrentTenant()
                ->where('active', true)
                ->where('updated_at', '<=', $date->endOfDay())
                ->count();

            $trend[] = $stockCount;
        }

        return $trend;
    }

    private function getTopMovingItemsCount(): int
    {
        $last30Days = now()->subDays(30);

        return \App\Models\StockMovement::forCurrentTenant()
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->distinct('stockable_type', 'stockable_id')
            ->count();
    }

    /**
     * Obtener estadísticas de stock por tipo
     */
    public function getStockStatsByType(): array
    {
        // Productos
        $products = Product::forCurrentTenant()->where('active', true);
        $productStats = [
            'total' => $products->count(),
            'in_stock' => $products->clone()->inStock()->count(),
            'low_stock' => $products->clone()->lowStock()->count(),
            'out_of_stock' => $products->clone()->outOfStock()->count(),
        ];

        // Papeles
        $papers = Paper::forCurrentTenant()->where('is_active', true);
        $paperStats = [
            'total' => $papers->count(),
            'in_stock' => $papers->clone()->inStock()->count(),
            'low_stock' => $papers->clone()->lowStock()->count(),
            'out_of_stock' => $papers->clone()->outOfStock()->count(),
        ];

        return [
            'products' => $productStats,
            'papers' => $paperStats,
            'combined' => [
                'total' => $productStats['total'] + $paperStats['total'],
                'in_stock' => $productStats['in_stock'] + $paperStats['in_stock'],
                'low_stock' => $productStats['low_stock'] + $paperStats['low_stock'],
                'out_of_stock' => $productStats['out_of_stock'] + $paperStats['out_of_stock'],
            ],
        ];
    }


    private function getInventoryValueMetrics(): array
    {
        // Valor total de productos
        $productsValue = Product::forCurrentTenant()
            ->where('active', true)
            ->selectRaw('sum(stock * purchase_price) as total_value, sum(case when stock <= min_stock then stock * purchase_price else 0 end) as low_stock_value')
            ->first();

        // Valor total de papeles
        $papersValue = Paper::forCurrentTenant()
            ->where('is_active', true)
            ->selectRaw('sum(stock * cost_per_sheet) as total_value, sum(case when stock <= min_stock then stock * cost_per_sheet else 0 end) as low_stock_value')
            ->first();

        $totalValue = ($productsValue->total_value ?? 0) + ($papersValue->total_value ?? 0);
        $lowStockValue = ($productsValue->low_stock_value ?? 0) + ($papersValue->low_stock_value ?? 0);

        return [
            'total_inventory_value' => $totalValue,
            'low_stock_value' => $lowStockValue,
            'risk_percentage' => $totalValue > 0 ? round(($lowStockValue / $totalValue) * 100, 1) : 0,
        ];
    }
}