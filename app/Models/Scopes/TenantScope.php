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
        // Tablas excluidas del scope automático
        $excludedTables = ['companies', 'users'];

        if (in_array($model->getTable(), $excludedTables)) {
            return;
        }

        // Intentar obtener tenant_id desde múltiples fuentes
        $tenantId = $this->getTenantId();

        // Aplicar scope solo si hay tenantId
        if ($tenantId) {
            $builder->where($model->getTable() . '.company_id', $tenantId);
        }
    }

    /**
     * Obtener el tenant ID desde múltiples fuentes
     */
    private function getTenantId(): ?int
    {
        // Prioridad 1: Config (establecido por middleware)
        $tenantId = Config::get('app.current_tenant_id');

        if ($tenantId) {
            return (int) $tenantId;
        }

        // Prioridad 2: Usuario autenticado
        if (auth()->check() && auth()->user()->company_id) {
            $companyId = auth()->user()->company_id;

            // Establecer en config para próximas consultas en el mismo request
            Config::set('app.current_tenant_id', $companyId);

            return (int) $companyId;
        }

        return null;
    }
}
