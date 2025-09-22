<?php

namespace App\Filament\SuperAdmin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $title = 'Dashboard Super Admin';

    public function getHeading(): string
    {
        return 'Panel de Super Administración';
    }

    public function getSubheading(): ?string
    {
        return 'Métricas globales y gestión del sistema SaaS';
    }

    public function getColumns(): int|array
    {
        return 12;
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Actualizar Métricas')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->redirect(request()->url())),

            \Filament\Actions\Action::make('admin_panel')
                ->label('Ir al Panel Admin')
                ->icon('heroicon-o-building-office')
                ->url('/admin')
                ->openUrlInNewTab(),
        ];
    }

    public function getWidgets(): array
    {
        return [
            // Widgets de la Fase 1 - Core Super Admin
            \App\Filament\SuperAdmin\Widgets\FinancialMetricsWidget::class,
            \App\Filament\SuperAdmin\Widgets\PaymentAnalyticsWidget::class,

            // Widgets de la Fase 2 - Analytics Avanzados (Charts)
            \App\Filament\SuperAdmin\Widgets\CohortAnalysisWidget::class,
            \App\Filament\SuperAdmin\Widgets\RevenueForecastWidget::class,
            \App\Filament\SuperAdmin\Widgets\PlanPerformanceWidget::class,
            \App\Filament\SuperAdmin\Widgets\GeographicRevenueWidget::class,

            // Widget de Gestión Crítica (Tabla completa)
            \App\Filament\SuperAdmin\Widgets\FailedPaymentsWidget::class,

            // Widgets anteriores temporalmente comentados
            // \App\Filament\SuperAdmin\Widgets\SystemMetricsWidget::class,
            // \App\Filament\SuperAdmin\Widgets\MrrWidget::class,
            // \App\Filament\SuperAdmin\Widgets\ChurnRateWidget::class,
            // \App\Filament\SuperAdmin\Widgets\RevenueChartWidget::class,
            // \App\Filament\SuperAdmin\Widgets\ActiveTenantsWidget::class,
        ];
    }
}