<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Company extends Model
{
    use SoftDeletes;

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
        'subscription_plan',
        'subscription_expires_at',
        'max_users',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'subscription_expires_at' => 'datetime',
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

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, $plan)
    {
        return $query->where('subscription_plan', $plan);
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
}