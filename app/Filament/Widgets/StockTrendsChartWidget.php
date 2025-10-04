<?php

namespace App\Filament\Widgets;
use App\Services\TenantContext;

use App\Models\StockMovement;
use Filament\Widgets\ChartWidget;

class StockTrendsChartWidget extends ChartWidget
{
    protected ?string $heading = 'Tendencias de Stock (30 días)';

    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '300s';

    protected function getData(): array
    {
        $companyId = TenantContext::id();
        $last30Days = now()->subDays(30);

        // Movimientos de los últimos 30 días agrupados por día
        $movements = StockMovement::where('company_id', $companyId)
            ->where('created_at', '>=', $last30Days)
            ->selectRaw('DATE(created_at) as date, type, SUM(quantity) as total_quantity')
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date');

        $trends = [];
        $labels = [];
        $inData = [];
        $outData = [];
        $netData = [];

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $dayMovements = $movements->get($dateStr, collect());

            $labels[] = $date->format('M j');
            $inValue = $dayMovements->where('type', 'in')->sum('total_quantity');
            $outValue = $dayMovements->where('type', 'out')->sum('total_quantity');

            $inData[] = $inValue;
            $outData[] = $outValue;
            $netData[] = $inValue - $outValue;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Entradas',
                    'data' => $inData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Salidas',
                    'data' => $outData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'fill' => false,
                    'tension' => 0.3,
                ],
                [
                    'label' => 'Stock Neto',
                    'data' => $netData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => [
                'intersect' => false,
                'mode' => 'index',
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}