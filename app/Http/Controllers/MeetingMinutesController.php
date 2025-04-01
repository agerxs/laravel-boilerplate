<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMinutes;
use Illuminate\Http\Request;
use App\Mail\MeetingMinutesSent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
            'status' => 'required|in:draft,published,pending_validation,validated'
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

    /**
     * Demander la validation d'un compte-rendu par le sous-préfet
     */
    public function requestValidation(Request $request, MeetingMinutes $minutes)
    {
        // Vérifier si le compte-rendu est déjà validé ou en attente de validation
        if ($minutes->status === 'validated') {
            return response()->json([
                'message' => 'Ce compte-rendu a déjà été validé',
                'minutes' => $minutes
            ], 400);
        }

        if ($minutes->status === 'pending_validation') {
            return response()->json([
                'message' => 'Ce compte-rendu est déjà en attente de validation',
                'minutes' => $minutes
            ], 400);
        }

        // Mettre à jour le statut du compte-rendu
        $minutes->update([
            'status' => 'pending_validation',
            'validation_requested_at' => now()
        ]);

        // TODO: Notifier le sous-préfet qu'un compte-rendu est en attente de validation

        return response()->json([
            'message' => 'Demande de validation envoyée avec succès',
            'minutes' => $minutes->fresh()
        ]);
    }

    /**
     * Valider un compte-rendu (réservé aux sous-préfets)
     */
    public function validates(Request $request, MeetingMinutes $minutes)
    {
        // Vérifier si l'utilisateur est un sous-préfet
        if (!Auth::user()->hasRole(['sous-prefet', 'Sous-prefet'])) {
            return response()->json([
                'message' => 'Vous n\'êtes pas autorisé à valider ce compte-rendu'
            ], 403);
        }

        $validated = $request->validate([
            'validation_comments' => 'nullable|string',
            'decision' => 'required|in:validate,reject'
        ]);

        if ($validated['decision'] === 'validate') {
            // Valider le compte-rendu
            $minutes->update([
                'status' => 'validated',
                'validated_at' => now(),
                'validated_by' => Auth::id(),
                'validation_comments' => $validated['validation_comments']
            ]);

            return response()->json([
                'message' => 'Compte-rendu validé avec succès',
                'minutes' => $minutes->fresh()
            ]);
        } else {
            // Rejeter la validation et remettre en statut "published"
            $minutes->update([
                'status' => 'published',
                'validation_comments' => $validated['validation_comments']
            ]);

            return response()->json([
                'message' => 'Validation du compte-rendu rejetée',
                'minutes' => $minutes->fresh()
            ]);
        }
    }

    public function import(Request $request, Meeting $meeting)
    {
        // Valider la demande
        $request->validate([
            'content' => 'required|string',
        ]);

        // Vérifier si le compte rendu existe déjà
        if ($meeting->minutes) {
            // Mettre à jour le compte rendu existant
            $meeting->minutes->update([
                'content' => $request->input('content'),
                'status' => 'draft'
            ]);
            $minutes = $meeting->minutes;
        } else {
            // Créer un nouveau compte rendu
            $minutes = $meeting->minutes()->create([
                'content' => $request->input('content'),
                'status' => 'draft'
            ]);
        }

        // Mettre à jour le statut de la réunion
        $meeting->update(['status' => 'completed']);

        return response()->json([
            'message' => 'Compte rendu importé avec succès',
            'minutes' => $minutes
        ]);
    }

    public function sendByEmail(Request $request, Meeting $meeting)
    {
        try {
            // Valider que la réunion a bien un compte rendu
            if (!$meeting->minutes) {
                return response()->json([
                    'message' => 'Cette réunion n\'a pas de compte rendu'
                ], 400);
            }

            // Récupérer les destinataires
            $recipients = $request->input('recipients', []);

            // Envoyer le mail à chaque destinataire
            foreach ($recipients as $recipient) {
                Mail::to($recipient)->send(new MeetingMinutesSent($meeting));
            }

            return response()->json([
                'message' => 'Compte rendu envoyé avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi du compte rendu : ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Une erreur est survenue lors de l\'envoi du compte rendu'
            ], 500);
        }
    }
} 