<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class PendingOrdersStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $tenantId = TenantContext::id();

        if (!$tenantId) {
            return [];
        }

        // Cache por 5 minutos
        $cacheKey = "pending_orders_stats_{$tenantId}";

        return Cache::remember($cacheKey, 300, function () use ($tenantId) {
            $draftCount = PurchaseOrder::forCurrentTenant()
                ->where('status', OrderStatus::DRAFT)
                ->count();

            $sentCount = PurchaseOrder::forCurrentTenant()
                ->where('status', OrderStatus::SENT)
                ->count();

            $confirmedCount = PurchaseOrder::forCurrentTenant()
                ->where('status', OrderStatus::CONFIRMED)
                ->count();

            $totalPending = PurchaseOrder::forCurrentTenant()
                ->whereIn('status', [OrderStatus::DRAFT, OrderStatus::SENT, OrderStatus::CONFIRMED])
                ->sum('total_amount') ?? 0;

            $overdueCount = PurchaseOrder::forCurrentTenant()
                ->whereIn('status', [OrderStatus::SENT, OrderStatus::CONFIRMED])
                ->where('expected_delivery_date', '<', now())
                ->count();

            // Órdenes próximas a vencer (3 días)
            $upcomingCount = PurchaseOrder::forCurrentTenant()
                ->whereIn('status', [OrderStatus::SENT, OrderStatus::CONFIRMED])
                ->whereBetween('expected_delivery_date', [now(), now()->addDays(3)])
                ->count();

            return [
                Stat::make('Borradores', $draftCount)
                    ->description('Órdenes en borrador')
                    ->descriptionIcon('heroicon-o-document')
                    ->color('gray')
                    ->chart($this->getChartData('draft', $tenantId))
                    ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index', ['tableFilters' => ['status' => ['value' => 'draft']]])),

                Stat::make('Enviadas', $sentCount)
                    ->description($upcomingCount > 0 ? "{$upcomingCount} entregas próximas" : 'Pendientes confirmación')
                    ->descriptionIcon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->chart($this->getChartData('sent', $tenantId))
                    ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index', ['tableFilters' => ['status' => ['value' => 'sent']]])),

                Stat::make('Confirmadas', $confirmedCount)
                    ->description('Pendientes de recepción')
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color('warning')
                    ->chart($this->getChartData('confirmed', $tenantId))
                    ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index', ['tableFilters' => ['status' => ['value' => 'confirmed']]])),

                Stat::make('Valor Pendiente', '$' . number_format($totalPending, 0))
                    ->description('Total en órdenes activas')
                    ->descriptionIcon('heroicon-o-currency-dollar')
                    ->color('success'),

                Stat::make('Retrasadas', $overdueCount)
                    ->description($overdueCount > 0 ? 'Requieren atención urgente' : 'Todo al día')
                    ->descriptionIcon($overdueCount > 0 ? 'heroicon-o-exclamation-triangle' : 'heroicon-o-check-badge')
                    ->color($overdueCount > 0 ? 'danger' : 'success'),
            ];
        });
    }

    /**
     * Obtener datos de gráfico para tendencia (últimos 7 días)
     */
    protected function getChartData(string $status, int $tenantId): array
    {
        $data = [];
        $statusEnum = match ($status) {
            'draft' => OrderStatus::DRAFT,
            'sent' => OrderStatus::SENT,
            'confirmed' => OrderStatus::CONFIRMED,
            default => OrderStatus::DRAFT,
        };

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->startOfDay();
            $count = PurchaseOrder::where('company_id', $tenantId)
                ->where('status', $statusEnum)
                ->whereDate($status === 'draft' ? 'created_at' : 'updated_at', $date)
                ->count();
            $data[] = $count;
        }

        return $data;
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
