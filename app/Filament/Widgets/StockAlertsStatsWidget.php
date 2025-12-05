<?php

namespace App\Filament\Widgets;

use App\Models\StockAlert;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StockAlertsStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = TenantContext::id();

        // Total alertas activas
        $activeAlerts = StockAlert::where('company_id', $companyId)
            ->whereIn('status', ['active', 'acknowledged'])
            ->count();

        // Alertas críticas
        $criticalAlerts = StockAlert::where('company_id', $companyId)
            ->where('severity', 'critical')
            ->whereIn('status', ['active', 'acknowledged'])
            ->count();

        // Alertas por resolver (activas)
        $pendingAlerts = StockAlert::where('company_id', $companyId)
            ->where('status', 'active')
            ->count();

        // Alertas resueltas hoy
        $resolvedToday = StockAlert::where('company_id', $companyId)
            ->where('status', 'resolved')
            ->whereDate('resolved_at', today())
            ->count();

        return [
            Stat::make('🔔 Alertas Activas', $activeAlerts)
                ->description($pendingAlerts . ' pendientes de reconocer')
                ->color($activeAlerts >= 10 ? 'danger' : ($activeAlerts > 0 ? 'warning' : 'success')),

            Stat::make('⚠️ Alertas Críticas', $criticalAlerts)
                ->description('Requieren atención inmediata')
                ->color($criticalAlerts > 0 ? 'danger' : 'success'),

            Stat::make('✅ Resueltas Hoy', $resolvedToday)
                ->description('Alertas resueltas en las últimas 24h')
                ->color('success'),

            Stat::make('📊 Tasa de Resolución', $this->getResolutionRate($companyId))
                ->description('Últimos 7 días')
                ->color('info'),
        ];
    }

    protected function getResolutionRate(int $companyId): string
    {
        $last7Days = now()->subDays(7);

        $triggered = StockAlert::where('company_id', $companyId)
            ->where('triggered_at', '>=', $last7Days)
            ->count();

        $resolved = StockAlert::where('company_id', $companyId)
            ->where('resolved_at', '>=', $last7Days)
            ->count();

        if ($triggered === 0) {
            return '0%';
        }

        $rate = round(($resolved / $triggered) * 100);
        return $rate . '%';
    }
}
