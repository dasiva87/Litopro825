<?php

namespace App\Policies;

use App\Models\Paper;
use App\Models\User;

class PaperPolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Admin y Manager pueden gestionar papeles.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Paper $paper): bool
    {
        // Puede ver papeles de su empresa O de proveedores aprobados
        if ($user->company_id === $paper->company_id) {
            return true;
        }

        // Verificar si es papel de proveedor aprobado
        $company = $user->company;
        if ($company && $company->isLitografia()) {
            $supplierCompanyIds = \App\Models\SupplierRelationship::where('client_company_id', $user->company_id)
                ->where('is_active', true)
                ->whereNotNull('approved_at')
                ->pluck('supplier_company_id')
                ->toArray();

            return in_array($paper->company_id, $supplierCompanyIds);
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Paper $paper): bool
    {
        // Solo puede actualizar papeles de su empresa
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Paper $paper): bool
    {
        // Solo puede eliminar papeles de su empresa
        // Y solo si no tiene movimientos de stock
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && $paper->stockMovements()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Paper $paper): bool
    {
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Paper $paper): bool
    {
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can adjust stock.
     */
    public function adjustStock(User $user, Paper $paper): bool
    {
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can activate/deactivate the paper.
     */
    public function toggleActive(User $user, Paper $paper): bool
    {
        return $user->company_id === $paper->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }
}
