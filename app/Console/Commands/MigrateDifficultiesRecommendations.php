<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateDifficultiesRecommendations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:difficulties-recommendations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate difficulties and recommendations from meeting_minutes to meetings table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting migration of difficulties and recommendations...');

        try {
            // Vérifier si les colonnes existent encore dans meeting_minutes
            $columns = DB::select("SHOW COLUMNS FROM meeting_minutes LIKE 'difficulties'");
            if (empty($columns)) {
                $this->warn('Column "difficulties" does not exist in meeting_minutes table. Migration may have already been run.');
                return;
            }

            // Récupérer toutes les réunions qui ont des difficultés ou recommandations dans meeting_minutes
            $minutesWithData = DB::table('meeting_minutes')
                ->whereNotNull('difficulties')
                ->orWhereNotNull('recommendations')
                ->get();

            $this->info("Found {$minutesWithData->count()} meeting minutes with difficulties or recommendations.");

            $migrated = 0;
            foreach ($minutesWithData as $minute) {
                // Mettre à jour la réunion correspondante
                $updated = DB::table('meetings')
                    ->where('id', $minute->meeting_id)
                    ->update([
                        'difficulties' => $minute->difficulties,
                        'recommendations' => $minute->recommendations,
                        'updated_at' => now(),
                    ]);

                if ($updated) {
                    $migrated++;
                    $this->line("Migrated data for meeting ID: {$minute->meeting_id}");
                }
            }

            $this->info("Successfully migrated {$migrated} meetings.");
            $this->info('Migration completed successfully!');

        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
