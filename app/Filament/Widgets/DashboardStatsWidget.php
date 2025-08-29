<?php

namespace App\Filament\Widgets;

use App\Models\Contact;
use App\Models\Document;
use App\Models\PaperOrder;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class DashboardStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';
    
    protected function getStats(): array
    {
        $company = auth()->user()->company;
        
        return [
            Stat::make('Cotizaciones Activas', $this->getActiveQuotations())
                ->description('Estado: Enviadas y En Revisión')
                ->descriptionIcon('heroicon-o-document-text')
                ->chart($this->getQuotationsTrend())
                ->color('primary'),
                
            Stat::make('Órdenes de Producción', $this->getProductionOrders())
                ->description('En proceso y programadas')
                ->descriptionIcon('heroicon-o-cog-6-tooth')
                ->chart($this->getProductionTrend())
                ->color('warning'),
                
            Stat::make('Ingresos del Mes', $this->getMonthlyRevenue())
                ->description('Documentos facturados')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->chart($this->getRevenueTrend())
                ->color('success'),
                
            Stat::make('Clientes Activos', $this->getActiveClients())
                ->description('Con cotizaciones recientes')
                ->descriptionIcon('heroicon-o-users')
                ->chart($this->getClientsTrend())
                ->color('info'),
                
            Stat::make('Pedidos de Papel', $this->getActivePaperOrders())
                ->description('Pendientes y confirmados')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->chart($this->getPaperOrdersTrend())
                ->color('secondary'),
                
            Stat::make('Stock Crítico', $this->getCriticalStock())
                ->description('Productos bajo mínimo')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),
        ];
    }
    
    private function getActiveQuotations(): int
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->whereHas('documentType', function ($query) {
                $query->where('code', 'QUOTE');
            })
            ->whereIn('status', ['sent', 'approved'])
            ->count();
    }
    
    private function getProductionOrders(): int
    {
        return Document::where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['in_production', 'approved'])
            ->count();
    }
    
    private function getMonthlyRevenue(): string
    {
        $revenue = Document::where('company_id', auth()->user()->company_id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->whereIn('status', ['completed'])
            ->sum('total');
            
        return '$' . number_format($revenue, 0, '.', ',');
    }
    
    private function getActiveClients(): int
    {
        return Contact::where('company_id', auth()->user()->company_id)
            ->where('type', 'customer')
            ->whereHas('documents', function ($query) {
                $query->where('created_at', '>=', now()->subDays(30));
            })
            ->count();
    }
    
    private function getActivePaperOrders(): int
    {
        return PaperOrder::where('company_id', auth()->user()->company_id)
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->count();
    }
    
    private function getCriticalStock(): int
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
    }
    
    private function getQuotationsTrend(): array
    {
        try {
            return Document::where('company_id', auth()->user()->company_id)
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
            return Document::where('company_id', auth()->user()->company_id)
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
            return Document::where('company_id', auth()->user()->company_id)
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
            return Contact::where('company_id', auth()->user()->company_id)
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
            return PaperOrder::where('company_id', auth()->user()->company_id)
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