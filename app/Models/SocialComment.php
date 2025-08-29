<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialComment extends Model
{
    use HasFactory, BelongsToTenant, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'post_id',
        'parent_comment_id',
        'content',
        'is_public',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }

    public function parentComment(): BelongsTo
    {
        return $this->belongsTo(SocialComment::class, 'parent_comment_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SocialComment::class, 'parent_comment_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(SocialLike::class, 'comment_id');
    }

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_comment_id');
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function isReply(): bool
    {
        return !is_null($this->parent_comment_id);
    }

    public function getLikesCountAttribute(): int
    {
        return $this->likes()->count();
    }
}