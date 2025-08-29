<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialLike extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'company_id',
        'user_id',
        'post_id',
        'comment_id',
        'reaction_type',
    ];

    // Reaction types
    const TYPE_LIKE = 'like';
    const TYPE_LOVE = 'love';
    const TYPE_HELPFUL = 'helpful';
    const TYPE_INTERESTED = 'interested';

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class, 'comment_id');
    }

    public function scopeForPost($query, int $postId)
    {
        return $query->where('post_id', $postId);
    }

    public function scopeForComment($query, int $commentId)
    {
        return $query->where('comment_id', $commentId);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('reaction_type', $type);
    }

    public function getReactionLabel(): string
    {
        return match($this->reaction_type) {
            self::TYPE_LIKE => '👍 Me gusta',
            self::TYPE_LOVE => '❤️ Me encanta',
            self::TYPE_HELPFUL => '💡 Útil',
            self::TYPE_INTERESTED => '🤔 Me interesa',
            default => 'Reacción',
        };
    }

    public static function getReactionTypes(): array
    {
        return [
            self::TYPE_LIKE => '👍 Me gusta',
            self::TYPE_LOVE => '❤️ Me encanta',
            self::TYPE_HELPFUL => '💡 Útil',
            self::TYPE_INTERESTED => '🤔 Me interesa',
        ];
    }
}