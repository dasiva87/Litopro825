<?php

namespace App\Filament\Resources\ProductionOrders\Tables;

use App\Enums\ProductionStatus;
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
                    ->copyable()
                    ->icon('heroicon-o-hashtag'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info')
                    ->icon('heroicon-o-building-office')
                    ->description(fn ($record) => $record->supplier?->tax_id ?? '')
                    ->placeholder('Sin asignar'),

                TextColumn::make('operator.name')
                    ->label('Operador')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('success')
                    ->icon('heroicon-o-user')
                    ->placeholder('Sin asignar'),

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

                TextColumn::make('total_impressions')
                    ->label('Millares')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->badge()
                    ->color('warning')
                    ->suffix(' M'),

                TextColumn::make('estimated_hours')
                    ->label('Horas Est.')
                    ->numeric(decimalPlaces: 1)
                    ->sortable()
                    ->suffix(' h')
                    ->toggleable(),

                TextColumn::make('progress')
                    ->label('Progreso')
                    ->state(fn ($record) => $record->getProgressPercentage())
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state == 0 => 'gray',
                        $state < 50 => 'warning',
                        $state < 100 => 'info',
                        default => 'success',
                    })
                    ->suffix('%')
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
                ViewAction::make()
                    ->label('Ver'),
                EditAction::make()
                    ->label('Editar'),
                DeleteAction::make()
                    ->label('Eliminar'),
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
