<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemMetricsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalCompanies = Company::count();
        $activeCompanies = Company::where('status', 'active')->count();
        $totalUsers = User::count();
        $activeSubscriptions = Subscription::where('stripe_status', 'active')->count();

        // Growth calculations (last 30 days)
        $companiesGrowth = Company::where('created_at', '>=', now()->subDays(30))->count();
        $usersGrowth = User::where('created_at', '>=', now()->subDays(30))->count();

        return [
            Stat::make('Total Empresas', $totalCompanies)
                ->description($companiesGrowth.' nuevas este mes')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Empresas Activas', $activeCompanies)
                ->description(number_format(($activeCompanies / max($totalCompanies, 1)) * 100, 1).'% del total')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('info'),

            Stat::make('Total Usuarios', $totalUsers)
                ->description($usersGrowth.' nuevos este mes')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('warning'),

            Stat::make('Suscripciones Activas', $activeSubscriptions)
                ->description('Generando ingresos')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('success'),
        ];
    }
}
