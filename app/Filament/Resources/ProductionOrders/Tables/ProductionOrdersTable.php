<?php

namespace App\Filament\Resources\ProductionOrders\Tables;

use App\Enums\ProductionStatus;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductionOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('production_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office')
                    ->placeholder('Sin asignar'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('scheduled_date')
                    ->label('Fecha Programada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->placeholder('Sin programar'),

                TextColumn::make('total_items')
                    ->label('Items')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('primary')
                    ->suffix(' items'),

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

                TextColumn::make('started_at')
                    ->label('Iniciado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('completed_at')
                    ->label('Completado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable()
                    ->toggledHiddenByDefault(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(ProductionStatus::class)
                    ->native(false),

                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name', fn ($query) => $query->whereIn('type', ['supplier', 'both']))
                    ->searchable()
                    ->preload()
                    ->native(false),

                SelectFilter::make('operator_user_id')
                    ->label('Operador')
                    ->relationship('operator', 'name')
                    ->searchable()
                    ->preload()
                    ->native(false),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),

                    EditAction::make(),

                    Action::make('send_email')
                        ->label(fn ($record) => $record->email_sent_at ? 'Reenviar Email' : 'Enviar Email')
                        ->icon('heroicon-o-envelope')
                        ->color(fn ($record) => $record->email_sent_at ? 'success' : 'warning')
                        ->requiresConfirmation()
                        ->modalHeading(fn ($record) => $record->email_sent_at
                            ? 'Reenviar Orden por Email'
                            : 'Enviar Orden por Email')
                        ->modalDescription(function ($record) {
                            $operatorName = $record->operator->name
                                ?? $record->supplierCompany->name
                                ?? $record->supplier->name
                                ?? 'Sin operador asignado';

                            $description = "Orden #{$record->production_number} para {$operatorName}";

                            if ($record->email_sent_at) {
                                $description .= "\n\n⚠️ Esta orden ya fue enviada el {$record->email_sent_at->format('d/m/Y H:i')}";
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
                                    ->body('La orden no tiene items. Agrega items antes de enviar.')
                                    ->send();
                                return;
                            }

                            // VALIDACIÓN 2: Verificar total items
                            if ($record->total_items <= 0) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('No se puede enviar')
                                    ->body('La orden no tiene items válidos.')
                                    ->send();
                                return;
                            }

                            // VALIDACIÓN 3: Verificar email del operador
                            $operatorEmail = $record->operator->email
                                ?? $record->supplierCompany->email
                                ?? $record->supplier->email;

                            if (!$operatorEmail) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('No se puede enviar')
                                    ->body('El operador/proveedor no tiene email configurado.')
                                    ->send();
                                return;
                            }

                            try {
                                // Enviar notificación con PDF
                                \Illuminate\Support\Facades\Notification::route('mail', $operatorEmail)
                                    ->notify(new \App\Notifications\ProductionOrderSent($record->id));

                                // Actualizar registro de envío y cambiar estado a "Enviada"
                                $record->update([
                                    'email_sent_at' => now(),
                                    'email_sent_by' => auth()->id(),
                                    'status' => \App\Enums\ProductionStatus::SENT,
                                ]);

                                \Filament\Notifications\Notification::make()
                                    ->success()
                                    ->title('Email enviado')
                                    ->body("Orden enviada exitosamente a {$operatorEmail}. Estado cambiado a 'Enviada'.")
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
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s'); // Auto-refresh every 30 seconds for live production tracking
    }
}
