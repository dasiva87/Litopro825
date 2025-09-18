<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'name',
        'stripe_id', // Reutilizamos para PayU transaction ID
        'stripe_status', // Reutilizamos para PayU status
        'stripe_price', // Reutilizamos para PayU plan
        'quantity',
        'trial_ends_at',
        'ends_at',
    ];

    protected $casts = [
        'trial_ends_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function active(): bool
    {
        return $this->stripe_status === 'active';
    }

    public function cancelled(): bool
    {
        return $this->stripe_status === 'cancelled';
    }

    public function ended(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }
}
