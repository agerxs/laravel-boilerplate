<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;

class TestPermissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?string $navigationLabel = 'Test des Permissions';
    protected static ?string $title = 'Test des Permissions Admin';
    protected static ?string $slug = 'test-permissions';

    protected static string $view = 'filament.pages.test-permissions';

    public function mount(): void
    {
        // Vérifier l'accès
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->canViewDashboard()) {
            abort(403, 'Accès non autorisé.');
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('test_permissions')
                ->label('Tester les Permissions')
                ->action('testUserPermissions')
                ->visible(function () {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    return $user && $user->canModifyDashboard();
                }),
        ];
    }

    public function testUserPermissions(): void
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        $this->notify('success', 'Test des permissions effectué pour ' . $user->name);
    }

    public function getUserInfo(): array
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        return [
            'name' => $user->name,
            'email' => $user->email,
            'is_super_admin' => $user->is_super_admin,
            'is_super_admin_method' => $user->isSuperAdmin(),
            'is_admin_method' => $user->isAdmin(),
            'can_view_dashboard' => $user->canViewDashboard(),
            'can_modify_dashboard' => $user->canModifyDashboard(),
            'roles' => $user->roles->pluck('name')->toArray(),
        ];
    }
}
