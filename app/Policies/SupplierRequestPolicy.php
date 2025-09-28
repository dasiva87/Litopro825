<?php

namespace App\Policies;

use App\Models\SupplierRequest;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SupplierRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Litografías y papelerías pueden ver solicitudes
        $company = $user->company;
        return $company && ($company->isLitografia() || $company->isPapeleria());
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupplierRequest $supplierRequest): bool
    {
        $company = $user->company;
        if (!$company) {
            return false;
        }

        // Puede ver si es el solicitante o el proveedor
        return $supplierRequest->requester_company_id === $company->id
            || $supplierRequest->supplier_company_id === $company->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Solo litografías pueden crear solicitudes
        $company = $user->company;
        return $company && $company->isLitografia();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupplierRequest $supplierRequest): bool
    {
        $company = $user->company;
        if (!$company) {
            return false;
        }

        // Solo papelerías pueden actualizar (responder) solicitudes que recibieron
        return $company->isPapeleria()
            && $supplierRequest->supplier_company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupplierRequest $supplierRequest): bool
    {
        $company = $user->company;
        if (!$company) {
            return false;
        }

        // Solo quien envió la solicitud puede eliminarla
        return $company->isLitografia()
            && $supplierRequest->requester_company_id === $company->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupplierRequest $supplierRequest): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupplierRequest $supplierRequest): bool
    {
        return false;
    }
}
