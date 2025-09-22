<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $totalPlans = Plan::count();
        $activePlans = Plan::where('is_active', true)->count();
        $totalSubscriptions = Subscription::count();
        $activeSubscriptions = Subscription::where('stripe_status', 'active')->count();

        return [
            Stat::make('Total Planes', $totalPlans)
                ->description('Planes configurados')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('primary'),

            Stat::make('Planes Activos', $activePlans)
                ->description('Disponibles para suscripción')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('Total Suscripciones', $totalSubscriptions)
                ->description('Suscripciones históricas')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Suscripciones Activas', $activeSubscriptions)
                ->description('Suscripciones vigentes')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('warning'),
        ];
    }
}