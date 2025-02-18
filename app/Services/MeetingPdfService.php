<?php

namespace App\Services;

use App\Models\Meeting;
use Barryvdh\DomPDF\Facade\Pdf;

class MeetingPdfService
{
    public function generateMinutesPdf(Meeting $meeting)
    {
        $pdf = PDF::loadView('pdf.meeting-minutes', [
            'meeting' => $meeting
        ]);

        return $pdf->output();
    }
} 