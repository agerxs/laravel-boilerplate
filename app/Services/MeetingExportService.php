<?php

namespace App\Services;

use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;

class MeetingExportService
{
    public function exportToPdf(Meeting $meeting)
    {
        $pdf = PDF::loadView('exports.meeting', [
            'meeting' => $meeting->load(['agenda.presenter', 'participants', 'minutes'])
        ]);

        return $pdf->download("reunion-{$meeting->id}.pdf");
    }
} 