<?php

namespace App\Filament\Resources\StockAlertResource\Pages;

use App\Filament\Resources\StockAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAlerts extends ListRecords
{
    protected static string $resource = StockAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('refresh_alerts')
                ->label('Actualizar Alertas')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    $alertService = app(\App\Services\StockAlertService::class);
                    $alertService->evaluateAllAlerts(auth()->user()->company_id);

                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Alertas Actualizadas')
                        ->body('Se han evaluado todas las alertas de stock.')
                        ->send();

                    redirect()->route('filament.admin.resources.stock-alerts.index');
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\StockAlertsStatsWidget::class,
        ];
    }
}
