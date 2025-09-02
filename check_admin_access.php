<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

echo "=== VÉRIFICATION ET CORRECTION DES ACCÈS ADMIN FILAMENT ===\n\n";

// Utilisateurs du Login.vue (connexion rapide)
$quickLoginUsers = [
    [
        'name' => 'Super Admin',
        'email' => 'superadmin@colocs.ci',
        'password' => 'password123',
        'role' => 'super_admin',
        'is_super_admin' => true
    ],
    [
        'name' => 'Admin Simple',
        'email' => 'admin@colocs.ci',
        'password' => 'password123',
        'role' => 'admin',
        'is_super_admin' => false
    ],
    [
        'name' => 'Président',
        'email' => 'president@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'is_super_admin' => false
    ],
    [
        'name' => 'Secrétaire',
        'email' => 'secretaire@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'is_super_admin' => false
    ],
    [
        'name' => 'Trésorier',
        'email' => 'tresorier@test.com',
        'password' => 'password123',
        'role' => 'admin',
        'is_super_admin' => false
    ]
];

// Utiliser uniquement les utilisateurs du Login.vue
$allAdminUsers = $quickLoginUsers;

echo "1. Vérification et création des rôles:\n";
$superAdminRole = Role::firstOrCreate(['name' => 'super_admin'], ['guard_name' => 'web']);
$adminRole = Role::firstOrCreate(['name' => 'admin'], ['guard_name' => 'web']);
echo "   ✅ Rôles créés/vérifiés\n\n";

echo "2. Traitement des utilisateurs admin:\n";
foreach ($allAdminUsers as $userData) {
    echo "   Traitement de: {$userData['email']}\n";
    
    // Créer ou récupérer l'utilisateur
    $user = User::firstOrCreate(
        ['email' => $userData['email']],
        [
            'name' => $userData['name'],
            'password' => Hash::make($userData['password']),
            'email_verified_at' => now(),
        ]
    );
    
    // Mettre à jour le mot de passe (au cas où il aurait changé)
    $user->password = Hash::make($userData['password']);
    $user->save();
    
    // Assigner le rôle
    if (!$user->hasRole($userData['role'])) {
        $user->assignRole($userData['role']);
        echo "     ✅ Rôle {$userData['role']} assigné\n";
    } else {
        echo "     ✅ Rôle {$userData['role']} déjà assigné\n";
    }
    
    // Configurer le niveau d'administration
    $user->is_super_admin = $userData['is_super_admin'];
    $user->save();
    
    $level = $userData['is_super_admin'] ? 'Super Admin' : 'Admin Simple';
    echo "     ✅ Configuré comme {$level}\n";
    
    // Test de la méthode canAccess
    $canAccess = $user->is_super_admin || $user->hasRole('admin');
    echo "     ✅ Accès Filament: " . ($canAccess ? 'AUTORISÉ' : 'REFUSÉ') . "\n";
    
    echo "\n";
}

echo "3. Test final de tous les utilisateurs:\n";
foreach ($allAdminUsers as $userData) {
    $user = User::where('email', $userData['email'])->first();
    if ($user) {
        $canAccess = $user->is_super_admin || $user->hasRole('admin');
        $status = $canAccess ? '✅ AUTORISÉ' : '❌ REFUSÉ';
        echo "   {$userData['email']}: {$status}\n";
    } else {
        echo "   {$userData['email']}: ❌ UTILISATEUR NON TROUVÉ\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "Utilisateurs configurés pour l'accès Filament:\n";
echo "- superadmin@colocs.ci (Super Admin) - password123\n";
echo "- admin@colocs.ci (Admin Simple) - password123\n";
echo "- president@test.com (Admin Simple) - password123\n";
echo "- secretaire@test.com (Admin Simple) - password123\n";
echo "- tresorier@test.com (Admin Simple) - password123\n";
echo "\nURL d'accès: /admin\n";
echo "Tous les utilisateurs peuvent maintenant se connecter avec le mot de passe: password123\n";
