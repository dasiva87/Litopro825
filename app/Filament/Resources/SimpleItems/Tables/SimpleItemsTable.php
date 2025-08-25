<?php

namespace App\Filament\Resources\SimpleItems\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SimpleItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->description),
                    
                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->numeric()
                    ->suffix(' uds')
                    ->sortable(),
                    
                TextColumn::make('dimensions')
                    ->label('Dimensiones')
                    ->getStateUsing(fn ($record) => 
                        $record->horizontal_size . ' × ' . $record->vertical_size . ' cm'
                    )
                    ->alignCenter(),
                    
                TextColumn::make('paper.code')
                    ->label('Papel')
                    ->searchable(),
                    
                TextColumn::make('printingMachine.name')
                    ->label('Máquina')
                    ->searchable()
                    ->toggleable(),
                    
                TextColumn::make('inks_display')
                    ->label('Tintas')
                    ->getStateUsing(fn ($record) => 
                        $record->ink_front_count . '×' . $record->ink_back_count . 
                        ($record->front_back_plate ? ' (T&R)' : '')
                    )
                    ->alignCenter(),
                    
                TextColumn::make('mounting_quantity')
                    ->label('Montaje')
                    ->numeric()
                    ->suffix(' pliegos')
                    ->sortable(),
                    
                TextColumn::make('cuts_display')
                    ->label('Cortes')
                    ->getStateUsing(fn ($record) => 
                        $record->paper_cuts_h . ' × ' . $record->paper_cuts_v . 
                        ' = ' . ($record->paper_cuts_h * $record->paper_cuts_v)
                    )
                    ->alignCenter()
                    ->toggleable(),
                    
                TextColumn::make('total_cost')
                    ->label('Costo')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('final_price')
                    ->label('Precio Final')
                    ->money('COP')
                    ->sortable()
                    ->weight('bold'),
                    
                TextColumn::make('unit_price')
                    ->label('Precio Unitario')
                    ->getStateUsing(fn ($record) => 
                        $record->quantity > 0 ? $record->final_price / $record->quantity : 0
                    )
                    ->money('COP')
                    ->sortable()
                    ->toggleable(),
                    
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
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
