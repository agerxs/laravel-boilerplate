<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AgendaItemController;
use App\Http\Controllers\MeetingController;
use App\Http\Controllers\AttendanceValidationController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\MeetingMinutesController;
use App\Http\Controllers\MeetingCommentController;
use App\Http\Controllers\LocalCommitteeController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\DashboardController;
//use App\Http\Controllers\VillageRepresentativesController;
use App\Http\Controllers\PaymentRateController;
use App\Http\Controllers\MeetingPaymentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendancePhotoController;
use App\Http\Controllers\MeetingPaymentListController;
use App\Http\Controllers\LocalityController;
use App\Http\Controllers\RepresentativeController;
use App\Http\Controllers\ExecutivePaymentController;
use App\Http\Controllers\TemplateController;
use App\Http\Controllers\BulkImportController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware('auth')
    ->middleware('verified')
    ->middleware('dashboard.access')
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');


    Route::post('/local-committees/save-progress', [LocalCommitteeController::class, 'saveProgress'])->name('local-committees.save-progress');

    Route::get('/local-committees/{id}/villages', [LocalCommitteeController::class, 'getVillages'])
        ->name('local-committees.get-villages');
        
    // Routes pour les listes de paiement
    Route::prefix('meeting-payments/lists')->name('meeting-payments.lists.')->group(function () {
        Route::get('/', [MeetingPaymentListController::class, 'index'])->name('index');
        Route::get('/create/{meeting}', [MeetingPaymentListController::class, 'create'])->name('create');
        Route::get('/export', [MeetingPaymentListController::class, 'exportPaymentLists'])->name('export');
        Route::get('/export-single/{meeting}', [MeetingPaymentListController::class, 'exportSingleMeeting'])->name('export-single');
        Route::post('/validate-all', [MeetingPaymentListController::class, 'validateAll'])->name('validate-all');
        Route::post('/items/{item}/validate', [MeetingPaymentListController::class, 'validateItem'])->name('validate-item');
        Route::post('/items/{item}/invalidate', [MeetingPaymentListController::class, 'invalidateItem'])->name('invalidate-item');
        Route::post('/{meeting}', [MeetingPaymentListController::class, 'store'])->name('store');
        Route::get('/{paymentList}', [MeetingPaymentListController::class, 'show'])->name('show');
        Route::post('/{paymentList}/submit', [MeetingPaymentListController::class, 'submit'])->name('submit');
        Route::post('/{paymentList}/validate', [MeetingPaymentListController::class, 'validates'])->name('validate');
        Route::post('/{paymentList}/reject', [MeetingPaymentListController::class, 'reject'])->name('reject');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    
    Route::get('/meetings/create-multiple', [MeetingController::class, 'createMultiple'])->name('meetings.create-multiple');
    Route::post('/meetings/store-multiple', [MeetingController::class, 'storeMultiple'])->name('meetings.store-multiple');
    Route::post('/meetings/import', [MeetingController::class, 'importMeetings'])->name('meetings.import');
    Route::post('/meetings/store-multiple-with-attachments', [MeetingController::class, 'storeMultipleWithAttachments'])->name('meetings.store-multiple-with-attachments');
    Route::get('/templates/meetings', [TemplateController::class, 'downloadMeetingsTemplate'])->name('templates.meetings');
    Route::resource('meetings', MeetingController::class);
    
    // Routes pour les imports par lots
    Route::get('/bulk-imports', [BulkImportController::class, 'index'])->name('bulk-imports.index');
    Route::get('/bulk-imports/{bulkImport}', [BulkImportController::class, 'show'])->name('bulk-imports.show');
    Route::get('/bulk-imports/{bulkImport}/download', [BulkImportController::class, 'download'])->name('bulk-imports.download');
    Route::delete('/bulk-imports/{bulkImport}', [BulkImportController::class, 'destroy'])->name('bulk-imports.destroy');
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show'])
        ->name('meetings.show')
        ->middleware('check.locality');
    
    // Routes pour l'éclatement des réunions
    Route::get('/meetings/{meeting}/split', [MeetingController::class, 'showSplitForm'])
        ->name('meetings.split')
        ->middleware('check.locality');
    Route::post('/meetings/{meeting}/split', [MeetingController::class, 'splitMeeting'])
        ->name('meetings.split.store')
        ->middleware('check.locality');
    
    // Routes API pour l'éclatement (accessibles depuis le web)
    Route::get('/api/meetings/{meeting}/split/villages', [MeetingController::class, 'getAvailableVillagesForSplit'])
        ->name('api.meetings.split.villages')
        ->middleware('check.locality');
    Route::post('/api/meetings/{meeting}/split', [MeetingController::class, 'splitMeetingApi'])
        ->name('api.meetings.split')
        ->middleware('check.locality');
    
    // Route pour supprimer une sous-réunion
    Route::delete('/meetings/{meeting}/sub-meetings/{subMeeting}', [MeetingController::class, 'deleteSubMeeting'])
        ->name('meetings.sub-meetings.destroy')
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
    Route::post('/minutes/{minutes}/validate', [MeetingMinutesController::class, 'validates'])
        ->name('minutes.validate')
        ->middleware('role:president|President|admin|Admin'); // Sous-préfets et admins peuvent valider

    // Routes pour les résultats des villages
    Route::prefix('meetings/{meeting}/village-results')->name('village-results.')->middleware('check.locality')->group(function () {
        Route::get('/', [App\Http\Controllers\VillageResultController::class, 'index'])->name('index');
        Route::get('/{village}', [App\Http\Controllers\VillageResultController::class, 'show'])->name('show');
        Route::post('/{village}', [App\Http\Controllers\VillageResultController::class, 'store'])->name('store');
        Route::post('/{village}/submit', [App\Http\Controllers\VillageResultController::class, 'submit'])->name('submit');
        Route::post('/{village}/validate', [App\Http\Controllers\VillageResultController::class, 'validateResults'])
            ->name('validate')
            ->middleware('role:president|President|admin|Admin'); // Seuls les superviseurs peuvent valider
        Route::delete('/{village}', [App\Http\Controllers\VillageResultController::class, 'destroy'])->name('destroy');
    });

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
    Route::put('/local-committees/{localCommittee}', [\App\Http\Controllers\LocalCommitteeController::class, 'update'])
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
        
    Route::post('/meetings/{meeting}/validate', [MeetingController::class, 'validateMeeting'])
        ->name('meetings.validate')
        ->middleware('role:president|President|admin|Admin'); // Sous-préfets et admins peuvent valider
        
    Route::post('/meetings/{meeting}/invalidate', [MeetingController::class, 'invalidate'])
        ->name('meetings.invalidate')
        ->middleware('role:president|President|admin|Admin'); // Sous-préfets et admins peuvent invalider

    //Route::get('/village-representatives', [VillageRepresentativesController::class, 'index'])->name('village-representatives.index');

    Route::post('/local-committees/{committeeId}/save-villages', [LocalCommitteeController::class, 'saveVillages'])->name('local-committees.save-villages');



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

    // Route pour le suivi des paiements des cadres
    Route::get('/executive-payments', [\App\Http\Controllers\ExecutivePaymentController::class, 'index'])
        ->name('executive-payments.index')
        ->middleware('role:tresorier|admin');
    
    // Routes pour l'export et la gestion des paiements des cadres
    Route::get('/executive-payments/export/all', [\App\Http\Controllers\ExecutivePaymentController::class, 'exportAll'])
        ->name('executive-payments.export.all')
        ->middleware('role:tresorier|admin');
    Route::get('/executive-payments/export/pending', [\App\Http\Controllers\ExecutivePaymentController::class, 'exportPending'])
        ->name('executive-payments.export.pending')
        ->middleware('role:tresorier|admin');
    Route::post('/executive-payments/{executivePayment}/update-status', [\App\Http\Controllers\ExecutivePaymentController::class, 'updateStatus'])
        ->name('executive-payments.update-status')
        ->middleware('role:tresorier|admin');

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
    Route::post('/meetings/{meeting}/unpublish', [MeetingController::class, 'unpublish'])
        ->name('meetings.unpublish');
    Route::patch('/meetings/{meeting}/update-enrollments', [MeetingController::class, 'updateEnrollments'])
        ->middleware(['auth', 'web'])
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

    // Routes pour les réunions
    Route::prefix('meetings')->name('meetings.')->group(function () {
        Route::get('/', [MeetingController::class, 'index'])->name('index');
        Route::get('/create', [MeetingController::class, 'create'])->name('create');
        Route::post('/', [MeetingController::class, 'store'])->name('store');
        Route::get('/{meeting}', [MeetingController::class, 'show'])->name('show');
        Route::post('/{meeting}/cancel', [MeetingController::class, 'cancel'])->name('cancel');
        Route::post('/{meeting}/confirm', [MeetingController::class, 'confirm'])->name('confirm');
        Route::post('/{meeting}/prevalidate', [MeetingController::class, 'prevalidate'])->name('prevalidate');
        Route::post('/{meeting}/validate', [MeetingController::class, 'validateMeeting'])->name('validate');
        Route::post('/{meeting}/validate-attendance', [MeetingController::class, 'validateAttendance'])->name('validate-attendance');
        Route::post('/{meeting}/reject-attendance', [MeetingController::class, 'rejectAttendance'])->name('reject-attendance');
        Route::post('/{meeting}/submit-attendance', [MeetingController::class, 'submitAttendance'])->name('submit-attendance');
        Route::post('/{meeting}/cancel-attendance-submission', [MeetingController::class, 'cancelAttendanceSubmission'])->name('cancel-attendance-submission');
        Route::delete('/{meeting}', [MeetingController::class, 'destroy'])->name('destroy');
        // ... other meeting routes ...
    });

    Route::resource('representatives', RepresentativeController::class);
});

