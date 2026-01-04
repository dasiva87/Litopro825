<?php

namespace App\Filament\Resources\CollectionAccounts\Tables;

use App\Enums\CollectionAccountStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
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

                TextColumn::make('account_number')
                    ->label('Número')
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

                TextColumn::make('paid_date')
                    ->label('Fecha de Pago')
                    ->date()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('email_sent_at')
                    ->label('Email')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Enviado' : 'Pendiente')
                    ->color(fn ($state) => $state ? 'success' : 'gray')
                    ->icon(fn ($state) => $state ? 'heroicon-o-check-circle' : 'heroicon-o-clock')
                    ->tooltip(fn ($record) => $record->email_sent_at
                        ? "Enviado: {$record->email_sent_at->format('d/m/Y H:i')}"
                        : 'Email no enviado')
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
                        ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
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
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),

                    Action::make('view_pdf')
                        ->label('Ver PDF')
                        ->icon('heroicon-o-document-text')
                        ->color('info')
                        ->url(fn ($record) => route('collection-accounts.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('send_email')
                    ->label('Enviar email')
                    ->icon('heroicon-o-envelope')
                    ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
                    ->tooltip(fn ($record) => $record->email_sent_at
                        ? 'Reenviar Email (enviado ' . $record->email_sent_at->diffForHumans() . ')'
                        : 'Enviar Email al Cliente')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->email_sent_at
                        ? 'Reenviar Cuenta por Email'
                        : 'Enviar Cuenta por Email')
                    ->modalDescription(function ($record) {
                        $clientName = $record->clientCompany->name
                            ?? $record->contact->name
                            ?? 'Sin cliente';

                        $description = "Cuenta #{$record->account_number} para {$clientName}";

                        if ($record->email_sent_at) {
                            $description .= "\n\n⚠️ Esta cuenta ya fue enviada el {$record->email_sent_at->format('d/m/Y H:i')}";
                        }

                        return $description;
                    })
                    ->modalIcon('heroicon-o-envelope')
                    ->action(function ($record) {
                        // VALIDACIÓN 1: Verificar items
                        if ($record->documentItems->isEmpty()) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede enviar')
                                ->body('La cuenta no tiene items. Agrega items antes de enviar.')
                                ->send();
                            return;
                        }

                        // VALIDACIÓN 2: Verificar total
                        if ($record->total_amount <= 0) {
                            \Filament\Notifications\Notification::make()
                                ->danger()
                                ->title('No se puede enviar')
                                ->body('La cuenta tiene un total de $0. Verifica los items.')
                                ->send();
                            return;
                        }

                        // VALIDACIÓN 3: Verificar email del cliente
                        $clientEmail = $record->clientCompany->email
                            ?? $record->contact->email;

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
                                ->notify(new \App\Notifications\CollectionAccountSent($record->id));

                            // Actualizar registro de envío y cambiar estado a "Enviada"
                            $record->update([
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
                        Select::make('new_status')
                            ->label('Nuevo Estado')
                            ->options(fn ($record) => collect(CollectionAccountStatus::cases())
                                ->filter(fn ($status) => $status !== $record->status) // Mostrar todos excepto el actual
                                ->mapWithKeys(fn ($status) => [$status->value => $status->getLabel()])
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
                                ->body("La cuenta cambió a: {$newStatus->getLabel()}")
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
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
