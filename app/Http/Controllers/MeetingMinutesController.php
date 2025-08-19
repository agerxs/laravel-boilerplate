<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMinutes;
use App\Http\Resources\MeetingMinutesResource;
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
            // Nouveaux champs pour les résultats des villages
            'people_to_enroll_count' => 'nullable|integer|min:0',
            'people_enrolled_count' => 'nullable|integer|min:0',
            'cmu_cards_available_count' => 'nullable|integer|min:0',
            'cmu_cards_distributed_count' => 'nullable|integer|min:0',
            'complaints_received_count' => 'nullable|integer|min:0',
            'complaints_processed_count' => 'nullable|integer|min:0',
        ]);

        // Validation supplémentaire pour s'assurer que les nombres traités ne dépassent pas les nombres reçus
        if (isset($validated['people_enrolled_count']) && isset($validated['people_to_enroll_count'])) {
            if ($validated['people_enrolled_count'] > $validated['people_to_enroll_count']) {
                return response()->json([
                    'message' => 'Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler'
                ], 422);
            }
        }

        if (isset($validated['cmu_cards_distributed_count']) && isset($validated['cmu_cards_available_count'])) {
            if ($validated['cmu_cards_distributed_count'] > $validated['cmu_cards_available_count']) {
                return response()->json([
                    'message' => 'Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles'
                ], 422);
            }
        }

        if (isset($validated['complaints_processed_count']) && isset($validated['complaints_received_count'])) {
            if ($validated['complaints_processed_count'] > $validated['complaints_received_count']) {
                return response()->json([
                    'message' => 'Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues'
                ], 422);
            }
        }

        $minutes = $meeting->minutes()->create([
            'content' => $validated['content'],
            'status' => 'draft',
            'people_to_enroll_count' => $validated['people_to_enroll_count'] ?? null,
            'people_enrolled_count' => $validated['people_enrolled_count'] ?? null,
            'cmu_cards_available_count' => $validated['cmu_cards_available_count'] ?? null,
            'cmu_cards_distributed_count' => $validated['cmu_cards_distributed_count'] ?? null,
            'complaints_received_count' => $validated['complaints_received_count'] ?? null,
            'complaints_processed_count' => $validated['complaints_processed_count'] ?? null,
        ]);

        // Ne pas changer automatiquement le statut de la réunion

        return response()->json([
            'message' => 'Compte rendu créé avec succès',
            'minutes' => new MeetingMinutesResource($minutes)
        ]);
    }

    public function update(Request $request, MeetingMinutes $minutes)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'status' => 'required|in:draft,published,pending_validation,validated',
            // Nouveaux champs pour les résultats des villages
            'people_to_enroll_count' => 'nullable|integer|min:0',
            'people_enrolled_count' => 'nullable|integer|min:0',
            'cmu_cards_available_count' => 'nullable|integer|min:0',
            'cmu_cards_distributed_count' => 'nullable|integer|min:0',
            'complaints_received_count' => 'nullable|integer|min:0',
            'complaints_processed_count' => 'nullable|integer|min:0',
        ]);

        // Validation supplémentaire pour s'assurer que les nombres traités ne dépassent pas les nombres reçus
        if (isset($validated['people_enrolled_count']) && isset($validated['people_to_enroll_count'])) {
            if ($validated['people_enrolled_count'] > $validated['people_to_enroll_count']) {
                return response()->json([
                    'message' => 'Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler'
                ], 422);
            }
        }

        if (isset($validated['cmu_cards_distributed_count']) && isset($validated['cmu_cards_available_count'])) {
            if ($validated['cmu_cards_distributed_count'] > $validated['cmu_cards_available_count']) {
                return response()->json([
                    'message' => 'Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles'
                ], 422);
            }
        }

        if (isset($validated['complaints_processed_count']) && isset($validated['complaints_received_count'])) {
            if ($validated['complaints_processed_count'] > $validated['complaints_received_count']) {
                return response()->json([
                    'message' => 'Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues'
                ], 422);
            }
        }

        $minutes->update($validated);

        if ($validated['status'] === 'published' && !$minutes->published_at) {
            $minutes->update(['published_at' => now()]);
        }

        return response()->json([
            'message' => 'Compte rendu mis à jour avec succès',
            'minutes' => new MeetingMinutesResource($minutes->fresh())
        ]);
    }

    /**
     * Demander la validation d'un compte-rendu par le président
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

        // TODO: Notifier le président qu'un compte-rendu est en attente de validation

        return response()->json([
            'message' => 'Demande de validation envoyée avec succès',
            'minutes' => new MeetingMinutesResource($minutes->fresh())
        ]);
    }

    /**
     * Valider un compte-rendu (réservé aux présidents)
     */
    public function validates(Request $request, MeetingMinutes $minutes)
    {
        // Vérifier si l'utilisateur est un président
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['president', 'President'])) {
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
                'minutes' => new MeetingMinutesResource($minutes->fresh())
            ]);
        } else {
            // Rejeter la validation et remettre en statut "published"
            $minutes->update([
                'status' => 'published',
                'validation_comments' => $validated['validation_comments']
            ]);

            return response()->json([
                'message' => 'Validation du compte-rendu rejetée',
                'minutes' => new MeetingMinutesResource($minutes->fresh())
            ]);
        }
    }

    public function import(Request $request, Meeting $meeting)
    {
        // Valider la demande
        $request->validate([
            'content' => 'required|string',
            // Nouveaux champs pour les résultats des villages
            'people_to_enroll_count' => 'nullable|integer|min:0',
            'people_enrolled_count' => 'nullable|integer|min:0',
            'cmu_cards_available_count' => 'nullable|integer|min:0',
            'cmu_cards_distributed_count' => 'nullable|integer|min:0',
            'complaints_received_count' => 'nullable|integer|min:0',
            'complaints_processed_count' => 'nullable|integer|min:0',
        ]);

        // Validation supplémentaire pour s'assurer que les nombres traités ne dépassent pas les nombres reçus
        if ($request->input('people_enrolled_count') && $request->input('people_to_enroll_count')) {
            if ($request->input('people_enrolled_count') > $request->input('people_to_enroll_count')) {
                return response()->json([
                    'message' => 'Le nombre de personnes enrôlées ne peut pas dépasser le nombre de personnes à enrôler'
                ], 422);
            }
        }

        if ($request->input('cmu_cards_distributed_count') && $request->input('cmu_cards_available_count')) {
            if ($request->input('cmu_cards_distributed_count') > $request->input('cmu_cards_available_count')) {
                return response()->json([
                    'message' => 'Le nombre de cartes distribuées ne peut pas dépasser le nombre de cartes disponibles'
                ], 422);
            }
        }

        if ($request->input('complaints_processed_count') && $request->input('complaints_received_count')) {
            if ($request->input('complaints_processed_count') > $request->input('complaints_received_count')) {
                return response()->json([
                    'message' => 'Le nombre de réclamations traitées ne peut pas dépasser le nombre de réclamations reçues'
                ], 422);
            }
        }

        // Vérifier si le compte rendu existe déjà
        if ($meeting->minutes) {
            // Mettre à jour le compte rendu existant
            $meeting->minutes->update([
                'content' => $request->input('content'),
                'status' => 'draft',
                'people_to_enroll_count' => $request->input('people_to_enroll_count'),
                'people_enrolled_count' => $request->input('people_enrolled_count'),
                'cmu_cards_available_count' => $request->input('cmu_cards_available_count'),
                'cmu_cards_distributed_count' => $request->input('cmu_cards_distributed_count'),
                'complaints_received_count' => $request->input('complaints_received_count'),
                'complaints_processed_count' => $request->input('complaints_processed_count'),
            ]);
            $minutes = $meeting->minutes;
        } else {
            // Créer un nouveau compte rendu
            $minutes = $meeting->minutes()->create([
                'content' => $request->input('content'),
                'status' => 'draft',
                'people_to_enroll_count' => $request->input('people_to_enroll_count'),
                'people_enrolled_count' => $request->input('people_enrolled_count'),
                'cmu_cards_available_count' => $request->input('cmu_cards_available_count'),
                'cmu_cards_distributed_count' => $request->input('cmu_cards_distributed_count'),
                'complaints_received_count' => $request->input('complaints_received_count'),
                'complaints_processed_count' => $request->input('complaints_processed_count'),
            ]);
        }

        // Ne pas changer automatiquement le statut de la réunion

        return response()->json([
            'message' => 'Compte rendu importé avec succès',
            'minutes' => new MeetingMinutesResource($minutes)
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