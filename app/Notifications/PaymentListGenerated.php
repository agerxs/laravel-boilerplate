<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Meeting;
use App\Models\User;

class PaymentListGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Meeting $meeting,
        protected User $submittedBy,
        protected string $fileName,
        protected int $representativesCount
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $committeeName = $this->meeting->localCommittee?->name ?? 'Comité Local';
        $meetingDate = $this->meeting->scheduled_date?->format('d/m/Y à H:i') ?? 'Date non définie';
        
        return (new MailMessage)
            ->subject('Nouvelle liste de paiement générée - ' . $committeeName)
            ->view('emails.payment-list-generated', [
                'notifiable' => $notifiable,
                'meeting' => $this->meeting,
                'submittedBy' => $this->submittedBy,
                'fileName' => $this->fileName,
                'representativesCount' => $this->representativesCount,
            ]);
    }

    /**
     * Détermine quels utilisateurs doivent recevoir cette notification
     */
    public static function shouldSendTo($user): bool
    {
        return $user->hasRole('tresorier');
    }
} 