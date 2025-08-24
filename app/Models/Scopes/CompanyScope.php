<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class CompanyScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // Solo aplicar si hay un usuario autenticado con empresa
        if (auth()->check() && auth()->user()->company_id) {
            $builder->where($model->getTable() . '.company_id', auth()->user()->company_id);
        }
    }
}