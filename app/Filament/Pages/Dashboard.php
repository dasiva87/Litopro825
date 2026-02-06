<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Dashboard extends Page
{
    protected static ?string $title = 'Panel de Control';

    protected static ?string $navigationLabel = 'Panel de Control';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\OnboardingWidget::class,
            \App\Filament\Widgets\DashboardStatsWidget::class,
            \App\Filament\Widgets\DeadlinesWidget::class,
            \App\Filament\Widgets\StockAlertsWidget::class,
            \App\Filament\Widgets\ActiveDocumentsWidget::class,
            \App\Filament\Widgets\ActiveProjectsWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return $this->getWidgets();
    }

    public function getHeaderWidgetsColumns(): int | array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}