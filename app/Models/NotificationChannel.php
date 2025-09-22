<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class NotificationChannel extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'status',
        'config',
        'rate_limits',
        'retry_settings',
        'default_template',
        'format_settings',
        'filters',
        'business_hours',
        'allowed_event_types',
        'priority',
        'supports_realtime',
        'supports_bulk',
        'total_sent',
        'total_delivered',
        'total_failed',
        'last_used_at',
        'last_error',
        'created_by',
        'notes',
    ];

    protected $casts = [
        'config' => 'array',
        'rate_limits' => 'array',
        'retry_settings' => 'array',
        'format_settings' => 'array',
        'filters' => 'array',
        'business_hours' => 'array',
        'allowed_event_types' => 'array',
        'supports_realtime' => 'boolean',
        'supports_bulk' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    // Relationships
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function recentLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class)->where('created_at', '>=', now()->subDays(7));
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSupportsRealtime($query)
    {
        return $query->where('supports_realtime', true);
    }

    public function scopeByPriority($query, int $priority)
    {
        return $query->where('priority', $priority);
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isTesting(): bool
    {
        return $this->status === 'testing';
    }

    public function canSendEventType(string $eventType): bool
    {
        if (!$this->allowed_event_types) {
            return true; // Si no hay restricciones, permite todos
        }

        return in_array($eventType, $this->allowed_event_types);
    }

    public function isWithinBusinessHours(): bool
    {
        if (!$this->business_hours) {
            return true; // Si no hay restricciones de horario, siempre está disponible
        }

        $now = now();
        $dayOfWeek = strtolower($now->format('l')); // monday, tuesday, etc.
        $currentTime = $now->format('H:i');

        $businessHours = $this->business_hours;

        if (!isset($businessHours[$dayOfWeek])) {
            return false; // No hay configuración para este día
        }

        $dayConfig = $businessHours[$dayOfWeek];

        if (!$dayConfig['enabled']) {
            return false; // Este día está deshabilitado
        }

        $startTime = $dayConfig['start'] ?? '09:00';
        $endTime = $dayConfig['end'] ?? '17:00';

        return $currentTime >= $startTime && $currentTime <= $endTime;
    }

    public function canSendNow(): bool
    {
        return $this->isActive() && $this->isWithinBusinessHours();
    }

    public function checkRateLimit(): bool
    {
        if (!$this->rate_limits) {
            return true; // Sin límites
        }

        $rateLimits = $this->rate_limits;

        // Verificar límite por minuto
        if (isset($rateLimits['per_minute'])) {
            $count = $this->notificationLogs()
                ->where('created_at', '>=', now()->subMinute())
                ->count();

            if ($count >= $rateLimits['per_minute']) {
                return false;
            }
        }

        // Verificar límite por hora
        if (isset($rateLimits['per_hour'])) {
            $count = $this->notificationLogs()
                ->where('created_at', '>=', now()->subHour())
                ->count();

            if ($count >= $rateLimits['per_hour']) {
                return false;
            }
        }

        // Verificar límite por día
        if (isset($rateLimits['per_day'])) {
            $count = $this->notificationLogs()
                ->where('created_at', '>=', now()->startOfDay())
                ->count();

            if ($count >= $rateLimits['per_day']) {
                return false;
            }
        }

        return true;
    }

    public function getSuccessRate(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        return round(($this->total_delivered / $this->total_sent) * 100, 2);
    }

    public function getFailureRate(): float
    {
        if ($this->total_sent === 0) {
            return 0;
        }

        return round(($this->total_failed / $this->total_sent) * 100, 2);
    }

    public function getRecentActivity(int $days = 7): array
    {
        $logs = $this->notificationLogs()
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, status')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get();

        return $logs->groupBy('date')->map(function ($dayLogs) {
            return [
                'total' => $dayLogs->sum('count'),
                'sent' => $dayLogs->where('status', 'sent')->sum('count'),
                'delivered' => $dayLogs->where('status', 'delivered')->sum('count'),
                'failed' => $dayLogs->where('status', 'failed')->sum('count'),
            ];
        })->toArray();
    }

    public function test(string $recipient, string $message = null): NotificationLog
    {
        $message = $message ?? "Test notification from channel: {$this->name}";

        return $this->notificationLogs()->create([
            'event_type' => 'test',
            'recipient_type' => $this->getRecipientType(),
            'recipient_identifier' => $recipient,
            'recipient_name' => 'Test Recipient',
            'subject' => 'Test Notification',
            'message' => $message,
            'channel_type' => $this->type,
            'priority' => 'normal',
            'is_test' => true,
        ]);
    }

    private function getRecipientType(): string
    {
        return match($this->type) {
            'email' => 'email',
            'slack' => 'slack_channel',
            'teams' => 'teams_channel',
            'discord' => 'discord_channel',
            'sms' => 'phone',
            'webhook' => 'url',
            default => 'unknown',
        };
    }

    public function updateStats(string $status): void
    {
        $this->increment('total_sent');

        if ($status === 'delivered') {
            $this->increment('total_delivered');
        } elseif ($status === 'failed') {
            $this->increment('total_failed');
        }

        $this->update(['last_used_at' => now()]);
    }

    public function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    public function setConfigValue(string $key, $value): void
    {
        $config = $this->config ?? [];
        data_set($config, $key, $value);
        $this->update(['config' => $config]);
    }

    public function getRetryDelay(int $attemptNumber): int
    {
        $retrySettings = $this->retry_settings ?? [];
        $strategy = $retrySettings['strategy'] ?? 'exponential';
        $baseDelay = $retrySettings['base_delay'] ?? 60; // seconds

        return match($strategy) {
            'linear' => $baseDelay * $attemptNumber,
            'exponential' => $baseDelay * (2 ** ($attemptNumber - 1)),
            'fixed' => $baseDelay,
            default => $baseDelay * $attemptNumber,
        };
    }

    public function getMaxRetries(): int
    {
        return $this->retry_settings['max_retries'] ?? 3;
    }

    public static function getAvailableTypes(): array
    {
        return [
            'email' => 'Email',
            'slack' => 'Slack',
            'teams' => 'Microsoft Teams',
            'discord' => 'Discord',
            'webhook' => 'Webhook',
            'sms' => 'SMS',
            'push' => 'Push Notification',
            'database' => 'Database',
        ];
    }

    public static function getDefaultConfig(string $type): array
    {
        return match($type) {
            'email' => [
                'smtp_host' => '',
                'smtp_port' => 587,
                'smtp_username' => '',
                'smtp_password' => '',
                'from_address' => '',
                'from_name' => '',
            ],
            'slack' => [
                'webhook_url' => '',
                'channel' => '#general',
                'username' => 'LitoPro Bot',
                'icon_emoji' => ':robot_face:',
            ],
            'teams' => [
                'webhook_url' => '',
                'theme_color' => '0076D7',
            ],
            'discord' => [
                'webhook_url' => '',
                'username' => 'LitoPro Bot',
                'avatar_url' => '',
            ],
            'webhook' => [
                'url' => '',
                'method' => 'POST',
                'headers' => [],
                'auth_type' => 'none',
            ],
            'sms' => [
                'provider' => 'twilio',
                'account_sid' => '',
                'auth_token' => '',
                'from_number' => '',
            ],
            'push' => [
                'provider' => 'fcm',
                'server_key' => '',
                'project_id' => '',
            ],
            default => [],
        };
    }
}
