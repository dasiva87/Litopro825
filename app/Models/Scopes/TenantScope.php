<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        // TEMPORALMENTE DESHABILITADO PARA DEBUGGING
        // TODO: Habilitar después de resolver el problema de recursión infinita
        return;

        // Priorizar tenant_id desde Config si existe (para comandos/jobs)
        $tenantId = Config::get('app.current_tenant_id');

        // Si no hay config, intentar obtener company_id sin usar auth()->user()
        if (! $tenantId) {
            $tenantId = $this->getTenantIdSafely();
        }

        // Aplicar scope solo si hay tenantId y no es la tabla companies
        if ($tenantId && $model->getTable() !== 'companies') {
            $builder->where($model->getTable().'.company_id', $tenantId);
        }
    }

    /**
     * Obtener tenant_id de forma segura sin recursión infinita
     */
    private function getTenantIdSafely(): ?int
    {
        try {
            // Método 1: Desde sesión si existe
            if (Session::has('company_id')) {
                return Session::get('company_id');
            }

            // Método 2: Desde guard sin llamar a user()
            if (auth()->check() && auth()->id()) {
                $userId = auth()->id();
                // Consulta directa a la BD sin usar el modelo User (evita scope)
                $companyId = DB::table('users')
                    ->where('id', $userId)
                    ->value('company_id');

                if ($companyId) {
                    // Guardar en sesión para futuros requests
                    Session::put('company_id', $companyId);
                    return $companyId;
                }
            }

            return null;
        } catch (\Exception $e) {
            // En caso de error, retornar null para evitar crashes
            return null;
        }
    }
}
