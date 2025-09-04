<?php

namespace App\Filament\Resources\MagazineItems\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\ViewAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class MagazineItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 50) {
                            return null;
                        }
                        return $state;
                    }),

                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->sortable()
                    ->alignCenter()
                    ->formatStateUsing(fn (string $state): string => number_format($state) . ' uds'),

                TextColumn::make('closed_dimensions')
                    ->label('Dimensiones')
                    ->alignCenter()
                    ->getStateUsing(function ($record): string {
                        return $record->closed_width . ' × ' . $record->closed_height . ' cm';
                    }),

                TextColumn::make('binding_type')
                    ->label('Encuadernación')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->colors([
                        'primary' => 'grapado',
                        'success' => 'cosido',
                        'warning' => 'anillado',
                        'danger' => 'hotmelt',
                        'secondary' => fn ($state): bool => !in_array($state, ['grapado', 'cosido', 'anillado', 'hotmelt']),
                    ]),

                TextColumn::make('total_pages')
                    ->label('Total Páginas')
                    ->alignCenter()
                    ->getStateUsing(function ($record): string {
                        return $record->total_pages . ' págs';
                    })
                    ->sortable(),

                TextColumn::make('pages_count')
                    ->label('Tipos de Página')
                    ->alignCenter()
                    ->counts('pages')
                    ->formatStateUsing(fn (string $state): string => $state . ' tipos'),

                TextColumn::make('final_price')
                    ->label('Precio Total')
                    ->sortable()
                    ->alignEnd()
                    ->money('COP'),

                TextColumn::make('unit_price')
                    ->label('Precio Unitario')
                    ->alignEnd()
                    ->getStateUsing(function ($record): float {
                        if (!$record->quantity || $record->quantity == 0) return 0;
                        return $record->final_price / $record->quantity;
                    })
                    ->money('COP'),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
                
                SelectFilter::make('binding_type')
                    ->label('Tipo de Encuadernación')
                    ->options([
                        'grapado' => 'Grapado',
                        'plegado' => 'Plegado',
                        'anillado' => 'Anillado',
                        'cosido' => 'Cosido',
                        'caballete' => 'Caballete',
                        'lomo' => 'Lomo',
                        'espiral' => 'Espiral',
                        'wire_o' => 'Wire-O',
                        'hotmelt' => 'Hot Melt',
                    ]),

                SelectFilter::make('binding_side')
                    ->label('Lado de Encuadernación')
                    ->options([
                        'izquierda' => 'Izquierda',
                        'derecha' => 'Derecha',
                        'arriba' => 'Arriba',
                        'abajo' => 'Abajo',
                    ]),
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
            ])
            ->defaultSort('created_at', 'desc');
    }
}