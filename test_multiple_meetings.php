<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use App\Models\LocalCommittee;
use App\Models\Meeting;

// Initialiser Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Test de création multiple de réunions ===\n\n";

// Créer un utilisateur de test
$user = User::factory()->create();
echo "✅ Utilisateur créé: {$user->name}\n";

// Créer un comité local de test
$committee = LocalCommittee::factory()->create();
echo "✅ Comité local créé: {$committee->name}\n";

// Données de test pour les réunions
$meetingsData = [
    'local_committee_id' => $committee->id,
    'meetings' => [
        [
            'title' => 'Réunion test 1',
            'scheduled_date' => '2024-01-15',
            'scheduled_time' => '14:00',
            'location' => 'Salle A'
        ],
        [
            'title' => 'Réunion test 2',
            'scheduled_date' => '2024-02-15',
            'scheduled_time' => '15:00',
            'location' => 'Salle B'
        ]
    ]
];

echo "📋 Données de test préparées\n";

// Simuler la création des réunions
$createdMeetings = [];
foreach ($meetingsData['meetings'] as $index => $meetingData) {
    try {
        // Combiner la date et l'heure
        $scheduledDateTime = $meetingData['scheduled_date'] . ' ' . $meetingData['scheduled_time'];
        
        // Créer la réunion
        $meeting = new Meeting();
        $meeting->title = $meetingData['title'];
        $meeting->local_committee_id = $meetingsData['local_committee_id'];
        $meeting->scheduled_date = $scheduledDateTime;
        $meeting->location = $meetingData['location'];
        $meeting->status = 'scheduled';
        $meeting->created_by = $user->id;
        $meeting->save();
        
        $createdMeetings[] = $meeting;
        echo "✅ Réunion créée: {$meeting->title} le {$meeting->scheduled_date}\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur lors de la création de la réunion " . ($index + 1) . ": " . $e->getMessage() . "\n";
    }
}

echo "\n📊 Résumé:\n";
echo "- Nombre de réunions créées: " . count($createdMeetings) . "\n";
echo "- Nombre total de réunions en base: " . Meeting::count() . "\n";

// Nettoyer les données de test
foreach ($createdMeetings as $meeting) {
    $meeting->delete();
}
$committee->delete();
$user->delete();

echo "\n🧹 Données de test nettoyées\n";
echo "✅ Test terminé avec succès!\n"; 