<?php

namespace App\Policies;

use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class SocialPostPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view-posts');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SocialPost $socialPost): bool
    {
        // Puede ver si tiene permiso y es de su empresa
        return $user->hasPermissionTo('view-posts') &&
               $user->company_id === $socialPost->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create-posts');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SocialPost $socialPost): bool
    {
        // Puede editar si tiene permiso y (es el autor O tiene permiso de editar posts)
        return $user->company_id === $socialPost->company_id &&
               ($user->id === $socialPost->user_id || $user->hasPermissionTo('edit-posts'));
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SocialPost $socialPost): bool
    {
        // Puede eliminar si tiene permiso y (es el autor O tiene permiso de eliminar posts)
        return $user->company_id === $socialPost->company_id &&
               ($user->id === $socialPost->user_id || $user->hasPermissionTo('delete-posts'));
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SocialPost $socialPost): bool
    {
        return $user->hasPermissionTo('delete-posts') &&
               $user->company_id === $socialPost->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SocialPost $socialPost): bool
    {
        return $user->hasRole(['Super Admin', 'Company Admin']) &&
               $user->company_id === $socialPost->company_id;
    }
}
