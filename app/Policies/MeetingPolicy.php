<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MeetingPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Meeting $meeting)
    {
        // Les administrateurs peuvent toujours modifier
        if ($user->hasRole('admin')) {
            return true;
        }

        // Les secrétaires peuvent modifier si la réunion n'est pas validée
        if ($user->hasRole(['secretaire', 'Secrétaire'])) {
            return !in_array($meeting->status, ['validated']);
        }

        return false;
    }

    public function updateEnrollments(User $user, Meeting $meeting)
    {
        // Les administrateurs peuvent toujours modifier les enrôlements
        if ($user->hasRole('admin')) {
            return true;
        }

        // Les secrétaires peuvent modifier les enrôlements si la réunion n'est pas validée
        if ($user->hasRole(['secretaire', 'Secrétaire'])) {
            return !in_array($meeting->status, ['validated']);
        }

        return false;
    }
} 