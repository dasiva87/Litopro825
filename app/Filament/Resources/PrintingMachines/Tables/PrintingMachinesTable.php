<?php

namespace App\Filament\Resources\PrintingMachines\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\BulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class PrintingMachinesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'offset' => 'primary',
                        'digital' => 'success',
                        'serigrafia' => 'warning',
                        'flexografia' => 'danger',
                        'rotativa' => 'secondary',
                        'plotter' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'offset' => 'Offset',
                        'digital' => 'Digital',
                        'serigrafia' => 'Serigrafía',
                        'flexografia' => 'Flexografía',
                        'rotativa' => 'Rotativa',
                        'plotter' => 'Plotter',
                        default => ucfirst($state),
                    }),
                    
                TextColumn::make('max_dimensions')
                    ->label('Dimensiones Máx.')
                    ->getStateUsing(fn ($record) => "{$record->max_width} × {$record->max_height} cm")
                    ->alignCenter(),
                    
                TextColumn::make('max_area')
                    ->label('Área Máx.')
                    ->getStateUsing(fn ($record) => number_format($record->max_area, 2) . ' cm²')
                    ->alignCenter()
                    ->toggleable(),
                    
                TextColumn::make('max_colors')
                    ->label('Colores')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
                    
                TextColumn::make('cost_per_impression')
                    ->label('Costo/Millar')
                    ->money('COP')
                    ->suffix(' /millar')
                    ->sortable(),

                TextColumn::make('costo_ctp')
                    ->label('Costo CTP')
                    ->sortable()
                    ->toggleable()
                    ->formatStateUsing(fn ($state) => $state > 0 ? '$' . number_format($state, 2) . ' /plancha' : 'N/A'),

                TextColumn::make('setup_cost')
                    ->label('Alistamiento')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('is_own')
                    ->label('Propiedad')
                    ->formatStateUsing(fn (bool $state, $record): string => 
                        $state ? 'Propia' : ($record->supplier ? $record->supplier->name : 'Sin proveedor')
                    )
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'secondary'),
                    
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->alignCenter(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options([
                        'offset' => 'Offset',
                        'digital' => 'Digital',
                        'serigrafia' => 'Serigrafía',
                        'flexografia' => 'Flexografía',
                        'rotativa' => 'Rotativa',
                        'plotter' => 'Plotter',
                    ]),
                    
                TernaryFilter::make('is_own')
                    ->label('Propiedad')
                    ->placeholder('Todas')
                    ->trueLabel('Propias')
                    ->falseLabel('Terceros'),
                    
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),
                    
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todas')
                    ->trueLabel('Activas')
                    ->falseLabel('Inactivas'),
                    
                Filter::make('colors_range')
                    ->label('Rango de Colores')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('colors_from')
                                    ->label('Desde')
                                    ->numeric()
                                    ->minValue(1),
                                TextInput::make('colors_to')
                                    ->label('Hasta')
                                    ->numeric()
                                    ->maxValue(8),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['colors_from'],
                                fn (Builder $query, $colors): Builder => $query->where('max_colors', '>=', $colors),
                            )
                            ->when(
                                $data['colors_to'],
                                fn (Builder $query, $colors): Builder => $query->where('max_colors', '<=', $colors),
                            );
                    }),
                    
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    
                    BulkAction::make('toggle_active')
                        ->label('Activar/Desactivar')
                        ->icon('heroicon-o-eye')
                        ->requiresConfirmation()
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                $record->update(['is_active' => !$record->is_active]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('name')
            ->recordUrl(null);
    }
}