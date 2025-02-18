<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Meeting;

class MeetingInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Meeting $meeting
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Invitation à une réunion')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Vous êtes invité(e) à participer à une réunion.')
            ->line('Titre : ' . $this->meeting->title)
            ->line('Date : ' . $this->meeting->start_datetime)
            ->line('Lieu : ' . $this->meeting->location)
            ->action('Voir les détails', route('meetings.show', $this->meeting))
            ->line('Merci de confirmer votre présence.');
    }
} 