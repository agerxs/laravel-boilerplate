<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\TestUsersSeeder;

class CreateTestUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:create-users {--force : Forcer la création même si des utilisateurs existent déjà}';

    /**
     * The console console description.
     *
     * @var string
     */
    protected $description = 'Créer des utilisateurs de test pour chaque profil avec leurs localités et comités associés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Démarrage de la création des utilisateurs de test...');
        
        if ($this->option('force')) {
            $this->warn('⚠️  Mode force activé - Les utilisateurs existants seront mis à jour');
        }

        try {
            // Exécuter le seeder
            $seeder = new TestUsersSeeder();
            $seeder->setCommand($this);
            $seeder->run();

            $this->newLine();
            $this->info('✅ Utilisateurs de test créés avec succès !');
            $this->newLine();
            
            // Afficher un résumé des comptes créés
            $this->displayTestAccountsSummary();
            
        } catch (\Exception $e) {
            $this->error('❌ Erreur lors de la création des utilisateurs de test : ' . $e->getMessage());
            $this->error('Trace : ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }

    private function displayTestAccountsSummary()
    {
        $this->info('📋 Récapitulatif des comptes de test créés :');
        $this->newLine();

        $headers = ['Rôle', 'Email', 'Téléphone', 'Mot de passe'];
        $rows = [
            ['Admin', 'admin@test.com', '0700000001', 'password123'],
            ['Président', 'president@test.com', '0700000002', 'password123'],
            ['Sous-Préfet', 'sousprefet@test.com', '0700000003', 'password123'],
            ['Secrétaire', 'secretaire@test.com', '0700000004', 'password123'],
            ['Tresorier', 'tresorier@test.com', '0700000005', 'password123'],
            ['Trésorier', 'tresorier@test.com', '0700000006', 'password123'],
            ['Superviseur', 'superviseur@test.com', '0700000007', 'password123'],
        ];

        $this->table($headers, $rows);
        
        $this->newLine();
        $this->info('🌍 Localités créées : Région Test > Département Test > Sous-Préfecture Test > Villages Test (3 villages)');
        $this->info('🏛️  Comité local créé : Comité Local Test');
        $this->newLine();
        $this->comment('💡 Conseil : Utilisez ces comptes pour tester les différentes fonctionnalités de l\'application');
    }
}

