<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class FilamentResourcePolicy
{
    use HandlesAuthorization;

    /**
     * Détermine si l'utilisateur peut voir la liste des ressources
     */
    public function viewAny(User $user): bool
    {
        return $user->canViewDashboard();
    }

    /**
     * Détermine si l'utilisateur peut voir une ressource spécifique
     */
    public function view(User $user, $model): bool
    {
        return $user->canViewDashboard();
    }

    /**
     * Détermine si l'utilisateur peut créer une ressource
     */
    public function create(User $user): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut modifier une ressource
     */
    public function update(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut supprimer une ressource
     */
    public function delete(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut restaurer une ressource
     */
    public function restore(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut forcer la suppression d'une ressource
     */
    public function forceDelete(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut répliquer une ressource
     */
    public function replicate(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }

    /**
     * Détermine si l'utilisateur peut réorganiser une ressource
     */
    public function reorder(User $user, $model): bool
    {
        return $user->canModifyDashboard();
    }
}
