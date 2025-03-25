<?php
require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Récupérer une sous-préfecture
$subPrefecture = \App\Models\Locality::whereHas('type', function($q) { 
    $q->where('name', 'subprefecture'); 
})->first();

if (!$subPrefecture) {
    echo "Erreur: Aucune sous-préfecture trouvée.\n";
    exit(1);
}

// Récupérer le département de cette sous-préfecture
$department = \App\Models\Locality::find($subPrefecture->parent_id);

if (!$department) {
    echo "Erreur: Aucun département trouvé pour la sous-préfecture.\n";
    exit(1);
}

// Créer un utilisateur sous-préfet de test
$subPrefet = new \App\Models\User([
    'name' => 'Sous Prefet Test',
    'email' => 'sousprefet@test.com',
    'password' => bcrypt('password123'),
    'locality_id' => $subPrefecture->id,
    'phone' => '0700000001',
    'email_verified_at' => now()
]);
$subPrefet->save();

// Créer un rôle sous-préfet si nécessaire
$role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'sous-prefet']);
$subPrefet->assignRole('sous-prefet');

// Créer un utilisateur préfet de test
$prefet = new \App\Models\User([
    'name' => 'Prefet Test',
    'email' => 'prefet@test.com',
    'password' => bcrypt('password123'),
    'locality_id' => $department->id,
    'phone' => '0700000002',
    'email_verified_at' => now()
]);
$prefet->save();

// Créer un rôle préfet si nécessaire
$rolePrefet = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'prefet']);
$prefet->assignRole('prefet');

// Créer un utilisateur secrétaire de test
$secretaire = new \App\Models\User([
    'name' => 'Secretaire Test',
    'email' => 'secretaire@test.com',
    'password' => bcrypt('password123'),
    'locality_id' => $subPrefecture->id,
    'phone' => '0700000003',
    'email_verified_at' => now()
]);
$secretaire->save();

// Créer un rôle secrétaire si nécessaire
$roleSecretaire = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'secretaire']);
$secretaire->assignRole('secretaire');

echo "Utilisateurs de test créés avec succès!\n";
echo "Sous-préfet: sousprefet@test.com / password123\n";
echo "Préfet: prefet@test.com / password123\n";
echo "Secrétaire: secretaire@test.com / password123\n"; 