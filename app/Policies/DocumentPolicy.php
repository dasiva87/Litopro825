<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Los usuarios pueden ver documentos de su empresa
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // Solo puede ver documentos de su empresa
        return $user->company_id === $document->company_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Los usuarios con empresa pueden crear documentos
        return $user->company_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        // Solo puede actualizar documentos de su empresa
        // Y solo si el documento está en estado editable
        return $user->company_id === $document->company_id
            && $document->canEdit();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        // Solo puede eliminar documentos de su empresa
        // Y solo si está en estado draft o rejected
        return $user->company_id === $document->company_id
            && in_array($document->status, ['draft', 'rejected']);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        // Solo puede restaurar documentos de su empresa
        return $user->company_id === $document->company_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        // Solo administradores de la empresa pueden eliminar permanentemente
        return $user->company_id === $document->company_id
            && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can send the document.
     */
    public function send(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id
            && $document->canSend();
    }

    /**
     * Determine whether the user can approve the document.
     */
    public function approve(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id
            && $document->canApprove();
    }

    /**
     * Determine whether the user can create a new version.
     */
    public function createVersion(User $user, Document $document): bool
    {
        return $user->company_id === $document->company_id;
    }
}
