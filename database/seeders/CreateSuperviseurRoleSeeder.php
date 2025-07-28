<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class CreateSuperviseurRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Vérifie si le rôle existe déjà
        if (!Role::where('name', 'Superviseur')->exists()) {
            Role::create([
                'name' => 'Superviseur',
                'guard_name' => 'web',
            ]);
        }
    }
} 