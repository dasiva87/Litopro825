<?php

namespace App\Filament\Resources\PurchaseOrders\Pages;

use App\Filament\Resources\PurchaseOrders\PurchaseOrderResource;
use Filament\Actions;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
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
                ])
                ->modalHeading('Enviar Orden por Email')
                ->modalDescription(fn () => "Enviar orden #{$this->record->order_number} a {$this->record->supplierCompany?->name}")
                ->action(function (array $data) {
                    $pdfService = new \App\Services\PurchaseOrderPdfService;
                    $sent = $pdfService->emailPdf($this->record, [$data['email']]);

                    if ($sent) {
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

            DeleteAction::make(),
        ];
    }
}
