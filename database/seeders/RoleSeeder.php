<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run()
    {
        $this->command->info("Début du seeding des rôles...");

        // Désactiver les contraintes de clé étrangère
        Schema::disableForeignKeyConstraints();
        
        // Vider la table des rôles
        DB::table('roles')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('role_has_permissions')->truncate();
        
        // Réactiver les contraintes de clé étrangère
        Schema::enableForeignKeyConstraints();

        $roles = [
            'admin',
            'sous-prefet',
            'secretaire',
            'tresorier'
        ];

        $createdRoles = [];

        foreach ($roles as $role) {
            try {
                // Vérifier si le rôle existe déjà avec le bon guard_name
                $existingRole = Role::where('name', $role)
                    ->where('guard_name', 'web')
                    ->first();
                
                if (!$existingRole) {
                    $newRole = Role::create([
                        'name' => $role,
                        'guard_name' => 'web'
                    ]);
                    $this->command->info("Rôle '{$role}' créé avec succès (ID: {$newRole->id}, guard: {$newRole->guard_name})");
                    $createdRoles[] = $newRole;
                } else {
                    $this->command->info("Le rôle '{$role}' existe déjà (ID: {$existingRole->id}, guard: {$existingRole->guard_name})");
                    $createdRoles[] = $existingRole;
                }
            } catch (\Exception $e) {
                $this->command->error("Erreur lors de la création du rôle '{$role}': " . $e->getMessage());
                $this->command->error("Trace: " . $e->getTraceAsString());
                throw $e;
            }
        }

        // Vérifier que tous les rôles ont été créés
        $this->command->info("Nombre total de rôles créés : " . count($createdRoles));
        foreach ($createdRoles as $role) {
            $this->command->info("- {$role->name} (ID: {$role->id}, guard: {$role->guard_name})");
        }

        // Vérifier que les rôles sont bien présents dans la base de données
        $dbRoles = Role::all();
        $this->command->info("\nVérification des rôles dans la base de données :");
        foreach ($dbRoles as $role) {
            $this->command->info("- {$role->name} (ID: {$role->id}, guard: {$role->guard_name})");
        }
    }
} 