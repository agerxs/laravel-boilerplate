<?php

namespace App\Http\Controllers;

use App\Services\AttendanceValidationNotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AttendanceValidationController extends Controller
{
    /**
     * Afficher la page de validation par token
     */
    public function showValidationPage(string $token)
    {
        $notificationService = app(AttendanceValidationNotificationService::class);
        $result = $notificationService->validateByToken($token);

        if (!$result['success']) {
            return Inertia::render('AttendanceValidation/Error', [
                'message' => $result['message'],
                'token' => $token
            ]);
        }

        return Inertia::render('AttendanceValidation/Success', [
            'message' => $result['message'],
            'meeting' => $result['meeting']
        ]);
    }

    /**
     * Valider les présences via token (action POST)
     */
    public function validateByToken(Request $request, string $token)
    {
        $notificationService = app(AttendanceValidationNotificationService::class);
        $result = $notificationService->validateByToken($token);

        if ($request->wantsJson()) {
            return response()->json($result);
        }

        if (!$result['success']) {
            return redirect()->route('attendance.validation.error', $token)
                ->with('error', $result['message']);
        }

        return redirect()->route('attendance.validation.success', $token)
            ->with('success', $result['message']);
    }
} 