<?php

namespace App\Filament\Resources\CollectionAccounts\Pages;

use App\Enums\CollectionAccountStatus;
use App\Filament\Resources\CollectionAccounts\CollectionAccountResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ViewRecord;

class ViewCollectionAccount extends ViewRecord
{
    protected static string $resource = CollectionAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->visible(fn () => $this->record->status !== CollectionAccountStatus::PAID),

            Action::make('view_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => route('collection-accounts.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('send_email')
                ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Cliente')
                ->icon('heroicon-o-envelope')
                ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
                ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
                ->badgeColor('success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->email_sent_at
                    ? 'Reenviar Cuenta por Email'
                    : 'Enviar Cuenta por Email')
                ->modalDescription(function () {
                    $clientName = $this->record->clientCompany->name
                        ?? $this->record->contact->name
                        ?? 'Sin cliente';

                    $description = "Cuenta #{$this->record->account_number} para {$clientName}\n\n";

                    if ($this->record->email_sent_at) {
                        $description .= "⚠️ Esta cuenta ya fue enviada el {$this->record->email_sent_at->format('d/m/Y H:i')}\n";
                        $description .= "¿Deseas reenviar el email?";
                    } else {
                        $description .= "Se enviará el email con el PDF de la cuenta al cliente.";
                    }

                    return $description;
                })
                ->modalIcon('heroicon-o-envelope')
                ->action(function () {
                    // VALIDACIÓN 1: Verificar items
                    if ($this->record->documentItems->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La cuenta no tiene items. Agrega items antes de enviar.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 2: Verificar total
                    if ($this->record->total_amount <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('La cuenta tiene un total de $0. Verifica los items.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 3: Verificar email del cliente
                    $clientEmail = $this->record->clientCompany->email
                        ?? $this->record->contact->email;

                    if (!$clientEmail) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('El cliente no tiene email configurado.')
                            ->send();
                        return;
                    }

                    try {
                        // Enviar notificación con PDF
                        \Illuminate\Support\Facades\Notification::route('mail', $clientEmail)
                            ->notify(new \App\Notifications\CollectionAccountSent($this->record->id));

                        // Actualizar registro de envío y cambiar estado a "Enviada"
                        $this->record->update([
                            'email_sent_at' => now(),
                            'email_sent_by' => auth()->id(),
                            'status' => \App\Enums\CollectionAccountStatus::SENT,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Email enviado')
                            ->body("Cuenta enviada exitosamente a {$clientEmail}. Estado cambiado a 'Enviada'.")
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Action::make('change_status')
                ->label('Cambiar Estado')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->form([
                    \Filament\Forms\Components\Select::make('new_status')
                        ->label('Nuevo Estado')
                        ->options(fn () => collect(CollectionAccountStatus::cases())
                            ->filter(fn ($status) => $status !== $this->record->status) // Mostrar todos excepto el actual
                            ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
                        )
                        ->required()
                        ->native(false),

                    Textarea::make('notes')
                        ->label('Notas')
                        ->placeholder('Notas sobre el cambio de estado...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $newStatus = CollectionAccountStatus::from($data['new_status']);

                    if ($this->record->changeStatus($newStatus, $data['notes'] ?? null)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Estado actualizado')
                            ->success()
                            ->body("La cuenta cambió a: {$newStatus->getLabel()}")
                            ->send();

                        // Refrescar la página para mostrar el nuevo estado
                        redirect()->to(CollectionAccountResource::getUrl('view', ['record' => $this->record]));
                    } else {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al cambiar estado')
                            ->danger()
                            ->body('La transición de estado no es válida')
                            ->send();
                    }
                })
                ->visible(fn () => $this->record->status !== CollectionAccountStatus::PAID), // No se puede cambiar si ya está pagada

            Action::make('mark_as_paid')
                ->label('Marcar como Pagada')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirmar Pago')
                ->modalDescription('¿Confirmas que esta cuenta de cobro ha sido pagada?')
                ->action(function () {
                    if ($this->record->changeStatus(CollectionAccountStatus::PAID)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Cuenta marcada como pagada')
                            ->success()
                            ->send();

                        // Refrescar la página para mostrar el nuevo estado
                        redirect()->to(CollectionAccountResource::getUrl('view', ['record' => $this->record]));
                    }
                })
                ->visible(fn () => $this->record->status === CollectionAccountStatus::APPROVED),
        ];
    }
}
