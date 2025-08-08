<?php

namespace App\Services;

use App\Models\User;
use App\Models\Meeting;
use App\Notifications\PaymentListGenerated;
use Illuminate\Support\Facades\Log;

class PaymentListNotificationService
{
    /**
     * Envoyer des notifications aux trésoriers lorsqu'une liste de paiement est générée
     */
    public function notifyTreasurers(Meeting $meeting, User $submittedBy, string $fileName, int $representativesCount): void
    {
        try {
            // Récupérer tous les utilisateurs avec le rôle trésorier
            $treasurers = User::role('tresorier')->get();
            
            if ($treasurers->isEmpty()) {
                Log::warning('Aucun trésorier trouvé pour la notification de liste de paiement', [
                    'meeting_id' => $meeting->id,
                    'submitted_by' => $submittedBy->id
                ]);
                return;
            }

            // Envoyer la notification à chaque trésorier
            foreach ($treasurers as $treasurer) {
                $treasurer->notify(new PaymentListGenerated(
                    $meeting,
                    $submittedBy,
                    $fileName,
                    $representativesCount
                ));
            }

            Log::info('Notifications de liste de paiement envoyées aux trésoriers', [
                'meeting_id' => $meeting->id,
                'treasurers_count' => $treasurers->count(),
                'representatives_count' => $representativesCount,
                'file_name' => $fileName
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi des notifications aux trésoriers', [
                'meeting_id' => $meeting->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Vérifier si des trésoriers existent dans le système
     */
    public function hasTreasurers(): bool
    {
        return User::role('tresorier')->exists();
    }

    /**
     * Obtenir le nombre de trésoriers dans le système
     */
    public function getTreasurersCount(): int
    {
        return User::role('tresorier')->count();
    }
} 