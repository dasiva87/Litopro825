<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

trait BelongsToTenant
{
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);
        
        static::creating(function ($model) {
            if (!$model->company_id) {
                $tenantId = Config::get('app.current_tenant_id');
                
                if (!$tenantId && Auth::check()) {
                    $tenantId = Auth::user()->company_id;
                }
                
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
}