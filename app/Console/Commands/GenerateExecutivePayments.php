<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Meeting;
use App\Services\ExecutivePaymentService;
use Illuminate\Support\Facades\Log;

class GenerateExecutivePayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payments:generate-executive {--meeting-id= : ID spécifique d\'une réunion} {--dry-run : Afficher ce qui serait fait sans l\'exécuter}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Générer automatiquement les paiements des cadres pour les réunions validées';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('=== Génération des paiements des cadres ===');
        
        $meetingId = $this->option('meeting-id');
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->warn('Mode DRY-RUN activé - Aucune modification ne sera effectuée');
        }
        
        // Récupérer les réunions validées
        $query = Meeting::where('status', 'validated');
        
        if ($meetingId) {
            $query->where('id', $meetingId);
            $this->info("Traitement de la réunion ID: {$meetingId}");
        } else {
            $this->info('Traitement de toutes les réunions validées');
        }
        
        $meetings = $query->get();
        
        if ($meetings->isEmpty()) {
            $this->warn('Aucune réunion validée trouvée.');
            return 0;
        }
        
        $this->info("Nombre de réunions à traiter : {$meetings->count()}");
        
        $service = new ExecutivePaymentService();
        $totalPayments = 0;
        $processedMeetings = 0;
        
        $progressBar = $this->output->createProgressBar($meetings->count());
        $progressBar->start();
        
        foreach ($meetings as $meeting) {
            try {
                if (!$dryRun) {
                    $paymentsCount = $service->generatePaymentsForMeeting($meeting);
                } else {
                    // En mode dry-run, simuler le calcul
                    $paymentsCount = $this->simulatePaymentsCount($meeting, $service);
                }
                
                if ($paymentsCount > 0) {
                    $totalPayments += $paymentsCount;
                    $processedMeetings++;
                    
                    if ($dryRun) {
                        $this->line("\n  - Réunion #{$meeting->id} ({$meeting->title}) : {$paymentsCount} paiements seraient générés");
                    } else {
                        $this->line("\n  - Réunion #{$meeting->id} ({$meeting->title}) : {$paymentsCount} paiements générés");
                    }
                }
                
            } catch (\Exception $e) {
                $this->error("\nErreur lors du traitement de la réunion #{$meeting->id} : " . $e->getMessage());
                Log::error("Erreur génération paiements réunion #{$meeting->id}", [
                    'error' => $e->getMessage(),
                    'meeting' => $meeting->toArray()
                ]);
            }
            
            $progressBar->advance();
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        if ($dryRun) {
            $this->info("=== RÉSUMÉ DRY-RUN ===");
            $this->info("Réunions qui seraient traitées : {$processedMeetings}");
            $this->info("Paiements qui seraient générés : {$totalPayments}");
        } else {
            $this->info("=== RÉSUMÉ ===");
            $this->info("Réunions traitées : {$processedMeetings}");
            $this->info("Paiements générés : {$totalPayments}");
        }
        
        $this->info('=== Fin de la génération ===');
        
        return 0;
    }
    
    /**
     * Simuler le nombre de paiements qui seraient générés (mode dry-run)
     */
    private function simulatePaymentsCount(Meeting $meeting, ExecutivePaymentService $service): int
    {
        // Logique simplifiée pour simuler le calcul
        $committee = $meeting->localCommittee;
        if (!$committee) {
            return 0;
        }
        
        $executives = \App\Models\User::where('locality_id', $committee->locality_id)
            ->whereHas('roles', function($query) {
                $query->whereIn('name', ['secretaire', 'sous-prefet']);
            })
            ->count();
        
        // Compter les réunions validées pour ce comité
        $validatedMeetingsCount = Meeting::where('local_committee_id', $committee->id)
            ->where('status', 'validated')
            ->count();
        
        // Calculer les paiements (1 paiement pour chaque groupe de 2 réunions)
        $paymentsPerExecutive = floor($validatedMeetingsCount / 2);
        
        return $executives * $paymentsPerExecutive;
    }
}
