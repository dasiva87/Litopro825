<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Plan;
use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinancialMetricsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Calcular MRR (Monthly Recurring Revenue)
        $mrr = $this->calculateMRR();
        $previousMonthMrr = $this->calculateMRR(-1);
        $mrrChange = $previousMonthMrr > 0 ? (($mrr - $previousMonthMrr) / $previousMonthMrr) * 100 : 0;

        // Calcular ARR (Annual Recurring Revenue)
        $arr = $mrr * 12;

        // Calcular Churn Rate
        $churnRate = $this->calculateChurnRate();

        // Calcular conversión de trial a pago
        $trialConversion = $this->calculateTrialConversion();

        return [
            Stat::make('MRR (Monthly Recurring Revenue)', '$'.number_format($mrr, 2))
                ->description($mrrChange >= 0 ? '+'.number_format($mrrChange, 1).'% vs mes anterior' : number_format($mrrChange, 1).'% vs mes anterior')
                ->descriptionIcon($mrrChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($mrrChange >= 0 ? 'success' : 'danger'),

            Stat::make('ARR (Annual Recurring Revenue)', '$'.number_format($arr, 2))
                ->description('Proyección anual basada en MRR')
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('info'),

            Stat::make('Churn Rate', number_format($churnRate, 1).'%')
                ->description('Tasa de cancelación mensual')
                ->descriptionIcon('heroicon-m-arrow-right-start-on-rectangle')
                ->color($churnRate <= 5 ? 'success' : ($churnRate <= 10 ? 'warning' : 'danger')),

            Stat::make('Conversión Trial→Pago', number_format($trialConversion, 1).'%')
                ->description('Últimos 30 días')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($trialConversion >= 20 ? 'success' : ($trialConversion >= 10 ? 'warning' : 'danger')),
        ];
    }

    private function calculateMRR(int $monthsBack = 0): float
    {
        $targetDate = now()->subMonths(abs($monthsBack));

        // Obtener suscripciones activas en el mes objetivo
        $activeSubscriptions = Subscription::where('stripe_status', 'active')
            ->where('created_at', '<=', $targetDate->endOfMonth())
            ->where(function ($query) use ($targetDate) {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>', $targetDate->startOfMonth());
            })
            ->get();

        $totalMrr = 0;

        foreach ($activeSubscriptions as $subscription) {
            // Buscar el plan correspondiente
            $plan = Plan::where('stripe_price_id', $subscription->stripe_price)->first();

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

    private function calculateChurnRate(): float
    {
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        // Suscripciones activas al inicio del mes
        $activeAtStart = Subscription::where('stripe_status', 'active')
            ->where('created_at', '<', $startOfMonth)
            ->count();

        if ($activeAtStart == 0) {
            return 0;
        }

        // Suscripciones canceladas durante el mes
        $cancelledThisMonth = Subscription::where('stripe_status', 'cancelled')
            ->whereBetween('ends_at', [$startOfMonth, $endOfMonth])
            ->count();

        return ($cancelledThisMonth / $activeAtStart) * 100;
    }

    private function calculateTrialConversion(): float
    {
        $thirtyDaysAgo = now()->subDays(30);

        // Suscripciones que terminaron su trial en los últimos 30 días
        $trialsEnded = Subscription::where('trial_ends_at', '>=', $thirtyDaysAgo)
            ->where('trial_ends_at', '<=', now())
            ->count();

        if ($trialsEnded == 0) {
            return 0;
        }

        // De esas, cuántas se convirtieron a activas
        $trialsConverted = Subscription::where('trial_ends_at', '>=', $thirtyDaysAgo)
            ->where('trial_ends_at', '<=', now())
            ->where('stripe_status', 'active')
            ->count();

        return ($trialsConverted / $trialsEnded) * 100;
    }
}
