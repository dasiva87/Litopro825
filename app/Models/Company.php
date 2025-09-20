<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Company extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'email',
        'phone',
        'address',
        'city_id',
        'state_id',
        'country_id',
        'tax_id',
        'logo',
        'website',
        'bio',
        'avatar',
        'banner',
        'facebook',
        'instagram',
        'twitter',
        'linkedin',
        'is_public',
        'allow_followers',
        'show_contact_info',
        'followers_count',
        'following_count',
        'posts_count',
        'subscription_plan',
        'subscription_expires_at',
        'max_users',
        'is_active',
        'status',
        'suspended_at',
        'suspension_reason',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_public' => 'boolean',
        'allow_followers' => 'boolean',
        'show_contact_info' => 'boolean',
        'subscription_expires_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($company) {
            if (empty($company->slug)) {
                $company->slug = Str::slug($company->name);
            }
        });

        static::created(function ($company) {
            // Auto-crear configuraciones de empresa
            $company->settings()->create([]);
        });
    }

    // Relaciones geográficas
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    // Relaciones del negocio
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function settings(): HasOne
    {
        return $this->hasOne(CompanySettings::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function papers(): HasMany
    {
        return $this->hasMany(Paper::class);
    }

    public function printingMachines(): HasMany
    {
        return $this->hasMany(PrintingMachine::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    // Relaciones de seguimiento
    public function followers(): HasMany
    {
        return $this->hasMany(CompanyFollower::class, 'followed_company_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(CompanyFollower::class, 'follower_company_id');
    }

    // Relaciones del sistema de facturación
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function usageMetrics(): HasMany
    {
        return $this->hasMany(UsageMetric::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, $plan)
    {
        return $query->where('subscription_plan', $plan);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    public function scopeOnTrial($query)
    {
        return $query->where('status', 'trial');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // Métodos de negocio
    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function isSubscriptionActive(): bool
    {
        return $this->subscription_expires_at === null ||
               $this->subscription_expires_at->isFuture();
    }

    // Helper methods for profile
    public function getAvatarUrl(): ?string
    {
        return $this->avatar ? Storage::url($this->avatar) : null;
    }

    public function getBannerUrl(): ?string
    {
        return $this->banner ? Storage::url($this->banner) : null;
    }

    public function getInitials(): string
    {
        return strtoupper(substr($this->name, 0, 2));
    }

    public function getProfileUrl(): string
    {
        return route('company.profile', $this->slug);
    }

    // Métodos de seguimiento
    public function isFollowing(Company $company): bool
    {
        return CompanyFollower::isFollowing($this->id, $company->id);
    }

    public function isFollowedBy(Company $company): bool
    {
        return CompanyFollower::isFollowing($company->id, $this->id);
    }

    public function follow(Company $company, User $user): CompanyFollower
    {
        $follow = CompanyFollower::follow($this->id, $company->id, $user->id);

        // Actualizar contadores
        $this->increment('following_count');
        $company->increment('followers_count');

        return $follow;
    }

    public function unfollow(Company $company): bool
    {
        $unfollowed = CompanyFollower::unfollow($this->id, $company->id);

        if ($unfollowed) {
            // Actualizar contadores
            $this->decrement('following_count');
            $company->decrement('followers_count');
        }

        return $unfollowed;
    }

    public function getFollowersCount(): int
    {
        return CompanyFollower::getFollowersCount($this->id);
    }

    public function getFollowingCount(): int
    {
        return CompanyFollower::getFollowingCount($this->id);
    }

    /**
     * PayU Subscription Methods
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscription_plan &&
               $this->subscription_plan !== 'free' &&
               $this->subscription_expires_at &&
               $this->subscription_expires_at->isFuture();
    }

    public function getCurrentPlan(): ?Plan
    {
        if (! $this->subscription_plan || $this->subscription_plan === 'free') {
            return null;
        }

        return Plan::where('name', $this->subscription_plan)->first();
    }

    // PayU subscriptions - to be implemented
    // public function payuSubscriptions()
    // {
    //     return $this->hasMany(PayuSubscription::class, 'company_id');
    // }

    /**
     * Status Management Methods
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && $this->is_active;
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isOnTrial(): bool
    {
        return $this->status === 'trial';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function suspend(?string $reason = null): void
    {
        $this->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspension_reason' => $reason,
        ]);

        ActivityLog::logSystemEvent('company_suspended', [
            'company_name' => $this->name,
            'reason' => $reason,
        ]);
    }

    public function reactivate(): void
    {
        $this->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspension_reason' => null,
        ]);

        ActivityLog::logSystemEvent('company_reactivated', [
            'company_name' => $this->name,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'suspension_reason' => $reason,
        ]);

        ActivityLog::logSystemEvent('company_cancelled', [
            'company_name' => $this->name,
            'reason' => $reason,
        ]);
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'active' => 'success',
            'trial' => 'info',
            'suspended' => 'warning',
            'cancelled' => 'danger',
            'pending' => 'secondary',
            default => 'gray',
        };
    }

    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'active' => 'Activo',
            'trial' => 'Prueba',
            'suspended' => 'Suspendido',
            'cancelled' => 'Cancelado',
            'pending' => 'Pendiente',
            default => 'Desconocido',
        };
    }
}
