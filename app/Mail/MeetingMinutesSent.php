<?php

namespace App\Mail;

use App\Models\Meeting;
use App\Services\MeetingPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MeetingMinutesSent extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Meeting $meeting
    ) {}

    public function build()
    {
        $pdfService = new MeetingPdfService();
        $pdf = $pdfService->generateMinutesPdf($this->meeting);

        $mail = $this->markdown('emails.meetings.minutes')
            ->subject('Compte rendu - ' . $this->meeting->title)
            ->attachData(
                $pdf,
                'compte-rendu-' . $this->meeting->id . '.pdf',
                ['mime' => 'application/pdf']
            );

        // Ajouter les autres pièces jointes
        foreach ($this->meeting->attachments as $attachment) {
            $mail->attach(storage_path('app/public/' . $attachment->file_path), [
                'as' => $attachment->name
            ]);
        }

        return $mail;
    }
} 