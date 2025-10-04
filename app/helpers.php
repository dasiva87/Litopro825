<?php

use App\Services\TenantContext;

if (!function_exists('tenant_id')) {
    /**
     * Obtener el ID del tenant actual
     *
     * @return int|null
     */
    function tenant_id(): ?int
    {
        return TenantContext::id();
    }
}

if (!function_exists('current_company_id')) {
    /**
     * Alias de tenant_id() para retrocompatibilidad
     *
     * @return int|null
     */
    function current_company_id(): ?int
    {
        return TenantContext::id();
    }
}

if (!function_exists('tenant')) {
    /**
     * Obtener la instancia del tenant (company) actual
     *
     * @return \App\Models\Company|null
     */
    function tenant(): ?\App\Models\Company
    {
        $tenantId = TenantContext::id();

        if ($tenantId === null) {
            return null;
        }

        return \App\Models\Company::find($tenantId);
    }
}

if (!function_exists('is_tenant_context_set')) {
    /**
     * Verificar si hay un contexto de tenant activo
     *
     * @return bool
     */
    function is_tenant_context_set(): bool
    {
        return TenantContext::hasContext();
    }
}
