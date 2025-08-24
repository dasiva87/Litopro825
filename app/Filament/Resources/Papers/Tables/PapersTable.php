<?php

namespace App\Filament\Resources\Papers\Tables;

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
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class PapersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                    
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                    
                TextColumn::make('weight')
                    ->label('Gramaje')
                    ->sortable()
                    ->suffix(' gr/m²')
                    ->alignCenter(),
                    
                TextColumn::make('dimensions')
                    ->label('Dimensiones')
                    ->getStateUsing(fn ($record) => "{$record->width} × {$record->height} cm")
                    ->alignCenter(),
                    
                TextColumn::make('area')
                    ->label('Área')
                    ->getStateUsing(fn ($record) => number_format($record->area, 2) . ' cm²')
                    ->alignCenter()
                    ->toggleable(),
                    
                TextColumn::make('cost_per_sheet')
                    ->label('Costo/Pliego')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('price')
                    ->label('Precio/Pliego')
                    ->money('COP')
                    ->sortable(),
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->suffix(' pliegos')
                    ->badge()
                    ->color(fn (int $state): string => match (true) {
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        $state <= 50 => 'primary',
                        default => 'success',
                    }),
                    
                TextColumn::make('is_own')
                    ->label('Propiedad')
                    ->formatStateUsing(fn (bool $state, $record): string => 
                        $state ? 'Propio' : ($record->supplier ? $record->supplier->name : 'Sin proveedor')
                    )
                    ->badge()
                    ->color(fn (bool $state): string => $state ? 'success' : 'secondary'),
                    
                TextColumn::make('margin')
                    ->label('Margen')
                    ->getStateUsing(fn ($record) => $record->margin)
                    ->suffix('%')
                    ->sortable(false)
                    ->toggleable()
                    ->color(fn (float $state): string => match (true) {
                        $state < 10 => 'danger',
                        $state < 25 => 'warning',
                        default => 'success',
                    }),
                    
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_own')
                    ->label('Propiedad')
                    ->placeholder('Todos')
                    ->trueLabel('Propios')
                    ->falseLabel('De Proveedores'),
                    
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->preload()
                    ->searchable(),
                    
                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos'),
                    
                Filter::make('weight_range')
                    ->label('Rango de Gramaje')
                    ->form([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('weight_from')
                                    ->label('Desde')
                                    ->numeric()
                                    ->suffix('gr/m²'),
                                TextInput::make('weight_to')
                                    ->label('Hasta')
                                    ->numeric()
                                    ->suffix('gr/m²'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['weight_from'],
                                fn (Builder $query, $weight): Builder => $query->where('weight', '>=', $weight),
                            )
                            ->when(
                                $data['weight_to'],
                                fn (Builder $query, $weight): Builder => $query->where('weight', '<=', $weight),
                            );
                    }),
                    
                Filter::make('stock_status')
                    ->label('Estado de Stock')
                    ->form([
                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'out' => 'Sin stock',
                                'low' => 'Stock bajo (≤ 10)',
                                'medium' => 'Stock medio (11-50)',
                                'high' => 'Stock alto (> 50)',
                            ])
                            ->placeholder('Seleccionar estado')
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->when($data['status'], function (Builder $query, string $status) {
                            return match ($status) {
                                'out' => $query->where('stock', '<=', 0),
                                'low' => $query->where('stock', '>', 0)->where('stock', '<=', 10),
                                'medium' => $query->where('stock', '>', 10)->where('stock', '<=', 50),
                                'high' => $query->where('stock', '>', 50),
                                default => $query,
                            };
                        });
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
                        
                    BulkAction::make('update_stock')
                        ->label('Actualizar Stock')
                        ->icon('heroicon-o-archive-box')
                        ->form([
                            TextInput::make('stock_adjustment')
                                ->label('Ajuste de Stock')
                                ->numeric()
                                ->required()
                                ->helperText('Ingresa un número positivo para agregar o negativo para reducir'),
                        ])
                        ->action(function ($records, array $data) {
                            $records->each(function ($record) use ($data) {
                                $newStock = max(0, $record->stock + $data['stock_adjustment']);
                                $record->update(['stock' => $newStock]);
                            });
                        }),
                ]),
            ])
            ->defaultSort('code')
            ->recordUrl(null);
    }
}