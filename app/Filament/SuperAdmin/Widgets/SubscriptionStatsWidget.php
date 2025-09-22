<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SubscriptionStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $active = Subscription::where('stripe_status', 'active')->count();
        $trialing = Subscription::where('stripe_status', 'trialing')->count();
        $cancelled = Subscription::where('stripe_status', 'cancelled')->count();
        $pastDue = Subscription::where('stripe_status', 'past_due')->count();
        $trialEndingSoon = Subscription::where('trial_ends_at', '>=', now())
            ->where('trial_ends_at', '<=', now()->addDays(7))
            ->count();

        return [
            Stat::make('Activas', $active)
                ->description('Suscripciones activas')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            Stat::make('En Prueba', $trialing)
                ->description('Período de prueba')
                ->descriptionIcon('heroicon-m-clock')
                ->color('info'),

            Stat::make('Canceladas', $cancelled)
                ->description('Suscripciones canceladas')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger'),

            Stat::make('Pago Pendiente', $pastDue)
                ->description('Requieren atención')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('warning'),

            Stat::make('Prueba Termina Pronto', $trialEndingSoon)
                ->description('Próximos 7 días')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}