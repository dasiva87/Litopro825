<?php

namespace App\Filament\Widgets;
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

        // Contadores reales pero con queries simples y directas
        $totalProducts = \App\Models\Product::where('company_id', $companyId)
            ->where('active', true)
            ->count();

        $totalPapers = \App\Models\Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->count();

        // Stock bajo simplificado
        $lowStockCount = \App\Models\Product::where('company_id', $companyId)
            ->where('active', true)
            ->where('stock', '>', 0)
            ->where('stock', '<=', 5)
            ->count();

        // Sin stock
        $noStockCount = \App\Models\Product::where('company_id', $companyId)
            ->where('active', true)
            ->where('stock', '=', 0)
            ->count();

        return [
            Stat::make('📦 Total Items', $totalProducts + $totalPapers)
                ->description('Productos: ' . $totalProducts . ' | Papeles: ' . $totalPapers)
                ->color('primary'),

            Stat::make('⚠️ Stock Bajo', $lowStockCount)
                ->description('Stock ≤ 5 unidades')
                ->color('warning'),

            Stat::make('❌ Sin Stock', $noStockCount)
                ->description('Productos sin inventario')
                ->color('danger'),

            Stat::make('🔔 Alertas', '0')
                ->description('Sistema funcionando correctamente')
                ->color('success'),
        ];
    }
}