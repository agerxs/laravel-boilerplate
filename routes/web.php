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
use App\Http\Controllers\VillageRepresentativesController;
use App\Http\Controllers\PaymentRateController;
use App\Http\Controllers\MeetingPaymentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancePhotoController;

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
    Route::resource('meetings', MeetingController::class);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])
        ->name('meetings.show')
        ->middleware('check.locality');
    
    // Routes pour les points d'ordre du jour
    Route::post('/meetings/{meeting}/agenda', [AgendaItemController::class, 'store'])
        ->name('agenda-items.store')
        ->middleware('check.locality');
    Route::put('/agenda-items/{agendaItem}', [AgendaItemController::class, 'update'])->name('agenda-items.update');
    Route::delete('/agenda-items/{agendaItem}', [AgendaItemController::class, 'destroy'])->name('agenda-items.destroy');
    Route::post('/meetings/{meeting}/agenda/reorder', [AgendaItemController::class, 'reorder'])
        ->name('agenda-items.reorder')
        ->middleware('check.locality');

    // Routes pour les pièces jointes
    Route::post('/meetings/{meeting}/attachments', [AttachmentController::class, 'store'])
        ->name('attachments.store')
        ->middleware('check.locality');
    Route::delete('/attachments/{attachment}', [AttachmentController::class, 'destroy'])->name('attachments.destroy');
    Route::get('/attachments/{attachment}/download', [AttachmentController::class, 'download'])
        ->name('attachments.download');

    // Routes pour les comptes rendus
    Route::post('/meetings/{meeting}/minutes', [MeetingMinutesController::class, 'store'])
        ->name('minutes.store')
        ->middleware('check.locality');
    Route::put('/minutes/{minutes}', [MeetingMinutesController::class, 'update'])->name('minutes.update');
    Route::post('/meetings/{meeting}/minutes/import', [MeetingMinutesController::class, 'import'])
        ->name('minutes.import')
        ->middleware('check.locality');
    Route::post('/meetings/{meeting}/minutes/send', [MeetingMinutesController::class, 'sendByEmail'])
        ->name('minutes.send')
        ->middleware('check.locality');
    
    // Nouvelles routes pour la validation des comptes-rendus
    Route::post('/minutes/{minutes}/request-validation', [MeetingMinutesController::class, 'requestValidation'])
        ->name('minutes.request-validation');
    Route::post('/minutes/{minutes}/validate', [MeetingMinutesController::class, 'validate'])
        ->name('minutes.validate')
        ->middleware('role:sous-prefet|Sous-prefet|admin|Admin'); // Sous-préfets et admins peuvent valider

    Route::get('/meetings/{meeting}/export', [MeetingController::class, 'export'])
        ->name('meetings.export')
        ->middleware('check.locality');

    // Routes pour les commentaires
    Route::get('/meetings/{meeting}/comments', [MeetingCommentController::class, 'index'])
        ->name('meeting.comments.index')
        ->middleware('check.locality');
    Route::post('/meetings/{meeting}/comments', [MeetingCommentController::class, 'store'])
        ->name('meeting.comments.store')
        ->middleware('check.locality');

    // Routes pour les brouillons - Placez ces routes AVANT les routes avec des paramètres
    Route::get('/local-committees/drafts', [LocalCommitteeController::class, 'getDrafts'])->name('local-committees.drafts');
    Route::get('/local-committees/load-draft/{id}', [LocalCommitteeController::class, 'loadDraft'])->name('local-committees.load-draft');
    Route::delete('/local-committees/delete-draft/{id}', [LocalCommitteeController::class, 'deleteDraft'])->name('local-committees.delete-draft');

    // Routes pour les comités locaux avec des paramètres
    Route::get('/local-committees', [LocalCommitteeController::class, 'index'])->name('local-committees.index');
    Route::get('/local-committees/create', [LocalCommitteeController::class, 'create'])->name('local-committees.create');
    Route::post('/local-committees', [LocalCommitteeController::class, 'store'])->name('local-committees.store');
    Route::get('/local-committees/{localCommittee}/edit', [LocalCommitteeController::class, 'edit'])
        ->name('local-committees.edit')
        ->middleware('check.locality');
    Route::put('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'update'])
        ->name('local-committees.update')
        ->middleware('check.locality');
    Route::delete('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'destroy'])
        ->name('local-committees.destroy')
        ->middleware('check.locality');
    Route::get('/local-committees/{localCommittee}', [LocalCommitteeController::class, 'show'])
        ->name('local-committees.show')
        ->middleware('check.locality');

    Route::get('/local-committees/{committeeId}/village-representatives', [LocalCommitteeController::class, 'showVillageRepresentatives'])
        ->name('local-committees.village-representatives')
        ->middleware('check.locality');
    Route::post('/local-committees/add-representatives', [LocalCommitteeController::class, 'addRepresentatives'])->name('local-committees.add-representatives');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');

    Route::post('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel'])
        ->name('meetings.cancel');

    Route::post('/meetings/{meeting}/notify', [MeetingController::class, 'notify'])
        ->name('meetings.notify');

    // Nouvelles routes pour la prévalidation et validation des réunions
    Route::post('/meetings/{meeting}/prevalidate', [MeetingController::class, 'prevalidate'])
        ->name('meetings.prevalidate')
        ->middleware('role:secretaire|Secrétaire|admin|Admin'); // Secrétaires et admins peuvent prévalider
        
    Route::post('/meetings/{meeting}/validate', [MeetingController::class, 'validate'])
        ->name('meetings.validate')
        ->middleware('role:sous-prefet|Sous-prefet|admin|Admin'); // Sous-préfets et admins peuvent valider
        
    Route::post('/meetings/{meeting}/invalidate', [MeetingController::class, 'invalidate'])
        ->name('meetings.invalidate')
        ->middleware('role:sous-prefet|Sous-prefet|admin|Admin'); // Sous-préfets et admins peuvent invalider

    Route::get('/village-representatives', [VillageRepresentativesController::class, 'index'])->name('village-representatives.index');

    Route::post('/local-committees/{committeeId}/save-villages', [LocalCommitteeController::class, 'saveVillages'])->name('local-committees.save-villages');

    Route::post('/local-committees/save-progress', [LocalCommitteeController::class, 'saveProgress'])->name('local-committees.save-progress');

    Route::get('/local-committees/{id}/villages', [LocalCommitteeController::class, 'getVillages'])
        ->name('local-committees.get-villages');

    // Routes pour les représentants des réunions
    Route::get('/meetings/{meeting}/representatives', [MeetingController::class, 'getRepresentatives'])
        ->name('meetings.representatives');
    Route::post('/meetings/{meeting}/representatives', [MeetingController::class, 'saveRepresentatives'])
        ->name('meetings.representatives.save');

    // Routes pour les taux de paiement
    Route::get('/payment-rates', [PaymentRateController::class, 'index'])->name('payment-rates.index');
    Route::get('/payment-rates/create', [PaymentRateController::class, 'create'])->name('payment-rates.create');
    Route::post('/payment-rates', [PaymentRateController::class, 'store'])->name('payment-rates.store');
    Route::get('/payment-rates/{paymentRate}/edit', [PaymentRateController::class, 'edit'])->name('payment-rates.edit');
    Route::put('/payment-rates/{paymentRate}', [PaymentRateController::class, 'update'])->name('payment-rates.update');
    Route::delete('/payment-rates/{paymentRate}', [PaymentRateController::class, 'destroy'])->name('payment-rates.destroy');
    Route::post('/payment-rates/bulk-update', [PaymentRateController::class, 'bulkUpdate'])->name('payment-rates.bulk-update');

    // Routes pour les paiements des réunions
    Route::get('/meeting-payments', [MeetingPaymentController::class, 'index'])->name('meeting-payments.index');
    Route::get('/meeting-payments/{meeting}', [MeetingPaymentController::class, 'show'])->name('meeting-payments.show');
    Route::post('/meeting-payments/{meeting}', [MeetingPaymentController::class, 'processPayments'])->name('meeting-payments.process');

    // Routes pour la gestion des listes de présence
    Route::get('/meetings/{meeting}/attendance', [AttendanceController::class, 'index'])
        ->name('meetings.attendance');
    Route::post('/attendees/{attendee}/present', [AttendanceController::class, 'markPresent'])
        ->name('attendees.present');
    Route::post('/attendees/{attendee}/absent', [AttendanceController::class, 'markAbsent'])
        ->name('attendees.absent');
    Route::post('/attendees/{attendee}/replacement', [AttendanceController::class, 'setReplacement'])
        ->name('attendees.replacement');
    Route::post('/attendees/{attendee}/comment', [AttendanceController::class, 'addComment'])
        ->name('attendees.comment');
    Route::post('/meetings/{meeting}/attendance/finalize', [AttendanceController::class, 'finalize'])
        ->name('meetings.attendance.finalize');
    Route::get('/meetings/{meeting}/attendance/export', [AttendanceController::class, 'exportPdf'])
        ->name('meetings.attendance.export');

    // Routes pour les réunions
    Route::get('/meetings/{meeting}/reschedule', [MeetingController::class, 'showRescheduleForm'])
        ->name('meetings.reschedule');
    Route::post('/meetings/{meeting}/reschedule-submit', [MeetingController::class, 'reschedule'])
        ->name('meetings.reschedule.submit');
    Route::post('/meetings/{meeting}/complete', [MeetingController::class, 'complete'])
        ->name('meetings.complete');
    Route::patch('/meetings/{meeting}/update-enrollments', [MeetingController::class, 'updateEnrollments'])
        ->name('meetings.update-enrollments');

    // Routes pour les photos de présence
    Route::post('/attendees/{attendee}/photo', [AttendancePhotoController::class, 'store'])
        ->name('attendance-photos.store');
    Route::get('/attendance-photos/{photo}', [AttendancePhotoController::class, 'show'])
        ->name('attendance-photos.show');
    Route::get('/attendance-photos/{photo}/thumbnail', [AttendancePhotoController::class, 'thumbnail'])
        ->name('attendance-photos.thumbnail');
    Route::delete('/attendance-photos/{photo}', [AttendancePhotoController::class, 'destroy'])
        ->name('attendance-photos.destroy');
});

Route::get('/doc', [App\Http\Controllers\Api\DocumentationController::class, 'index'])->name('api.documentation');

require __DIR__.'/auth.php';
