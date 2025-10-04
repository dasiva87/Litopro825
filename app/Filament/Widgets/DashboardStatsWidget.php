<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Document;
use App\Models\PaperOrder;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';
    
    protected function getStats(): array
    {
        $company = auth()->user()->company;
        $cacheKey = "dashboard_stats_{$company->id}";

        // Cache stats for 5 minutes
        $stats = Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return [
                'active_quotations' => $this->getActiveQuotations(),
                'production_orders' => $this->getProductionOrders(),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'active_clients' => $this->getActiveClients(),
                'active_paper_orders' => $this->getActivePaperOrders(),
                'critical_stock' => $this->getCriticalStock(),
            ];
        });

        // Cache trends separately (more expensive queries)
        $trendsKey = "dashboard_trends_{$company->id}";
        $trends = Cache::remember($trendsKey, now()->addMinutes(10), function () {
            return [
                'quotations' => $this->getQuotationsTrend(),
                'production' => $this->getProductionTrend(),
                'revenue' => $this->getRevenueTrend(),
                'clients' => $this->getClientsTrend(),
                'paper_orders' => $this->getPaperOrdersTrend(),
            ];
        });

        return [
            Stat::make('Cotizaciones Activas', $stats['active_quotations'])
                ->description('Estado: Enviadas y En Revisión')
                ->descriptionIcon('heroicon-o-document-text')
                ->chart($trends['quotations'])
                ->color('primary'),

            Stat::make('Órdenes de Producción', $stats['production_orders'])
                ->description('En proceso y programadas')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->chart($trends['production'])
                ->color('warning'),

            Stat::make('Ingresos del Mes', $stats['monthly_revenue'])
                ->description('Documentos facturados')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->chart($trends['revenue'])
                ->color('success'),

            Stat::make('Clientes Activos', $stats['active_clients'])
                ->description('Con cotizaciones recientes')
                ->descriptionIcon('heroicon-o-users')
                ->chart($trends['clients'])
                ->color('info'),

            Stat::make('Pedidos de Papel', $stats['active_paper_orders'])
                ->description('Pendientes y confirmados')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->chart($trends['paper_orders'])
                ->color('secondary'),

            Stat::make('Stock Crítico', $stats['critical_stock'])
                ->description('Productos bajo mínimo')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
    
    private function getActiveQuotations(): int
    {
        return Document::forCurrentTenant()
            ->whereHas('documentType', function ($query) {
                $query->where('code', 'QUOTE');
            })
            ->whereIn('status', ['sent', 'approved'])
            ->count();
    }

    private function getProductionOrders(): int
    {
        return Document::forCurrentTenant()
            ->whereIn('status', ['in_production', 'approved'])
            ->count();
    }

    private function getMonthlyRevenue(): string
    {
        $revenue = Document::forCurrentTenant()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['completed'])
            ->sum('total');

        return '$' . number_format($revenue, 0, '.', ',');
    }

    private function getActiveClients(): int
    {
        return Contact::forCurrentTenant()
            ->where('type', 'customer')
            ->whereHas('documents', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
    }

    private function getActivePaperOrders(): int
    {
        return PaperOrder::forCurrentTenant()
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->count();
    }

    private function getCriticalStock(): int
    {
        return Product::forCurrentTenant()
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
    }
    
    private function getQuotationsTrend(): array
    {
        try {
            return Document::forCurrentTenant()
                ->whereHas('documentType', function ($query) {
                    $query->where('code', 'QUOTE');
                })
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0]; // Default 7 days of zeros
        }
    }

    private function getProductionTrend(): array
    {
        try {
            return Document::forCurrentTenant()
                ->whereIn('status', ['in_production', 'approved'])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getRevenueTrend(): array
    {
        try {
            return Document::forCurrentTenant()
                ->whereIn('status', ['completed'])
                ->selectRaw('DATE(created_at) as date, SUM(total) as total')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->pluck('total')
                ->map(fn($value) => (int) $value / 1000)
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getClientsTrend(): array
    {
        try {
            return Contact::forCurrentTenant()
                ->where('type', 'customer')
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }

    private function getPaperOrdersTrend(): array
    {
        try {
            return PaperOrder::forCurrentTenant()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupByRaw('DATE(created_at)')
                ->orderByRaw('DATE(created_at)')
                ->pluck('count')
                ->toArray();
        } catch (\Exception $e) {
            return [0, 0, 0, 0, 0, 0, 0];
        }
    }
}