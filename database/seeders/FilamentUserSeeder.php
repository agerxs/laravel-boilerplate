<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class FilamentUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
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

        // Créer l'utilisateur super admin
        $superAdminUser = User::firstOrCreate(
            ['email' => 'admin@colocs.ci'],
            [
                'name' => 'Administrateur Système',
                'password' => Hash::make('password'),
                'phone' => '+225070000000',
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle super_admin à l'utilisateur super admin
        if (!$superAdminUser->hasRole('super_admin')) {
            $superAdminUser->assignRole('super_admin');
            $this->command->info("Rôle super_admin assigné à l'utilisateur super admin");
        }

        // Marquer l'utilisateur comme super admin Filament
        $superAdminUser->update(['is_super_admin' => true]);

        // Créer l'utilisateur admin simple
        $adminUser = User::firstOrCreate(
            ['email' => 'admin.simple@colocs.ci'],
            [
                'name' => 'Administrateur Simple',
                'password' => Hash::make('password'),
                'phone' => '+225070000001',
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle admin à l'utilisateur admin simple
        if (!$adminUser->hasRole('admin')) {
            $adminUser->assignRole('admin');
            $this->command->info("Rôle admin assigné à l'utilisateur admin simple");
        }

        // Ne PAS marquer comme super admin (niveau 2)
        $adminUser->update(['is_super_admin' => false]);

        // Créer un utilisateur admin simple de test
        $adminTestUser = User::firstOrCreate(
            ['email' => 'admin.test@colocs.ci'],
            [
                'name' => 'Admin Test Colocs',
                'password' => Hash::make('password'),
                'phone' => '+225070000002',
                'email_verified_at' => now(),
            ]
        );

        // Assigner le rôle admin à l'utilisateur admin de test
        if (!$adminTestUser->hasRole('admin')) {
            $adminTestUser->assignRole('admin');
            $this->command->info("Rôle admin assigné à l'utilisateur admin de test");
        }

        // Ne PAS marquer comme super admin (niveau 2)
        $adminTestUser->update(['is_super_admin' => false]);

        $this->command->info("Utilisateurs admin Filament créés avec succès:");
        $this->command->info("- Super Admin: {$superAdminUser->email}");
        $this->command->info("- Admin Simple: {$adminUser->email}");
        $this->command->info("- Admin Test: {$adminTestUser->email}");
    }
}
