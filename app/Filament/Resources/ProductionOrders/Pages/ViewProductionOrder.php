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

    /**
     * Determina si el usuario puede gestionar los estados de esta orden
     * Solo puede gestionar si:
     * - Es orden PROPIA (company_id = mi empresa, sin supplier_company_id externo)
     * - Es orden RECIBIDA (supplier_company_id = mi empresa)
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

    /**
     * Determina si es una orden propia (producción interna)
     */
    protected function isOwnOrder(): bool
    {
        $userCompanyId = auth()->user()->company_id;
        return $this->record->company_id === $userCompanyId && is_null($this->record->supplier_company_id);
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->canManageStatus()),

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
                // Solo visible para órdenes propias o enviadas (no recibidas)
                ->visible(fn () => !$this->isReceivedOrder() && $this->record->company_id === auth()->user()->company_id)
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

            // Acción para marcar como RECIBIDA (solo órdenes recibidas de otras empresas)
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

            // Iniciar producción (órdenes propias o recibidas)
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
                ->visible(function () {
                    if (!$this->canManageStatus()) {
                        return false;
                    }

                    // Visible en estados que permiten iniciar producción
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
                    Notification::make()->success()->title('Producción Iniciada')->send();
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

            // Reanudar producción (desde pausa)
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
                ->label('Completar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn () => $this->canManageStatus() && $this->record->status === ProductionStatus::IN_PROGRESS)
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::COMPLETED]);
                    Notification::make()->success()->title('Producción Completada')->send();
                }),

            // Cancelar orden
            Action::make('cancel_order')
                ->label('Cancelar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancelar Orden')
                ->modalDescription('¿Estás seguro de cancelar esta orden? Esta acción no se puede deshacer.')
                ->visible(function () {
                    if (!$this->canManageStatus()) {
                        return false;
                    }
                    // No se puede cancelar si ya está completada o cancelada
                    return !in_array($this->record->status, [
                        ProductionStatus::COMPLETED,
                        ProductionStatus::CANCELLED,
                    ]);
                })
                ->action(function () {
                    $this->record->update(['status' => ProductionStatus::CANCELLED]);
                    Notification::make()->success()->title('Orden Cancelada')->send();
                }),
        ];
    }
}
