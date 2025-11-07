<?php

namespace App\Filament\Resources\CollectionAccounts\Tables;

use App\Enums\CollectionAccountStatus;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CollectionAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account_type')
                    ->label('Tipo')
                    ->state(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'Enviada' : 'Recibida';
                    })
                    ->badge()
                    ->color(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'info' : 'success';
                    })
                    ->icon(function ($record) {
                        $currentCompanyId = auth()->user()->company_id;

                        return $record->company_id === $currentCompanyId ? 'heroicon-o-paper-airplane' : 'heroicon-o-inbox-arrow-down';
                    }),

                TextColumn::make('account_number')
                    ->label('Número de Cuenta')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('clientCompany.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('issue_date')
                    ->label('Fecha de Emisión')
                    ->date()
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && $record->status !== CollectionAccountStatus::PAID ? 'danger' : null),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('documentItems')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('createdBy.name')
                    ->label('Creado por')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(fn () => collect(CollectionAccountStatus::cases())
                        ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                    )
                    ->multiple()
                    ->native(false),

                SelectFilter::make('client_company_id')
                    ->label('Cliente')
                    ->relationship('clientCompany', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                SelectFilter::make('account_type')
                    ->label('Tipo de Cuenta')
                    ->options([
                        'sent' => 'Enviadas',
                        'received' => 'Recibidas',
                    ])
                    ->query(function ($query, array $data) {
                        $currentCompanyId = auth()->user()->company_id;

                        if ($data['value'] === 'sent') {
                            return $query->where('company_id', $currentCompanyId);
                        } elseif ($data['value'] === 'received') {
                            return $query->where('client_company_id', $currentCompanyId);
                        }

                        return $query;
                    })
                    ->native(false),

                \Filament\Tables\Filters\Filter::make('overdue')
                    ->label('Vencidas')
                    ->query(fn ($query) => $query
                        ->where('due_date', '<', now())
                        ->whereNotIn('status', [CollectionAccountStatus::PAID->value, CollectionAccountStatus::CANCELLED->value])
                    )
                    ->toggle(),

                \Filament\Tables\Filters\Filter::make('due_soon')
                    ->label('Por Vencer (7 días)')
                    ->query(fn ($query) => $query
                        ->whereBetween('due_date', [now(), now()->addDays(7)])
                        ->whereNotIn('status', [CollectionAccountStatus::PAID->value, CollectionAccountStatus::CANCELLED->value])
                    )
                    ->toggle(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),

                Action::make('view_pdf')
                    ->label('Ver PDF')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn ($record) => route('collection-accounts.pdf', $record))
                    ->openUrlInNewTab(),

                Action::make('download_pdf')
                    ->label('Descargar PDF')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->url(fn ($record) => route('collection-accounts.pdf.download', $record)),

                Action::make('send_email')
                    ->label('Enviar por Email')
                    ->icon('heroicon-o-envelope')
                    ->color('primary')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('email')
                            ->label('Email del Cliente')
                            ->email()
                            ->default(fn ($record) => $record->clientCompany->email)
                            ->required(),

                        \Filament\Forms\Components\Textarea::make('message')
                            ->label('Mensaje Adicional')
                            ->placeholder('Mensaje personalizado para incluir en el email...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        try {
                            // Generar PDF
                            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('collection-accounts.pdf', ['collectionAccount' => $record->load([
                                'company',
                                'clientCompany',
                                'documentItems.itemable',
                                'documentItems.document',
                                'createdBy'
                            ])])
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
                                'collectionAccount' => $record,
                                'customMessage' => $data['message'] ?? null,
                            ], function ($message) use ($record, $data, $pdf) {
                                $message->to($data['email'])
                                    ->subject("Cuenta de Cobro #{$record->account_number} - {$record->company->name}")
                                    ->attachData($pdf->output(), $record->account_number . '.pdf', [
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
                        Select::make('new_status')
                            ->label('Nuevo Estado')
                            ->options(fn ($record) => collect(CollectionAccountStatus::cases())
                                ->filter(fn ($status) => $status !== $record->status) // Mostrar todos excepto el actual
                                ->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                            )
                            ->required()
                            ->native(false),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->placeholder('Notas sobre el cambio de estado...')
                            ->rows(3),
                    ])
                    ->action(function ($record, array $data) {
                        $newStatus = CollectionAccountStatus::from($data['new_status']);

                        if ($record->changeStatus($newStatus, $data['notes'] ?? null)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Estado actualizado')
                                ->success()
                                ->body("La cuenta cambió a: {$newStatus->label()}")
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Error al cambiar estado')
                                ->danger()
                                ->body('La transición de estado no es válida')
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => true), // Siempre visible para permitir cualquier cambio

                Action::make('mark_as_paid')
                    ->label('Marcar como Pagada')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Confirmar Pago')
                    ->modalDescription('¿Confirmas que esta cuenta de cobro ha sido pagada?')
                    ->action(function ($record) {
                        if ($record->changeStatus(CollectionAccountStatus::PAID)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Cuenta marcada como pagada')
                                ->success()
                                ->send();
                        }
                    })
                    ->visible(fn ($record) => $record->status === CollectionAccountStatus::APPROVED),

                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
