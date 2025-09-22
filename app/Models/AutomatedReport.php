<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class AutomatedReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'report_type',
        'data_sources',
        'metrics',
        'filters',
        'grouping',
        'frequency',
        'custom_cron',
        'day_of_month',
        'day_of_week',
        'time_of_day',
        'timezone',
        'recipients',
        'delivery_methods',
        'format',
        'include_charts',
        'include_raw_data',
        'template',
        'chart_configs',
        'custom_message',
        'branding',
        'retention_days',
        'archive_reports',
        'alert_conditions',
        'alert_thresholds',
        'send_only_on_changes',
        'last_run_at',
        'next_run_at',
        'execution_count',
        'last_error',
        'last_status',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'data_sources' => 'array',
        'metrics' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'recipients' => 'array',
        'delivery_methods' => 'array',
        'chart_configs' => 'array',
        'branding' => 'array',
        'alert_conditions' => 'array',
        'alert_thresholds' => 'array',
        'include_charts' => 'boolean',
        'include_raw_data' => 'boolean',
        'archive_reports' => 'boolean',
        'send_only_on_changes' => 'boolean',
        'last_run_at' => 'datetime',
        'next_run_at' => 'datetime',
        'time_of_day' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function executions(): HasMany
    {
        return $this->hasMany(ReportExecution::class)->orderBy('created_at', 'desc');
    }

    public function latestExecution(): HasMany
    {
        return $this->hasMany(ReportExecution::class)->latest()->limit(1);
    }

    public function successfulExecutions(): HasMany
    {
        return $this->hasMany(ReportExecution::class)->where('status', 'completed');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueForExecution($query)
    {
        return $query->active()
                    ->where('next_run_at', '<=', now());
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('report_type', $type);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isDueForExecution(): bool
    {
        return $this->isActive() &&
               $this->next_run_at &&
               $this->next_run_at->isPast();
    }

    public function calculateNextRunTime(): Carbon
    {
        $baseTime = Carbon::parse($this->time_of_day)->setTimezone($this->timezone);
        $now = now()->setTimezone($this->timezone);

        return match($this->frequency) {
            'daily' => $now->addDay()->setTime($baseTime->hour, $baseTime->minute),
            'weekly' => $this->calculateNextWeekly($baseTime),
            'monthly' => $this->calculateNextMonthly($baseTime),
            'quarterly' => $this->calculateNextQuarterly($baseTime),
            'yearly' => $this->calculateNextYearly($baseTime),
            'custom' => $this->calculateNextCustom(),
            default => $now->addMonth()->setTime($baseTime->hour, $baseTime->minute),
        };
    }

    private function calculateNextWeekly(Carbon $baseTime): Carbon
    {
        $nextRun = now()->setTimezone($this->timezone);
        $targetDay = $this->day_of_week ?? 1; // Default Monday

        // Find next occurrence of target day
        $daysUntilTarget = ($targetDay - $nextRun->dayOfWeek + 7) % 7;
        if ($daysUntilTarget === 0 && $nextRun->format('H:i') >= $baseTime->format('H:i')) {
            $daysUntilTarget = 7; // Next week if time has passed today
        }

        return $nextRun->addDays($daysUntilTarget)->setTime($baseTime->hour, $baseTime->minute);
    }

    private function calculateNextMonthly(Carbon $baseTime): Carbon
    {
        $nextRun = now()->setTimezone($this->timezone);
        $targetDay = $this->day_of_month ?? 1;

        // Set to target day of current month
        $nextRun->day = min($targetDay, $nextRun->daysInMonth);

        // If day has passed or time has passed today, move to next month
        if ($nextRun->day < now()->day ||
            ($nextRun->day === now()->day && $nextRun->format('H:i') >= $baseTime->format('H:i'))) {
            $nextRun->addMonth();
            $nextRun->day = min($targetDay, $nextRun->daysInMonth);
        }

        return $nextRun->setTime($baseTime->hour, $baseTime->minute);
    }

    private function calculateNextQuarterly(Carbon $baseTime): Carbon
    {
        $nextRun = now()->setTimezone($this->timezone);

        // Calculate next quarter
        $currentQuarter = ceil($nextRun->month / 3);
        $nextQuarter = $currentQuarter + 1;

        if ($nextQuarter > 4) {
            $nextQuarter = 1;
            $nextRun->addYear();
        }

        $nextRun->month = ($nextQuarter - 1) * 3 + 1;
        $nextRun->day = min($this->day_of_month ?? 1, $nextRun->daysInMonth);

        return $nextRun->setTime($baseTime->hour, $baseTime->minute);
    }

    private function calculateNextYearly(Carbon $baseTime): Carbon
    {
        $nextRun = now()->setTimezone($this->timezone);
        $nextRun->addYear();
        $nextRun->month = 1;
        $nextRun->day = min($this->day_of_month ?? 1, $nextRun->daysInMonth);

        return $nextRun->setTime($baseTime->hour, $baseTime->minute);
    }

    private function calculateNextCustom(): Carbon
    {
        if (!$this->custom_cron) {
            return now()->addHour(); // Fallback
        }

        // Simple cron parsing - for production use a proper cron library
        // This is a simplified implementation
        return now()->addHour();
    }

    public function updateNextRunTime(): void
    {
        $this->update([
            'next_run_at' => $this->calculateNextRunTime(),
        ]);
    }

    public function getAvailableMetrics(): array
    {
        return match($this->report_type) {
            'financial' => [
                'mrr' => 'Monthly Recurring Revenue',
                'arr' => 'Annual Recurring Revenue',
                'total_revenue' => 'Revenue Total',
                'average_deal_size' => 'Tamaño Promedio de Deal',
                'conversion_rate' => 'Tasa de Conversión',
                'churn_rate' => 'Tasa de Churn',
                'customer_lifetime_value' => 'Customer Lifetime Value',
                'cost_per_acquisition' => 'Costo por Adquisición',
            ],
            'subscription_metrics' => [
                'new_subscriptions' => 'Nuevas Suscripciones',
                'cancelled_subscriptions' => 'Suscripciones Canceladas',
                'subscription_growth_rate' => 'Tasa de Crecimiento',
                'trial_conversion_rate' => 'Conversión de Trial',
                'subscription_distribution' => 'Distribución por Plan',
                'payment_failures' => 'Fallas de Pago',
            ],
            'user_activity' => [
                'active_users' => 'Usuarios Activos',
                'new_registrations' => 'Nuevos Registros',
                'user_engagement' => 'Engagement de Usuario',
                'feature_usage' => 'Uso de Features',
                'session_duration' => 'Duración de Sesión',
                'page_views' => 'Vistas de Página',
            ],
            'system_performance' => [
                'response_times' => 'Tiempos de Respuesta',
                'error_rates' => 'Tasas de Error',
                'uptime' => 'Tiempo de Actividad',
                'database_performance' => 'Performance de BD',
                'api_usage' => 'Uso de API',
            ],
            default => [],
        };
    }

    public function getFormattedRecipients(): string
    {
        $recipients = $this->recipients ?? [];
        return implode(', ', array_slice($recipients, 0, 3)) .
               (count($recipients) > 3 ? ' y ' . (count($recipients) - 3) . ' más' : '');
    }

    public function getLastExecutionStatus(): ?string
    {
        return $this->executions()->first()?->status;
    }

    public function getSuccessRate(): float
    {
        $total = $this->executions()->count();
        if ($total === 0) return 0;

        $successful = $this->successfulExecutions()->count();
        return round(($successful / $total) * 100, 1);
    }

    public function execute(string $triggerType = 'scheduled', ?User $triggeredBy = null): ReportExecution
    {
        $execution = $this->executions()->create([
            'status' => 'pending',
            'trigger_type' => $triggerType,
            'triggered_by' => $triggeredBy?->id,
        ]);

        // Queue the actual report generation
        // dispatch(new GenerateAutomatedReportJob($execution));

        return $execution;
    }

    public static function getAvailableReportTypes(): array
    {
        return [
            'financial' => 'Reportes Financieros',
            'subscription_metrics' => 'Métricas de Suscripciones',
            'user_activity' => 'Actividad de Usuarios',
            'system_performance' => 'Performance del Sistema',
            'custom' => 'Reporte Personalizado',
        ];
    }

    public static function getAvailableFrequencies(): array
    {
        return [
            'daily' => 'Diario',
            'weekly' => 'Semanal',
            'monthly' => 'Mensual',
            'quarterly' => 'Trimestral',
            'yearly' => 'Anual',
            'custom' => 'Personalizado (Cron)',
        ];
    }

    public static function getAvailableFormats(): array
    {
        return [
            'pdf' => 'PDF',
            'excel' => 'Excel (XLSX)',
            'csv' => 'CSV',
            'html' => 'HTML',
            'json' => 'JSON',
        ];
    }

    public static function getAvailableDeliveryMethods(): array
    {
        return [
            'email' => 'Email',
            'slack' => 'Slack',
            'teams' => 'Microsoft Teams',
            'webhook' => 'Webhook',
            'ftp' => 'FTP/SFTP',
            'cloud_storage' => 'Cloud Storage',
        ];
    }
}
