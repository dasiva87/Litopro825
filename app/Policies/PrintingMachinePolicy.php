<?php

namespace App\Policies;

use App\Models\PrintingMachine;
use App\Models\User;

class PrintingMachinePolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Admin y Manager pueden gestionar máquinas de impresión.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PrintingMachine $printingMachine): bool
    {
        // Solo puede ver máquinas de su empresa
        return $user->company_id === $printingMachine->company_id;
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
    public function update(User $user, PrintingMachine $printingMachine): bool
    {
        // Solo puede actualizar máquinas de su empresa
        return $user->company_id === $printingMachine->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PrintingMachine $printingMachine): bool
    {
        // Solo puede eliminar máquinas de su empresa
        // Y solo si no tiene items asociados
        return $user->company_id === $printingMachine->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && $printingMachine->documentItems()->count() === 0;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PrintingMachine $printingMachine): bool
    {
        return $user->company_id === $printingMachine->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PrintingMachine $printingMachine): bool
    {
        return $user->company_id === $printingMachine->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can activate/deactivate the machine.
     */
    public function toggleActive(User $user, PrintingMachine $printingMachine): bool
    {
        return $user->company_id === $printingMachine->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }
}
