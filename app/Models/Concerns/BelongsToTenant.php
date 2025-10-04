<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\TenantScope;
use App\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (!$model->company_id) {
                $tenantId = TenantContext::id();

                if ($tenantId) {
                    $model->company_id = $tenantId;
                }
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Scope para filtrar por el tenant actual
     *
     * Uso: Document::forCurrentTenant()->get()
     */
    public function scopeForCurrentTenant(Builder $query): Builder
    {
        $tenantId = TenantContext::id();

        if ($tenantId !== null) {
            $query->where($this->getTable() . '.company_id', $tenantId);
        }

        return $query;
    }

    /**
     * Scope para filtrar por un tenant específico
     *
     * Uso: Document::forTenant($companyId)->get()
     */
    public function scopeForTenant(Builder $query, int $companyId): Builder
    {
        return $query->where($this->getTable() . '.company_id', $companyId);
    }

    /**
     * Verificar si este modelo pertenece al tenant actual
     */
    public function belongsToCurrentTenant(): bool
    {
        return TenantContext::owns($this);
    }

    /**
     * Obtener el ID del tenant (alias de company_id)
     */
    public function getTenantId(): ?int
    {
        return $this->company_id;
    }
}