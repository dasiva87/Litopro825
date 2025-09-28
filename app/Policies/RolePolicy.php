<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;

class RolePolicy
{
    /**
     * Determine whether the user can view any models.
     * Solo Company Admin y Super Admin pueden gestionar roles.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Role $role): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Company Admin no puede ver el rol Super Admin
        if ($user->hasRole('Company Admin')) {
            return $role->name !== 'Super Admin';
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole(['Super Admin', 'Company Admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Role $role): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Company Admin no puede modificar Super Admin
        if ($user->hasRole('Company Admin')) {
            return $role->name !== 'Super Admin';
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Role $role): bool
    {
        if ($user->hasRole('Super Admin')) {
            return true;
        }

        // Company Admin no puede eliminar Super Admin ni su propio rol
        if ($user->hasRole('Company Admin')) {
            return $role->name !== 'Super Admin' && $role->name !== 'Company Admin';
        }

        return false;
    }

    /**
     * Determine whether the user can manage permissions for roles.
     */
    public function managePermissions(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }
}