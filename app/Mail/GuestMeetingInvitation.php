<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class GuestMeetingInvitation extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting,
        public string $guestName
    ) {}

    public function build()
    {
        return $this->markdown('emails.meetings.guest-invitation')
            ->subject('Invitation à une réunion - ' . $this->meeting->title);
    }
} 