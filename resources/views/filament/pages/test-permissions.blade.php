<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Informations de l'utilisateur connecté</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Nom:</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['name'] }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">Email:</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['email'] }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">is_super_admin (DB):</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['is_super_admin'] ? 'Oui' : 'Non' }}</span>
                    </div>
                </div>
                
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">isSuperAdmin():</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['is_super_admin_method'] ? 'Oui' : 'Non' }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">isAdmin():</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['is_admin_method'] ? 'Oui' : 'Non' }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">canViewDashboard():</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['can_view_dashboard'] ? 'Oui' : 'Non' }}</span>
                    </div>
                    
                    <div class="flex justify-between">
                        <span class="font-medium text-gray-700">canModifyDashboard():</span>
                        <span class="text-gray-900">{{ $this->getUserInfo()['can_modify_dashboard'] ? 'Oui' : 'Non' }}</span>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <span class="font-medium text-gray-700">Rôles:</span>
                <div class="mt-2 flex flex-wrap gap-2">
                    @foreach($this->getUserInfo()['roles'] as $role)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $role }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Test des Permissions</h2>
            
            <div class="space-y-4">
                <div class="p-4 bg-blue-50 rounded-lg">
                    <h3 class="font-medium text-blue-900">Niveau d'Administration</h3>
                    <p class="text-blue-700 mt-1">
                        @if($this->getUserInfo()['can_modify_dashboard'])
                            <strong>Super Admin</strong> - Vous avez accès complet à toutes les fonctionnalités et pouvez modifier tous les éléments.
                        @elseif($this->getUserInfo()['can_view_dashboard'])
                            <strong>Admin Simple</strong> - Vous pouvez voir le dashboard mais ne pouvez pas modifier les éléments. Seuls les super admins peuvent effectuer des modifications.
                        @else
                            <strong>Accès limité</strong> - Vous n'avez pas accès au dashboard admin.
                        @endif
                    </p>
                </div>
                
                <div class="p-4 bg-yellow-50 rounded-lg">
                    <h3 class="font-medium text-yellow-900">Actions Disponibles</h3>
                    <ul class="text-yellow-700 mt-1 space-y-1">
                        @if($this->getUserInfo()['can_modify_dashboard'])
                            <li>✅ Créer, modifier et supprimer des utilisateurs</li>
                            <li>✅ Créer, modifier et supprimer des taux de paiement</li>
                            <li>✅ Accès complet à toutes les ressources</li>
                            <li>✅ Gestion des rôles et permissions</li>
                        @elseif($this->getUserInfo()['can_view_dashboard'])
                            <li>✅ Voir la liste des utilisateurs (lecture seule)</li>
                            <li>✅ Voir la liste des taux de paiement (lecture seule)</li>
                            <li>❌ Impossible de créer, modifier ou supprimer</li>
                            <li>❌ Accès limité aux fonctionnalités d'administration</li>
                        @else
                            <li>❌ Aucun accès au dashboard admin</li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>

