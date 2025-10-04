<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Actions;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;

class PurchaseOrderNotificationsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 1;

    protected static ?string $heading = '🔔 Órdenes de Pedido - Atención Requerida';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                PurchaseOrder::query()
                    ->forCurrentTenant()
                    ->where(function ($query) {
                        $query->where('status', 'sent')
                              ->where('created_at', '<', now()->subDays(3))
                              ->orWhere(function ($q) {
                                  $q->where('status', 'confirmed')
                                    ->where('expected_delivery_date', '<', now()->addDays(2));
                              });
                    })
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Orden')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('supplierCompany.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Borrador',
                        'sent' => 'Enviada',
                        'confirmed' => 'Confirmada',
                        'partially_received' => 'Parcialmente Recibida',
                        'completed' => 'Completada',
                        'cancelled' => 'Cancelada',
                        default => $state
                    })
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'sent' => 'warning',
                        'confirmed' => 'info',
                        'partially_received' => 'primary',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray'
                    }),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('COP')
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Creada')
                    ->since()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Entrega Esperada')
                    ->date()
                    ->sortable()
                    ->color(fn ($record) =>
                        $record->expected_delivery_date && $record->expected_delivery_date->isPast()
                            ? 'danger'
                            : ($record->expected_delivery_date && $record->expected_delivery_date->diffInDays() <= 2
                                ? 'warning'
                                : 'gray')
                    ),
            ])
            ->actions([
                Actions\ViewAction::make()
                    ->url(fn ($record) => route('purchase-orders.pdf', $record->id))
                    ->openUrlInNewTab(),
            ])
            ->emptyStateHeading('🎉 Todas las órdenes están al día')
            ->emptyStateDescription('No hay órdenes que requieran atención inmediata.')
            ->emptyStateIcon('heroicon-o-check-circle');
    }

    public static function canView(): bool
    {
        return Auth::check() && Auth::user()->company_id !== null;
    }
}