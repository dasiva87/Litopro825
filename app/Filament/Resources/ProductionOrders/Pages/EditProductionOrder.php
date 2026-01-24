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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Limpiar campos que no existen en la BD
        unset($data['supplier_type']);
        unset($data['supplier_company_id']);

        return $data;
    }

    /**
     * Determina si el usuario puede gestionar los estados de esta orden
     */
    protected function canManageStatus(): bool
    {
        $userCompanyId = auth()->user()->company_id;
        $record = $this->record;

        // Orden RECIBIDA: otra empresa me la envió para que yo produzca
        if ($record->supplier_company_id === $userCompanyId) {
            return true;
        }

        // Orden PROPIA: mi empresa la creó Y no tiene proveedor externo
        if ($record->company_id === $userCompanyId && is_null($record->supplier_company_id)) {
            return true;
        }

        // Orden ENVIADA: mi empresa la creó pero tiene proveedor externo = NO puedo gestionar
        return false;
    }

    /**
     * Determina si es una orden recibida de otra empresa
     */
    protected function isReceivedOrder(): bool
    {
        return $this->record->supplier_company_id === auth()->user()->company_id;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Enviar orden (solo para órdenes propias que se envían a proveedor externo)
            Action::make('send')
                ->label('Marcar como Enviada')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Marcar Orden como Enviada')
                ->modalDescription('¿Está seguro de que desea marcar esta orden como enviada?')
                ->visible(function () {
                    $record = $this->record;
                    // Solo si es mi orden, está en borrador, tiene items, y tiene proveedor externo
                    return $record->company_id === auth()->user()->company_id
                        && $record->status === ProductionStatus::DRAFT
                        && $record->total_items > 0
                        && !is_null($record->supplier_company_id);
                })
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::SENT]);
                    Notification::make()
                        ->success()
                        ->title('Orden Enviada')
                        ->body('La orden ha sido marcada como enviada')
                        ->send();
                }),

            // Marcar como recibida (solo para órdenes recibidas de otras empresas)
            Action::make('mark_received')
                ->label('Marcar como Recibida')
                ->icon('heroicon-o-inbox-arrow-down')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Recepción')
                ->modalDescription('¿Confirmas que has recibido esta orden de producción?')
                ->visible(fn () => $this->isReceivedOrder() && $this->record->status === ProductionStatus::SENT)
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::RECEIVED]);
                    Notification::make()->success()->title('Orden marcada como Recibida')->send();
                }),

            // Iniciar producción
            Action::make('start_production')
                ->label('Iniciar Producción')
                ->icon('heroicon-o-play')
                ->color('success')
                ->form([
                    Components\Select::make('operator_user_id')
                        ->label('Operador que realizará la producción')
                        ->relationship(
                            name: 'operator',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query
                                ->where('company_id', auth()->user()->company_id)
                                ->where('is_active', true)
                        )
                        ->required()
                        ->searchable()
                        ->preload(),
                ])
                ->visible(function () {
                    if (!$this->canManageStatus()) {
                        return false;
                    }
                    return in_array($this->record->status, [
                        ProductionStatus::DRAFT,
                        ProductionStatus::RECEIVED,
                    ]);
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'operator_user_id' => $data['operator_user_id'],
                        'status' => ProductionStatus::IN_PROGRESS,
                    ]);
                    Notification::make()
                        ->success()
                        ->title('Producción Iniciada')
                        ->body('La orden de producción ha comenzado')
                        ->send();
                }),

            // Pausar producción
            Action::make('pause_production')
                ->label('Pausar')
                ->icon('heroicon-o-pause-circle')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Pausar Producción')
                ->modalDescription('¿Deseas pausar esta orden de producción?')
                ->visible(fn () => $this->canManageStatus() && $this->record->status === ProductionStatus::IN_PROGRESS)
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::ON_HOLD]);
                    Notification::make()->success()->title('Producción Pausada')->send();
                }),

            // Reanudar producción
            Action::make('resume_production')
                ->label('Reanudar')
                ->icon('heroicon-o-play')
                ->color('warning')
                ->requiresConfirmation()
                ->visible(fn () => $this->canManageStatus() && $this->record->status === ProductionStatus::ON_HOLD)
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::IN_PROGRESS]);
                    Notification::make()->success()->title('Producción Reanudada')->send();
                }),

            // Completar producción
            Action::make('complete_production')
                ->label('Completar Producción')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Completar Producción')
                ->modalDescription('¿Confirma que todos los items han sido producidos?')
                ->visible(fn () => $this->canManageStatus() && $this->record->status === ProductionStatus::IN_PROGRESS)
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::COMPLETED]);
                    Notification::make()
                        ->success()
                        ->title('Producción Completada')
                        ->send();
                }),

            // Cancelar orden
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
                ->visible(function () {
                    if (!$this->canManageStatus()) {
                        return false;
                    }
                    return !in_array($this->record->status, [
                        ProductionStatus::COMPLETED,
                        ProductionStatus::CANCELLED,
                    ]);
                })
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => ProductionStatus::CANCELLED,
                        'notes' => ($this->record->notes ?? '') . "\n\nMotivo de cancelación: " . $data['cancellation_reason'],
                    ]);
                    Notification::make()
                        ->danger()
                        ->title('Orden Cancelada')
                        ->send();
                }),

            DeleteAction::make()
                ->visible(fn () => $this->canManageStatus()),
            ForceDeleteAction::make()
                ->visible(fn () => $this->canManageStatus()),
            RestoreAction::make(),
        ];
    }
}
