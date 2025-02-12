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

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('meetings.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/meetings', [MeetingController::class, 'index'])->name('meetings.index');
    // Routes pour les réunions
    Route::resource('meetings', MeetingController::class);
    
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

    Route::get('/meetings/{meeting}/export', [MeetingController::class, 'export'])
        ->name('meetings.export');

    // Routes pour les commentaires
    Route::get('/meetings/{meeting}/comments', [MeetingCommentController::class, 'index'])
        ->name('meeting.comments.index');
    Route::post('/meetings/{meeting}/comments', [MeetingCommentController::class, 'store'])
        ->name('meeting.comments.store');
});

require __DIR__.'/auth.php';
