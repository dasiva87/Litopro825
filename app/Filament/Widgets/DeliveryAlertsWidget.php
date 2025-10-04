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

class DeliveryAlertsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Alertas de Entrega';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\IconColumn::make('urgency')
                    ->label('Urgencia')
                    ->icon(fn (PurchaseOrder $record) => $this->getUrgencyIcon($record))
                    ->color(fn (PurchaseOrder $record) => $this->getUrgencyColor($record))
                    ->tooltip(fn (PurchaseOrder $record) => $this->getUrgencyTooltip($record)),

                Tables\Columns\TextColumn::make('order_number')
                    ->label('Número')
                    ->searchable()
                    ->copyable()
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('supplierCompany.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('expected_delivery_date')
                    ->label('Fecha Esperada')
                    ->date('d/m/Y')
                    ->sortable()
                    ->description(fn (PurchaseOrder $record) => $this->getDaysRemaining($record)),

                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Monto')
                    ->money('COP'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (OrderStatus $state) => $state->getLabel())
                    ->color(fn (OrderStatus $state) => $state->getColor()),

                Tables\Columns\TextColumn::make('days_diff')
                    ->label('Días')
                    ->state(function (PurchaseOrder $record) {
                        $days = now()->diffInDays($record->expected_delivery_date, false);
                        if ($days < 0) {
                            return abs($days) . ' días atrasada';
                        } elseif ($days == 0) {
                            return 'Hoy';
                        } elseif ($days == 1) {
                            return 'Mañana';
                        } else {
                            return $days . ' días';
                        }
                    })
                    ->badge()
                    ->color(function (PurchaseOrder $record) {
                        $days = now()->diffInDays($record->expected_delivery_date, false);
                        if ($days < 0) return 'danger';
                        if ($days <= 1) return 'warning';
                        if ($days <= 3) return 'info';
                        return 'success';
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('alert_level')
                    ->label('Nivel de Alerta')
                    ->options([
                        'overdue' => '🔴 Atrasadas',
                        'today' => '🟠 Hoy',
                        'tomorrow' => '🟡 Mañana',
                        'soon' => '🟢 Próximos 3 días',
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (!isset($data['value'])) {
                            return $query;
                        }

                        return match ($data['value']) {
                            'overdue' => $query->where('expected_delivery_date', '<', now()->startOfDay()),
                            'today' => $query->whereDate('expected_delivery_date', now()),
                            'tomorrow' => $query->whereDate('expected_delivery_date', now()->addDay()),
                            'soon' => $query->whereBetween('expected_delivery_date', [now()->addDays(2), now()->addDays(3)]),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn (PurchaseOrder $record) => route('filament.admin.resources.purchase-orders.purchase-orders.view', ['record' => $record])),
            ])
            ->defaultSort('expected_delivery_date', 'asc')
            ->paginated([10, 25, 50])
            ->defaultPaginationPageOption(10)
            ->poll('60s');
    }

    protected function getTableQuery(): Builder
    {
        $tenantId = TenantContext::id();

        if (!$tenantId) {
            return PurchaseOrder::query()->whereRaw('1 = 0');
        }

        // Órdenes activas con entrega en próximos 7 días o atrasadas
        return PurchaseOrder::query()
            ->where('company_id', $tenantId)
            ->whereIn('status', [OrderStatus::SENT, OrderStatus::CONFIRMED])
            ->where(function (Builder $query) {
                $query->where('expected_delivery_date', '<=', now()->addDays(7))
                    ->orWhere('expected_delivery_date', '<', now());
            })
            ->with(['supplierCompany'])
            ->orderByRaw('
                CASE
                    WHEN expected_delivery_date < ? THEN 1
                    WHEN expected_delivery_date = ? THEN 2
                    WHEN expected_delivery_date = ? THEN 3
                    ELSE 4
                END
            ', [
                now()->startOfDay(),
                now()->startOfDay(),
                now()->addDay()->startOfDay(),
            ])
            ->orderBy('expected_delivery_date', 'asc');
    }

    protected function getUrgencyIcon(PurchaseOrder $record): string
    {
        $days = now()->diffInDays($record->expected_delivery_date, false);

        if ($days < 0) {
            return 'heroicon-o-exclamation-circle';
        } elseif ($days <= 1) {
            return 'heroicon-o-exclamation-triangle';
        } elseif ($days <= 3) {
            return 'heroicon-o-clock';
        }

        return 'heroicon-o-check-circle';
    }

    protected function getUrgencyColor(PurchaseOrder $record): string
    {
        $days = now()->diffInDays($record->expected_delivery_date, false);

        if ($days < 0) return 'danger';
        if ($days <= 1) return 'warning';
        if ($days <= 3) return 'info';
        return 'success';
    }

    protected function getUrgencyTooltip(PurchaseOrder $record): string
    {
        $days = now()->diffInDays($record->expected_delivery_date, false);

        if ($days < 0) {
            return 'Atrasada ' . abs($days) . ' día(s)';
        } elseif ($days == 0) {
            return 'Entrega hoy';
        } elseif ($days == 1) {
            return 'Entrega mañana';
        }

        return 'Entrega en ' . $days . ' días';
    }

    protected function getDaysRemaining(PurchaseOrder $record): string
    {
        $days = now()->diffInDays($record->expected_delivery_date, false);

        if ($days < 0) {
            return '⚠️ Atrasada ' . abs($days) . ' día(s)';
        } elseif ($days == 0) {
            return '🔥 Entrega hoy';
        } elseif ($days == 1) {
            return '⏰ Entrega mañana';
        }

        return '📅 En ' . $days . ' días';
    }

    /**
     * Widget solo visible para litografías
     */
    public static function canView(): bool
    {
        $tenantId = TenantContext::id();

        if (!$tenantId) {
            return false;
        }

        $company = Company::find($tenantId);

        return $company && $company->company_type === 'litografia';
    }
}
