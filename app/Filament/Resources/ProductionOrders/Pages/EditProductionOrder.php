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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Marcar como Enviada')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Marcar Orden como Enviada')
                ->modalDescription('¿Está seguro de que desea marcar esta orden como enviada?')
                ->visible(fn ($record) => $record->status === ProductionStatus::DRAFT && $record->total_items > 0)
                ->action(function ($record) {
                    if ($record->changeStatus(ProductionStatus::SENT, 'Orden marcada como enviada desde panel')) {
                        Notification::make()
                            ->success()
                            ->title('Orden Enviada')
                            ->body('La orden ha sido marcada como enviada')
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
                // Solo visible para órdenes propias (company_id = empresa logueada) sin proveedor externo
                ->visible(function ($record) {
                    $userCompanyId = auth()->user()->company_id;

                    // Solo órdenes de MI empresa (no órdenes que me enviaron)
                    if ($record->company_id !== $userCompanyId) {
                        return false;
                    }

                    // Verificar estado
                    if (!in_array($record->status, [ProductionStatus::DRAFT, ProductionStatus::SENT])) {
                        return false;
                    }

                    // Si tiene supplier_company_id es para proveedor externo
                    if (!is_null($record->supplier_company_id)) {
                        return false;
                    }

                    // Si tiene supplier_id, verificar que sea un contacto de MI empresa (proveedor interno)
                    if (!is_null($record->supplier_id) && $record->supplier) {
                        return $record->supplier->company_id === $userCompanyId;
                    }

                    // Sin proveedor = producción interna
                    return true;
                })
                ->action(function ($record, array $data) {
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
