<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyFollower extends Model
{
    use HasFactory;

    protected $fillable = [
        'follower_company_id',
        'followed_company_id',
        'user_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relaciones
    public function followerCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'follower_company_id');
    }

    public function followedCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'followed_company_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where(function ($q) use ($companyId) {
            $q->where('follower_company_id', $companyId)
              ->orWhere('followed_company_id', $companyId);
        });
    }

    public function scopeFollowing($query, $companyId)
    {
        return $query->where('follower_company_id', $companyId);
    }

    public function scopeFollowers($query, $companyId)
    {
        return $query->where('followed_company_id', $companyId);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Métodos estáticos de utilidad
    public static function isFollowing(int $followerCompanyId, int $followedCompanyId): bool
    {
        return static::where('follower_company_id', $followerCompanyId)
            ->where('followed_company_id', $followedCompanyId)
            ->exists();
    }

    public static function follow(int $followerCompanyId, int $followedCompanyId, int $userId): self
    {
        return static::firstOrCreate(
            [
                'follower_company_id' => $followerCompanyId,
                'followed_company_id' => $followedCompanyId,
            ],
            ['user_id' => $userId]
        );
    }

    public static function unfollow(int $followerCompanyId, int $followedCompanyId): bool
    {
        return static::where('follower_company_id', $followerCompanyId)
            ->where('followed_company_id', $followedCompanyId)
            ->delete() > 0;
    }

    public static function getFollowersCount(int $companyId): int
    {
        return static::where('followed_company_id', $companyId)->count();
    }

    public static function getFollowingCount(int $companyId): int
    {
        return static::where('follower_company_id', $companyId)->count();
    }
}