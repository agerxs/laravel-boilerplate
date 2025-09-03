<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== TEST DES LOGS ===\n";

// Test 1: Log simple
\Log::info("Test de log simple - " . date('Y-m-d H:i:s'));

// Test 2: Test des méthodes User
$user = \App\Models\User::where('email', 'superadmin@colocs.ci')->first();
if ($user) {
    echo "Test avec utilisateur: {$user->email}\n";
    $user->canViewDashboard(); // Cela va déclencher les logs
} else {
    echo "Utilisateur superadmin@colocs.ci non trouvé\n";
}

// Test 3: Test AdminPanelProvider
echo "Test AdminPanelProvider::canAccess()\n";
\App\Providers\Filament\AdminPanelProvider::canAccess();

echo "\n=== LOGS GÉNÉRÉS ===\n";
echo "Vérifiez le fichier: storage/logs/laravel.log\n";
echo "Dernières lignes:\n";

$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    foreach ($lastLines as $line) {
        echo $line;
    }
} else {
    echo "Fichier de log non trouvé: $logFile\n";
}
