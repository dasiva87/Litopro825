<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'event',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    // Relaciones
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Métodos estáticos para logging común
    public static function logLogin(User $user, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return self::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'event' => 'login',
            'properties' => [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    public static function logLogout(User $user, ?string $ipAddress = null, ?string $userAgent = null): self
    {
        return self::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'event' => 'logout',
            'properties' => [
                'user_name' => $user->name,
                'user_email' => $user->email,
            ],
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
        ]);
    }

    public static function logModelEvent(string $event, Model $subject, ?User $user = null, array $properties = []): self
    {
        $user = $user ?? auth()->user();

        return self::create([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'event' => $event,
            'subject_type' => get_class($subject),
            'subject_id' => $subject->id,
            'properties' => array_merge([
                'subject_name' => $subject->name ?? $subject->title ?? 'N/A',
            ], $properties),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    public static function logSystemEvent(string $event, array $properties = [], ?User $user = null): self
    {
        return self::create([
            'company_id' => $user?->company_id,
            'user_id' => $user?->id,
            'event' => $event,
            'properties' => $properties,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    // Helpers
    public function getDescription(): string
    {
        $userName = $this->user?->name ?? 'Sistema';
        $subjectName = $this->properties['subject_name'] ?? $this->properties['user_name'] ?? 'N/A';

        return match ($this->event) {
            'login' => "{$userName} inició sesión",
            'logout' => "{$userName} cerró sesión",
            'create' => "{$userName} creó {$subjectName}",
            'update' => "{$userName} actualizó {$subjectName}",
            'delete' => "{$userName} eliminó {$subjectName}",
            'view' => "{$userName} vio {$subjectName}",
            default => "{$userName} realizó acción '{$this->event}' en {$subjectName}",
        };
    }

    public function getEventColor(): string
    {
        return match ($this->event) {
            'login' => 'success',
            'logout' => 'warning',
            'create' => 'info',
            'update' => 'primary',
            'delete' => 'danger',
            'view' => 'gray',
            default => 'gray',
        };
    }
}
