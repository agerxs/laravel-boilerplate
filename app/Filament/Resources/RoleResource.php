<?php

namespace App\Filament\Resources;

use Althinect\FilamentSpatieRolesPermissions\Resources\RoleResource as BaseRoleResource;

class RoleResource extends BaseRoleResource
{
    protected static ?string $navigationGroup = 'Gestion des utilisateurs';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
} 