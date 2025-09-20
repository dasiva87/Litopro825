<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;

class ChurnRateWidget extends ChartWidget
{
    protected ?string $heading = 'Churn Rate (%)';

    protected function getData(): array
    {
        // Calculate churn rate for the last 12 months
        $months = [];
        $churnData = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthName = $date->format('M Y');
            $months[] = $monthName;

            // Calculate churn rate for this month
            $churnRate = $this->calculateChurnRateForMonth($date);
            $churnData[] = $churnRate;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Churn Rate (%)',
                    'data' => $churnData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
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

    private function calculateChurnRateForMonth($date): float
    {
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        // Companies that were active at the start of the month
        $activeAtStart = Company::where('created_at', '<', $startOfMonth)
            ->where(function ($query) use ($startOfMonth) {
                $query->where('status', '!=', 'cancelled')
                    ->orWhere('updated_at', '>=', $startOfMonth);
            })
            ->count();

        // Companies that churned during the month (cancelled or suspended)
        $churned = Company::whereBetween('updated_at', [$startOfMonth, $endOfMonth])
            ->whereIn('status', ['cancelled', 'suspended'])
            ->count();

        // Calculate churn rate
        if ($activeAtStart === 0) {
            return 0;
        }

        return round(($churned / $activeAtStart) * 100, 2);
    }

    public function getDescription(): ?string
    {
        $currentChurn = $this->calculateChurnRateForMonth(now());
        $lastMonthChurn = $this->calculateChurnRateForMonth(now()->subMonth());

        $change = $currentChurn - $lastMonthChurn;
        $changeText = $change >= 0 ? '+'.number_format($change, 1).'%' : number_format($change, 1).'%';

        return "Churn actual: {$currentChurn}% ({$changeText} vs mes anterior)";
    }
}
