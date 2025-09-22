<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'stripe_price_id',
        'price',
        'currency',
        'interval',
        'trial_days',
        'features',
        'limits',
        'payment_methods',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'limits' => 'array',
        'payment_methods' => 'array',
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'stripe_price', 'stripe_price_id');
    }

    public function activeSubscriptions(): HasMany
    {
        return $this->subscriptions()->where('stripe_status', 'active');
    }

    public function getFormattedPriceAttribute(): string
    {
        return '$'.number_format($this->price, 2).'/'.$this->interval;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function isFree(): bool
    {
        return $this->price == 0 || $this->slug === 'free';
    }

    public function scopeFree($query)
    {
        return $query->where('price', 0)->orWhere('slug', 'free');
    }
}
