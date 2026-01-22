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

            Action::make('view_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => route('production-orders.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('send_email')
                ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Operador')
                ->icon('heroicon-o-envelope')
                ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
                ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
                ->badgeColor('success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->email_sent_at
                    ? 'Reenviar Orden por Email'
                    : 'Enviar Orden por Email')
                ->modalDescription(function () {
                    $operatorName = $this->record->operator->name
                        ?? $this->record->supplierCompany->name
                        ?? $this->record->supplier->name
                        ?? 'Sin operador asignado';

                    $description = "Orden #{$this->record->production_number} para {$operatorName}\n\n";

                    if ($this->record->email_sent_at) {
                        $description .= "⚠️ Esta orden ya fue enviada el {$this->record->email_sent_at->format('d/m/Y H:i')}\n";
                        $description .= "¿Deseas reenviar el email?";
                    } else {
                        $description .= "Se enviará el email con el PDF de la orden al operador.";
                    }

                    return $description;
                })
                ->modalIcon('heroicon-o-envelope')
                ->action(function () {
                    // VALIDACIÓN 1: Verificar items
                    if ($this->record->documentItems->isEmpty()) {
                        Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden no tiene items. Agrega items antes de enviar.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 2: Verificar total items
                    if ($this->record->total_items <= 0) {
                        Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden no tiene items válidos.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 3: Verificar email del operador
                    $operatorEmail = $this->record->operator->email
                        ?? $this->record->supplierCompany->email
                        ?? $this->record->supplier->email;

                    if (!$operatorEmail) {
                        Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('El operador/proveedor no tiene email configurado.')
                            ->send();
                        return;
                    }

                    try {
                        // Enviar notificación con PDF
                        \Illuminate\Support\Facades\Notification::route('mail', $operatorEmail)
                            ->notify(new \App\Notifications\ProductionOrderSent($this->record->id));

                        // Actualizar registro de envío y cambiar estado a "Enviada"
                        $this->record->update([
                            'email_sent_at' => now(),
                            'email_sent_by' => auth()->id(),
                            'status' => ProductionStatus::SENT,
                        ]);

                        Notification::make()
                            ->success()
                            ->title('Email enviado')
                            ->body("Orden enviada exitosamente a {$operatorEmail}. Estado cambiado a 'Enviada'.")
                            ->send();

                    } catch (\Exception $e) {
                        Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body($e->getMessage())
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
                        ->searchable()
                        ->preload()
                        ->required(),
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
                    $record->update([
                        'operator_user_id' => $data['operator_user_id'],
                    ]);
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
