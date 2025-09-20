<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsageMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'metric_type',
        'value',
        'unit',
        'period_start',
        'period_end',
        'metadata',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'period_start' => 'date',
        'period_end' => 'date',
        'metadata' => 'array',
    ];

    // Relaciones
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('metric_type', $type);
    }

    public function scopeForPeriod($query, $start, $end)
    {
        return $query->where('period_start', '>=', $start)
            ->where('period_end', '<=', $end);
    }

    public function scopeCurrentMonth($query)
    {
        return $query->where('period_start', '>=', now()->startOfMonth())
            ->where('period_end', '<=', now()->endOfMonth());
    }

    // Métodos estáticos para métricas comunes
    public static function recordUserCount(int $companyId, int $count, $periodStart = null, $periodEnd = null): self
    {
        return self::create([
            'company_id' => $companyId,
            'metric_type' => 'users',
            'value' => $count,
            'unit' => 'count',
            'period_start' => $periodStart ?? now()->startOfMonth(),
            'period_end' => $periodEnd ?? now()->endOfMonth(),
        ]);
    }

    public static function recordDocumentCount(int $companyId, int $count, $periodStart = null, $periodEnd = null): self
    {
        return self::create([
            'company_id' => $companyId,
            'metric_type' => 'documents',
            'value' => $count,
            'unit' => 'count',
            'period_start' => $periodStart ?? now()->startOfMonth(),
            'period_end' => $periodEnd ?? now()->endOfMonth(),
        ]);
    }

    public static function recordStorageUsage(int $companyId, float $sizeInMB, $periodStart = null, $periodEnd = null): self
    {
        return self::create([
            'company_id' => $companyId,
            'metric_type' => 'storage',
            'value' => $sizeInMB,
            'unit' => 'MB',
            'period_start' => $periodStart ?? now()->startOfMonth(),
            'period_end' => $periodEnd ?? now()->endOfMonth(),
        ]);
    }

    public static function recordApiCalls(int $companyId, int $count, $periodStart = null, $periodEnd = null): self
    {
        return self::create([
            'company_id' => $companyId,
            'metric_type' => 'api_calls',
            'value' => $count,
            'unit' => 'count',
            'period_start' => $periodStart ?? now()->startOfMonth(),
            'period_end' => $periodEnd ?? now()->endOfMonth(),
        ]);
    }

    // Métodos de agregación
    public static function getTotalForCompany(int $companyId, string $metricType, $start = null, $end = null): float
    {
        $query = self::where('company_id', $companyId)
            ->where('metric_type', $metricType);

        if ($start && $end) {
            $query->forPeriod($start, $end);
        }

        return (float) $query->sum('value');
    }

    public static function getAverageForCompany(int $companyId, string $metricType, $start = null, $end = null): float
    {
        $query = self::where('company_id', $companyId)
            ->where('metric_type', $metricType);

        if ($start && $end) {
            $query->forPeriod($start, $end);
        }

        return (float) $query->avg('value');
    }

    public function getFormattedValueAttribute(): string
    {
        return number_format($this->value, 2).($this->unit ? ' '.$this->unit : '');
    }
}
