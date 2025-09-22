<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PlanExperiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'control_plan_id',
        'variant_plan_id',
        'traffic_split',
        'confidence_level',
        'min_sample_size',
        'started_at',
        'ended_at',
        'duration_days',
        'target_metrics',
        'results',
        'statistical_significance',
        'winner',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'target_metrics' => 'array',
        'results' => 'array',
        'confidence_level' => 'decimal:2',
        'statistical_significance' => 'decimal:2',
    ];

    // No aplicar TenantScope ya que los experimentos son globales del sistema
    protected static function booted()
    {
        // Los experimentos de planes son gestionados solo por Super Admin
        // No necesitan tenant scope
    }

    public function controlPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'control_plan_id');
    }

    public function variantPlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'variant_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    // Métodos de utilidad
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->started_at &&
               $this->started_at->isPast() &&
               (!$this->ended_at || $this->ended_at->isFuture());
    }

    public function getDaysRunning(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endDate = $this->ended_at ?? now();
        return $this->started_at->diffInDays($endDate);
    }

    public function getPlannedEndDate(): ?\Carbon\Carbon
    {
        if (!$this->started_at) {
            return null;
        }

        return $this->started_at->addDays($this->duration_days);
    }

    public function getPlannedEndDateAttribute(): ?\Carbon\Carbon
    {
        return $this->getPlannedEndDate();
    }

    public function shouldEnd(): bool
    {
        if (!$this->isActive()) {
            return false;
        }

        $plannedEnd = $this->getPlannedEndDate();
        return $plannedEnd && $plannedEnd->isPast();
    }

    public function calculateResults(): array
    {
        // Obtener métricas de ambos planes
        $controlMetrics = $this->getMetricsForPlan($this->controlPlan);
        $variantMetrics = $this->getMetricsForPlan($this->variantPlan);

        // Calcular significancia estadística (simplified)
        $conversionDiff = $variantMetrics['conversion_rate'] - $controlMetrics['conversion_rate'];
        $confidence = $this->calculateStatisticalSignificance($controlMetrics, $variantMetrics);

        // Determinar ganador
        $winner = 'inconclusive';
        if ($confidence >= $this->confidence_level) {
            $winner = $conversionDiff > 0 ? 'variant' : 'control';
        }

        return [
            'control' => $controlMetrics,
            'variant' => $variantMetrics,
            'conversion_lift' => $conversionDiff,
            'confidence' => $confidence,
            'winner' => $winner,
            'sample_size' => $controlMetrics['sample_size'] + $variantMetrics['sample_size'],
        ];
    }

    private function getMetricsForPlan(Plan $plan): array
    {
        // Obtener suscripciones del plan durante el experimento
        $subscriptions = Subscription::where(function ($query) use ($plan) {
                $query->where('stripe_price', $plan->stripe_price_id)
                    ->orWhere('stripe_price', 'plan_' . $plan->id)
                    ->orWhere('stripe_price', 'price_' . $plan->id);
            })
            ->when($this->started_at, function ($query) {
                $query->where('created_at', '>=', $this->started_at);
            })
            ->when($this->ended_at, function ($query) {
                $query->where('created_at', '<=', $this->ended_at);
            });

        $totalSubscriptions = $subscriptions->count();
        $activeSubscriptions = $subscriptions->where('stripe_status', 'active')->count();

        // Calcular métricas
        $conversionRate = $totalSubscriptions > 0 ? ($activeSubscriptions / $totalSubscriptions) * 100 : 0;

        // Calcular revenue (simplificado)
        $monthlyPrice = match ($plan->interval) {
            'year' => $plan->price / 12,
            'week' => $plan->price * 4.33,
            'day' => $plan->price * 30,
            default => $plan->price,
        };

        $totalRevenue = $activeSubscriptions * $monthlyPrice;
        $revenuePerUser = $totalSubscriptions > 0 ? $totalRevenue / $totalSubscriptions : 0;

        return [
            'sample_size' => $totalSubscriptions,
            'conversions' => $activeSubscriptions,
            'conversion_rate' => round($conversionRate, 2),
            'total_revenue' => $totalRevenue,
            'revenue_per_user' => round($revenuePerUser, 2),
        ];
    }

    private function calculateStatisticalSignificance(array $control, array $variant): float
    {
        // Simplified statistical significance calculation
        // En producción se usaría una librería estadística más robusta

        $n1 = $control['sample_size'];
        $n2 = $variant['sample_size'];
        $x1 = $control['conversions'];
        $x2 = $variant['conversions'];

        if ($n1 < 30 || $n2 < 30) {
            return 0; // Muestra muy pequeña
        }

        $p1 = $n1 > 0 ? $x1 / $n1 : 0;
        $p2 = $n2 > 0 ? $x2 / $n2 : 0;
        $p_pooled = ($x1 + $x2) / ($n1 + $n2);

        $se = sqrt($p_pooled * (1 - $p_pooled) * (1/$n1 + 1/$n2));

        if ($se == 0) {
            return 0;
        }

        $z = abs($p2 - $p1) / $se;

        // Aproximación de p-value a confidence level
        // Z > 1.96 ≈ 95% confidence
        // Z > 2.58 ≈ 99% confidence

        if ($z >= 2.58) return 99.0;
        if ($z >= 1.96) return 95.0;
        if ($z >= 1.64) return 90.0;

        return max(0, 50 + ($z / 1.96) * 45); // Scaling aproximado
    }
}