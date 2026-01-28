<?php

namespace App\Filament\Resources\Projects\RelationManagers;

use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Órdenes de Pedido';

    protected static ?string $recordTitleAttribute = 'order_number';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label('Entrega')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP'),
            ])
            ->actions([
                ViewAction::make()
                    ->url(fn ($record) => route('filament.admin.resources.purchase-orders.view', $record)),
            ])
            ->defaultSort('order_date', 'desc');
    }
}
