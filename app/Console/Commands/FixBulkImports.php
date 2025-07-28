<?php

namespace App\Console\Commands;

use App\Models\BulkImport;
use App\Models\Meeting;
use Illuminate\Console\Command;

class FixBulkImports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bulk-imports:fix';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Corriger le nombre de réunions créées pour tous les BulkImports';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔧 Correction des BulkImports...');

        $bulkImports = BulkImport::all();
        $totalFixed = 0;

        foreach ($bulkImports as $bulkImport) {
            $this->line("📋 Traitement du BulkImport ID: {$bulkImport->id}");

            // Compter les réunions liées directement
            $meetingsCount = Meeting::where('bulk_import_id', $bulkImport->id)->count();

            if ($meetingsCount == 0) {
                // Si pas de réunions liées, chercher les réunions récentes du même utilisateur et comité
                $recentMeetings = Meeting::where('created_by', $bulkImport->user_id)
                    ->where('local_committee_id', $bulkImport->local_committee_id)
                    ->where('created_at', '>=', $bulkImport->created_at)
                    ->count();

                $this->line("  - Réunions liées: {$meetingsCount}");
                $this->line("  - Réunions récentes du même utilisateur/comité: {$recentMeetings}");

                if ($recentMeetings > 0) {
                    $bulkImport->update([
                        'meetings_created' => $recentMeetings
                    ]);
                    $totalFixed++;
                    $this->info("  ✅ Mis à jour avec {$recentMeetings} réunions");
                } else {
                    $this->warn("  ⚠️ Aucune réunion trouvée");
                }
            } else {
                // Mettre à jour avec le bon nombre
                $bulkImport->update([
                    'meetings_created' => $meetingsCount
                ]);
                $totalFixed++;
                $this->info("  ✅ Mis à jour avec {$meetingsCount} réunions");
            }
        }

        $this->newLine();
        $this->info("🎉 Correction terminée ! {$totalFixed} BulkImport(s) mis à jour.");
    }
}
