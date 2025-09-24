<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MrrWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 10;

    protected function getStats(): array
    {
        // Calculamos MRR básico a partir de suscripciones activas
        $currentMrr = \App\Models\Subscription::where('stripe_status', 'active')
            ->sum('amount') / 100; // Convertir de centavos

        $previousMrr = \App\Models\Subscription::where('stripe_status', 'active')
            ->where('created_at', '<=', now()->subMonth())
            ->sum('amount') / 100;

        $growth = $previousMrr > 0 ? (($currentMrr - $previousMrr) / $previousMrr) * 100 : 0;

        return [
            Stat::make('MRR Actual', '$'.number_format($currentMrr, 2))
                ->description(($growth >= 0 ? '+' : '').number_format($growth, 1).'% vs mes anterior')
                ->descriptionIcon($growth >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($growth >= 0 ? 'success' : 'danger'),
        ];
    }
}
