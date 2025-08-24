<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Config;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = Config::get('app.current_tenant_id');
        
        if ($tenantId && $model->getTable() !== 'companies') {
            $builder->where($model->getTable() . '.company_id', $tenantId);
        }
    }
}