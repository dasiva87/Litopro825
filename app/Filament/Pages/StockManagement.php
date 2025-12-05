<?php

namespace App\Filament\Pages;

use App\Enums\NavigationGroup;
use App\Services\StockAlertService;
use BackedEnum;
use Filament\Pages\Page;
use UnitEnum;

class StockManagement extends Page
{
    protected string $view = 'filament.pages.stock-management';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Gestión de Stock';

    protected static ?string $title = 'Dashboard de Gestión de Stock';

    protected static ?int $navigationSort = 3;

    protected static UnitEnum|string|null $navigationGroup = NavigationGroup::Inventario;

    protected ?string $pollingInterval = '30s';

    protected StockAlertService $alertService;

    public function boot(): void
    {
        $this->alertService = app(StockAlertService::class);
    }

    public function refreshData(): void
    {
        // Refrescar alertas
        $this->alertService->evaluateAllAlerts(auth()->user()->company_id);

        $this->js('$refresh');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('refresh')
                ->label('Actualizar Datos')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(fn() => $this->refreshData()),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\SimpleStockKpisWidget::class,
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            \App\Filament\Widgets\StockTrendsChartWidget::class,
            \App\Filament\Widgets\TopConsumedProductsWidget::class,
            \App\Filament\Widgets\CriticalAlertsTableWidget::class,
            \App\Filament\Widgets\RecentMovementsWidget::class,
        ];
    }

    public function getWidgetData(): array
    {
        return [];
    }
}
