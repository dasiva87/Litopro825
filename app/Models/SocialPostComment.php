<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SocialPostComment extends Model
{
    use HasFactory, SoftDeletes;

    // NOTA: NO usa BelongsToTenant porque los comentarios deben ser cross-tenant
    // (usuarios de una empresa pueden comentar posts de otras empresas)

    protected $fillable = [
        'company_id',
        'post_id',
        'user_id',
        'content',
        'is_private',
    ];

    protected $casts = [
        'is_private' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublic($query)
    {
        return $query->where('is_private', false);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}