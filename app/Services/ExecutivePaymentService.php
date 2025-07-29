<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\MeetingPayment;
use App\Models\PaymentRate;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class ExecutivePaymentService
{
    /**
     * Génère les paiements des cadres pour une réunion validée
     */
    public function generatePaymentsForMeeting(Meeting $meeting)
    {
        Log::info("=== DÉBUT GÉNÉRATION PAIEMENTS CADRES ===");
        Log::info("Réunion ID: {$meeting->id}, Titre: {$meeting->title}");
        Log::info("Comité local: {$meeting->localCommittee->name}");
        Log::info("Localité ID: {$meeting->localCommittee->locality_id}");
        
        // Récupérer les rôles de secrétaire et président
        $executiveRoles = Role::whereIn('name', ['secretaire', 'sous-prefet'])->get();
        Log::info("Rôles recherchés: secretaire, sous-prefet");
        Log::info("Rôles trouvés: " . $executiveRoles->pluck('name')->join(', '));
        
        if ($executiveRoles->isEmpty()) {
            Log::warning('Les rôles "secretaire" ou "sous-prefet" n\'ont pas été trouvés.');
            return 0;
        }

        // Récupérer les utilisateurs ayant ces rôles dans la localité de la réunion
        $localCommittee = $meeting->localCommittee;
        $executives = User::role($executiveRoles->pluck('name'))
            ->where('locality_id', $localCommittee->locality_id)
            ->get();

        Log::info("Cadres trouvés pour la localité {$localCommittee->locality_id}: {$executives->count()}");
        foreach ($executives as $exec) {
            Log::info("  - {$exec->name} (ID: {$exec->id}) avec rôles: " . $exec->roles->pluck('name')->join(', '));
        }

        $paymentsCreatedCount = 0;

        foreach ($executives as $executive) {
            Log::info("--- TRAITEMENT DE {$executive->name} (ID: {$executive->id}) ---");

            // Les cadres sont payés pour l'organisation et validation des réunions,
            // pas pour leur présence physique. Pas besoin de vérifier s'ils ont assisté.
            Log::info("{$executive->name} est cadre dans cette localité. Éligible pour paiement.");

            // Trouver le dernier paiement de cet utilisateur
            $lastPayment = MeetingPayment::where('user_id', $executive->id)
                ->latest('id')
                ->first();
            
            if ($lastPayment) {
                Log::info("Dernier paiement trouvé: ID {$lastPayment->id}, Réunion {$lastPayment->meeting_id}, Date: {$lastPayment->created_at}");
            } else {
                Log::info("Aucun paiement précédent trouvé pour {$executive->name}.");
            }
            
            // Récupérer toutes les réunions validées dans la localité depuis le dernier paiement
            $meetingsSinceLastPaymentQuery = DB::table('meetings')
                ->where('local_committee_id', $meeting->local_committee_id)
                ->where('status', 'validated')
                ->select('meetings.id', 'meetings.scheduled_date', 'meetings.title')
                ->orderBy('meetings.scheduled_date', 'asc');

            if ($lastPayment) {
                // Pour éviter de payer pour une réunion déjà incluse dans un paiement précédent,
                // on ne prend que les réunions après celle du dernier paiement.
                $lastPaidMeeting = Meeting::find($lastPayment->meeting_id);
                if ($lastPaidMeeting) {
                     $meetingsSinceLastPaymentQuery->where('meetings.scheduled_date', '>', $lastPaidMeeting->scheduled_date);
                     Log::info("Recherche des réunions après {$lastPaidMeeting->scheduled_date}");
                }
            } else {
                 Log::info("Recherche de toutes les réunions validées (aucun paiement précédent)");
            }
            
            $meetingsToPay = $meetingsSinceLastPaymentQuery->get();

            Log::info("Réunions trouvées à payer: {$meetingsToPay->count()}");
            foreach ($meetingsToPay as $meetingToPay) {
                Log::info("  - Réunion {$meetingToPay->id}: {$meetingToPay->title} ({$meetingToPay->scheduled_date})");
            }

            // Calculer combien de groupes de 2 réunions on peut former
            $numberOfPaymentGroups = floor($meetingsToPay->count() / 2);
            Log::info("Nombre de groupes de 2 réunions possibles: {$numberOfPaymentGroups}");

            if ($numberOfPaymentGroups > 0) {
                // Récupérer le taux de paiement pour le rôle de l'utilisateur
                $userRole = $executive->roles->first();
                Log::info("Rôle de l'utilisateur: {$userRole->name} (ID: {$userRole->id})");
                
                $paymentRate = PaymentRate::where('role', $userRole->name)->value('meeting_rate');

                Log::info("Recherche du taux de paiement pour le rôle {$userRole->name}...");
                if (!$paymentRate) {
                    Log::warning("Aucun taux de paiement trouvé pour le rôle: {$userRole->name}");
                    continue;
                }
                
                Log::info("Taux de paiement trouvé: {$paymentRate}");

                // Grouper les réunions par 2
                $meetingChunks = $meetingsToPay->chunk(2);
                Log::info("Nombre de chunks de réunions: " . $meetingChunks->count());
                
                foreach($meetingChunks as $index => $chunk) {
                    Log::info("Traitement du chunk " . ($index + 1) . " avec {$chunk->count()} réunion(s)");
                    if ($chunk->count() == 2) {
                        $meetingForPayment = $chunk->last();
                        Log::info("Création du paiement pour les réunions: " . $chunk->pluck('id')->join(' et '));
                        
                        // Créer le paiement avec les deux réunions déclencheuses
                        $payment = MeetingPayment::create([
                            'user_id' => $executive->id,
                            'meeting_id' => $meetingForPayment->id,
                            'amount' => $paymentRate,
                            'role' => $userRole->name,
                            'payment_status' => 'pending',
                            'triggering_meetings' => json_encode($chunk->pluck('id')->values()),
                        ]);
                        
                        $paymentsCreatedCount++;
                        Log::info("✅ Paiement créé avec succès: ID {$payment->id}");
                    } else {
                        Log::info("Chunk ignoré car il ne contient que {$chunk->count()} réunion(s) (il en faut 2)");
                    }
                }
            } else {
                Log::info("Pas assez de réunions pour créer un paiement (minimum 2 requises)");
            }
        }

        Log::info("=== FIN GÉNÉRATION PAIEMENTS CADRES ===");
        Log::info("Total des paiements créés: {$paymentsCreatedCount}");
        
        return $paymentsCreatedCount;
    }
} 