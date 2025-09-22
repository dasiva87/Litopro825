<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnterprisePlan extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'company_id',
        'base_plan_id',
        'custom_price',
        'custom_interval',
        'custom_interval_count',
        'discount_percentage',
        'custom_limits',
        'additional_features',
        'removed_features',
        'custom_billing_cycle',
        'billing_day',
        'payment_terms',
        'requires_po',
        'billing_notes',
        'sla_terms',
        'support_tier',
        'dedicated_support',
        'account_manager_email',
        'api_rate_limit',
        'white_labeling',
        'custom_integrations',
        'single_sign_on',
        'security_requirements',
        'effective_date',
        'expiration_date',
        'contract_length_months',
        'auto_renewal',
        'approval_status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'created_by',
        'sales_rep_id',
        'internal_notes',
        'contract_documents',
    ];

    protected $casts = [
        'custom_limits' => 'array',
        'additional_features' => 'array',
        'removed_features' => 'array',
        'sla_terms' => 'array',
        'custom_integrations' => 'array',
        'security_requirements' => 'array',
        'contract_documents' => 'array',
        'effective_date' => 'datetime',
        'expiration_date' => 'datetime',
        'approved_at' => 'datetime',
        'custom_price' => 'decimal:2',
        'discount_percentage' => 'decimal:2',
        'custom_billing_cycle' => 'boolean',
        'requires_po' => 'boolean',
        'dedicated_support' => 'boolean',
        'white_labeling' => 'boolean',
        'single_sign_on' => 'boolean',
        'auto_renewal' => 'boolean',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function basePlan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'base_plan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function salesRep(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sales_rep_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePendingApproval($query)
    {
        return $query->where('approval_status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('approval_status', 'approved');
    }

    public function scopeEffective($query)
    {
        return $query->where('effective_date', '<=', now())
                    ->where(function ($q) {
                        $q->whereNull('expiration_date')
                          ->orWhere('expiration_date', '>', now());
                    });
    }

    // Helper methods
    public function isActive(): bool
    {
        return $this->status === 'active' &&
               $this->approval_status === 'approved' &&
               $this->isEffective();
    }

    public function isEffective(): bool
    {
        $effectiveStart = $this->effective_date ?? $this->created_at;
        $effectiveEnd = $this->expiration_date;

        return $effectiveStart->isPast() &&
               (!$effectiveEnd || $effectiveEnd->isFuture());
    }

    public function needsRenewal(): bool
    {
        if (!$this->expiration_date || $this->auto_renewal) {
            return false;
        }

        return $this->expiration_date->diffInDays(now()) <= 30;
    }

    public function getEffectivePrice(): float
    {
        if ($this->custom_price) {
            return $this->custom_price;
        }

        $basePrice = $this->basePlan->price;

        if ($this->discount_percentage > 0) {
            return $basePrice * (1 - ($this->discount_percentage / 100));
        }

        return $basePrice;
    }

    public function getEffectiveFeatures(): array
    {
        $baseFeatures = $this->basePlan->features ?? [];
        $additionalFeatures = $this->additional_features ?? [];
        $removedFeatures = $this->removed_features ?? [];

        // Combinar features base con adicionales
        $allFeatures = array_merge($baseFeatures, $additionalFeatures);

        // Remover features excluidas
        return array_diff($allFeatures, $removedFeatures);
    }

    public function getEffectiveLimits(): array
    {
        $baseLimits = $this->basePlan->limits ?? [];
        $customLimits = $this->custom_limits ?? [];

        // Override limits with custom values
        return array_merge($baseLimits, $customLimits);
    }

    public function getDaysUntilRenewal(): ?int
    {
        if (!$this->expiration_date) {
            return null;
        }

        return $this->expiration_date->diffInDays(now());
    }

    public function canBeApproved(): bool
    {
        return $this->approval_status === 'pending' &&
               $this->status === 'draft';
    }

    public function approve(User $approver, ?string $notes = null): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
            'status' => 'active',
        ]);

        return true;
    }

    public function reject(User $approver, string $notes): bool
    {
        $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'approval_notes' => $notes,
        ]);

        return true;
    }

    public function generateContractSummary(): array
    {
        return [
            'client' => $this->company->name,
            'plan_name' => $this->name,
            'base_plan' => $this->basePlan->name,
            'effective_price' => $this->getEffectivePrice(),
            'billing_cycle' => $this->custom_interval ?? $this->basePlan->interval,
            'contract_length' => $this->contract_length_months,
            'effective_period' => [
                'start' => $this->effective_date,
                'end' => $this->expiration_date,
            ],
            'features' => $this->getEffectiveFeatures(),
            'limits' => $this->getEffectiveLimits(),
            'support_tier' => $this->support_tier,
            'sla_terms' => $this->sla_terms,
        ];
    }
}
