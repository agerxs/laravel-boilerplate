<?php

namespace App\Mail;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AgendaUpdatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting
    ) {}

    public function build()
    {
        return $this->markdown('emails.meetings.agenda-updated')
            ->subject('Ordre du jour mis à jour - ' . $this->meeting->title);
    }
} 