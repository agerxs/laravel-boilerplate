<?php

namespace App\Notifications;

use App\Models\Meeting;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class AgendaUpdated extends Notification
{
    use Queueable;

    public function __construct(
        protected Meeting $meeting
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Ordre du jour mis à jour - {$this->meeting->title}")
            ->line("L'ordre du jour de la réunion a été mis à jour.")
            ->line("Date : " . $this->meeting->start_datetime->format('d/m/Y H:i'))
            ->line("Lieu : " . $this->meeting->location)
            ->action('Voir la réunion', route('meetings.show', $this->meeting));
    }

    public function toArray($notifiable): array
    {
        return [
            'meeting_id' => $this->meeting->id,
            'message' => "L'ordre du jour de la réunion {$this->meeting->title} a été mis à jour"
        ];
    }
} 