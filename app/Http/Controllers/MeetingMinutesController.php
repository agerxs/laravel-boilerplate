<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMinutes;
use Illuminate\Http\Request;
use App\Mail\MeetingMinutesSent;
use Illuminate\Support\Facades\Mail;

class MeetingMinutesController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $minutes = $meeting->minutes()->create([
            'content' => $validated['content'],
            'status' => 'draft'
        ]);

        // Mettre à jour le statut de la réunion
        $meeting->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Compte rendu créé avec succès',
            'minutes' => $minutes
        ]);
    }

    public function update(Request $request, MeetingMinutes $minutes)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'status' => 'required|in:draft,published'
        ]);

        $minutes->update($validated);

        if ($validated['status'] === 'published' && !$minutes->published_at) {
            $minutes->update(['published_at' => now()]);
        }

        return response()->json([
            'message' => 'Compte rendu mis à jour avec succès',
            'minutes' => $minutes->fresh()
        ]);
    }

    public function import(Request $request, Meeting $meeting)
    {
        $request->validate([
            'file' => 'required|file|mimes:doc,docx'
        ]);

        $file = $request->file('file');
        
        // Utilisez PhpWord pour lire le fichier
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getPathname());
        
        // Convertir en HTML
        $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
        $html = '';
        ob_start();
        $htmlWriter->save('php://output');
        $html = ob_get_clean();
        
        return response()->json([
            'content' => $html
        ]);
    }

    public function sendByEmail(Meeting $meeting)
    {
        try {
            $meeting->load(['minutes', 'attachments', 'participants']);

            // Envoyer aux participants qui sont des utilisateurs
            $meeting->participants()
                ->whereNotNull('user_id')
                ->with('user')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    if ($participant->user) {
                        Mail::to($participant->user->email)
                            ->send(new MeetingMinutesSent($meeting));
                    }
                });

            // Envoyer aux invités externes
            $meeting->participants()
                ->whereNotNull('guest_email')
                ->get()
                ->each(function ($participant) use ($meeting) {
                    Mail::to($participant->guest_email)
                        ->send(new MeetingMinutesSent($meeting));
                });

            return response()->json([
                'message' => 'Compte rendu envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'envoi du compte rendu'
            ], 500);
        }
    }
} 