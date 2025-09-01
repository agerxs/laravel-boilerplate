<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();


        $this->call([
            RoleSeeder::class,
            \Database\Seeders\Models\LocalityTypeSeeder::class,
            \Database\Seeders\Models\LocalitySeeder::class,
            ReferenceDataSeeder::class,
            SecretarySeeder::class,
            SubPrefectSeeder::class,
            LocalCommitteeSeeder::class,
            //FilamentUserSeeder::class, // Créer l'utilisateur admin Filament
            //AdminUsersSeeder::class, // Créer les utilisateurs admin avec les 2 niveaux
            TestUsersSeeder::class, // Ajouter le seeder des utilisateurs de test
            //MeetingSeeder::class,
            
        ]);
  
    }
}
