<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\AttendeeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocalCommitteeController as ApiLocalCommitteeController;
use App\Http\Controllers\SubPrefectureController;
use App\Http\Controllers\LocalCommitteeController;
use App\Http\Controllers\Api\OfflineController;
use App\Http\Controllers\Api\LocalityController;
use App\Http\Controllers\Api\LocalityTypeController;
use App\Http\Controllers\Api\RepresentativeController as ApiRepresentativeController;
use App\Http\Controllers\RepresentativeController;
use App\Http\Controllers\Api\PaymentListController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Api\MeetingSplitController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

// Routes publiques
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Routes protégées
Route::middleware(['auth:sanctum'])->group(function () {
    // Routes d'authentification
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Routes pour les réunions
    Route::get('/meetings', [MeetingController::class, 'index']);
    Route::get('/meetings/{meeting}', [MeetingController::class, 'show']);
    Route::post('/meetings', [MeetingController::class, 'store']);
    Route::put('/meetings/{meeting}', [MeetingController::class, 'update']);
    Route::delete('/meetings/{meeting}', [MeetingController::class, 'destroy']);
    
    // Actions sur les réunions
    Route::post('/meetings/{meeting}/confirm', [MeetingController::class, 'confirm']);
    Route::post('/meetings/{meeting}/prevalidate', [MeetingController::class, 'prevalidate']);
    Route::post('/meetings/{meeting}/validate', [MeetingController::class, 'validateMeeting']);
    Route::post('/meetings/{meeting}/cancel', [MeetingController::class, 'cancel']);
    Route::post('/meetings/{meeting}/reschedule', [MeetingController::class, 'reschedule']);
    Route::post('/meetings/{meeting}/finalize', [MeetingController::class, 'finalize']);
    
    // Gestion des présences
    Route::post('/meetings/{meeting}/attendance', [MeetingController::class, 'markAttendance']);
    Route::post('/meetings/{meeting}/attendances/submit', [MeetingController::class, 'submitAttendanceList']);
    Route::post('/meetings/{meeting}/attendances/submit-with-photos', [MeetingController::class, 'submitAttendanceListWithPhotos']);
    Route::post('/meetings/{meeting}/attendances/validate', [MeetingController::class, 'validateAttendance']);
    Route::post('/meetings/{meeting}/attendances/reject', [MeetingController::class, 'rejectAttendanceList']);
    Route::get('/meetings/{meeting}/attendances/export', [MeetingController::class, 'exportAttendancePDF']);
    
    Route::post('/meetings/{meeting}/enroll', [MeetingController::class, 'enroll']);
    Route::post('/meetings/{meeting}/unenroll', [MeetingController::class, 'unenroll']);

    // Routes pour l'éclatement des réunions
    Route::prefix('meetings/{meeting}/split')->group(function () {
        Route::get('/villages', [MeetingSplitController::class, 'getAvailableVillages'])->name('api.meetings.split.villages');
        Route::post('/', [MeetingSplitController::class, 'splitMeeting'])->name('api.meetings.split');
        Route::post('/consolidate', [MeetingSplitController::class, 'consolidateResults'])->name('api.meetings.split.consolidate');
        Route::get('/details', [MeetingSplitController::class, 'getMeetingWithSubMeetings'])->name('api.meetings.split.details');
    });

    Route::post('/attachments/upload', [AttachmentController::class, 'upload']);
    Route::resource('attachments', AttachmentController::class);
    Route::post('/attachments/upload-mobile', [AttachmentController::class, 'uploadFromMobile']);
    // Routes pour les participants
    Route::get('/meetings/{meeting}/attendees', [AttendeeController::class, 'index']);
    Route::post('/attendees/{attendee}/present', [AttendeeController::class, 'markPresent']);
    Route::post('/attendees/{attendee}/absent', [AttendeeController::class, 'markAbsent']);
    Route::post('/attendees/{attendee}/comment', [AttendeeController::class, 'addComment']);
    Route::post('meetings/attendees/{attendee}/confirm-presence-with-photo', [AttendeeController::class, 'confirmPresenceWithPhoto'])
        ->name('meetings.attendees.confirm-presence-with-photo');
    Route::post('meetings/attendees/{attendee}/delete-photo', [AttendeeController::class, 'deletePhoto'])
        ->name('meetings.attendees.delete-photo');

    // Routes pour les données de référence
    Route::get('/sub-prefectures', [SubPrefectureController::class, 'index']);
    Route::get('/local-committees', [ApiLocalCommitteeController::class, 'index']);
    Route::get('/local-committees/{localCommittee}', [ApiLocalCommitteeController::class, 'show']);
    
    // Routes pour l'import en lot
    Route::post('/bulk-imports', [\App\Http\Controllers\Api\BulkImportController::class, 'store']);
    Route::get('/bulk-imports', [\App\Http\Controllers\Api\BulkImportController::class, 'index']);
    Route::get('/bulk-imports/{bulkImport}', [\App\Http\Controllers\Api\BulkImportController::class, 'show']);
    Route::post('/bulk-imports/{bulkImport}/process', [\App\Http\Controllers\Api\BulkImportController::class, 'process']);
    Route::get('/bulk-imports/{bulkImport}/download', [\App\Http\Controllers\Api\BulkImportController::class, 'download']);

    // Routes pour le mode hors ligne
    Route::post('/offline/queue', [OfflineController::class, 'queueOperation']);
    Route::post('/offline/photos', [OfflineController::class, 'queuePhoto']);
    Route::post('/offline/sync', [OfflineController::class, 'sync']);
    Route::get('/offline/status', [OfflineController::class, 'getSyncStatus']);
    Route::post('/offline/conflicts/resolve', [OfflineController::class, 'resolveConflict']);
    Route::get('/offline/data', [OfflineController::class, 'getLocalData']);

    // Routes pour les représentants
    Route::prefix('representatives')->group(function () {
        Route::get('/', [ApiRepresentativeController::class, 'index']);
        Route::post('/', [ApiRepresentativeController::class, 'store']);
        Route::get('/mine', [ApiRepresentativeController::class, 'myRepresentatives']);
        Route::get('/{representative}', [ApiRepresentativeController::class, 'show']);
        Route::put('/{representative}', [ApiRepresentativeController::class, 'update']);
        Route::delete('/{representative}', [ApiRepresentativeController::class, 'destroy']);
        
    });

    // Endpoint villages pour l'utilisateur connecté
    Route::get('/localities/villages', [LocalityController::class, 'villages']);
    
    // Routes pour les résultats des villages
    Route::prefix('meetings/{meeting}/village-results')->group(function () {
        Route::get('/', [App\Http\Controllers\VillageResultController::class, 'index'])->name('api.village-results.index');
        Route::get('/{village}', [App\Http\Controllers\VillageResultController::class, 'show'])->name('api.village-results.show');
        Route::post('/{village}', [App\Http\Controllers\VillageResultController::class, 'store'])->name('api.village-results.store');
        Route::post('/{village}/submit', [App\Http\Controllers\VillageResultController::class, 'submit'])->name('api.village-results.submit');
        Route::post('/{village}/validate', [App\Http\Controllers\VillageResultController::class, 'validateResults'])->name('api.village-results.validate');
        Route::delete('/{village}', [App\Http\Controllers\VillageResultController::class, 'destroy'])->name('api.village-results.destroy');
    });
});

Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
Route::put('/meetings/{meeting}/reschedule', [MeetingController::class, 'reschedule'])->name('meetings.reschedule');
Route::get('/meetings/{meeting}/reschedule', [MeetingController::class, 'showRescheduleForm'])->name('meetings.reschedule.form');

    // Routes pour les localités
    Route::prefix('localities')->group(function () {
        Route::get('/', [LocalityController::class, 'index']);
        Route::get('/{locality}', [LocalityController::class, 'show']);
        Route::put('/{locality}', [LocalityController::class, 'update']);
        Route::get('/{locality}/children', [LocalityController::class, 'children']);
    });

// Routes pour les types de localités
Route::prefix('locality-types')->group(function () {
    Route::get('/', [LocalityTypeController::class, 'index']);
    Route::get('/{type}', [LocalityTypeController::class, 'show']);
    Route::post('/', [LocalityTypeController::class, 'store']);
    Route::put('/{type}', [LocalityTypeController::class, 'update']);
    Route::delete('/{type}', [LocalityTypeController::class, 'destroy']);
    Route::get('/{type}/localities', [LocalityTypeController::class, 'localities']);
});

Route::prefix('payment-lists')->group(function () {
    Route::get('/', [PaymentListController::class, 'index']);
    Route::get('/{id}', [PaymentListController::class, 'show']);
});

Route::prefix('app-versions')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\AppVersionController::class, 'index']);
    Route::post('/', [\App\Http\Controllers\Api\AppVersionController::class, 'store']);
    Route::get('/latest', [\App\Http\Controllers\Api\AppVersionController::class, 'latest']);
    Route::get('/{id}', [\App\Http\Controllers\Api\AppVersionController::class, 'show']);
});
