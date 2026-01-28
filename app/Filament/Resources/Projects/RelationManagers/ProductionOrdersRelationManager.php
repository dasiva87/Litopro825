<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductionOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'productionOrders';

    protected static ?string $title = 'Órdenes de Producción';

    protected static ?string $recordTitleAttribute = 'production_number';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('production_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('scheduled_date')
                    ->label('Programada')
                    ->date()
                    ->sortable(),

                TextColumn::make('started_at')
                    ->label('Iniciada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('completed_at')
                    ->label('Completada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.production-orders.view', $record)),
            ])
            ->defaultSort('scheduled_date', 'desc');
    }
}
