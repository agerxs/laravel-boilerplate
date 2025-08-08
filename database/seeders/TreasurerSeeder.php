<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TreasurerSeeder extends Seeder
{
    public function run()
    {
        $this->command->info("Création des utilisateurs trésoriers...");

        $treasurers = [
            [
                'name' => 'Trésorier Principal',
                'email' => 'tresorier@colocs.ci',
                'password' => Hash::make('password'),
                'phone' => '+22507012345',
            ],
            [
                'name' => 'Trésorier Adjoint',
                'email' => 'tresorier.adjoint@colocs.ci',
                'password' => Hash::make('password'),
                'phone' => '+22508098765',
            ],
        ];

        foreach ($treasurers as $treasurerData) {
            // Vérifier si l'utilisateur existe déjà
            $existingUser = User::where('email', $treasurerData['email'])->first();
            
            if (!$existingUser) {
                $user = User::create($treasurerData);
                $user->assignRole('tresorier');
                
                $this->command->info("Trésorier créé: {$user->name} ({$user->email})");
            } else {
                // S'assurer que l'utilisateur existant a le rôle trésorier
                if (!$existingUser->hasRole('tresorier')) {
                    $existingUser->assignRole('tresorier');
                    $this->command->info("Rôle trésorier ajouté à: {$existingUser->name}");
                } else {
                    $this->command->info("Trésorier existe déjà: {$existingUser->name}");
                }
            }
        }

        $this->command->info("Nombre total de trésoriers: " . User::role('tresorier')->count());
    }
} 