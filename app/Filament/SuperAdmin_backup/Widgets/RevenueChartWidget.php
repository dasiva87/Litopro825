<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Company;
use App\Models\Plan;
use Filament\Widgets\ChartWidget;

class RevenueChartWidget extends ChartWidget
{
    protected ?string $heading = 'Revenue Breakdown by Plan';

    protected function getData(): array
    {
        // Get revenue by plan
        $planRevenue = [];
        $planLabels = [];
        $planColors = [];

        $plans = Plan::where('is_active', true)->get();

        foreach ($plans as $plan) {
            $companiesOnPlan = Company::where('subscription_plan', $plan->name)
                ->where('status', 'active')
                ->count();

            $monthlyRevenue = $companiesOnPlan * ($plan->interval === 'yearly' ? ($plan->price / 12) : $plan->price);

            if ($monthlyRevenue > 0) {
                $planLabels[] = $plan->name;
                $planRevenue[] = $monthlyRevenue;
                $planColors[] = $this->getPlanColor($plan->name);
            }
        }

        return [
            'datasets' => [
                [
                    'data' => $planRevenue,
                    'backgroundColor' => $planColors,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $planLabels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function getPlanColor(string $planName): string
    {
        return match ($planName) {
            'free' => 'rgb(156, 163, 175)',
            'basic' => 'rgb(59, 130, 246)',
            'professional' => 'rgb(16, 185, 129)',
            'enterprise' => 'rgb(245, 158, 11)',
            default => 'rgb(107, 114, 128)',
        };
    }

    public function getDescription(): ?string
    {
        $totalRevenue = Company::where('status', 'active')
            ->join('plans', 'companies.subscription_plan', '=', 'plans.name')
            ->sum(\DB::raw('CASE WHEN plans.interval = "yearly" THEN plans.price / 12 ELSE plans.price END'));

        return 'Revenue mensual total: $'.number_format($totalRevenue, 2);
    }
}
