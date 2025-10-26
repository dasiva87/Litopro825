<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Enums\ProductionStatus;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewProductionOrder extends ViewRecord
{
    protected static string $resource = ProductionOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),

            Action::make('queue')
                ->label('Poner en Cola')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === ProductionStatus::DRAFT && $record->total_items > 0)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::QUEUED)) {
                        Notification::make()->success()->title('Orden en Cola')->send();
                    }
                }),

            Action::make('start_production')
                ->label('Iniciar Producción')
                ->icon('heroicon-o-play')
                ->color('success')
                ->form([
                    Components\Select::make('supplier_id')
                        ->label('Proveedor')
                        ->relationship(
                            name: 'supplier',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query
                                ->where('company_id', auth()->user()->company_id)
                                ->whereIn('type', ['supplier', 'both'])
                        )
                        ->required(),
                    Components\Select::make('operator_user_id')
                        ->label('Operador')
                        ->relationship(
                            name: 'operator',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('company_id', auth()->user()->company_id)
                        )
                        ->required(),
                ])
                ->visible(fn ($record) => $record->status === ProductionStatus::QUEUED)
                ->action(function ($record, array $data) {
                    $record->update($data);
                    if ($record->changeStatus(ProductionStatus::IN_PROGRESS)) {
                        Notification::make()->success()->title('Producción Iniciada')->send();
                    }
                }),

            Action::make('complete_production')
                ->label('Completar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === ProductionStatus::IN_PROGRESS)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::COMPLETED)) {
                        Notification::make()->success()->title('Producción Completada')->send();
                    }
                }),
        ];
    }
}
