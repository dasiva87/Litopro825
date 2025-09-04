<?php

namespace App\Filament\Resources\TalonarioItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TalonarioItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numero_inicial')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numero_final')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('numeros_por_talonario')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('prefijo')
                    ->searchable(),
                TextColumn::make('ancho')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('alto')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sheets_total_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('finishing_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('design_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('transport_value')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('profit_percentage')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('total_cost')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_price')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
