<?php
/**
 * Script de test pour le TestUsersSeeder
 * 
 * Ce script permet de tester rapidement le seeder sans passer par Artisan
 * Usage: php test-seeder.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Test du TestUsersSeeder\n";
echo "==========================\n\n";

try {
    // Créer une instance du seeder
    $seeder = new \Database\Seeders\TestUsersSeeder();
    
    // Créer un mock de la commande pour les logs
    $command = new class extends \Illuminate\Console\Command {
        public function info($string) { echo "✅ {$string}\n"; }
        public function warn($string) { echo "⚠️  {$string}\n"; }
        public function error($string) { echo "❌ {$string}\n"; }
    };
    
    $seeder->setCommand($command);
    
    // Exécuter le seeder
    echo "🚀 Démarrage de la création des utilisateurs de test...\n\n";
    $seeder->run();
    
    echo "\n🎉 Test terminé avec succès !\n";
    
    // Afficher un résumé des utilisateurs créés
    echo "\n📋 Résumé des utilisateurs créés :\n";
    $users = \App\Models\User::where('email', 'like', '%@test.com')->get();
    
    if ($users->count() > 0) {
        foreach ($users as $user) {
            $roles = $user->getRoleNames()->implode(', ');
            echo "- {$user->name} ({$user->email}) - Rôles: {$roles}\n";
        }
    } else {
        echo "Aucun utilisateur de test trouvé.\n";
    }
    
    // Afficher les localités créées
    echo "\n🌍 Localités créées :\n";
    $localities = \App\Models\Locality::where('name', 'like', '%Test%')->get();
    
    if ($localities->count() > 0) {
        // Grouper par type de localité
        $groupedLocalities = $localities->groupBy('locality_type_id');
        
        foreach ($groupedLocalities as $typeId => $typeLocalities) {
            $type = \App\Models\LocalityType::find($typeId);
            $typeName = $type ? $type->display_name : 'Inconnu';
            echo "\n📍 {$typeName} :\n";
            
            foreach ($typeLocalities as $locality) {
                echo "  - {$locality->name}\n";
            }
        }
    } else {
        echo "Aucune localité de test trouvée.\n";
    }
    
    // Afficher les comités locaux
    echo "\n🏛️  Comités locaux :\n";
    $committees = \App\Models\LocalCommittee::where('name', 'like', '%Test%')->get();
    
    if ($committees->count() > 0) {
        foreach ($committees as $committee) {
            $locality = $committee->locality ? $committee->locality->name : 'Inconnue';
            echo "- {$committee->name} (Localité: {$locality})\n";
        }
    } else {
        echo "Aucun comité local de test trouvé.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Erreur lors du test : " . $e->getMessage() . "\n";
    echo "Trace : " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n✨ Test terminé !\n";

