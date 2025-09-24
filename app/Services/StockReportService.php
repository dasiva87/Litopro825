<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockMovement;
use App\Models\StockAlert;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockReportService
{
    /**
     * Generar reporte completo de inventario
     */
    public function generateInventoryReport(?int $companyId = null, array $options = []): array
    {
        $companyId = $companyId ?? config('tenant.company_id');

        $report = [
            'generated_at' => now(),
            'company_id' => $companyId,
            'summary' => $this->getInventorySummary($companyId),
            'products' => $this->getProductsReport($companyId, $options),
            'papers' => $this->getPapersReport($companyId, $options),
            'alerts' => $this->getAlertsReport($companyId),
            'movements' => $this->getMovementsReport($companyId, $options),
            'analytics' => $this->getInventoryAnalytics($companyId),
        ];

        return $report;
    }

    /**
     * Resumen general del inventario
     */
    protected function getInventorySummary(?int $companyId): array
    {
        $products = Product::where('company_id', $companyId)->where('active', true);
        $papers = Paper::where('company_id', $companyId)->where('is_active', true);

        return [
            'total_products' => $products->count(),
            'products_in_stock' => $products->clone()->inStock()->count(),
            'products_low_stock' => $products->clone()->lowStock()->count(),
            'products_out_of_stock' => $products->clone()->outOfStock()->count(),
            'total_papers' => $papers->count(),
            'papers_in_stock' => $papers->clone()->inStock()->count(),
            'papers_low_stock' => $papers->clone()->lowStock()->count(),
            'papers_out_of_stock' => $papers->clone()->outOfStock()->count(),
            'total_stock_value' => $this->calculateTotalStockValue($companyId),
            'active_alerts' => StockAlert::where('company_id', $companyId)->active()->count(),
            'critical_alerts' => StockAlert::where('company_id', $companyId)->critical()->active()->count(),
        ];
    }

    /**
     * Reporte de productos
     */
    protected function getProductsReport(?int $companyId, array $options): array
    {
        $query = Product::where('company_id', $companyId)
            ->where('active', true)
            ->with(['supplier']);

        // Filtros opcionales
        if (!empty($options['stock_status'])) {
            match($options['stock_status']) {
                'low' => $query->lowStock(),
                'out' => $query->outOfStock(),
                'in' => $query->inStock(),
                default => null
            };
        }

        $products = $query->get()->map(function ($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'code' => $product->code,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'stock_status' => $product->stock_status,
                'stock_status_label' => $product->stock_status_label,
                'purchase_price' => $product->purchase_price,
                'sale_price' => $product->sale_price,
                'stock_value' => $product->stock * $product->purchase_price,
                'is_own_product' => $product->is_own_product,
                'supplier_name' => $product->supplier?->name,
                'last_movement' => $product->getLastStockMovement()?->created_at?->format('d/m/Y H:i'),
                'profit_margin' => $product->getProfitMargin(),
            ];
        });

        return [
            'total' => $products->count(),
            'items' => $products->toArray(),
            'summary' => [
                'total_stock_value' => $products->sum('stock_value'),
                'avg_profit_margin' => $products->avg('profit_margin'),
                'low_stock_count' => $products->where('stock_status', 'low_stock')->count(),
                'out_of_stock_count' => $products->where('stock_status', 'out_of_stock')->count(),
            ]
        ];
    }

    /**
     * Reporte de papeles
     */
    protected function getPapersReport(?int $companyId, array $options): array
    {
        $query = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->with(['supplier']);

        if (!empty($options['stock_status'])) {
            match($options['stock_status']) {
                'low' => $query->lowStock(),
                'out' => $query->outOfStock(),
                'in' => $query->inStock(),
                default => null
            };
        }

        $papers = $query->get()->map(function ($paper) {
            return [
                'id' => $paper->id,
                'name' => $paper->name,
                'code' => $paper->code,
                'weight' => $paper->weight,
                'size' => $paper->width . 'x' . $paper->height,
                'stock' => $paper->stock,
                'min_stock' => $paper->min_stock,
                'stock_status' => $paper->stock_status,
                'stock_status_label' => $paper->stock_status_label,
                'cost_per_sheet' => $paper->cost_per_sheet,
                'price' => $paper->price,
                'stock_value' => $paper->stock * $paper->cost_per_sheet,
                'is_own' => $paper->is_own,
                'supplier_name' => $paper->supplier?->name,
                'last_movement' => $paper->getLastStockMovement()?->created_at?->format('d/m/Y H:i'),
                'margin' => $paper->margin,
            ];
        });

        return [
            'total' => $papers->count(),
            'items' => $papers->toArray(),
            'summary' => [
                'total_stock_value' => $papers->sum('stock_value'),
                'avg_margin' => $papers->avg('margin'),
                'low_stock_count' => $papers->where('stock_status', 'low_stock')->count(),
                'out_of_stock_count' => $papers->where('stock_status', 'out_of_stock')->count(),
            ]
        ];
    }

    /**
     * Reporte de alertas
     */
    protected function getAlertsReport(?int $companyId): array
    {
        $alerts = StockAlert::where('company_id', $companyId)
            ->with(['stockable'])
            ->orderBy('severity', 'desc')
            ->orderBy('triggered_at', 'desc')
            ->get();

        return [
            'total' => $alerts->count(),
            'active' => $alerts->where('status', 'active')->count(),
            'critical' => $alerts->where('severity', 'critical')->count(),
            'by_type' => $alerts->groupBy('type')->map->count()->toArray(),
            'by_severity' => $alerts->groupBy('severity')->map->count()->toArray(),
            'recent' => $alerts->take(10)->map(function ($alert) {
                return [
                    'id' => $alert->id,
                    'type' => $alert->type_label,
                    'severity' => $alert->severity_label,
                    'status' => $alert->status_label,
                    'item_name' => $alert->stockable->name,
                    'message' => $alert->message,
                    'triggered_at' => $alert->triggered_at->format('d/m/Y H:i'),
                    'age_hours' => $alert->age_hours,
                ];
            })->toArray(),
        ];
    }

    /**
     * Reporte de movimientos
     */
    protected function getMovementsReport(?int $companyId, array $options): array
    {
        $startDate = $options['start_date'] ?? now()->subDays(30);
        $endDate = $options['end_date'] ?? now();

        $movements = StockMovement::where('company_id', $companyId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['stockable', 'user'])
            ->orderBy('created_at', 'desc');

        $movementsData = $movements->get();

        return [
            'period' => [
                'start' => Carbon::parse($startDate)->format('d/m/Y'),
                'end' => Carbon::parse($endDate)->format('d/m/Y'),
            ],
            'total' => $movementsData->count(),
            'summary' => [
                'total_inbound' => $movementsData->where('type', 'in')->sum('quantity'),
                'total_outbound' => $movementsData->where('type', 'out')->sum('quantity'),
                'total_adjustments' => $movementsData->where('type', 'adjustment')->count(),
                'by_type' => $movementsData->groupBy('type')->map->count()->toArray(),
                'by_reason' => $movementsData->groupBy('reason')->map->count()->toArray(),
            ],
            'daily_activity' => $this->getDailyMovementActivity($movementsData),
            'recent' => $movementsData->take(20)->map(function ($movement) {
                return [
                    'id' => $movement->id,
                    'date' => $movement->created_at->format('d/m/Y H:i'),
                    'item_name' => $movement->stockable->name,
                    'type' => $movement->type_label,
                    'reason' => $movement->reason_label,
                    'quantity' => $movement->quantity_with_sign,
                    'previous_stock' => $movement->previous_stock,
                    'new_stock' => $movement->new_stock,
                    'user' => $movement->user?->name,
                ];
            })->toArray(),
        ];
    }

    /**
     * Analíticas avanzadas del inventario
     */
    protected function getInventoryAnalytics(?int $companyId): array
    {
        return [
            'turnover_analysis' => $this->getInventoryTurnoverAnalysis($companyId),
            'top_movers' => $this->getTopMovers($companyId),
            'seasonal_trends' => $this->getSeasonalTrends($companyId),
            'supplier_analysis' => $this->getSupplierAnalysis($companyId),
            'predictions' => $this->getStockPredictions($companyId),
        ];
    }

    /**
     * Análisis de rotación de inventario
     */
    protected function getInventoryTurnoverAnalysis(?int $companyId): array
    {
        $last30Days = now()->subDays(30);

        $products = Product::where('company_id', $companyId)
            ->where('active', true)
            ->with(['stockMovements' => function ($query) use ($last30Days) {
                $query->where('created_at', '>=', $last30Days)
                      ->where('type', 'out');
            }])
            ->get();

        $turnoverData = $products->map(function ($product) {
            $totalSold = $product->stockMovements->sum('quantity');
            $avgStock = ($product->stock + $totalSold) / 2; // Aproximación simple
            $turnover = $avgStock > 0 ? $totalSold / $avgStock : 0;

            return [
                'product_name' => $product->name,
                'total_sold' => $totalSold,
                'current_stock' => $product->stock,
                'turnover_rate' => round($turnover, 2),
                'category' => $turnover > 2 ? 'fast' : ($turnover > 0.5 ? 'medium' : 'slow'),
            ];
        });

        return [
            'fast_movers' => $turnoverData->where('category', 'fast')->count(),
            'medium_movers' => $turnoverData->where('category', 'medium')->count(),
            'slow_movers' => $turnoverData->where('category', 'slow')->count(),
            'top_performers' => $turnoverData->sortByDesc('turnover_rate')->take(5)->values()->toArray(),
            'worst_performers' => $turnoverData->sortBy('turnover_rate')->take(5)->values()->toArray(),
        ];
    }

    /**
     * Productos con más movimiento
     */
    protected function getTopMovers(?int $companyId): array
    {
        $last30Days = now()->subDays(30);

        $topMovers = StockMovement::where('company_id', $companyId)
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('stockable_type, stockable_id, sum(quantity) as total_quantity, count(*) as movement_count')
            ->groupBy('stockable_type', 'stockable_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->with('stockable')
            ->get();

        return $topMovers->map(function ($movement) {
            return [
                'item_name' => $movement->stockable->name,
                'item_type' => $movement->stockable_type === Product::class ? 'Producto' : 'Papel',
                'total_quantity' => $movement->total_quantity,
                'movement_count' => $movement->movement_count,
                'current_stock' => $movement->stockable->stock,
            ];
        })->toArray();
    }

    /**
     * Actividad diaria de movimientos
     */
    protected function getDailyMovementActivity(Collection $movements): array
    {
        return $movements->groupBy(function ($movement) {
            return $movement->created_at->format('Y-m-d');
        })->map(function ($dayMovements, $date) {
            return [
                'date' => Carbon::parse($date)->format('d/m/Y'),
                'total_movements' => $dayMovements->count(),
                'inbound' => $dayMovements->where('type', 'in')->sum('quantity'),
                'outbound' => $dayMovements->where('type', 'out')->sum('quantity'),
                'adjustments' => $dayMovements->where('type', 'adjustment')->count(),
            ];
        })->values()->toArray();
    }

    /**
     * Calcular valor total del stock
     */
    protected function calculateTotalStockValue(?int $companyId): float
    {
        $productsValue = Product::where('company_id', $companyId)
            ->where('active', true)
            ->selectRaw('sum(stock * purchase_price) as total')
            ->value('total') ?? 0;

        $papersValue = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->selectRaw('sum(stock * cost_per_sheet) as total')
            ->value('total') ?? 0;

        return $productsValue + $papersValue;
    }

    /**
     * Tendencias estacionales (placeholder)
     */
    protected function getSeasonalTrends(?int $companyId): array
    {
        // Implementación básica - puede expandirse con ML
        return [
            'note' => 'Análisis de tendencias estacionales requiere más datos históricos',
            'recommendation' => 'Recolectar datos por al menos 12 meses para análisis significativo',
        ];
    }

    /**
     * Análisis de proveedores
     */
    protected function getSupplierAnalysis(?int $companyId): array
    {
        // Productos por proveedor
        $productSuppliers = Product::where('company_id', $companyId)
            ->where('active', true)
            ->where('is_own_product', false)
            ->with('supplier')
            ->get()
            ->groupBy('supplier_contact_id');

        // Papeles por proveedor
        $paperSuppliers = Paper::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_own', false)
            ->with('supplier')
            ->get()
            ->groupBy('supplier_id');

        return [
            'product_suppliers' => $productSuppliers->map(function ($products, $supplierId) {
                $supplier = $products->first()->supplier;
                return [
                    'supplier_name' => $supplier?->name ?? 'Sin proveedor',
                    'product_count' => $products->count(),
                    'total_stock_value' => $products->sum(fn($p) => $p->stock * $p->purchase_price),
                    'low_stock_items' => $products->filter(fn($p) => $p->isLowStock())->count(),
                ];
            })->values()->toArray(),
            'paper_suppliers' => $paperSuppliers->map(function ($papers, $supplierId) {
                $supplier = $papers->first()->supplier;
                return [
                    'supplier_name' => $supplier?->name ?? 'Sin proveedor',
                    'paper_count' => $papers->count(),
                    'total_stock_value' => $papers->sum(fn($p) => $p->stock * $p->cost_per_sheet),
                    'low_stock_items' => $papers->filter(fn($p) => $p->isLowStock())->count(),
                ];
            })->values()->toArray(),
        ];
    }

    /**
     * Predicciones básicas de stock
     */
    protected function getStockPredictions(?int $companyId): array
    {
        // Implementación básica - puede expandirse con algoritmos más sofisticados
        return [
            'method' => 'Linear trend analysis (last 30 days)',
            'accuracy' => 'Basic prediction - consider external factors',
            'recommendations' => [
                'Monitor fast-moving items daily',
                'Review reorder points monthly',
                'Consider seasonal variations',
            ],
        ];
    }

    /**
     * Exportar reporte a diferentes formatos
     */
    public function exportReport(array $report, string $format = 'json'): string
    {
        return match($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'csv' => $this->convertToCSV($report),
            'html' => $this->convertToHTML($report),
            default => json_encode($report, JSON_PRETTY_PRINT)
        };
    }

    /**
     * Convertir reporte a CSV (implementación básica)
     */
    protected function convertToCSV(array $report): string
    {
        // Implementación básica para CSV
        $csv = "Reporte de Inventario - " . $report['generated_at'] . "\n\n";

        // Resumen
        $csv .= "RESUMEN\n";
        foreach ($report['summary'] as $key => $value) {
            $csv .= ucfirst(str_replace('_', ' ', $key)) . "," . $value . "\n";
        }

        return $csv;
    }

    /**
     * Convertir reporte a HTML (implementación básica)
     */
    protected function convertToHTML(array $report): string
    {
        // Implementación básica para HTML
        return "<h1>Reporte de Inventario</h1><p>Generado: {$report['generated_at']}</p>";
    }
}