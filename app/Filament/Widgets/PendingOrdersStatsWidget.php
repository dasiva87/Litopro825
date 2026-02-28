<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\PurchaseOrder;
use App\Services\TenantContext;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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
            // Query consolidada: obtiene todos los conteos en una sola consulta
            $stats = PurchaseOrder::where('company_id', $tenantId)
                ->selectRaw("
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as draft_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent_count,
                    SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as confirmed_count,
                    SUM(CASE WHEN status IN (?, ?, ?) THEN total_amount ELSE 0 END) as total_pending,
                    SUM(CASE WHEN status IN (?, ?) AND expected_delivery_date < ? THEN 1 ELSE 0 END) as overdue_count,
                    SUM(CASE WHEN status IN (?, ?) AND expected_delivery_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as upcoming_count
                ", [
                    OrderStatus::DRAFT->value,
                    OrderStatus::SENT->value,
                    OrderStatus::CONFIRMED->value,
                    OrderStatus::DRAFT->value, OrderStatus::SENT->value, OrderStatus::CONFIRMED->value,
                    OrderStatus::SENT->value, OrderStatus::CONFIRMED->value, now(),
                    OrderStatus::SENT->value, OrderStatus::CONFIRMED->value, now(), now()->addDays(3),
                ])
                ->first();

            $draftCount = (int) ($stats->draft_count ?? 0);
            $sentCount = (int) ($stats->sent_count ?? 0);
            $confirmedCount = (int) ($stats->confirmed_count ?? 0);
            $totalPending = (float) ($stats->total_pending ?? 0);
            $overdueCount = (int) ($stats->overdue_count ?? 0);
            $upcomingCount = (int) ($stats->upcoming_count ?? 0);

            // Obtener datos de gráficos en una sola query consolidada
            $chartData = $this->getChartDataBatch($tenantId);

            return [
                Stat::make('Borradores', $draftCount)
                    ->description('Órdenes en borrador')
                    ->descriptionIcon('heroicon-o-document')
                    ->color('gray')
                    ->chart($chartData['draft'] ?? array_fill(0, 7, 0))
                    ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index', ['tableFilters' => ['status' => ['value' => 'draft']]])),

                Stat::make('Enviadas', $sentCount)
                    ->description($upcomingCount > 0 ? "{$upcomingCount} entregas próximas" : 'Pendientes confirmación')
                    ->descriptionIcon('heroicon-o-paper-airplane')
                    ->color('info')
                    ->chart($chartData['sent'] ?? array_fill(0, 7, 0))
                    ->url(route('filament.admin.resources.purchase-orders.purchase-orders.index', ['tableFilters' => ['status' => ['value' => 'sent']]])),

                Stat::make('Confirmadas', $confirmedCount)
                    ->description('Pendientes de recepción')
                    ->descriptionIcon('heroicon-o-check-circle')
                    ->color('warning')
                    ->chart($chartData['confirmed'] ?? array_fill(0, 7, 0))
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
     * Obtener datos de gráficos para todos los estados en una sola query
     * Optimizado: 1 query en lugar de 21
     */
    protected function getChartDataBatch(int $tenantId): array
    {
        $startDate = now()->subDays(6)->startOfDay();
        $endDate = now()->endOfDay();

        // Query consolidada para obtener conteos por fecha y estado
        $results = PurchaseOrder::where('company_id', $tenantId)
            ->whereIn('status', [OrderStatus::DRAFT, OrderStatus::SENT, OrderStatus::CONFIRMED])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->get();

        // Inicializar arrays con 0s para los últimos 7 días
        $chartData = [
            'draft' => array_fill(0, 7, 0),
            'sent' => array_fill(0, 7, 0),
            'confirmed' => array_fill(0, 7, 0),
        ];

        // Mapear resultados a los arrays
        foreach ($results as $result) {
            $dayIndex = now()->startOfDay()->diffInDays($result->date);
            $dayIndex = 6 - $dayIndex; // Invertir para que el más reciente sea el último

            if ($dayIndex >= 0 && $dayIndex < 7) {
                $statusKey = match ($result->status) {
                    OrderStatus::DRAFT => 'draft',
                    OrderStatus::SENT => 'sent',
                    OrderStatus::CONFIRMED => 'confirmed',
                    default => null,
                };

                if ($statusKey) {
                    $chartData[$statusKey][$dayIndex] = (int) $result->count;
                }
            }
        }

        return $chartData;
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
