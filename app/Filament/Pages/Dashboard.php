<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Dashboard';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?int $navigationSort = -1;

    protected static ?string $slug = '';

    protected static bool $shouldRegisterNavigation = true;

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\OnboardingWidget::class,
            \App\Filament\Widgets\PurchaseOrderNotificationsWidget::class,
            \App\Filament\Widgets\PurchaseOrdersOverviewWidget::class,
            \App\Filament\Widgets\DashboardStatsWidget::class,
            \App\Filament\Widgets\AdvancedStockAlertsWidget::class,
            \App\Filament\Widgets\ActiveDocumentsWidget::class,
            \App\Filament\Widgets\StockAlertsWidget::class,
            \App\Filament\Widgets\DeadlinesWidget::class,
        ];
    }
}