<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AgendaItemController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\MeetingMinutesController;
use App\Http\Controllers\MeetingCommentController;
use App\Http\Controllers\LocalCommitteeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnrollmentRequestController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::resource('meetings', MeetingController::class)
        ->except(['show']);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])
        ->name('meetings.show');
    
    // Routes pour les points d'ordre du jour
    Route::post('/meetings/{meeting}/agenda', [AgendaItemController::class, 'store'])->name('agenda-items.store');
    Route::put('/agenda-items/{agendaItem}', [AgendaItemController::class, 'update'])->name('agenda-items.update');
    Route::delete('/agenda-items/{agendaItem}', [AgendaItemController::class, 'destroy'])->name('agenda-items.destroy');
    Route::post('/meetings/{meeting}/agenda/reorder', [AgendaItemController::class, 'reorder'])->name('agenda-items.reorder');

    // Routes pour les pièces jointes
    Route::post('/meetings/{meeting}/attachments', [AttachmentController::class, 'store'])->name('attachments.store');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Routes pour les comptes rendus
    Route::post('/meetings/{meeting}/minutes', [MeetingMinutesController::class, 'store'])->name('minutes.store');
    Route::put('/minutes/{minutes}', [MeetingMinutesController::class, 'update'])->name('minutes.update');
    Route::post('/meetings/{meeting}/minutes/import', [MeetingMinutesController::class, 'import'])
        ->name('minutes.import');
    Route::post('/meetings/{meeting}/minutes/send', [MeetingMinutesController::class, 'sendByEmail'])
        ->name('minutes.send');

    Route::get('/meetings/{meeting}/export', [MeetingController::class, 'export'])
        ->name('meetings.export');

    // Routes pour les commentaires
    Route::get('/meetings/{meeting}/comments', [MeetingCommentController::class, 'index'])
        ->name('meeting.comments.index');
    Route::post('/meetings/{meeting}/comments', [MeetingCommentController::class, 'store'])
        ->name('meeting.comments.store');

    // Routes pour les comités locaux
    Route::get('/local-committees', [LocalCommitteeController::class, 'index'])
        ->name('local-committees.index');
    Route::get('/local-committees/create', [LocalCommitteeController::class, 'create'])
        ->name('local-committees.create');
    Route::post('/local-committees', [LocalCommitteeController::class, 'store'])
        ->name('local-committees.store');
    Route::get('/local-committees/{localCommittee}/edit', [LocalCommitteeController::class, 'edit'])
        ->name('local-committees.edit');
    Route::put('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'update'])
        ->name('local-committees.update');
    Route::delete('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'destroy'])
        ->name('local-committees.destroy');
    Route::get('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'show'])
        ->name('local-committees.show');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::post('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])
        ->name('meetings.cancel');

    Route::post('/meetings/{meeting}/notify', [MeetingController::class, 'notify'])
        ->name('meetings.notify');

    // Routes pour les demandes d'enrôlement
    Route::post('/meetings/{meeting}/enrollment-requests', [EnrollmentRequestController::class, 'store'])
        ->name('enrollment-requests.store');
    Route::get('/meetings/{meeting}/enrollment-requests', [EnrollmentRequestController::class, 'index'])
        ->name('enrollment-requests.index');
    Route::put('/enrollment-requests/{enrollmentRequest}', [EnrollmentRequestController::class, 'update'])
        ->name('enrollment-requests.update');
    Route::delete('/enrollment-requests/{enrollmentRequest}', [EnrollmentRequestController::class, 'destroy'])
        ->name('enrollment-requests.destroy');
});

require __DIR__.'/auth.php';
