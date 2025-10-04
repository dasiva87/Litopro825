<?php

namespace App\Filament\Widgets;
use App\Services\TenantContext;

use App\Enums\OrderStatus;
use App\Models\PurchaseOrder;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Órdenes Recientes';

    public function table(Table $table): Table
    {
        $companyId = TenantContext::id();

        return $table
            ->query(
                PurchaseOrder::where('company_id', $companyId)
                    ->whereIn('status', [OrderStatus::DRAFT, OrderStatus::SENT, OrderStatus::CONFIRMED])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->url(fn ($record) => route('filament.admin.resources.purchase-orders.purchase-orders.view', $record))
                    ->copyable(),

                TextColumn::make('supplierCompany.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date()
                    ->sortable(),

                TextColumn::make('expected_delivery_date')
                    ->label('Entrega Esperada')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) => $record->expected_delivery_date < now() ? 'danger' : null),

                TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('documentItems')
                    ->badge()
                    ->color('info'),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->url(fn ($record) => route('filament.admin.resources.purchase-orders.purchase-orders.view', $record)),
            ]);
    }
}
