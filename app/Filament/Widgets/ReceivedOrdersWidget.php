<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\TenantContext;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class ReceivedOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Órdenes Recibidas';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('company.name')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->description(fn (PurchaseOrder $record) => $record->company->email ?? ''),

                Tables\Columns\TextColumn::make('order_date')
                    ->label('Fecha Orden')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Entrega Esperada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->color(fn (PurchaseOrder $record) => $record->expected_delivery_date < now() ? 'danger' : 'success'),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto Total')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->getLabel())
                    ->color(fn (OrderStatus $state) => $state->getColor()),

                Tables\Columns\TextColumn::make('documentItems')
                    ->label('Items')
                    ->formatStateUsing(fn (PurchaseOrder $record) => $record->documentItems()->count())
                    ->badge()
                    ->color('gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        OrderStatus::SENT->value => OrderStatus::SENT->getLabel(),
                        OrderStatus::CONFIRMED->value => OrderStatus::CONFIRMED->getLabel(),
                        OrderStatus::RECEIVED->value => OrderStatus::RECEIVED->getLabel(),
                    ])
                    ->default(OrderStatus::SENT->value),
            ])
            ->actions([
                Tables\Actions\Action::make('confirm')
                    ->label('Confirmar')
                    ->icon('heroicon-o-check-circle')
                    ->color('warning')
                    ->visible(fn (PurchaseOrder $record) => $record->status === OrderStatus::SENT)
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $record->update(['status' => OrderStatus::CONFIRMED]);
                        $this->dispatch('notify', title: 'Orden confirmada exitosamente', type: 'success');
                    }),

                Tables\Actions\Action::make('mark_received')
                    ->label('Marcar Recibida')
                    ->icon('heroicon-o-archive-box')
                    ->color('success')
                    ->visible(fn (PurchaseOrder $record) => $record->status === OrderStatus::CONFIRMED)
                    ->requiresConfirmation()
                    ->action(function (PurchaseOrder $record) {
                        $record->update([
                            'status' => OrderStatus::RECEIVED,
                            'actual_delivery_date' => now(),
                        ]);
                        $this->dispatch('notify', title: 'Orden marcada como recibida', type: 'success');
                    }),

                Tables\Actions\Action::make('view')
                    ->label('Ver Detalles')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (PurchaseOrder $record) => route('filament.admin.resources.purchase-orders.purchase-orders.view', ['record' => $record])),
            ])
            ->defaultSort('order_date', 'desc')
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5)
            ->poll('30s');
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = TenantContext::id();

        if (!$tenantId) {
            return PurchaseOrder::query()->whereRaw('1 = 0'); // Empty query
        }

        // Papelerías ven órdenes donde son proveedoras
        return PurchaseOrder::query()
            ->where('supplier_company_id', $tenantId)
            ->whereIn('status', [OrderStatus::SENT, OrderStatus::CONFIRMED, OrderStatus::RECEIVED])
            ->with(['company', 'documentItems'])
            ->latest('order_date');
    }

    /**
     * Widget solo visible para papelerías
     */
    public static function canView(): bool
    {
        $tenantId = TenantContext::id();

        if (!$tenantId) {
            return false;
        }

        $company = Company::find($tenantId);

        return $company && $company->company_type === 'papeleria';
    }
}
