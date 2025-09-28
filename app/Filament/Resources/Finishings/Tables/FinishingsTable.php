<?php

namespace App\Filament\Resources\Finishings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class FinishingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('measurement_unit')
                    ->label('Unidad de Medida')
                    ->formatStateUsing(fn($state) => $state->label())
                    ->badge()
                    ->color(fn($state) => match($state->value) {
                        'millar' => 'info',
                        'rango' => 'warning', 
                        'unidad' => 'success',
                        'tamaño' => 'primary',
                        'por_numero' => 'indigo',
                        'por_talonario' => 'purple',
                    }),
                TextColumn::make('unit_price')
                    ->label('Precio')
                    ->money('COP')
                    ->sortable(),
                TextColumn::make('provider_name')
                    ->label('Proveedor')
                    ->badge()
                    ->color(fn($state) => $state === 'Propio' ? 'success' : 'gray'),
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean(),
                TextColumn::make('ranges_count')
                    ->label('Rangos')
                    ->counts('ranges')
                    ->visible(fn($record) => $record?->measurement_unit === \App\Enums\FinishingMeasurementUnit::RANGO),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