// Routes pour la validation par lien magique (en dehors du groupe meetings)
Route::get('/attendance/validate/{token}', [AttendanceValidationController::class, 'showValidationPage'])->name('attendance.validate-by-token');
Route::post('/attendance/validate/{token}', [AttendanceValidationController::class, 'validateByToken'])->name('attendance.validate-by-token.post');
Route::get('/attendance/validation/success/{token}', [AttendanceValidationController::class, 'showValidationPage'])->name('attendance.validation.success');
Route::get('/attendance/validation/error/{token}', [AttendanceValidationController::class, 'showValidationPage'])->name('attendance.validation.error');

Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    // ... existing routes ...

    // Routes pour les localités
    Route::get('/localities', [LocalityController::class, 'index'])->name('localities.index');
    Route::get('/localities/{locality}', [LocalityController::class, 'show'])->name('localities.show');

    // Documentation API
    Route::get('/api-doc', [App\Http\Controllers\Api\DocumentationController::class, 'index'])
        ->name('api.documentation');
});

Route::middleware(['auth'])->prefix('admin/app-versions')->group(function () {
    Route::get('/', [\App\Http\Controllers\AppVersionController::class, 'adminIndex'])->name('admin.app_versions.index');
    Route::post('/', [\App\Http\Controllers\AppVersionController::class, 'adminStore'])->name('admin.app_versions.store');
    Route::delete('/{id}', [\App\Http\Controllers\AppVersionController::class, 'adminDestroy'])->name('admin.app_versions.destroy');
});

Route::middleware(['auth'])->prefix('admin/device-stats')->group(function () {
    Route::get('/', [\App\Http\Controllers\Admin\DeviceStatsController::class, 'index'])->name('admin.device_stats.index');
    Route::get('/refresh', [\App\Http\Controllers\Admin\DeviceStatsController::class, 'refreshStats'])->name('admin.device_stats.refresh');
    Route::get('/device/{deviceId}', [\App\Http\Controllers\Admin\DeviceStatsController::class, 'showDevice'])->name('admin.device_stats.device');
    Route::get('/downloads/details', [\App\Http\Controllers\Admin\DeviceStatsController::class, 'downloadDetails'])->name('admin.device_stats.downloads');
});

Route::resource('payment-rates', PaymentRateController::class);
Route::resource('meeting-payments', MeetingPaymentController::class);
Route::resource('executive-payments', ExecutivePaymentController::class)->only(['index']);

Route::post('/meetings/{meeting}/attendance', [AttendanceController::class, 'store'])->name('meetings.attendance.store');
Route::delete('/meetings/{meeting}/attendance/{attendee}', [AttendanceController::class, 'destroy'])->name('meetings.attendance.destroy');

require __DIR__.'/auth.php';
