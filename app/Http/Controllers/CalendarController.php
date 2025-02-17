<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index()
    {
        $meetings = Meeting::query()
            ->select('id', 'title', 'description', 'start_datetime', 'end_datetime', 'location', 'status')
            ->orderBy('start_datetime')
            ->get();

        Log::info('Calendar meetings:', $meetings->toArray());

        return Inertia::render('Calendar/Index', [
            'meetings' => $meetings
        ]);
    }
} 