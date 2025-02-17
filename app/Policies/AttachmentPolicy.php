<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Attachment;

class AttachmentPolicy
{
    public function view(User $user, Attachment $attachment)
    {
        // Si l'utilisateur est l'organisateur de la réunion
        if ($attachment->meeting->organizer_id === $user->id) {
            return true;
        }

        // Si l'utilisateur est un participant de la réunion
        return $attachment->meeting->participants()
            ->where('user_id', $user->id)
            ->exists();
    }

    public function delete(User $user, Attachment $attachment)
    {
        return $attachment->meeting->organizer_id === $user->id || 
               $attachment->uploaded_by === $user->id;
    }
} 