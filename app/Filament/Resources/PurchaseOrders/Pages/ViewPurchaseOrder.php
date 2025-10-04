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
                ->label('Enviar por Email')
                ->icon('heroicon-o-envelope')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\TextInput::make('email')
                        ->label('Email del Proveedor')
                        ->email()
                        ->required()
                        ->default(fn () => $this->record->supplierCompany?->email)
                        ->helperText(fn () => $this->record->supplierCompany?->email
                            ? 'Email configurado en el proveedor'
                            : 'El proveedor no tiene email configurado. Ingresa uno manualmente.'),

                    \Filament\Forms\Components\Checkbox::make('change_status_to_sent')
                        ->label('Cambiar estado a "Enviada"')
                        ->default(fn () => $this->record->status === OrderStatus::DRAFT)
                        ->visible(fn () => $this->record->status === OrderStatus::DRAFT)
                        ->helperText('Esto actualizará el estado de la orden y enviará notificación al proveedor'),
                ])
                ->modalHeading('Enviar Orden por Email')
                ->modalDescription(fn () => "Enviar orden #{$this->record->order_number} a {$this->record->supplierCompany?->name}")
                ->action(function (array $data) {
                    $pdfService = new \App\Services\PurchaseOrderPdfService;
                    $sent = $pdfService->emailPdf($this->record, [$data['email']]);

                    if ($sent) {
                        // Cambiar estado si está marcado
                        if (($data['change_status_to_sent'] ?? false) && $this->record->status === OrderStatus::DRAFT) {
                            $this->record->changeStatus(OrderStatus::SENT);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Email enviado')
                            ->body("Orden enviada exitosamente a {$data['email']}")
                            ->success()
                            ->send();
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al enviar')
                            ->body('No se pudo enviar el email. Revisa la configuración SMTP.')
                            ->danger()
                            ->send();
                    }
                }),

            Actions\EditAction::make(),
        ];
    }
}
