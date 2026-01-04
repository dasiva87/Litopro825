<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

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
                    // VALIDACIÓN 1: Verificar items
                    if ($this->record->purchaseOrderItems->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden no tiene items. Agrega items antes de enviar.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 2: Verificar total
                    if ($this->record->total_amount <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La orden tiene un total de $0. Verifica los items.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 3: Obtener email del proveedor
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

                        // Actualizar registro de envío y cambiar estado a "Enviada"
                        $this->record->update([
                            'email_sent_at' => now(),
                            'email_sent_by' => auth()->id(),
                            'status' => \App\Enums\OrderStatus::SENT,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Email enviado')
                            ->body("Orden enviada exitosamente a {$supplierEmail}. Estado cambiado a 'Enviada'.")
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            DeleteAction::make(),
        ];
    }
}
