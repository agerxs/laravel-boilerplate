<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Meeting;
use App\Services\PaymentListNotificationService;
use Illuminate\Support\Facades\Log;

class TestTreasurerNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:treasurer-notification {meeting_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test l\'envoi de notifications aux trésoriers pour une liste de paiement';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $meetingId = $this->argument('meeting_id');
        
        // Récupérer la réunion
        $meeting = Meeting::find($meetingId);
        if (!$meeting) {
            $this->error("Réunion avec l'ID {$meetingId} non trouvée.");
            return 1;
        }

        // Récupérer un utilisateur test (le premier utilisateur)
        $user = User::first();
        if (!$user) {
            $this->error("Aucun utilisateur trouvé dans la base de données.");
            return 1;
        }

        $notificationService = new PaymentListNotificationService();

        // Vérifier s'il y a des trésoriers
        if (!$notificationService->hasTreasurers()) {
            $this->warn("Aucun trésorier trouvé dans le système.");
            $this->info("Création d'un utilisateur trésorier de test...");
            
            // Créer un trésorier de test
            $treasurer = User::create([
                'name' => 'Trésorier Test',
                'email' => 'tresorier@test.com',
                'password' => bcrypt('password'),
            ]);
            $treasurer->assignRole('tresorier');
            
            $this->info("Trésorier de test créé: {$treasurer->email}");
        }

        $this->info("Envoi de la notification de test...");
        
        try {
            $notificationService->notifyTreasurers(
                $meeting,
                $user,
                'Liste_Paiement_Test.csv',
                5 // Nombre de représentants de test
            );
            
            $this->info("Notification envoyée avec succès !");
            $this->info("Nombre de trésoriers notifiés: " . $notificationService->getTreasurersCount());
            
        } catch (\Exception $e) {
            $this->error("Erreur lors de l'envoi de la notification: " . $e->getMessage());
            Log::error("Erreur test notification trésorier", [
                'meeting_id' => $meetingId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }

        return 0;
    }
} 