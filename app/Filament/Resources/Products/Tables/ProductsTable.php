<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Código')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->toggleable(),
                    
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->description ? 
                            $record->name . "\n\n" . $record->description : 
                            $record->name;
                    }),
                    
                TextColumn::make('supplier_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn ($record) => $record->is_own_product ? 'success' : 'info')
                    ->toggleable(),
                    
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->placeholder('Producto propio')
                    ->toggleable(),
                    
                TextColumn::make('purchase_price')
                    ->label('Precio Compra')
                    ->money('COP')
                    ->sortable()
                    ->toggleable(),
                    
                TextColumn::make('sale_price')
                    ->label('Precio Venta')
                    ->money('COP')
                    ->sortable()
                    ->fontFamily('mono')
                    ->color('success'),
                    
                TextColumn::make('profit_margin')
                    ->label('Margen')
                    ->getStateUsing(fn ($record) => number_format($record->getProfitMargin(), 1) . '%')
                    ->badge()
                    ->color(function ($record) {
                        $margin = $record->getProfitMargin();
                        if ($margin >= 50) return 'success';
                        if ($margin >= 25) return 'warning';
                        return 'danger';
                    })
                    ->sortable(query: function ($query, $direction) {
                        return $query->orderByRaw("((sale_price - purchase_price) / NULLIF(purchase_price, 0) * 100) {$direction}");
                    })
                    ->toggleable(),
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->suffix(' uds')
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record->stock_status_color),
                    
                TextColumn::make('stock_status_label')
                    ->label('Estado Stock')
                    ->badge()
                    ->color(fn ($record) => $record->stock_status_color)
                    ->toggleable(),
                    
                IconColumn::make('active')
                    ->label('Activo')
                    ->boolean()
                    ->toggleable(),
                    
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                TextColumn::make('deleted_at')
                    ->label('Eliminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
                
                Tables\Filters\SelectFilter::make('active')
                    ->label('Estado')
                    ->options([
                        1 => 'Activos',
                        0 => 'Inactivos',
                    ]),
                    
                Tables\Filters\SelectFilter::make('is_own_product')
                    ->label('Tipo de Producto')
                    ->options([
                        1 => 'Productos Propios',
                        0 => 'Productos de Terceros',
                    ]),
                    
                Tables\Filters\SelectFilter::make('stock_status')
                    ->label('Estado del Stock')
                    ->options([
                        'sin_stock' => 'Sin Stock',
                        'stock_bajo' => 'Stock Bajo',
                        'stock_normal' => 'Stock Normal',
                    ])
                    ->query(function ($query, $data) {
                        if (!$data['value']) return $query;
                        
                        return match($data['value']) {
                            'sin_stock' => $query->where('stock', 0),
                            'stock_bajo' => $query->whereColumn('stock', '<=', 'min_stock')->where('stock', '>', 0),
                            'stock_normal' => $query->whereColumn('stock', '>', 'min_stock'),
                            default => $query,
                        };
                    }),
                    
                Tables\Filters\Filter::make('low_stock')
                    ->label('Stock Bajo')
                    ->query(fn ($query) => $query->whereColumn('stock', '<=', 'min_stock'))
                    ->toggle(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
                ForceDeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->emptyStateHeading('No hay productos registrados')
            ->emptyStateDescription('Comienza agregando tu primer producto al inventario.')
;
    }
}