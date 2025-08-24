<?php

namespace App\Filament\Resources\Documents\Tables;

use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Textarea;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('document_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->sortable(),
                    
                TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(),
                    
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'secondary',
                        'sent' => 'primary',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'in_production' => 'warning',
                        'completed' => 'success',
                        'cancelled' => 'gray',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'in_production' => 'En Producción',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                    
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                    
                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('valid_until')
                    ->label('Válida Hasta')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->isExpired() ? 'danger' : null),
                    
                TextColumn::make('version')
                    ->label('Versión')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        'in_production' => 'En Producción',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                    ])
                    ->multiple(),
                    
                SelectFilter::make('document_type_id')
                    ->label('Tipo')
                    ->relationship('documentType', 'name'),
                    
                Filter::make('expired')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => $query->where('valid_until', '<', now()))
                    ->toggle(),
                    
                Filter::make('expiring_soon')
                    ->label('Por Vencer (7 días)')
                    ->query(fn (Builder $query): Builder => $query->expiringSoon())
                    ->toggle(),
                    
                TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->visible(fn ($record) => $record->canEdit()),
                    
                    Action::make('send')
                        ->label('Enviar')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->visible(fn ($record) => $record->canSend())
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->markAsSent()),
                        
                    Action::make('approve')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn ($record) => $record->canApprove())
                        ->requiresConfirmation()
                        ->action(fn ($record) => $record->markAsApproved()),
                        
                    Action::make('reject')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn ($record) => $record->canApprove())
                        ->form([
                            Textarea::make('reason')
                                ->label('Motivo de Rechazo')
                                ->required(),
                        ])
                        ->action(function ($record, array $data) {
                            $record->markAsRejected($data['reason']);
                        }),
                        
                    Action::make('new_version')
                        ->label('Nueva Versión')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $newDocument = $record->createNewVersion();
                            return redirect()->route('filament.admin.resources.documents.edit', $newDocument);
                        }),
                        
                    DeleteAction::make()
                        ->visible(fn ($record) => $record->isDraft()),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    
                    BulkAction::make('mark_as_sent')
                        ->label('Marcar como Enviado')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('primary')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(fn ($record) => $record->canSend() && $record->markAsSent());
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}