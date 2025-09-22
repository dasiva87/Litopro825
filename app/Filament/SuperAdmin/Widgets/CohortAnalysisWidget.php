<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Subscription;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CohortAnalysisWidget extends ChartWidget
{
    protected static ?int $sort = 5;

    protected int | string | array $columnSpan = 'full';

    public function getHeading(): ?string
    {
        return 'Análisis de Cohortes - Retención de Clientes';
    }

    public function getDescription(): ?string
    {
        return 'Retención de clientes por mes de primera suscripción. Cada línea representa una cohorte mensual.';
    }

    protected function getData(): array
    {
        // Obtener datos de cohortes de los últimos 6 meses
        $cohortData = $this->calculateCohortAnalysis();

        return [
            'datasets' => $cohortData['datasets'],
            'labels' => $cohortData['labels'],
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
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'callback' => 'function(value) { return value + "%"; }',
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Retención (%)',
                    ],
                ],
                'x' => [
                    'title' => [
                        'display' => true,
                        'text' => 'Meses desde primera suscripción',
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

    private function calculateCohortAnalysis(): array
    {
        $cohorts = [];
        $colors = [
            'rgb(255, 99, 132)',
            'rgb(54, 162, 235)',
            'rgb(255, 205, 86)',
            'rgb(75, 192, 192)',
            'rgb(153, 102, 255)',
            'rgb(255, 159, 64)',
        ];

        // Calcular cohortes de los últimos 6 meses
        for ($i = 5; $i >= 0; $i--) {
            $cohortMonth = now()->subMonths($i)->startOfMonth();
            $cohortName = $cohortMonth->format('M Y');

            // Obtener suscripciones que empezaron en este mes
            $cohortSubscriptions = Subscription::where('created_at', '>=', $cohortMonth)
                ->where('created_at', '<', $cohortMonth->copy()->addMonth())
                ->pluck('id')
                ->toArray();

            if (empty($cohortSubscriptions)) {
                continue;
            }

            $retentionData = [];
            $totalCohortSize = count($cohortSubscriptions);

            // Calcular retención para cada mes subsecuente
            for ($month = 0; $month <= $i; $month++) {
                $periodStart = $cohortMonth->copy()->addMonths($month);
                $periodEnd = $periodStart->copy()->addMonth();

                // Contar cuántas suscripciones de esta cohorte siguen activas
                $activeInPeriod = Subscription::whereIn('id', $cohortSubscriptions)
                    ->where(function ($query) use ($periodStart, $periodEnd) {
                        $query->where('stripe_status', 'active')
                            ->where('created_at', '<=', $periodEnd)
                            ->where(function ($q) use ($periodStart) {
                                $q->whereNull('ends_at')
                                    ->orWhere('ends_at', '>', $periodStart);
                            });
                    })
                    ->count();

                $retentionRate = $totalCohortSize > 0 ? ($activeInPeriod / $totalCohortSize) * 100 : 0;
                $retentionData[] = round($retentionRate, 1);
            }

            $cohorts[] = [
                'label' => $cohortName,
                'data' => $retentionData,
                'borderColor' => $colors[count($cohorts) % count($colors)],
                'backgroundColor' => $colors[count($cohorts) % count($colors)] . '20',
                'fill' => false,
                'tension' => 0.1,
            ];
        }

        // Labels para el eje X (meses desde primera suscripción)
        $maxMonths = 6;
        $labels = [];
        for ($i = 0; $i < $maxMonths; $i++) {
            $labels[] = "Mes $i";
        }

        return [
            'datasets' => $cohorts,
            'labels' => $labels,
        ];
    }
}