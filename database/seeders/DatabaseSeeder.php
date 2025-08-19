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
            TestUsersSeeder::class, // Ajouter le seeder des utilisateurs de test
            //MeetingSeeder::class,
            
        ]);
  
    }
}
