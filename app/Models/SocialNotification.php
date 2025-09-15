<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialNotification extends Model
{
    use BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'sender_id',
        'type',
        'title',
        'message',
        'data',
        'read_at',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    // Tipos de notificaciones
    const TYPE_NEW_POST = 'new_post';
    const TYPE_POST_COMMENT = 'post_comment';
    const TYPE_POST_REACTION = 'post_reaction';
    const TYPE_POST_MENTION = 'post_mention';
    const TYPE_NEW_FOLLOWER = 'new_follower';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper methods
    public function isRead(): bool
    {
        return !is_null($this->read_at);
    }

    public function markAsRead(): bool
    {
        if ($this->isRead()) {
            return true;
        }

        return $this->update(['read_at' => now()]);
    }

    public function markAsUnread(): bool
    {
        return $this->update(['read_at' => null]);
    }

    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_NEW_POST => 'Nuevo Post',
            self::TYPE_POST_COMMENT => 'Nuevo Comentario',
            self::TYPE_POST_REACTION => 'Nueva Reacción',
            self::TYPE_POST_MENTION => 'Te mencionaron',
            self::TYPE_NEW_FOLLOWER => 'Nuevo Seguidor',
        ];
    }

    public function getTypeLabel(): string
    {
        return self::getTypeLabels()[$this->type] ?? 'Notificación';
    }

    public function getIcon(): string
    {
        return match($this->type) {
            self::TYPE_NEW_POST => '📝',
            self::TYPE_POST_COMMENT => '💬',
            self::TYPE_POST_REACTION => '❤️',
            self::TYPE_POST_MENTION => '👤',
            self::TYPE_NEW_FOLLOWER => '👥',
            default => '🔔',
        };
    }

    public function getColorClass(): string
    {
        return match($this->type) {
            self::TYPE_NEW_POST => 'text-blue-600',
            self::TYPE_POST_COMMENT => 'text-green-600',
            self::TYPE_POST_REACTION => 'text-red-600',
            self::TYPE_POST_MENTION => 'text-purple-600',
            self::TYPE_NEW_FOLLOWER => 'text-orange-600',
            default => 'text-gray-600',
        };
    }
}
