<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\AttendanceValidationToken;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AttendanceValidationNotificationService
{
    /**
     * Envoyer les notifications de validation des présences
     */
    public function sendValidationNotifications(Meeting $meeting)
    {
        try {
            // Récupérer les préfets de la localité
            $prefets = User::whereHas('roles', function ($query) {
                $query->where('name', 'prefet');
            })->where('locality_id', $meeting->localCommittee->locality_id)
            ->get();

            if ($prefets->isEmpty()) {
                Log::warning("Aucun préfet trouvé pour la localité {$meeting->localCommittee->locality->name}");
                return false;
            }

            $tokensCreated = 0;

            foreach ($prefets as $prefet) {
                if ($prefet->email) {
                    $token = AttendanceValidationToken::generateToken($meeting, $prefet->email);
                    
                    // Envoyer l'email
                    $this->sendValidationEmail($prefet, $meeting, $token);
                    
                    $tokensCreated++;
                }
            }

            Log::info("Notifications de validation envoyées pour la réunion {$meeting->id} à {$tokensCreated} préfets");
            return true;

        } catch (\Exception $e) {
            Log::error("Erreur lors de l'envoi des notifications de validation: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Envoyer l'email de validation
     */
    private function sendValidationEmail(User $prefet, Meeting $meeting, AttendanceValidationToken $token)
    {
        $validationUrl = route('attendance.validate-by-token', $token->token);
        
        $data = [
            'prefet' => $prefet,
            'meeting' => $meeting,
            'validationUrl' => $validationUrl,
            'expiresAt' => $token->expires_at->format('d/m/Y à H:i'),
        ];

        Mail::send('emails.attendance-validation', $data, function ($message) use ($prefet, $meeting) {
            $message->to($prefet->email, $prefet->name)
                   ->subject("Validation des présences - Réunion {$meeting->title}");
        });
    }

    /**
     * Valider les présences via un token
     */
    public function validateByToken(string $tokenString)
    {
        $token = AttendanceValidationToken::where('token', $tokenString)->first();

        if (!$token) {
            return ['success' => false, 'message' => 'Token invalide'];
        }

        if ($token->isUsed()) {
            return ['success' => false, 'message' => 'Ce lien a déjà été utilisé'];
        }

        if ($token->isExpired()) {
            return ['success' => false, 'message' => 'Ce lien a expiré'];
        }

        $meeting = $token->meeting;

        // Vérifier que les présences sont soumises
        if ($meeting->attendance_status !== 'submitted') {
            return ['success' => false, 'message' => 'Les présences ne sont pas soumises'];
        }

        // Vérifier qu'il y a des participants présents
        $presentAttendees = $meeting->attendees()->where('is_present', true)->count();
        if ($presentAttendees === 0) {
            return ['success' => false, 'message' => 'Aucun participant présent'];
        }

        // Marquer le token comme utilisé
        $token->update([
            'used_at' => now(),
            'used_by' => null, // Pas d'utilisateur connecté via lien magique
        ]);

        // Valider les présences
        $meeting->update([
            'attendance_validated_at' => now(),
            'attendance_validated_by' => null, // Pas d'utilisateur connecté
        ]);

        return [
            'success' => true, 
            'message' => 'Présences validées avec succès',
            'meeting' => $meeting
        ];
    }
} 