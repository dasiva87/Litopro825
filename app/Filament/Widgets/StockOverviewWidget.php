<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockMovement;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget consolidado de Stock - Vista minimalista
 * Reemplaza: SimpleStockKpisWidget, StockMovementsKpisWidget, StockAlertsWidget
 */
class StockOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $companyId = TenantContext::id();

        // Contadores de inventario
        $totalProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->count();

        $totalPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        $totalItems = $totalProducts + $totalPapers;

        // Items críticos (stock <= mínimo)
        $criticalProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $criticalPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $criticalCount = $criticalProducts + $criticalPapers;

        // Sin stock
        $noStockProducts = Product::where('company_id', $companyId)
            ->where('active', true)
            ->where('stock', '<=', 0)
            ->count();

        $noStockPapers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('stock', '<=', 0)
            ->count();

        $noStockCount = $noStockProducts + $noStockPapers;

        // Movimientos de hoy
        $todayMovements = StockMovement::where('company_id', $companyId)
            ->whereDate('created_at', today())
            ->count();

        // Colores dinámicos
        $criticalColor = match (true) {
            $criticalCount >= 5 => 'danger',
            $criticalCount > 0 => 'warning',
            default => 'success'
        };

        $noStockColor = match (true) {
            $noStockCount >= 3 => 'danger',
            $noStockCount > 0 => 'warning',
            default => 'success'
        };

        return [
            Stat::make('Total Inventario', number_format($totalItems))
                ->description($totalProducts . ' productos · ' . $totalPapers . ' papeles')
                ->descriptionIcon('heroicon-m-cube')
                ->color('primary')
                ->url(route('filament.admin.resources.products.index')),

            Stat::make('Stock Crítico', number_format($criticalCount))
                ->description($criticalCount > 0 ? 'Requieren reabastecimiento' : 'Todo en orden')
                ->descriptionIcon($criticalCount > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($criticalColor)
                ->url(route('filament.admin.resources.products.index') . '?tableFilters[stock_status][value]=low'),

            Stat::make('Sin Stock', number_format($noStockCount))
                ->description($noStockCount > 0 ? 'Items agotados' : 'Inventario completo')
                ->descriptionIcon($noStockCount > 0 ? 'heroicon-m-x-circle' : 'heroicon-m-check-badge')
                ->color($noStockColor)
                ->url(route('filament.admin.resources.products.index') . '?tableFilters[stock_status][value]=out'),

            Stat::make('Movimientos Hoy', number_format($todayMovements))
                ->description($todayMovements > 0 ? 'Entradas y salidas' : 'Sin actividad')
                ->descriptionIcon($todayMovements > 0 ? 'heroicon-m-arrow-path' : 'heroicon-m-clock')
                ->color($todayMovements > 0 ? 'info' : 'gray'),
        ];
    }
}
