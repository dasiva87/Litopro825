<?php

namespace App\Policies;

use App\Models\CollectionAccount;
use App\Models\User;

class CollectionAccountPolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Admin y Manager pueden gestionar cuentas de cobro.
     */
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can view the model.
     * Permite ver tanto las cuentas creadas como las recibidas.
     */
    public function view(User $user, CollectionAccount $collectionAccount): bool
    {
        // Puede ver si es la empresa creadora O la empresa cliente
        return $user->company_id === $collectionAccount->company_id
            || $user->company_id === $collectionAccount->client_company_id;
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
    public function update(User $user, CollectionAccount $collectionAccount): bool
    {
        // La empresa creadora puede actualizar si está en draft o pending
        if ($user->company_id === $collectionAccount->company_id) {
            return $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
                && in_array($collectionAccount->status->value, ['draft', 'pending']);
        }

        // La empresa cliente puede actualizar solo el estado (para marcar como pagado)
        if ($user->company_id === $collectionAccount->client_company_id) {
            return $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CollectionAccount $collectionAccount): bool
    {
        // Solo la empresa creadora puede eliminar
        // Y solo si está en estado draft
        return $user->company_id === $collectionAccount->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && $collectionAccount->status->value === 'draft';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CollectionAccount $collectionAccount): bool
    {
        return $user->company_id === $collectionAccount->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CollectionAccount $collectionAccount): bool
    {
        return $user->company_id === $collectionAccount->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can send the collection account.
     */
    public function send(User $user, CollectionAccount $collectionAccount): bool
    {
        return $user->company_id === $collectionAccount->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && in_array($collectionAccount->status->value, ['draft', 'pending']);
    }

    /**
     * Determine whether the user can approve the collection account.
     */
    public function approve(User $user, CollectionAccount $collectionAccount): bool
    {
        return $user->company_id === $collectionAccount->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin'])
            && $collectionAccount->status->value === 'pending';
    }

    /**
     * Determine whether the user can mark as paid.
     */
    public function markAsPaid(User $user, CollectionAccount $collectionAccount): bool
    {
        // La empresa cliente puede marcar como pagado
        return $user->company_id === $collectionAccount->client_company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
            && $collectionAccount->status->value === 'sent';
    }

    /**
     * Determine whether the user can change status.
     */
    public function changeStatus(User $user, CollectionAccount $collectionAccount): bool
    {
        // Empresa creadora puede cambiar estados
        if ($user->company_id === $collectionAccount->company_id) {
            return $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
        }

        // Empresa cliente solo puede marcar como pagado
        if ($user->company_id === $collectionAccount->client_company_id) {
            return $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager'])
                && $collectionAccount->status->value === 'sent';
        }

        return false;
    }
}
