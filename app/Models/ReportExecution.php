<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReportExecution extends Model
{
    use HasFactory;

    protected $fillable = [
        'automated_report_id',
        'status',
        'started_at',
        'completed_at',
        'execution_time_seconds',
        'generated_data',
        'file_path',
        'file_format',
        'file_size_bytes',
        'file_hash',
        'delivery_status',
        'recipients_count',
        'successful_deliveries',
        'failed_deliveries',
        'rows_processed',
        'records_included',
        'data_period',
        'error_message',
        'execution_log',
        'warnings',
        'has_significant_changes',
        'change_summary',
        'variance_percentage',
        'trigger_type',
        'triggered_by',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'generated_data' => 'array',
        'delivery_status' => 'array',
        'data_period' => 'array',
        'execution_log' => 'array',
        'warnings' => 'array',
        'change_summary' => 'array',
        'has_significant_changes' => 'boolean',
        'variance_percentage' => 'decimal:4',
    ];

    // Relationships
    public function automatedReport(): BelongsTo
    {
        return $this->belongsTo(AutomatedReport::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper methods
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    public function start(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);
    }

    public function complete(array $data = []): void
    {
        $executionTime = $this->started_at ?
            now()->diffInSeconds($this->started_at) :
            null;

        $updateData = array_merge($data, [
            'status' => 'completed',
            'completed_at' => now(),
            'execution_time_seconds' => $executionTime,
        ]);

        $this->update($updateData);

        // Update parent report
        $this->automatedReport->update([
            'last_run_at' => now(),
            'execution_count' => $this->automatedReport->execution_count + 1,
            'last_status' => 'success',
            'last_error' => null,
        ]);

        $this->automatedReport->updateNextRunTime();
    }

    public function fail(string $errorMessage, array $additionalData = []): void
    {
        $executionTime = $this->started_at ?
            now()->diffInSeconds($this->started_at) :
            null;

        $updateData = array_merge($additionalData, [
            'status' => 'failed',
            'completed_at' => now(),
            'execution_time_seconds' => $executionTime,
            'error_message' => $errorMessage,
        ]);

        $this->update($updateData);

        // Update parent report
        $this->automatedReport->update([
            'last_run_at' => now(),
            'execution_count' => $this->automatedReport->execution_count + 1,
            'last_status' => 'failed',
            'last_error' => $errorMessage,
        ]);
    }

    public function getExecutionDuration(): ?string
    {
        if (!$this->execution_time_seconds) {
            return null;
        }

        $seconds = $this->execution_time_seconds;

        if ($seconds < 60) {
            return "{$seconds}s";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes}m {$remainingSeconds}s";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours}h {$remainingMinutes}m {$remainingSeconds}s";
    }

    public function getFormattedFileSize(): ?string
    {
        if (!$this->file_size_bytes) {
            return null;
        }

        $bytes = $this->file_size_bytes;
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }

    public function getDeliverySuccessRate(): float
    {
        if ($this->recipients_count === 0) {
            return 0;
        }

        return round(($this->successful_deliveries / $this->recipients_count) * 100, 1);
    }

    public function hasSignificantChanges(): bool
    {
        return $this->has_significant_changes ?? false;
    }

    public function getDataPeriodFormatted(): ?string
    {
        if (!$this->data_period) {
            return null;
        }

        $from = Carbon::parse($this->data_period['from'] ?? null);
        $to = Carbon::parse($this->data_period['to'] ?? null);

        if (!$from || !$to) {
            return null;
        }

        return $from->format('M j, Y') . ' - ' . $to->format('M j, Y');
    }

    public function addLog(string $level, string $message, array $context = []): void
    {
        $logs = $this->execution_log ?? [];

        $logs[] = [
            'timestamp' => now()->toISOString(),
            'level' => $level,
            'message' => $message,
            'context' => $context,
        ];

        $this->update(['execution_log' => $logs]);
    }

    public function addWarning(string $message, array $context = []): void
    {
        $warnings = $this->warnings ?? [];

        $warnings[] = [
            'timestamp' => now()->toISOString(),
            'message' => $message,
            'context' => $context,
        ];

        $this->update(['warnings' => $warnings]);
    }

    public function updateDeliveryStatus(string $method, bool $success, ?string $error = null): void
    {
        $deliveryStatus = $this->delivery_status ?? [];

        $deliveryStatus[$method] = [
            'success' => $success,
            'timestamp' => now()->toISOString(),
            'error' => $error,
        ];

        $this->update(['delivery_status' => $deliveryStatus]);

        // Update counters
        if ($success) {
            $this->increment('successful_deliveries');
        } else {
            $this->increment('failed_deliveries');
        }
    }

    public function calculateVarianceFromPrevious(): ?float
    {
        $previousExecution = $this->automatedReport
            ->executions()
            ->completed()
            ->where('id', '<', $this->id)
            ->first();

        if (!$previousExecution || !$previousExecution->generated_data) {
            return null;
        }

        // Simple variance calculation based on primary metric
        $currentValue = $this->getPrimaryMetricValue();
        $previousValue = $previousExecution->getPrimaryMetricValue();

        if (!$currentValue || !$previousValue || $previousValue == 0) {
            return null;
        }

        return round((($currentValue - $previousValue) / $previousValue) * 100, 4);
    }

    private function getPrimaryMetricValue(): ?float
    {
        if (!$this->generated_data) {
            return null;
        }

        // Get the first numeric value from generated data
        foreach ($this->generated_data as $value) {
            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }
}
