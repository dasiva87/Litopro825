<?php

namespace App\Filament\Widgets;

use App\Models\Document;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ActiveDocumentsWidget extends BaseWidget
{
    protected static ?string $heading = '📋 Documentos Activos';
    
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';
    
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Document::query()
                    ->where('company_id', auth()->user()->company_id)
                    ->whereNotIn('status', ['completed', 'cancelled'])
                    ->with(['contact', 'documentType', 'user'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('document_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                    
                Tables\Columns\TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Cotización' => 'info',
                        'Orden de Trabajo' => 'warning',
                        'Factura' => 'success',
                        'Remisión' => 'secondary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('contact.name')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'approved' => 'info',
                        'in_production' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'in_production' => 'En Producción',
                        'completed' => 'Completado',
                        'cancelled' => 'Cancelado',
                        default => ucfirst($state),
                    }),
                    
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money('COP')
                    ->sortable()
                    ->alignment('right'),
                    
                Tables\Columns\TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Responsable')
                    ->limit(20),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'sent' => 'Enviado',
                        'approved' => 'Aprobado',
                        'in_production' => 'En Producción',
                    ])
                    ->multiple(),
                    
                Tables\Filters\SelectFilter::make('document_type_id')
                    ->label('Tipo de Documento')
                    ->relationship('documentType', 'name')
                    ->multiple(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn (Document $record): string => route('filament.admin.resources.documents.view', $record)),
                    
                EditAction::make()
                    ->url(fn (Document $record): string => route('filament.admin.resources.documents.edit', $record)),
                    
                Action::make('pdf')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (Document $record): string => route('documents.pdf', $record))
                    ->openUrlInNewTab(true),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('mark_as_sent')
                        ->label('Marcar como Enviado')
                        ->icon('heroicon-o-paper-airplane')
                        ->color('warning')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'sent']);
                            });
                        })
                        ->requiresConfirmation(),
                        
                    BulkAction::make('mark_as_approved')
                        ->label('Marcar como Aprobado')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['status' => 'approved']);
                            });
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(10);
    }
}