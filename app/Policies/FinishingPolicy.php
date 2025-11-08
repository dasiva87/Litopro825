<?php

namespace App\Policies;

use App\Models\Finishing;
use App\Models\User;

class FinishingPolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Admin y Manager pueden gestionar acabados.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Finishing $finishing): bool
    {
        // Solo puede ver acabados de su empresa
        return $user->company_id === $finishing->company_id;
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
    public function update(User $user, Finishing $finishing): bool
    {
        // Solo puede actualizar acabados de su empresa
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Finishing $finishing): bool
    {
        // Solo puede eliminar acabados de su empresa
        // Y solo si no tiene items asociados
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && $finishing->simpleItems()->count() === 0
            && $finishing->digitalItems()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Finishing $finishing): bool
    {
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Finishing $finishing): bool
    {
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can activate/deactivate the finishing.
     */
    public function toggleActive(User $user, Finishing $finishing): bool
    {
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can manage ranges.
     */
    public function manageRanges(User $user, Finishing $finishing): bool
    {
        return $user->company_id === $finishing->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }
}
