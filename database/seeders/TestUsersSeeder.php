<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Locality;
use App\Models\LocalityType;
use App\Models\LocalCommittee;
use App\Models\LocalCommitteeMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class TestUsersSeeder extends Seeder
{
    public function run()
    {
        $this->command->info("Début de la création des utilisateurs de test...");


        // Créer une hiérarchie de localités de test
        $testLocality = $this->createTestLocalityHierarchy();

        // Récupérer un comité local existant ou en créer un si aucun n'existe
        $existingCommittee = $this->getExistingLocalCommittee($testLocality);

        // Créer les utilisateurs de test pour chaque rôle
        $this->createTestUsers($testLocality, $existingCommittee);

        $this->command->info("Utilisateurs de test créés avec succès!");
    }

 

    private function createTestLocalityHierarchy()
    {
        $localities = config('test-users.localities', []);
        
        // Créer une région de test
        $region = Locality::firstOrCreate(
            ['name' => $localities['region']['name'] ?? 'Région Test'],
            [
                'locality_type_id' => LocalityType::where('name', 'region')->first()->id,
                'parent_id' => null
            ]
        );

        // Créer un département de test
        $department = Locality::firstOrCreate(
            ['name' => $localities['department']['name'] ?? 'Département Test'],
            [
                'locality_type_id' => LocalityType::where('name', 'departement')->first()->id,
                'parent_id' => $region->id
            ]
        );

        // Créer une sous-préfecture de test
        $subPrefecture = Locality::firstOrCreate(
            ['name' => $localities['sub_prefecture']['name'] ?? 'Sous-Préfecture Test'],
            [
                'locality_type_id' => LocalityType::where('name', 'sub_prefecture')->first()->id,
                'parent_id' => $department->id
            ]
        );

        // Créer plusieurs villages de test
        $villages = [];
        $villageNames = $localities['village']['names'] ?? ['Village Test 1', 'Village Test 2', 'Village Test 3'];
        
        foreach ($villageNames as $villageName) {
            $village = Locality::firstOrCreate(
                ['name' => $villageName],
                [
                    'locality_type_id' => LocalityType::where('name', 'village')->first()->id,
                    'parent_id' => $subPrefecture->id
                ]
            );
            $villages[] = $village;
        }

        $villageNamesList = implode(', ', array_map(fn($v) => $v->name, $villages));
        $this->command->info("Hiérarchie de localités de test créée : {$region->name} > {$department->name} > {$subPrefecture->name} > Villages: {$villageNamesList}");

        return $subPrefecture; // Retourner la sous-préfecture pour les utilisateurs
    }

    private function getExistingLocalCommittee($locality)
    {
        // Essayer de trouver un comité local existant dans la localité ou ses parents
        $committee = LocalCommittee::where('locality_id', $locality->id)
            ->orWhereHas('locality', function($query) use ($locality) {
                $query->where('id', $locality->parent_id);
            })
            ->orWhereHas('locality.parent', function($query) use ($locality) {
                $query->where('id', $locality->parent_id);
            })
            ->first();

        if ($committee) {
            $this->command->info("Comité local existant trouvé : {$committee->name} (Localité: {$committee->locality->name})");
            return $committee;
        }

        // Si aucun comité n'existe, en créer un simple
        $this->command->warn("Aucun comité local trouvé, création d'un comité de base...");
        
        $committee = LocalCommittee::create([
            'name' => 'Comité Local ' . $locality->name,
            'locality_id' => $locality->id,
            'installation_date' => now(),
            'status' => 'active'
        ]);

        $this->command->info("Comité local créé : {$committee->name}");
        return $committee;
    }

    private function createTestUsers($locality, $committee)
    {
        // Récupérer la configuration des utilisateurs de test
        $testUsers = config('test-users.users', []);
        $defaultPassword = config('test-users.default_password', 'password123');
        $options = config('test-users.options', []);
        
        if (empty($testUsers)) {
            $this->command->warn('Aucun utilisateur de test configuré. Utilisation des utilisateurs par défaut.');
            $testUsers = $this->getDefaultTestUsers();
        }

        foreach ($testUsers as $userData) {
            try {
                // Vérifier si l'utilisateur existe déjà
                $existingUser = User::where('email', $userData['email'])->first();
                
                if ($existingUser) {
                    $this->command->info("Utilisateur {$userData['name']} existe déjà, mise à jour...");
                    $user = $existingUser;
                    
                    // Mettre à jour les informations
                    $user->update([
                        'name' => $userData['name'],
                        'phone' => $userData['phone'],
                        'locality_id' => $locality->id
                    ]);
                } else {
                    // Créer un nouvel utilisateur
                    $user = User::create([
                        'name' => $userData['name'],
                        'email' => $userData['email'],
                        'password' => bcrypt('password123'),
                        'locality_id' => $locality->id,
                        'phone' => $userData['phone'],
                        'email_verified_at' => now()
                    ]);

                    $this->command->info("Utilisateur {$userData['name']} créé avec succès");
                }

                // Créer le rôle s'il n'existe pas
                $role = Role::firstOrCreate(
                    ['name' => $userData['role']],
                    ['guard_name' => 'web']
                );

                // Assigner le rôle à l'utilisateur
                if (!$user->hasRole($userData['role'])) {
                    $user->assignRole($userData['role']);
                    $this->command->info("Rôle {$userData['role']} assigné à {$userData['name']}");
                }

                // Ajouter l'utilisateur au comité local selon la configuration
                if (isset($userData['add_to_committee']) && $userData['add_to_committee']) {
                    $this->addUserToCommittee($user, $committee, $userData['role']);
                }

            } catch (\Exception $e) {
                $this->command->error("Erreur lors de la création de l'utilisateur {$userData['name']}: " . $e->getMessage());
            }
        }
    }

    private function addUserToCommittee($user, $committee, $role)
    {
        // Vérifier si l'utilisateur est déjà membre du comité
        $existingMember = LocalCommitteeMember::where('user_id', $user->id)
            ->where('local_committee_id', $committee->id)
            ->first();

        if (!$existingMember) {
            LocalCommitteeMember::create([
                'local_committee_id' => $committee->id,
                'user_id' => $user->id,
                'role' => $role,
                'status' => 'active',
                'first_name' => explode(' ', $user->name)[0] ?? $user->name,
                'last_name' => explode(' ', $user->name)[1] ?? '',
                'phone' => $user->phone
            ]);

            $this->command->info("Utilisateur {$user->name} ajouté au comité local avec le rôle {$role}");
        }
    }

    private function getDefaultTestUsers()
    {
        return [
            [
                'name' => 'Admin Test',
                'email' => 'admin@test.com',
                'phone' => '0700000001',
                'role' => 'admin',
                'description' => 'Administrateur système',
                'add_to_committee' => false
            ],
            [
                'name' => 'Président Test',
                'email' => 'president@test.com',
                'phone' => '0700000002',
                'role' => 'president',
                'description' => 'Président du comité local',
                'add_to_committee' => true
            ],
            [
                'name' => 'Sous-Préfet Test',
                'email' => 'sousprefet@test.com',
                'phone' => '0700000003',
                'role' => 'president',
                'description' => 'Sous-préfet de la localité',
                'add_to_committee' => true
            ],
            [
                'name' => 'Secrétaire Test',
                'email' => 'secretaire@test.com',
                'phone' => '0700000004',
                'role' => 'secretaire',
                'description' => 'Secrétaire du comité local',
                'add_to_committee' => true
            ],
            [
                'name' => 'Tresorier Test',
                'email' => 'tresorier@test.com',
                'phone' => '0700000005',
                'role' => 'tresorier',
                'description' => 'Tresorier des réunions',
                'add_to_committee' => true
            ],
            [
                'name' => 'Trésorier Test',
                'email' => 'tresorier@test.com',
                'phone' => '0700000006',
                'role' => 'tresorier',
                'description' => 'Trésorier du comité local',
                'add_to_committee' => true
            ],
            [
                'name' => 'Superviseur Test',
                'email' => 'superviseur@test.com',
                'phone' => '0700000007',
                'role' => 'superviseur',
                'description' => 'Superviseur régional',
                'add_to_committee' => false
            ]
        ];
    }
}
