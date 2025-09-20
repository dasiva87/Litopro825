<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\ChartWidget;

class MrrWidget extends ChartWidget
{
    protected ?string $heading = 'Monthly Recurring Revenue (MRR)';

    protected function getData(): array
    {
        // Calculate MRR for the last 12 months
        $months = [];
        $mrrData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');
            $months[] = $monthName;

            // Calculate MRR for this month
            $mrr = $this->calculateMrrForMonth($date);
            $mrrData[] = $mrr;
        }

        return [
            'datasets' => [
                [
                    'label' => 'MRR ($)',
                    'data' => $mrrData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function calculateMrrForMonth($date): float
    {
        // Get all active subscriptions for the month
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->where('created_at', '<=', $date->endOfMonth())
            ->whereNull('ends_at')
            ->orWhere('ends_at', '>', $date->endOfMonth())
            ->with('company')
            ->get();

        $totalMrr = 0;

        foreach ($activeSubscriptions as $subscription) {
            if ($subscription->company && $subscription->company->subscription_plan) {
                $plan = Plan::where('name', $subscription->company->subscription_plan)->first();
                if ($plan) {
                    // Convert to monthly if needed
                    $monthlyPrice = $plan->interval === 'yearly' ? ($plan->price / 12) : $plan->price;
                    $totalMrr += $monthlyPrice;
                }
            }
        }

        return round($totalMrr, 2);
    }

    public function getDescription(): ?string
    {
        $currentMrr = $this->calculateMrrForMonth(now());
        $lastMonthMrr = $this->calculateMrrForMonth(now()->subMonth());

        $growth = $lastMonthMrr > 0 ? (($currentMrr - $lastMonthMrr) / $lastMonthMrr) * 100 : 0;
        $growthText = $growth >= 0 ? '+'.number_format($growth, 1).'%' : number_format($growth, 1).'%';

        return 'MRR actual: $'.number_format($currentMrr, 2)." ({$growthText} vs mes anterior)";
    }
}
