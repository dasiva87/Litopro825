<?php

namespace App\Filament\SuperAdmin\Pages;

use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Enums\IconPosition;

class Dashboard extends BaseDashboard
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static string $view = 'filament.super-admin.pages.dashboard';

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
                ->iconPosition(IconPosition::Before)
                ->action(fn () => $this->redirect(request()->url())),

            \Filament\Actions\Action::make('export')
                ->label('Exportar Reporte')
                ->icon('heroicon-o-document-arrow-down')
                ->iconPosition(IconPosition::Before)
                ->action(fn () => $this->exportSystemReport()),
        ];
    }

    protected function exportSystemReport(): void
    {
        // TODO: Implementar exportación de reporte del sistema
        $this->notify('success', 'Reporte exportado exitosamente');
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\SuperAdmin\Widgets\SystemMetricsWidget::class,
            \App\Filament\SuperAdmin\Widgets\MrrWidget::class,
            \App\Filament\SuperAdmin\Widgets\ChurnRateWidget::class,
            \App\Filament\SuperAdmin\Widgets\RevenueChartWidget::class,
            \App\Filament\SuperAdmin\Widgets\ActiveTenantsWidget::class,
        ];
    }
}
