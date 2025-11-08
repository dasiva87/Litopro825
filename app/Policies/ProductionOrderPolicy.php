<?php

namespace App\Policies;

use App\Models\ProductionOrder;
use App\Models\User;

class ProductionOrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Los usuarios con empresa pueden ver órdenes de producción
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo puede ver órdenes de su empresa O si es el operador asignado
        return $user->company_id === $productionOrder->company_id
            || $user->id === $productionOrder->operator_user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los usuarios con empresa pueden crear órdenes de producción
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ProductionOrder $productionOrder): bool
    {
        // Puede actualizar si es de su empresa O si es el operador asignado
        // El operador puede actualizar notas y marcar tareas completadas
        return $user->company_id === $productionOrder->company_id
            || $user->id === $productionOrder->operator_user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo puede eliminar órdenes de su empresa
        // Y solo si está en estado pending o draft
        return $user->company_id === $productionOrder->company_id
            && in_array($productionOrder->status->value, ['pending', 'draft']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo puede restaurar órdenes de su empresa
        return $user->company_id === $productionOrder->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo administradores de la empresa pueden eliminar permanentemente
        return $user->company_id === $productionOrder->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can assign operators to the order.
     */
    public function assignOperator(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo usuarios de la misma empresa (managers/admins)
        return $user->company_id === $productionOrder->company_id;
    }

    /**
     * Determine whether the user can mark the order as quality checked.
     */
    public function qualityCheck(User $user, ProductionOrder $productionOrder): bool
    {
        // Solo usuarios de la empresa (no el operador regular)
        return $user->company_id === $productionOrder->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }

    /**
     * Determine whether the user can change the order status.
     */
    public function changeStatus(User $user, ProductionOrder $productionOrder): bool
    {
        // El operador puede cambiar estado a in_progress y completed
        // Managers/Admins pueden cambiar a cualquier estado
        if ($user->id === $productionOrder->operator_user_id) {
            return true;
        }

        return $user->company_id === $productionOrder->company_id
            && $user->hasAnyRole(['Super Admin', 'Company Admin', 'Manager']);
    }
}
