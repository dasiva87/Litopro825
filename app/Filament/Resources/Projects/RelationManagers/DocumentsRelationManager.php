<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Cotizaciones';

    protected static ?string $recordTitleAttribute = 'document_number';

    public function table(Table $table): Table
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
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'sent' => 'info',
                        'approved' => 'success',
                        'rejected' => 'danger',
                        'in_production' => 'warning',
                        'completed' => 'success',
                        default => 'secondary',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        'in_production' => 'En Producción',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state,
                    }),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('COP'),
            ])
            ->headerActions([
                Action::make('create_document')
                    ->label('Nueva Cotización')
                    ->icon('heroicon-o-plus')
                    ->url(fn () => route('filament.admin.resources.documents.create', [
                        'project_id' => $this->ownerRecord->id,
                    ])),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.documents.view', $record)),
            ])
            ->defaultSort('date', 'desc');
    }
}
