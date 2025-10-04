<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Paper;
use App\Models\StockMovement;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class StockPredictionService
{
    /**
     * Predecir cuándo se agotará el stock de un item
     */
    public function predictStockDepletion(Model $stockable, int $daysToAnalyze = 30): array
    {
        $currentStock = $stockable->stock;
        $averageConsumption = $this->calculateAverageConsumption($stockable, $daysToAnalyze);

        if ($averageConsumption <= 0) {
            return [
                'prediction_possible' => false,
                'reason' => 'No consumption data available',
                'recommendation' => 'Monitor for at least one week to generate predictions',
            ];
        }

        $daysUntilDepletion = $currentStock / $averageConsumption;
        $depletionDate = now()->addDays($daysUntilDepletion);

        // Calcular confianza basada en consistencia de datos
        $confidence = $this->calculatePredictionConfidence($stockable, $daysToAnalyze);

        return [
            'prediction_possible' => true,
            'current_stock' => $currentStock,
            'average_daily_consumption' => round($averageConsumption, 2),
            'days_until_depletion' => round($daysUntilDepletion, 1),
            'predicted_depletion_date' => $depletionDate->format('Y-m-d'),
            'confidence_level' => $confidence,
            'confidence_label' => $this->getConfidenceLabel($confidence),
            'recommendation' => $this->generateReorderRecommendation($stockable, $daysUntilDepletion),
            'analysis_period' => $daysToAnalyze,
        ];
    }

    /**
     * Calcular consumo promedio diario
     */
    protected function calculateAverageConsumption(Model $stockable, int $days): float
    {
        $movements = $stockable->stockMovements()
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($movements->isEmpty()) {
            return 0;
        }

        $totalConsumption = $movements->sum('quantity');
        $actualDays = min($days, $movements->first()->created_at->diffInDays(now()) + 1);

        return $actualDays > 0 ? $totalConsumption / $actualDays : 0;
    }

    /**
     * Calcular nivel de confianza de la predicción
     */
    protected function calculatePredictionConfidence(Model $stockable, int $days): float
    {
        $movements = $stockable->stockMovements()
            ->where('type', 'out')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($movements->count() < 3) {
            return 0.3; // Baja confianza con pocos datos
        }

        // Agrupar por día para calcular variabilidad
        $dailyConsumption = $movements->groupBy(function ($movement) {
            return $movement->created_at->format('Y-m-d');
        })->map(function ($dayMovements) {
            return $dayMovements->sum('quantity');
        });

        if ($dailyConsumption->count() < 3) {
            return 0.5; // Confianza media
        }

        // Calcular coeficiente de variación
        $mean = $dailyConsumption->avg();
        $stdDev = $this->calculateStandardDeviation($dailyConsumption->values()->toArray());

        $coefficientOfVariation = $mean > 0 ? $stdDev / $mean : 1;

        // Convertir a nivel de confianza (menos variación = más confianza)
        $confidence = max(0.3, min(0.95, 1 - ($coefficientOfVariation / 2)));

        return round($confidence, 2);
    }

    /**
     * Calcular desviación estándar
     */
    protected function calculateStandardDeviation(array $values): float
    {
        $count = count($values);
        if ($count <= 1) {
            return 0;
        }

        $mean = array_sum($values) / $count;
        $squaredDifferences = array_map(function ($value) use ($mean) {
            return pow($value - $mean, 2);
        }, $values);

        $variance = array_sum($squaredDifferences) / ($count - 1);
        return sqrt($variance);
    }

    /**
     * Obtener etiqueta de confianza
     */
    protected function getConfidenceLabel(float $confidence): string
    {
        return match(true) {
            $confidence >= 0.8 => 'Alta',
            $confidence >= 0.6 => 'Media',
            $confidence >= 0.4 => 'Baja',
            default => 'Muy Baja'
        };
    }

    /**
     * Generar recomendación de reorden
     */
    protected function generateReorderRecommendation(Model $stockable, float $daysUntilDepletion): string
    {
        $minStock = $stockable->min_stock ?? 0;

        if ($daysUntilDepletion <= 0) {
            return 'URGENTE: Reabastecer inmediatamente - Stock agotado';
        }

        if ($daysUntilDepletion <= 3) {
            return 'CRÍTICO: Reabastecer en las próximas 24-48 horas';
        }

        if ($daysUntilDepletion <= 7) {
            return 'IMPORTANTE: Planificar reabastecimiento esta semana';
        }

        if ($daysUntilDepletion <= 14) {
            return 'MODERADO: Considerar pedido en los próximos días';
        }

        if ($daysUntilDepletion <= 30) {
            return 'NORMAL: Stock suficiente por ahora, monitorear';
        }

        return 'ESTABLE: Stock suficiente por más de un mes';
    }

    /**
     * Predecir demanda futura basada en tendencias
     */
    public function predictDemandTrends(Model $stockable, int $forecastDays = 30): array
    {
        $historicalData = $this->getHistoricalDemandData($stockable, 90); // 90 días de historia

        if ($historicalData->count() < 7) {
            return [
                'prediction_possible' => false,
                'reason' => 'Insufficient historical data',
                'minimum_days_needed' => 7,
            ];
        }

        // Aplicar regresión lineal simple
        $trend = $this->calculateLinearTrend($historicalData);
        $forecast = $this->generateForecast($trend, $forecastDays);

        return [
            'prediction_possible' => true,
            'historical_period_days' => 90,
            'forecast_period_days' => $forecastDays,
            'trend' => [
                'direction' => $trend['slope'] > 0.1 ? 'increasing' : ($trend['slope'] < -0.1 ? 'decreasing' : 'stable'),
                'slope' => round($trend['slope'], 4),
                'confidence' => $trend['r_squared'],
            ],
            'forecast' => $forecast,
            'recommendations' => $this->generateDemandRecommendations($trend, $stockable),
        ];
    }

    /**
     * Obtener datos históricos de demanda
     */
    protected function getHistoricalDemandData(Model $stockable, int $days): Collection
    {
        $movements = $stockable->stockMovements()
            ->where('type', 'out')
            ->where('reason', 'sale') // Solo ventas reales
            ->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at')
            ->get();

        // Agrupar por día
        return $movements->groupBy(function ($movement) {
            return $movement->created_at->format('Y-m-d');
        })->map(function ($dayMovements, $date) {
            return [
                'date' => $date,
                'demand' => $dayMovements->sum('quantity'),
                'day_number' => Carbon::parse($date)->diffInDays(now()->subDays(90)),
            ];
        })->values();
    }

    /**
     * Calcular tendencia lineal
     */
    protected function calculateLinearTrend(Collection $data): array
    {
        $n = $data->count();
        $sumX = $data->sum('day_number');
        $sumY = $data->sum('demand');
        $sumXY = $data->sum(function ($point) {
            return $point['day_number'] * $point['demand'];
        });
        $sumXX = $data->sum(function ($point) {
            return $point['day_number'] * $point['day_number'];
        });

        // Calcular pendiente y intersección
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);
        $intercept = ($sumY - $slope * $sumX) / $n;

        // Calcular R²
        $meanY = $sumY / $n;
        $ssTotal = $data->sum(function ($point) use ($meanY) {
            return pow($point['demand'] - $meanY, 2);
        });
        $ssResidual = $data->sum(function ($point) use ($slope, $intercept) {
            $predicted = $slope * $point['day_number'] + $intercept;
            return pow($point['demand'] - $predicted, 2);
        });

        $rSquared = $ssTotal > 0 ? 1 - ($ssResidual / $ssTotal) : 0;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
            'r_squared' => round($rSquared, 3),
        ];
    }

    /**
     * Generar pronóstico
     */
    protected function generateForecast(array $trend, int $days): array
    {
        $forecast = [];
        $baseDay = 90; // Días históricos

        for ($i = 1; $i <= $days; $i++) {
            $dayNumber = $baseDay + $i;
            $predicted = max(0, $trend['slope'] * $dayNumber + $trend['intercept']);

            $forecast[] = [
                'day' => $i,
                'date' => now()->addDays($i)->format('Y-m-d'),
                'predicted_demand' => round($predicted, 2),
            ];
        }

        return $forecast;
    }

    /**
     * Generar recomendaciones basadas en demanda
     */
    protected function generateDemandRecommendations(array $trend, Model $stockable): array
    {
        $recommendations = [];

        if ($trend['slope'] > 0.5) {
            $recommendations[] = 'Demanda creciente detectada - considerar aumentar stock de seguridad';
            $recommendations[] = 'Revisar capacidad de suministro con proveedores';
        } elseif ($trend['slope'] < -0.5) {
            $recommendations[] = 'Demanda decreciente - evaluar reducir pedidos futuros';
            $recommendations[] = 'Considerar promociones para mover inventario';
        } else {
            $recommendations[] = 'Demanda estable - mantener niveles actuales de reposición';
        }

        if ($trend['r_squared'] < 0.3) {
            $recommendations[] = 'Patrones de demanda irregulares - aumentar frecuencia de monitoreo';
        }

        return $recommendations;
    }

    /**
     * Generar predicciones para todos los items de una empresa
     */
    public function generateBulkPredictions(?int $companyId = null): array
    {
        $companyId = $companyId ?? config('tenant.company_id');
        $results = [];

        // Productos
        $products = Product::forTenant($companyId)
            ->where('active', true)
            ->get();

        foreach ($products as $product) {
            $prediction = $this->predictStockDepletion($product);
            if ($prediction['prediction_possible']) {
                $results['products'][] = array_merge($prediction, [
                    'item_id' => $product->id,
                    'item_name' => $product->name,
                    'item_type' => 'product',
                ]);
            }
        }

        // Papeles
        $papers = Paper::forTenant($companyId)
            ->where('is_active', true)
            ->get();

        foreach ($papers as $paper) {
            $prediction = $this->predictStockDepletion($paper);
            if ($prediction['prediction_possible']) {
                $results['papers'][] = array_merge($prediction, [
                    'item_id' => $paper->id,
                    'item_name' => $paper->name,
                    'item_type' => 'paper',
                ]);
            }
        }

        // Estadísticas generales
        $allPredictions = collect(array_merge(
            $results['products'] ?? [],
            $results['papers'] ?? []
        ));

        $results['summary'] = [
            'total_predictions' => $allPredictions->count(),
            'critical_items' => $allPredictions->where('days_until_depletion', '<=', 7)->count(),
            'warning_items' => $allPredictions->whereBetween('days_until_depletion', [7, 14])->count(),
            'stable_items' => $allPredictions->where('days_until_depletion', '>', 14)->count(),
            'high_confidence' => $allPredictions->where('confidence_level', '>=', 0.8)->count(),
            'average_confidence' => round($allPredictions->avg('confidence_level'), 2),
        ];

        return $results;
    }

    /**
     * Obtener items que necesitan reabastecimiento pronto
     */
    public function getReorderAlerts(?int $companyId = null, int $daysThreshold = 14): array
    {
        $predictions = $this->generateBulkPredictions($companyId);
        $allPredictions = collect(array_merge(
            $predictions['products'] ?? [],
            $predictions['papers'] ?? []
        ));

        return [
            'urgent' => $allPredictions->where('days_until_depletion', '<=', 3)->values()->toArray(),
            'critical' => $allPredictions->whereBetween('days_until_depletion', [3, 7])->values()->toArray(),
            'warning' => $allPredictions->whereBetween('days_until_depletion', [7, $daysThreshold])->values()->toArray(),
            'summary' => [
                'urgent_count' => $allPredictions->where('days_until_depletion', '<=', 3)->count(),
                'critical_count' => $allPredictions->whereBetween('days_until_depletion', [3, 7])->count(),
                'warning_count' => $allPredictions->whereBetween('days_until_depletion', [7, $daysThreshold])->count(),
            ],
        ];
    }
}