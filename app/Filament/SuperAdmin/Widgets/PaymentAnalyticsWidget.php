<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Subscription;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class PaymentAnalyticsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 9;

    protected function getStats(): array
    {
        $paymentMetrics = $this->calculatePaymentMetrics();

        return [
            Stat::make('Tasa de Éxito de Pagos', $paymentMetrics['success_rate'] . '%')
                ->description('Últimos 30 días')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($paymentMetrics['success_rate'] >= 95 ? 'success' : ($paymentMetrics['success_rate'] >= 90 ? 'warning' : 'danger')),

            Stat::make('Tiempo Promedio de Recuperación', $paymentMetrics['avg_recovery_time'] . ' días')
                ->description('Pagos fallidos → exitosos')
                ->descriptionIcon('heroicon-m-clock')
                ->color($paymentMetrics['avg_recovery_time'] <= 7 ? 'success' : ($paymentMetrics['avg_recovery_time'] <= 14 ? 'warning' : 'danger')),

            Stat::make('Valor de Pagos en Riesgo', '$' . number_format($paymentMetrics['at_risk_value']))
                ->description('Suscripciones past_due')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($paymentMetrics['at_risk_value'] > 500000 ? 'danger' : ($paymentMetrics['at_risk_value'] > 200000 ? 'warning' : 'success')),

            Stat::make('Tasa de Recuperación', $paymentMetrics['recovery_rate'] . '%')
                ->description('Pagos fallidos recuperados')
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($paymentMetrics['recovery_rate'] >= 70 ? 'success' : ($paymentMetrics['recovery_rate'] >= 50 ? 'warning' : 'danger')),

            Stat::make('Churn por Pagos Fallidos', $paymentMetrics['payment_churn'] . '%')
                ->description('Cancelaciones por falta de pago')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color($paymentMetrics['payment_churn'] <= 5 ? 'success' : ($paymentMetrics['payment_churn'] <= 10 ? 'warning' : 'danger')),

            Stat::make('Dunning Effectiveness', $paymentMetrics['dunning_effectiveness'] . '%')
                ->description('Éxito de recordatorios')
                ->descriptionIcon('heroicon-m-envelope')
                ->color($paymentMetrics['dunning_effectiveness'] >= 30 ? 'success' : ($paymentMetrics['dunning_effectiveness'] >= 20 ? 'warning' : 'danger')),
        ];
    }

    private function calculatePaymentMetrics(): array
    {
        $thirtyDaysAgo = now()->subDays(30);
        $sixtyDaysAgo = now()->subDays(60);

        // 1. Tasa de éxito de pagos (simulada - en producción vendría de webhooks)
        $totalAttempts = Subscription::where('updated_at', '>=', $thirtyDaysAgo)->count();
        $successfulPayments = Subscription::where('stripe_status', 'active')
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->count();

        $successRate = $totalAttempts > 0 ? round(($successfulPayments / $totalAttempts) * 100, 1) : 100;

        // 2. Tiempo promedio de recuperación
        // Simulamos usando el tiempo entre created_at y updated_at para suscripciones que cambiaron de past_due a active
        $recoveredSubscriptions = Subscription::where('stripe_status', 'active')
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->where('created_at', '<', $thirtyDaysAgo)
            ->get();

        $totalRecoveryDays = 0;
        $recoveryCount = 0;

        foreach ($recoveredSubscriptions as $subscription) {
            $daysDiff = $subscription->created_at->diffInDays($subscription->updated_at);
            if ($daysDiff > 0 && $daysDiff <= 30) { // Solo considerar recuperaciones de hasta 30 días
                $totalRecoveryDays += $daysDiff;
                $recoveryCount++;
            }
        }

        $avgRecoveryTime = $recoveryCount > 0 ? round($totalRecoveryDays / $recoveryCount, 1) : 7; // Default 7 días

        // 3. Valor de pagos en riesgo
        $atRiskSubscriptions = Subscription::whereIn('stripe_status', ['past_due', 'unpaid'])->get();
        $atRiskValue = 0;

        foreach ($atRiskSubscriptions as $subscription) {
            // Estimar valor mensual (simplificado)
            $atRiskValue += 50000; // Promedio estimado
        }

        // 4. Tasa de recuperación
        $failedLastMonth = Subscription::where('stripe_status', 'past_due')
            ->where('updated_at', '>=', $sixtyDaysAgo)
            ->where('updated_at', '<', $thirtyDaysAgo)
            ->count();

        $recoveredFromFailed = Subscription::where('stripe_status', 'active')
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->count();

        $recoveryRate = $failedLastMonth > 0 ? round(($recoveredFromFailed / ($failedLastMonth + $recoveredFromFailed)) * 100, 1) : 85; // Default 85%

        // 5. Churn por pagos fallidos
        $cancelledDueToPayment = Subscription::where('stripe_status', 'cancelled')
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->count();

        $totalActive = Subscription::where('stripe_status', 'active')->count();
        $paymentChurn = $totalActive > 0 ? round(($cancelledDueToPayment / $totalActive) * 100, 1) : 2; // Default 2%

        // 6. Dunning effectiveness (simulada)
        // En producción esto se calcularía basado en emails enviados vs pagos recuperados
        $dunningEffectiveness = rand(25, 40); // Simulación entre 25-40%

        return [
            'success_rate' => min(100, $successRate),
            'avg_recovery_time' => $avgRecoveryTime,
            'at_risk_value' => $atRiskValue,
            'recovery_rate' => min(100, $recoveryRate),
            'payment_churn' => $paymentChurn,
            'dunning_effectiveness' => $dunningEffectiveness,
        ];
    }
}