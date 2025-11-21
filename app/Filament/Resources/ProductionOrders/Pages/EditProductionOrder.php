<?php

namespace App\Filament\Resources\ProductionOrders\Pages;

use App\Enums\ProductionStatus;
use App\Filament\Resources\ProductionOrders\ProductionOrderResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProductionOrder extends EditRecord
{
    protected static string $resource = ProductionOrderResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Determinar el tipo de proveedor basado en los campos de BD
        $data['supplier_type'] = !empty($data['supplier_company_id']) ? 'company' : 'contact';

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('queue')
                ->label('Poner en Cola')
                ->icon('heroicon-o-queue-list')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Poner Orden en Cola')
                ->modalDescription('¿Está seguro de que desea poner esta orden en cola para producción?')
                ->visible(fn ($record) => $record->status === ProductionStatus::DRAFT && $record->total_items > 0)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::QUEUED, 'Orden puesta en cola desde panel')) {
                        Notification::make()
                            ->success()
                            ->title('Orden en Cola')
                            ->body('La orden ha sido puesta en cola para producción')
                            ->send();
                    } else {
                        Notification::make()
                            ->danger()
                            ->title('Error')
                            ->body('No se pudo cambiar el estado de la orden')
                            ->send();
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
                        ->required()
                        ->searchable()
                        ->preload(),

                    Components\Select::make('operator_user_id')
                        ->label('Operador')
                        ->relationship(
                            name: 'operator',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query->where('company_id', auth()->user()->company_id)
                        )
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->visible(fn ($record) => $record->status === ProductionStatus::QUEUED)
                ->action(function ($record, array $data) {
                    $record->supplier_id = $data['supplier_id'];
                    $record->operator_user_id = $data['operator_user_id'];
                    $record->save();

                    if ($record->changeStatus(ProductionStatus::IN_PROGRESS, 'Producción iniciada')) {
                        Notification::make()
                            ->success()
                            ->title('Producción Iniciada')
                            ->body('La orden de producción ha comenzado')
                            ->send();
                    }
                }),

            Action::make('pause_production')
                ->label('Pausar')
                ->icon('heroicon-o-pause')
                ->color('warning')
                ->form([
                    Components\Textarea::make('notes')
                        ->label('Motivo de la Pausa')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn ($record) => $record->status === ProductionStatus::IN_PROGRESS)
                ->action(function ($record, array $data) {
                    if ($record->changeStatus(ProductionStatus::ON_HOLD, $data['notes'])) {
                        Notification::make()
                            ->warning()
                            ->title('Producción Pausada')
                            ->send();
                    }
                }),

            Action::make('resume_production')
                ->label('Reanudar')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn ($record) => $record->status === ProductionStatus::ON_HOLD)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::QUEUED, 'Producción reanudada')) {
                        Notification::make()
                            ->success()
                            ->title('Producción Reanudada')
                            ->send();
                    }
                }),

            Action::make('complete_production')
                ->label('Completar Producción')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Completar Producción')
                ->modalDescription('¿Confirma que todos los items han sido producidos?')
                ->visible(fn ($record) => $record->status === ProductionStatus::IN_PROGRESS)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::COMPLETED, 'Producción completada')) {
                        $efficiency = $record->getEfficiency();

                        Notification::make()
                            ->success()
                            ->title('Producción Completada')
                            ->body("Eficiencia: {$efficiency['efficiency_percentage']}% | Cumplimiento: {$efficiency['fulfillment_percentage']}%")
                            ->send();
                    }
                }),

            Action::make('cancel')
                ->label('Cancelar Orden')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->form([
                    Components\Textarea::make('cancellation_reason')
                        ->label('Motivo de Cancelación')
                        ->required()
                        ->rows(3),
                ])
                ->visible(fn ($record) => $record->canBeCancelled())
                ->action(function ($record, array $data) {
                    if ($record->changeStatus(ProductionStatus::CANCELLED, $data['cancellation_reason'])) {
                        Notification::make()
                            ->danger()
                            ->title('Orden Cancelada')
                            ->send();
                    }
                }),

            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }
}
