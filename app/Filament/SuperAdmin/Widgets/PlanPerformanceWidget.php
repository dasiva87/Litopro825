<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

class PlanPerformanceWidget extends ChartWidget
{
    protected static ?int $sort = 7;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Rendimiento por Plan';
    }

    public function getDescription(): ?string
    {
        return 'Análisis comparativo de rendimiento: suscripciones activas, ingresos mensuales y tasa de conversión por plan.';
    }

    protected function getData(): array
    {
        $performanceData = $this->calculatePlanPerformance();

        return [
            'datasets' => [
                [
                    'label' => 'Suscripciones Activas',
                    'data' => $performanceData['subscriptions'],
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(255, 206, 86, 0.8)',
                        'rgba(255, 99, 132, 0.8)',
                    ],
                    'borderColor' => [
                        'rgba(54, 162, 235, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)',
                    ],
                    'borderWidth' => 1,
                ],
            ],
            'labels' => $performanceData['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'right',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            var label = context.label || "";
                            var value = context.parsed;
                            var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                            var percentage = ((value / total) * 100).toFixed(1);
                            return label + ": " + value + " (" + percentage + "%)";
                        }',
                    ],
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    private function calculatePlanPerformance(): array
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order')->get();
        $labels = [];
        $subscriptions = [];
        $revenues = [];
        $conversions = [];

        foreach ($plans as $plan) {
            $labels[] = $plan->name;

            // Contar suscripciones activas
            $activeSubscriptions = Subscription::where('stripe_status', 'active')
                ->where(function ($query) use ($plan) {
                    $query->where('stripe_price', $plan->stripe_price_id)
                        ->orWhere('stripe_price', 'plan_' . $plan->id)
                        ->orWhere('stripe_price', 'price_' . $plan->id);
                })
                ->count();

            $subscriptions[] = $activeSubscriptions;

            // Calcular ingresos mensuales del plan
            $monthlyRevenue = $activeSubscriptions * $this->getMonthlyPrice($plan);
            $revenues[] = $monthlyRevenue;

            // Calcular tasa de conversión de trial a activo
            $trialSubscriptions = Subscription::where('stripe_status', 'trialing')
                ->where(function ($query) use ($plan) {
                    $query->where('stripe_price', $plan->stripe_price_id)
                        ->orWhere('stripe_price', 'plan_' . $plan->id)
                        ->orWhere('stripe_price', 'price_' . $plan->id);
                })
                ->count();

            $totalSubscriptions = $activeSubscriptions + $trialSubscriptions;
            $conversionRate = $totalSubscriptions > 0 ? ($activeSubscriptions / $totalSubscriptions) * 100 : 0;
            $conversions[] = round($conversionRate, 1);
        }

        return [
            'labels' => $labels,
            'subscriptions' => $subscriptions,
            'revenues' => $revenues,
            'conversions' => $conversions,
        ];
    }

    private function getMonthlyPrice(Plan $plan): float
    {
        return match ($plan->interval) {
            'year' => $plan->price / 12,
            'week' => $plan->price * 4.33,
            'day' => $plan->price * 30,
            default => $plan->price,
        };
    }
}