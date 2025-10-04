<?php

namespace App\Filament\Widgets;

use App\Models\StockMovement;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockMovementsKpisWidget extends BaseWidget
{
    protected ?string $pollingInterval = '180s';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Obtener contadores por tipo
        $entriesCount = StockMovement::forCurrentTenant()
            ->where('type', 'in')
            ->count();

        $outboundCount = StockMovement::forCurrentTenant()
            ->where('type', 'out')
            ->count();

        $adjustmentsCount = StockMovement::forCurrentTenant()
            ->where('type', 'adjustment')
            ->count();

        $todayCount = StockMovement::forCurrentTenant()
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        // Calcular cambios del periodo anterior para trending
        $entriesLastWeek = StockMovement::forCurrentTenant()
            ->where('type', 'in')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        $outboundLastWeek = StockMovement::forCurrentTenant()
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subWeek())
            ->count();

        return [
            Stat::make('📈 Entradas Totales', number_format($entriesCount))
                ->description($entriesLastWeek > 0 ? ($entriesLastWeek . ' esta semana') : 'Sin movimientos esta semana')
                ->descriptionIcon($entriesLastWeek > 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-minus')
                ->color('success')
                ->chart([$entriesCount - $entriesLastWeek, $entriesLastWeek]),

            Stat::make('📉 Salidas Totales', number_format($outboundCount))
                ->description($outboundLastWeek > 0 ? ($outboundLastWeek . ' esta semana') : 'Sin movimientos esta semana')
                ->descriptionIcon($outboundLastWeek > 0 ? 'heroicon-m-arrow-trending-down' : 'heroicon-m-minus')
                ->color('danger')
                ->chart([$outboundCount - $outboundLastWeek, $outboundLastWeek]),

            Stat::make('⚖️ Ajustes Realizados', number_format($adjustmentsCount))
                ->description('Correcciones de inventario')
                ->descriptionIcon('heroicon-m-adjustments-horizontal')
                ->color('warning'),

            Stat::make('🕐 Movimientos Hoy', number_format($todayCount))
                ->description('Actividad del día actual')
                ->descriptionIcon($todayCount > 0 ? 'heroicon-m-clock' : 'heroicon-m-moon')
                ->color($todayCount > 0 ? 'info' : 'gray')
                ->chart($this->getTodayChart()),
        ];
    }

    private function getTodayChart(): array
    {
        $chart = [];

        // Últimas 7 horas de actividad
        for ($i = 6; $i >= 0; $i--) {
            $hourStart = now()->subHours($i)->startOfHour();
            $hourEnd = now()->subHours($i)->endOfHour();

            $count = StockMovement::forCurrentTenant()
                ->whereBetween('created_at', [$hourStart, $hourEnd])
                ->count();

            $chart[] = $count;
        }

        return $chart;
    }
}