<?php

use App\Http\Controllers\MeetingPaymentExportController;
use App\Http\Controllers\MeetingPaymentListController;
use App\Http\Controllers\PaymentJustificationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])->group(function () {
    
    // Routes pour les listes de paiement
    Route::prefix('meeting-payments/lists')->name('meeting-payments.lists.')->group(function () {
        Route::get('/', [MeetingPaymentListController::class, 'index'])->name('index');
        Route::get('/{paymentList}', [MeetingPaymentListController::class, 'show'])->name('show');
        Route::put('/{paymentList}', [MeetingPaymentListController::class, 'update'])->name('update');
        Route::post('/{paymentList}/submit', [MeetingPaymentListController::class, 'submit'])->name('submit');
        Route::post('/{paymentList}/validate', [MeetingPaymentListController::class, 'validate'])->name('validate');
        Route::post('/{paymentList}/reject', [MeetingPaymentListController::class, 'reject'])->name('reject');
        Route::post('/validate-all', [MeetingPaymentListController::class, 'validateAll'])->name('validate-all');
        Route::post('/{paymentList}/validate-item/{item}', [MeetingPaymentListController::class, 'validateItem'])->name('validate-item');
        Route::post('/{paymentList}/invalidate-item/{item}', [MeetingPaymentListController::class, 'invalidateItem'])->name('invalidate-item');
        
        // Route pour récupérer les participants
        Route::get('/{paymentList}/participants', [MeetingPaymentListController::class, 'getParticipants'])->name('participants');
        
        // Routes d'export
        Route::get('/export', [MeetingPaymentListController::class, 'exportPaymentLists'])->name('export');
        Route::get('/export-single/{meeting}', [MeetingPaymentListController::class, 'exportSingleMeeting'])->name('export-single');
    });
    
    // Routes pour les exports avec tracking
    Route::prefix('meeting-payments/export')->name('meeting-payments.export.')->group(function () {
        Route::post('/single/{paymentList}', [MeetingPaymentExportController::class, 'exportSingle'])->name('single');
        Route::post('/multiple', [MeetingPaymentExportController::class, 'exportMultiple'])->name('multiple');
        Route::post('/{paymentList}/mark-paid', [MeetingPaymentExportController::class, 'markAsPaid'])->name('mark-paid');
        Route::post('/mark-paid-multiple', [MeetingPaymentExportController::class, 'markMultipleAsPaid'])->name('mark-paid-multiple');
    });
    
    // Routes pour les pièces justificatives
    Route::prefix('meeting-payments/{paymentList}/justifications')->name('meeting-payments.justifications.')->group(function () {
        Route::get('/', [PaymentJustificationController::class, 'index'])->name('index');
        Route::post('/', [PaymentJustificationController::class, 'store'])->name('store');
        Route::put('/{justification}', [PaymentJustificationController::class, 'update'])->name('update');
        Route::delete('/{justification}', [PaymentJustificationController::class, 'destroy'])->name('destroy');
        Route::get('/{justification}/download', [PaymentJustificationController::class, 'download'])->name('download');
    });
});
