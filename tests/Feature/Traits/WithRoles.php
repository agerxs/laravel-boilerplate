<?php

namespace Tests\Feature\Traits;

use Spatie\Permission\Models\Role;

trait WithRoles
{
    protected function setUpRoles()
    {
        $roles = ['admin', 'secretaire', 'Secrétaire'];
        
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
    }
} 