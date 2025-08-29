<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;

class StockAlertsWidget extends Widget
{
    protected string $view = 'filament.widgets.stock-alerts';
    
    protected static ?int $sort = 10;
    
    protected int | string | array $columnSpan = [
        'md' => 1,
        'xl' => 1,
    ];
    
    public function getCriticalStockProducts()
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    'urgency_level' => $this->getUrgencyLevel($product),
                    'supplier' => $product->supplierContact->name ?? 'Sin proveedor',
                    'last_purchase_price' => $product->purchase_price,
                ];
            });
    }
    
    public function getLowStockProducts()
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '>', 'min_stock')
            ->whereRaw('stock <= (min_stock * 1.5)')  // 50% above minimum
            ->orderBy('stock', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'current_stock' => $product->stock,
                    'min_stock' => $product->min_stock,
                    'supplier' => $product->supplierContact->name ?? 'Sin proveedor',
                ];
            });
    }
    
    private function getUrgencyLevel(Product $product): string
    {
        if ($product->stock <= 0) {
            return 'critical'; // Sin stock
        } elseif ($product->stock <= ($product->min_stock * 0.5)) {
            return 'high'; // Menos del 50% del mínimo
        } elseif ($product->stock <= $product->min_stock) {
            return 'medium'; // En el mínimo
        }
        
        return 'low';
    }
    
    public function getTotalCriticalItems(): int
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();
    }
    
    public function getEstimatedRestockCost(): float
    {
        return Product::where('company_id', auth()->user()->company_id)
            ->where('active', true)
            ->whereColumn('stock', '<=', 'min_stock')
            ->get()
            ->sum(function ($product) {
                $neededQuantity = max(0, $product->min_stock - $product->stock + 10); // +10 buffer
                return $neededQuantity * $product->purchase_price;
            });
    }
    
    public function getViewData(): array
    {
        return [
            'criticalStock' => $this->getCriticalStockProducts(),
            'lowStock' => $this->getLowStockProducts(),
            'totalCriticalItems' => $this->getTotalCriticalItems(),
            'estimatedRestockCost' => $this->getEstimatedRestockCost(),
        ];
    }
}