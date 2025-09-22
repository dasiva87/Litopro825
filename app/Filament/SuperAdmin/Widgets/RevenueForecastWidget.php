<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RevenueForecastWidget extends ChartWidget
{
    protected static ?int $sort = 6;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Proyección de Ingresos - MRR';
    }

    public function getDescription(): ?string
    {
        return 'Proyección de MRR basada en tendencias históricas y crecimiento actual. Línea azul: histórico, línea verde: proyección.';
    }

    protected function getData(): array
    {
        $forecastData = $this->calculateRevenueForecast();

        return [
            'datasets' => [
                [
                    'label' => 'MRR Histórico',
                    'data' => $forecastData['historical'],
                    'borderColor' => 'rgb(54, 162, 235)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.1)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'MRR Proyectado',
                    'data' => $forecastData['projected'],
                    'borderColor' => 'rgb(75, 192, 192)',
                    'backgroundColor' => 'rgba(75, 192, 192, 0.1)',
                    'borderDash' => [5, 5],
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Escenario Optimista (+20%)',
                    'data' => $forecastData['optimistic'],
                    'borderColor' => 'rgb(144, 238, 144)',
                    'backgroundColor' => 'rgba(144, 238, 144, 0.05)',
                    'borderDash' => [2, 2],
                    'fill' => false,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Escenario Conservador (-10%)',
                    'data' => $forecastData['conservative'],
                    'borderColor' => 'rgb(255, 182, 193)',
                    'backgroundColor' => 'rgba(255, 182, 193, 0.05)',
                    'borderDash' => [2, 2],
                    'fill' => false,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $forecastData['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": $" + context.parsed.y.toLocaleString(); }',
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "$" + value.toLocaleString(); }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'MRR (COP)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Período',
                    ],
                ],
            ],
            'interaction' => [
                'mode' => 'nearest',
                'axis' => 'x',
                'intersect' => false,
            ],
        ];
    }

    private function calculateRevenueForecast(): array
    {
        // Obtener datos históricos de MRR de los últimos 6 meses
        $historicalData = [];
        $labels = [];

        // Calcular MRR histórico
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i)->startOfMonth();
            $mrr = $this->calculateMRRForMonth($date);
            $historicalData[] = $mrr;
            $labels[] = $date->format('M Y');
        }

        // Calcular tasa de crecimiento promedio
        $growthRates = [];
        for ($i = 1; $i < count($historicalData); $i++) {
            if ($historicalData[$i - 1] > 0) {
                $growthRate = (($historicalData[$i] - $historicalData[$i - 1]) / $historicalData[$i - 1]) * 100;
                $growthRates[] = $growthRate;
            }
        }

        $avgGrowthRate = count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 5; // 5% default
        $avgGrowthRate = max(-50, min(50, $avgGrowthRate)); // Limitar entre -50% y 50%

        // Proyectar próximos 6 meses
        $currentMRR = end($historicalData);
        $projectedData = array_fill(0, 6, null); // Llenar con null para los meses históricos
        $optimisticData = array_fill(0, 6, null);
        $conservativeData = array_fill(0, 6, null);

        for ($i = 0; $i < 6; $i++) {
            $futureDate = now()->addMonths($i + 1);
            $labels[] = $futureDate->format('M Y');

            // Proyección base
            $projectedMRR = $currentMRR * pow(1 + ($avgGrowthRate / 100), $i + 1);
            $projectedData[] = round($projectedMRR, 2);

            // Escenario optimista (+20% sobre la proyección base)
            $optimisticMRR = $projectedMRR * 1.2;
            $optimisticData[] = round($optimisticMRR, 2);

            // Escenario conservador (-10% sobre la proyección base)
            $conservativeMRR = $projectedMRR * 0.9;
            $conservativeData[] = round($conservativeMRR, 2);
        }

        return [
            'historical' => array_merge($historicalData, array_fill(0, 6, null)),
            'projected' => $projectedData,
            'optimistic' => $optimisticData,
            'conservative' => $conservativeData,
            'labels' => $labels,
        ];
    }

    private function calculateMRRForMonth(Carbon $date): float
    {
        // Obtener suscripciones activas en el mes objetivo
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->where('created_at', '<=', $date->endOfMonth())
            ->where(function ($query) use ($date) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $date->startOfMonth());
            })
            ->get();

        $totalMrr = 0;

        foreach ($activeSubscriptions as $subscription) {
            // Buscar el plan correspondiente
            $plan = Plan::where('stripe_price_id', $subscription->stripe_price)
                ->orWhere('id', str_replace(['plan_', 'price_'], '', $subscription->stripe_price))
                ->first();

            if ($plan) {
                // Convertir precio a mensual si es necesario
                $monthlyPrice = match ($plan->interval) {
                    'year' => $plan->price / 12,
                    'week' => $plan->price * 4.33, // 4.33 semanas por mes en promedio
                    'day' => $plan->price * 30,
                    default => $plan->price, // 'month'
                };

                $totalMrr += $monthlyPrice * ($subscription->quantity ?? 1);
            }
        }

        return $totalMrr;
    }
}