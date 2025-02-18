<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting
    ) {}

    public function build()
    {
        return $this->markdown('emails.meetings.cancelled')
            ->subject('Réunion annulée - ' . $this->meeting->title);
    }
} 