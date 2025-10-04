<?php

namespace App\Services;

use App\Exceptions\TenantContextException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

/**
 * Servicio centralizado para manejo del contexto de tenant (multi-tenancy)
 *
 * Proporciona métodos helpers para acceder al tenant actual de forma consistente
 * y aplicar scopes de tenant a queries de forma manual cuando sea necesario.
 */
class TenantContext
{
    /**
     * Obtener el ID del tenant actual
     *
     * Orden de prioridad:
     * 1. Config 'app.current_tenant_id' (establecido por middleware)
     * 2. Usuario autenticado company_id
     * 3. Exception si no hay contexto
     *
     * @throws TenantContextException
     */
    public static function id(): ?int
    {
        // Método 1: Desde config (establecido por SetTenantContext middleware)
        if (Config::has('app.current_tenant_id')) {
            return Config::get('app.current_tenant_id');
        }

        // Método 2: Desde usuario autenticado
        if (Auth::check() && Auth::user()->company_id) {
            return Auth::user()->company_id;
        }

        // No hay contexto de tenant disponible
        return null;
    }

    /**
     * Obtener el ID del tenant actual o fallar
     *
     * @throws TenantContextException
     */
    public static function idOrFail(): int
    {
        $tenantId = self::id();

        if ($tenantId === null) {
            throw new TenantContextException(
                'No tenant context available. User must be authenticated or tenant context must be set.'
            );
        }

        return $tenantId;
    }

    /**
     * Verificar si hay un contexto de tenant activo
     */
    public static function hasContext(): bool
    {
        return self::id() !== null;
    }

    /**
     * Aplicar scope de tenant a un query builder
     *
     * Uso:
     * Document::query()->tap(fn($q) => TenantContext::scopeQuery($q))->get();
     *
     * O mejor aún:
     * Document::forCurrentTenant()->get();
     */
    public static function scopeQuery(Builder $query, string $column = 'company_id'): Builder
    {
        $tenantId = self::id();

        if ($tenantId !== null) {
            $query->where($column, $tenantId);
        }

        return $query;
    }

    /**
     * Establecer el contexto de tenant manualmente
     *
     * Útil para testing o jobs en background
     */
    public static function setContext(int $companyId): void
    {
        Config::set('app.current_tenant_id', $companyId);
    }

    /**
     * Limpiar el contexto de tenant
     *
     * Útil para testing
     */
    public static function clearContext(): void
    {
        Config::set('app.current_tenant_id', null);
    }

    /**
     * Ejecutar código con un contexto de tenant específico
     *
     * Útil para jobs o tareas que necesitan cambiar de tenant temporalmente
     *
     * Uso:
     * TenantContext::runAs($companyId, function() {
     *     // código que se ejecuta con el tenant específico
     * });
     */
    public static function runAs(int $companyId, callable $callback): mixed
    {
        $originalContext = self::id();

        try {
            self::setContext($companyId);
            return $callback();
        } finally {
            // Restaurar contexto original
            if ($originalContext !== null) {
                self::setContext($originalContext);
            } else {
                self::clearContext();
            }
        }
    }

    /**
     * Obtener el company_id del usuario autenticado
     *
     * @deprecated Usar TenantContext::id() en su lugar
     */
    public static function getCurrentCompanyId(): ?int
    {
        return self::id();
    }

    /**
     * Verificar si un modelo pertenece al tenant actual
     */
    public static function owns(object $model, string $column = 'company_id'): bool
    {
        $tenantId = self::id();

        if ($tenantId === null) {
            return false;
        }

        return $model->{$column} === $tenantId;
    }
}
