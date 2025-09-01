<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class FilamentUserSeeder extends Seeder
{
    /**
     * Exécuter le seeder.
     */
    public function run(): void
    {
        $this->command->info("Création des utilisateurs admin Filament...");

        // Créer le rôle super_admin s'il n'existe pas
        $superAdminRole = Role::firstOrCreate(
            ['name' => 'super_admin'],
            ['guard_name' => 'web']
        );

        // Créer le rôle admin s'il n'existe pas
        $adminRole = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );

        // Créer l'utilisateur super admin pour Filament
        $superAdminUser = User::firstOrCreate(
            ['email' => 'filament.admin@colocs.ci'],
            [
                'name' => 'Admin Filament',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle super_admin à l'utilisateur super admin
        if (!$superAdminUser->hasRole('super_admin')) {
            $superAdminUser->assignRole('super_admin');
            $this->command->info("Rôle super_admin assigné à l'utilisateur admin Filament");
        }

        // Marquer l'utilisateur comme super admin Filament
        $superAdminUser->update(['is_super_admin' => true]);

        // Créer l'utilisateur admin simple pour Filament
        $adminUser = User::firstOrCreate(
            ['email' => 'filament.admin.simple@colocs.ci'],
            [
                'name' => 'Admin Simple Filament',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle admin à l'utilisateur admin simple
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
            $this->command->info("Rôle admin assigné à l'utilisateur admin simple Filament");
        }

        // Ne PAS marquer comme super admin (niveau 2)
        $adminUser->update(['is_super_admin' => false]);

        $this->command->info("Utilisateurs admin Filament créés avec succès:");
        $this->command->info("- Super Admin Filament: {$superAdminUser->email} (mot de passe: password)");
        $this->command->info("- Admin Simple Filament: {$adminUser->email} (mot de passe: password)");
        $this->command->info("");
        $this->command->info("IMPORTANT: Utilisez ces identifiants pour accéder à l'interface Filament:");
        $this->command->info("URL: /admin");
        $this->command->info("Email: filament.admin@colocs.ci");
        $this->command->info("Mot de passe: password");
    }
}
