<?php

namespace App\Filament\Resources\CollectionAccounts\Pages;

use App\Enums\CollectionAccountStatus;
use App\Filament\Resources\CollectionAccounts\CollectionAccountResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;

class EditCollectionAccount extends EditRecord
{
    protected static string $resource = CollectionAccountResource::class;

    public function mount(int | string $record): void
    {
        parent::mount($record);

        // Redirigir a la vista si la cuenta ya está pagada
        if ($this->record->status === CollectionAccountStatus::PAID) {
            \Filament\Notifications\Notification::make()
                ->title('No se puede editar')
                ->warning()
                ->body('No se puede editar una cuenta de cobro que ya está pagada.')
                ->send();

            redirect()->to(CollectionAccountResource::getUrl('view', ['record' => $this->record]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),

            Action::make('view_pdf')
                ->label('Ver PDF')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(fn () => route('collection-accounts.pdf', $this->record))
                ->openUrlInNewTab(),

            Action::make('send_email')
                ->label('Enviar por Email')
                ->icon('heroicon-o-envelope')
                ->color('primary')
                ->form([
                    TextInput::make('email')
                        ->label('Email del Cliente')
                        ->email()
                        ->default(fn () => $this->record->clientCompany->email)
                        ->required(),

                    Textarea::make('message')
                        ->label('Mensaje Adicional')
                        ->placeholder('Mensaje personalizado para incluir en el email...')
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    try {
                        // Generar PDF
                        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('collection-accounts.pdf', [
                            'collectionAccount' => $this->record->load([
                                'company',
                                'clientCompany',
                                'documentItems.itemable',
                                'documentItems.document',
                                'createdBy'
                            ])
                        ])
                        ->setPaper('letter', 'portrait')
                        ->setOptions([
                            'defaultFont' => 'Arial',
                            'isRemoteEnabled' => true,
                            'isHtml5ParserEnabled' => true,
                            'dpi' => 150,
                            'defaultPaperSize' => 'letter',
                        ]);

                        // Enviar email
                        \Illuminate\Support\Facades\Mail::send('emails.collection-account-sent', [
                            'collectionAccount' => $this->record,
                            'customMessage' => $data['message'] ?? null,
                        ], function ($message) use ($data, $pdf) {
                            $message->to($data['email'])
                                ->subject("Cuenta de Cobro #{$this->record->account_number} - {$this->record->company->name}")
                                ->attachData($pdf->output(), $this->record->account_number . '.pdf', [
                                    'mime' => 'application/pdf',
                                ]);
                        });

                        \Filament\Notifications\Notification::make()
                            ->title('Email enviado')
                            ->success()
                            ->body("Cuenta de cobro enviada a {$data['email']}")
                            ->send();
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Error al enviar email')
                            ->danger()
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
                            ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
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
                            ->body("La cuenta cambió a: {$newStatus->label()}")
                            ->send();

                        $this->refreshFormData([
                            'status',
                            'paid_date'
                        ]);
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

                        $this->refreshFormData([
                            'status',
                            'paid_date'
                        ]);
                    }
                })
                ->visible(fn () => $this->record->status === CollectionAccountStatus::APPROVED),

            DeleteAction::make(),
        ];
    }
}
