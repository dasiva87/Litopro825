<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Enums\OrderStatus;
use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ViewRecord;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('change_status')
                ->label('Cambiar Estado')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->visible(fn () => ! in_array($this->record->status, [OrderStatus::RECEIVED, OrderStatus::CANCELLED]))
                ->form(function () {
                    $nextStatuses = $this->record->status->getNextStatuses();

                    return [
                        Select::make('new_status')
                            ->label('Nuevo Estado')
                            ->options(collect($nextStatuses)->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()]))
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->placeholder('Opcional: Agrega notas sobre este cambio de estado'),
                    ];
                })
                ->modalHeading(fn () => "Cambiar Estado - Orden #{$this->record->order_number}")
                ->modalDescription(fn () => "Estado actual: {$this->record->status->getLabel()}")
                ->action(function (array $data) {
                    $newStatus = OrderStatus::from($data['new_status']);
                    $oldStatus = $this->record->status;

                    if ($this->record->changeStatus($newStatus, $data['notes'] ?? null)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Estado actualizado')
                            ->body("Orden cambiada de {$oldStatus->getLabel()} a {$newStatus->getLabel()}")
                            ->success()
                            ->send();

                        // Si se cambió a 'sent', notificar
                        if ($newStatus === OrderStatus::SENT) {
                            \Filament\Notifications\Notification::make()
                                ->title('Notificación enviada')
                                ->body('Se ha enviado una notificación al proveedor')
                                ->info()
                                ->send();
                        }
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Error')
                            ->body("No se puede cambiar de {$oldStatus->getLabel()} a {$newStatus->getLabel()}")
                            ->danger()
                            ->send();
                    }
                }),

            Actions\Action::make('view_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => route('purchase-orders.pdf', $this->record->id))
                ->openUrlInNewTab(),

            Actions\Action::make('send_email')
                ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Proveedor')
                ->icon('heroicon-o-envelope')
                ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
                ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
                ->badgeColor('success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->email_sent_at
                    ? 'Reenviar Orden por Email'
                    : 'Enviar Orden por Email')
                ->modalDescription(function () {
                    $supplierName = $this->record->supplierCompany->name
                        ?? $this->record->supplier->name
                        ?? 'Sin proveedor';

                    $description = "Orden #{$this->record->order_number} para {$supplierName}\n\n";

                    if ($this->record->email_sent_at) {
                        $description .= "⚠️ Esta orden ya fue enviada el {$this->record->email_sent_at->format('d/m/Y H:i')}\n";
                        $description .= "¿Deseas reenviar el email?";
                    } else {
                        $description .= "Se enviará el email con el PDF de la orden al proveedor.";
                    }

                    return $description;
                })
                ->modalIcon('heroicon-o-envelope')
                ->action(function () {
                    // Validar que tenga items
                    if ($this->record->purchaseOrderItems->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden no tiene items. Agrega items antes de enviar.')
                            ->send();
                        return;
                    }

                    // Validar que tenga total
                    if ($this->record->total_amount <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden tiene un total de $0. Verifica los items.')
                            ->send();
                        return;
                    }

                    // Obtener email del proveedor
                    $supplierEmail = $this->record->supplierCompany->email
                        ?? $this->record->supplier->email;

                    if (!$supplierEmail) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('El proveedor no tiene email configurado.')
                            ->send();
                        return;
                    }

                    try {
                        // Enviar notificación con PDF
                        \Illuminate\Support\Facades\Notification::route('mail', $supplierEmail)
                            ->notify(new \App\Notifications\PurchaseOrderCreated($this->record->id));

                        // Actualizar registro de envío
                        $this->record->update([
                            'email_sent_at' => now(),
                            'email_sent_by' => auth()->id(),
                        ]);

                        $action = $this->record->wasChanged('email_sent_at') ? 'reenviado' : 'enviado';

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Email ' . $action)
                            ->body("Orden {$action} exitosamente a {$supplierEmail}")
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\EditAction::make(),
        ];
    }
}
