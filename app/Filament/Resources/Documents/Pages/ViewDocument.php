<?php

namespace App\Filament\Resources\Documents\Pages;

use App\Filament\Resources\Documents\DocumentResource;
use App\Filament\Resources\Documents\Widgets\FinancialSummaryWidget;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewDocument extends ViewRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn () => $this->record->canEdit()),

            Actions\Action::make('print_pdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                ->color('gray')
                ->url(fn () => route('documents.pdf', $this->record))
                ->openUrlInNewTab(),

            Actions\Action::make('send_email')
                ->label(fn () => $this->record->email_sent_at ? 'Reenviar Email' : 'Enviar Email al Cliente')
                ->icon('heroicon-o-envelope')
                ->color(fn () => $this->record->email_sent_at ? 'success' : 'warning')
                ->badge(fn () => $this->record->email_sent_at ? 'Enviado' : null)
                ->badgeColor('success')
                ->requiresConfirmation()
                ->modalHeading(fn () => $this->record->email_sent_at
                    ? 'Reenviar Documento por Email'
                    : 'Enviar Documento por Email')
                ->modalDescription(function () {
                    $clientName = $this->record->clientCompany->name
                        ?? $this->record->contact->name
                        ?? 'Sin cliente';

                    $documentTypeName = $this->record->documentType->name ?? 'Documento';

                    $description = "{$documentTypeName} #{$this->record->document_number} para {$clientName}\n\n";

                    if ($this->record->email_sent_at) {
                        $description .= "⚠️ Este documento ya fue enviado el {$this->record->email_sent_at->format('d/m/Y H:i')}\n";
                        $description .= "¿Deseas reenviar el email?";
                    } else {
                        $description .= "Se enviará el email con el PDF del documento al cliente.";
                    }

                    return $description;
                })
                ->modalIcon('heroicon-o-envelope')
                ->action(function () {
                    // VALIDACIÓN 1: Verificar items
                    if ($this->record->items->isEmpty()) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('El documento no tiene items. Agrega items antes de enviar.')
                            ->send();
                        return;
                    }

                    // VALIDACIÓN 2: Verificar total
                    if ($this->record->total <= 0) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('No se puede enviar')
                            ->body('El documento tiene un total de $0. Verifica los items.')
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
                            ->notify(new \App\Notifications\QuoteSent($this->record->id));

                        // Actualizar registro de envío
                        $this->record->update([
                            'email_sent_at' => now(),
                            'email_sent_by' => auth()->id(),
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Email enviado')
                            ->body("Documento enviado exitosamente a {$clientEmail}")
                            ->send();

                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->danger()
                            ->title('Error al enviar email')
                            ->body($e->getMessage())
                            ->send();
                    }
                }),

            Actions\Action::make('send')
                ->label('Enviar')
                ->icon('heroicon-o-paper-airplane')
                ->color('primary')
                ->visible(fn () => $this->record->canSend())
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsSent()),

            Actions\Action::make('approve')
                ->label('Aprobar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->canApprove())
                ->requiresConfirmation()
                ->action(fn () => $this->record->markAsApproved()),
        ];
    }

    protected function getFooterWidgets(): array
    {
        return [
            FinancialSummaryWidget::make([
                'record' => $this->record,
            ]),
        ];
    }
}