<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SubPrefectureController;
use App\Http\Controllers\LocalCommitteeController;
use App\Http\Controllers\MeetingController;

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/sub-prefectures', [SubPrefectureController::class, 'index']);
Route::get('/sub-prefectures/{subPrefectureId}/villages', [SubPrefectureController::class, 'villages']);
Route::get('/local-committees', [LocalCommitteeController::class, 'index'])->name('localCommittees.index');
Route::get('/meetings/create', [MeetingController::class, 'create'])->name('meetings.create');
Route::post('/meetings', [MeetingController::class, 'store'])->name('meetings.store');
Route::get('/meetings/{meeting}/edit', [MeetingController::class, 'edit'])->name('meetings.edit');
Route::put('/meetings/{meeting}', [MeetingController::class, 'update'])->name('meetings.update');
Route::put('/meetings/{meeting}/reschedule', [MeetingController::class, 'reschedule'])->name('meetings.reschedule');
Route::get('/meetings/{meeting}/reschedule', [MeetingController::class, 'showRescheduleForm'])->name('meetings.reschedule.form');
