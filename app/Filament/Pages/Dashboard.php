<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = 'dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\OnboardingWidget::class,
            \App\Filament\Widgets\DashboardStatsWidget::class,
            \App\Filament\Widgets\ActiveDocumentsWidget::class,
            \App\Filament\Widgets\StockAlertsWidget::class,
            \App\Filament\Widgets\DeadlinesWidget::class,
        ];
    }
}
