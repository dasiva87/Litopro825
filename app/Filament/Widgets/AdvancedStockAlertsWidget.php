<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockAlert;
use App\Services\StockAlertService;
use App\Services\StockPredictionService;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class AdvancedStockAlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.advanced-stock-alerts-simple';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = [
        'md' => 2,
        'xl' => 2,
    ];

    protected static ?string $heading = 'Alertas Avanzadas de Stock';

    public function getHeading(): string
    {
        return static::$heading ?? 'Alertas Avanzadas de Stock';
    }

    protected StockAlertService $alertService;
    protected StockPredictionService $predictionService;

    public function __construct()
    {
        $this->alertService = app(StockAlertService::class);
        $this->predictionService = app(StockPredictionService::class);
    }

    /**
     * Obtener resumen de alertas activas
     */
    public function getActiveAlerts(): array
    {
        return $this->alertService->getAlertsSummary();
    }

    /**
     * Obtener alertas críticas recientes
     */
    public function getCriticalAlerts(): array
    {
        return StockAlert::where('company_id', auth()->user()->company_id)
            ->critical()
            ->active()
            ->with(['stockable'])
            ->orderBy('triggered_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'item_name' => $alert->stockable->name,
                    'item_type' => $alert->stockable_type === Product::class ? 'Producto' : 'Papel',
                    'type_label' => $alert->type_label,
                    'severity_label' => $alert->severity_label,
                    'severity_color' => $alert->severity_color,
                    'message' => $alert->message,
                    'current_stock' => $alert->current_stock,
                    'min_stock' => $alert->min_stock,
                    'age_hours' => $alert->age_hours,
                    'triggered_at' => $alert->triggered_at->format('d/m/Y H:i'),
                ];
            })
            ->toArray();
    }

    /**
     * Obtener predicciones de agotamiento
     */
    public function getDepletionPredictions(): array
    {
        $reorderAlerts = $this->predictionService->getReorderAlerts(
            auth()->user()->company_id,
            14 // 14 días de umbral
        );

        return [
            'urgent' => array_slice($reorderAlerts['urgent'], 0, 3),
            'critical' => array_slice($reorderAlerts['critical'], 0, 3),
            'summary' => $reorderAlerts['summary'],
        ];
    }

    /**
     * Obtener estadísticas de stock por tipo
     */
    public function getStockStatsByType(): array
    {
        $companyId = auth()->user()->company_id;

        // Productos
        $products = Product::where('company_id', $companyId)->where('active', true);
        $productStats = [
            'total' => $products->count(),
            'in_stock' => $products->clone()->inStock()->count(),
            'low_stock' => $products->clone()->lowStock()->count(),
            'out_of_stock' => $products->clone()->outOfStock()->count(),
        ];

        // Papeles
        $papers = Paper::where('company_id', $companyId)->where('is_active', true);
        $paperStats = [
            'total' => $papers->count(),
            'in_stock' => $papers->clone()->inStock()->count(),
            'low_stock' => $papers->clone()->lowStock()->count(),
            'out_of_stock' => $papers->clone()->outOfStock()->count(),
        ];

        return [
            'products' => $productStats,
            'papers' => $paperStats,
            'combined' => [
                'total' => $productStats['total'] + $paperStats['total'],
                'in_stock' => $productStats['in_stock'] + $paperStats['in_stock'],
                'low_stock' => $productStats['low_stock'] + $paperStats['low_stock'],
                'out_of_stock' => $productStats['out_of_stock'] + $paperStats['out_of_stock'],
            ],
        ];
    }

    /**
     * Obtener top items con más movimiento
     */
    public function getTopMovingItems(): array
    {
        $companyId = auth()->user()->company_id;
        $last30Days = now()->subDays(30);

        // Obtener items con más movimientos de salida en los últimos 30 días
        $topMovers = \App\Models\StockMovement::where('company_id', $companyId)
            ->where('type', 'out')
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('stockable_type, stockable_id, sum(quantity) as total_out, count(*) as movement_count')
            ->groupBy('stockable_type', 'stockable_id')
            ->orderByDesc('total_out')
            ->limit(5)
            ->get();

        return $topMovers->map(function ($movement) {
            $stockable = $movement->stockable_type::find($movement->stockable_id);

            if (!$stockable) {
                return null;
            }

            return [
                'name' => $stockable->name,
                'type' => $movement->stockable_type === Product::class ? 'Producto' : 'Papel',
                'total_out' => $movement->total_out,
                'movement_count' => $movement->movement_count,
                'current_stock' => $stockable->stock,
                'stock_status' => $stockable->stock_status,
                'stock_status_color' => $stockable->stock_status_color,
            ];
        })->filter()->values()->toArray();
    }

    /**
     * Obtener métricas de valor de inventario
     */
    public function getInventoryValueMetrics(): array
    {
        $companyId = auth()->user()->company_id;

        // Valor total de productos
        $productsValue = Product::where('company_id', $companyId)
            ->where('active', true)
            ->selectRaw('sum(stock * purchase_price) as total_value, sum(case when stock <= min_stock then stock * purchase_price else 0 end) as low_stock_value')
            ->first();

        // Valor total de papeles
        $papersValue = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->selectRaw('sum(stock * cost_per_sheet) as total_value, sum(case when stock <= min_stock then stock * cost_per_sheet else 0 end) as low_stock_value')
            ->first();

        $totalValue = ($productsValue->total_value ?? 0) + ($papersValue->total_value ?? 0);
        $lowStockValue = ($productsValue->low_stock_value ?? 0) + ($papersValue->low_stock_value ?? 0);

        return [
            'total_inventory_value' => $totalValue,
            'low_stock_value' => $lowStockValue,
            'risk_percentage' => $totalValue > 0 ? round(($lowStockValue / $totalValue) * 100, 1) : 0,
            'products_value' => $productsValue->total_value ?? 0,
            'papers_value' => $papersValue->total_value ?? 0,
        ];
    }

    /**
     * Obtener alertas por gravedad para el gráfico
     */
    public function getAlertsBySeverity(): array
    {
        $companyId = auth()->user()->company_id;

        $alertCounts = StockAlert::where('company_id', $companyId)
            ->active()
            ->selectRaw('severity, count(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return [
            'critical' => $alertCounts['critical'] ?? 0,
            'high' => $alertCounts['high'] ?? 0,
            'medium' => $alertCounts['medium'] ?? 0,
            'low' => $alertCounts['low'] ?? 0,
        ];
    }

    /**
     * Acciones rápidas para el widget
     */
    public function acknowledgeAlert(int $alertId): void
    {
        $alert = StockAlert::find($alertId);
        if ($alert && $alert->company_id === auth()->user()->company_id) {
            $alert->acknowledge();
        }
    }

    public function resolveAlert(int $alertId): void
    {
        $alert = StockAlert::find($alertId);
        if ($alert && $alert->company_id === auth()->user()->company_id) {
            $alert->resolve();
        }
    }

    public function refreshAlerts(): void
    {
        $this->alertService->evaluateAllAlerts(auth()->user()->company_id);
    }

    /**
     * Obtener datos para la vista
     */
    public function getViewData(): array
    {
        return [
            'activeAlerts' => $this->getActiveAlerts(),
            'criticalAlerts' => $this->getCriticalAlerts(),
            'depletionPredictions' => $this->getDepletionPredictions(),
            'stockStats' => $this->getStockStatsByType(),
            'topMovingItems' => $this->getTopMovingItems(),
            'inventoryValue' => $this->getInventoryValueMetrics(),
            'alertsBySeverity' => $this->getAlertsBySeverity(),
        ];
    }

    /**
     * Polling para actualizaciones en tiempo real
     */
    public function getPollingInterval(): ?string
    {
        return '120s'; // Actualizar cada 2 minutos para mejor performance
    }

    /**
     * Cache key for widget data
     */
    protected function getCacheKey(): string
    {
        return 'advanced_stock_alerts_' . auth()->user()->company_id;
    }

    /**
     * Cache duration in seconds
     */
    protected function getCacheDuration(): int
    {
        return 60; // Cache por 1 minuto
    }
}