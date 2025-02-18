<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\User;
use App\Notifications\MeetingInvitation;
use Illuminate\Support\Facades\Mail;
use App\Mail\GuestMeetingInvitation;

class MeetingInvitationService
{
    public function inviteParticipants(Meeting $meeting, array $participants)
    {
        foreach ($participants as $participant) {
            if (isset($participant['user_id'])) {
                // Participant interne (utilisateur)
                $user = User::find($participant['user_id']);
                $meeting->participants()->create([
                    'user_id' => $user->id
                ]);
                
                $user->notify(new MeetingInvitation($meeting));
            } else {
                // Invité externe
                $meeting->participants()->create([
                    'guest_email' => $participant['email'],
                    'guest_name' => $participant['name']
                ]);
                
                Mail::to($participant['email'])
                    ->send(new GuestMeetingInvitation($meeting, $participant['name']));
            }
        }
    }
} 