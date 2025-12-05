<?php

namespace App\Filament\Resources\StockAlertResource\Pages;

use App\Filament\Resources\StockAlertResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewStockAlert extends ViewRecord
{
    protected static string $resource = StockAlertResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('acknowledge')
                ->label('Reconocer')
                ->icon('heroicon-o-hand-raised')
                ->color('warning')
                ->visible(fn () => $this->record->status === 'active')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->acknowledge(auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Alerta Reconocida')
                        ->body('La alerta ha sido marcada como reconocida.')
                        ->send();

                    return redirect()->route('filament.admin.resources.stock-alerts.view', ['record' => $this->record]);
                }),

            Actions\Action::make('resolve')
                ->label('Resolver')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, ['active', 'acknowledged']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->resolve(auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Alerta Resuelta')
                        ->body('La alerta ha sido marcada como resuelta.')
                        ->send();

                    return redirect()->route('filament.admin.resources.stock-alerts.index');
                }),

            Actions\Action::make('dismiss')
                ->label('Descartar')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, ['active', 'acknowledged']))
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->dismiss(auth()->id());
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Alerta Descartada')
                        ->body('La alerta ha sido descartada.')
                        ->send();

                    return redirect()->route('filament.admin.resources.stock-alerts.index');
                }),

            Actions\Action::make('view_item')
                ->label('Ver Item')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->url(function () {
                    if ($this->record->stockable_type === 'App\Models\Product') {
                        return route('filament.admin.resources.products.edit', ['record' => $this->record->stockable_id]);
                    } else {
                        return route('filament.admin.resources.papers.edit', ['record' => $this->record->stockable_id]);
                    }
                })
                ->openUrlInNewTab(),
        ];
    }
}
