<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\EnrollmentRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EnrollmentRequestController extends Controller
{
    public function index(Meeting $meeting)
    {
        $requests = $meeting->enrollmentRequests()
            ->orderBy('created_at', 'desc')
            ->get();

        if (request()->wantsJson()) {
            return response()->json([
                'requests' => $requests
            ]);
        }

        return Inertia::render('Meetings/Show', [
            'meeting' => $meeting,
            'enrollmentRequests' => $requests
        ]);
    }

    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $enrollmentRequest = $meeting->enrollmentRequests()->create([
            ...$validated,
            'status' => 'pending'
        ]);

        return redirect()->back()->with('success', 'Demande d\'enrôlement créée avec succès');
    }

    public function update(Request $request, EnrollmentRequest $enrollmentRequest)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:255',
            'notes' => 'nullable|string'
        ]);

        $enrollmentRequest->update($validated);

        return redirect()->back()->with('success', 'Demande d\'enrôlement mise à jour avec succès');
    }

    public function destroy(EnrollmentRequest $enrollmentRequest)
    {
        $enrollmentRequest->delete();
        return redirect()->back()->with('success', 'Personne supprimée avec succès');
    }
} 