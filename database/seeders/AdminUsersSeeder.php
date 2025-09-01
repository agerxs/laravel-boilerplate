<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info("Création des utilisateurs d'administration avec les 2 niveaux...");

        // Créer les rôles s'ils n'existent pas
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        // Configuration des utilisateurs admin
        $adminUsers = [
            [
                'name' => 'Super Administrateur Principal',
                'email' => 'superadmin@colocs.ci',
                'password' => 'password',
                'phone' => '+225070000000',
                'role' => 'super_admin',
                'is_super_admin' => true,
                'description' => 'Super administrateur principal avec accès complet'
            ],
          
            [
                'name' => 'Admin Test',
                'email' => 'admin.test@colocs.ci',
                'password' => 'password',
                'phone' => '+225070000003',
                'role' => 'admin',
                'is_super_admin' => false,
                'description' => 'Administrateur de test avec accès en lecture seule'
            ]
        ];

        foreach ($adminUsers as $userData) {
            // Créer ou récupérer l'utilisateur
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => Hash::make($userData['password']),
                    'phone' => $userData['phone'],
                    'email_verified_at' => now(),
                ]
            );

            // Assigner le rôle
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
                $this->command->info("Rôle {$userData['role']} assigné à {$userData['name']}");
            }

            // Configurer le niveau d'administration
            $user->update(['is_super_admin' => $userData['is_super_admin']]);
            
            $level = $userData['is_super_admin'] ? 'Super Admin (Niveau 1)' : 'Admin Simple (Niveau 2)';
            $this->command->info("Utilisateur {$userData['name']} configuré comme {$level}");

            // Afficher les informations de connexion
            $this->command->info("  - Email: {$userData['email']}");
            $this->command->info("  - Mot de passe: {$userData['password']}");
            $this->command->info("  - Description: {$userData['description']}");
        }

        $this->command->info("Utilisateurs d'administration créés avec succès !");
        $this->command->info("Vous pouvez maintenant vous connecter avec l'un de ces comptes pour tester les différents niveaux d'accès.");
    }
}

