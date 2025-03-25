<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class CreateAdminRole extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:create-admin {email=admin@gmail.com : Email de l\'utilisateur}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Créer un rôle administrateur et l\'attribuer à un utilisateur spécifié';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Créer le rôle admin s'il n'existe pas
        $role = Role::firstOrCreate(['name' => 'admin']);
        $this->info('Rôle admin ' . ($role->wasRecentlyCreated ? 'créé' : 'existant'));

        // Trouver l'utilisateur
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("Aucun utilisateur trouvé avec l'email: {$email}");
            return 1;
        }

        // Attribuer le rôle à l'utilisateur
        if ($user->hasRole('admin')) {
            $this->info("L'utilisateur {$user->name} a déjà le rôle admin");
        } else {
            $user->assignRole('admin');
            $this->info("Rôle admin attribué à l'utilisateur {$user->name} ({$user->email})");
        }

        // Afficher tous les rôles de l'utilisateur
        $roles = $user->getRoleNames()->toArray();
        $this->info("Rôles de l'utilisateur: " . implode(', ', $roles));

        return 0;
    }
}
