<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use App\Models\User;

class ListRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Lister tous les rôles existants avec leurs utilisateurs associés';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Liste des rôles existants:');
        $roles = Role::all();
        
        if ($roles->isEmpty()) {
            $this->warn('Aucun rôle trouvé');
            return;
        }
        
        $headers = ['ID', 'Nom', 'Nombre d\'utilisateurs'];
        $rows = [];
        
        foreach ($roles as $role) {
            $userCount = User::role($role->name)->count();
            $rows[] = [$role->id, $role->name, $userCount];
        }
        
        $this->table($headers, $rows);
        
        $this->info('Premiers utilisateurs du système:');
        $users = User::orderBy('id')->take(5)->get(['id', 'name', 'email']);
        
        if ($users->isEmpty()) {
            $this->warn('Aucun utilisateur trouvé');
            return;
        }
        
        $userHeaders = ['ID', 'Nom', 'Email', 'Rôles'];
        $userRows = [];
        
        foreach ($users as $user) {
            $userRoles = $user->getRoleNames()->implode(', ');
            $userRows[] = [$user->id, $user->name, $user->email, $userRoles];
        }
        
        $this->table($userHeaders, $userRows);
    }
}
