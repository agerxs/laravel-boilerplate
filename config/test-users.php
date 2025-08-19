<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configuration des utilisateurs de test
    |--------------------------------------------------------------------------
    |
    | Ce fichier contient la configuration pour la création automatique
    | des utilisateurs de test dans l'application.
    |
    */

    // Mot de passe par défaut pour tous les utilisateurs de test
    'default_password' => env('TEST_USERS_PASSWORD', 'password123'),

    // Localités de test à créer
    'localities' => [
        'region' => [
            'name' => 'Région Test',
            'display_name' => 'Région',
        ],
        'department' => [
            'name' => 'Département Test',
            'display_name' => 'Département',
        ],
        'sub_prefecture' => [
            'name' => 'Sous-Préfecture Test',
            'display_name' => 'Sous-Préfecture',
        ],
        'village' => [
            'names' => [
                'Village Test 1',
                'Village Test 2', 
                'Village Test 3'
            ],
            'display_name' => 'Village',
        ],
        // Exemple d'ajout d'autres types de localités
        // 'commune' => [
        //     'names' => [
        //         'Commune Test 1',
        //         'Commune Test 2'
        //     ],
        //     'display_name' => 'Commune',
        // ],
        // 'canton' => [
        //     'names' => [
        //         'Canton Test 1',
        //         'Canton Test 2'
        //     ],
        //     'display_name' => 'Canton',
        // ],
    ],

    // Comité local de test
    'local_committee' => [
        'name' => 'Comité Local Test',
        'status' => 'active',
    ],

    // Utilisateurs de test à créer
    'users' => [
        [
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'phone' => '0700000001',
            'role' => 'admin',
            'description' => 'Administrateur système',
            'add_to_committee' => false, // L'admin n'est pas membre du comité local
        ],
        [
            'name' => 'Président Test',
            'email' => 'president@test.com',
            'phone' => '0700000002',
            'role' => 'president',
            'description' => 'Président du comité local',
            'add_to_committee' => true,
        ],
        [
            'name' => 'Secrétaire Test',
            'email' => 'secretaire@test.com',
            'phone' => '0700000004',
            'role' => 'secretaire',
            'description' => 'Secrétaire du comité local',
            'add_to_committee' => true,
        ],
        [
            'name' => 'Tresorier Test',
            'email' => 'tresorier@test.com',
            'phone' => '0700000005',
            'role' => 'tresorier',
            'description' => 'Tresorier des réunions',
            'add_to_committee' => true,
        ],
        [
            'name' => 'Trésorier Test',
            'email' => 'tresorier@test.com',
            'phone' => '0700000006',
            'role' => 'tresorier',
            'description' => 'Trésorier du comité local',
            'add_to_committee' => true,
        ],
        [
            'name' => 'Superviseur Test',
            'email' => 'superviseur@test.com',
            'phone' => '0700000007',
            'role' => 'superviseur',
            'description' => 'Superviseur régional',
            'add_to_committee' => false, // Le superviseur n'est pas membre du comité local
        ],
    ],

    // Options de configuration
    'options' => [
        // Vérifier si les utilisateurs existent déjà avant de les créer
        'check_existing' => true,
        
        // Mettre à jour les utilisateurs existants
        'update_existing' => true,
        
        // Créer les rôles s'ils n'existent pas
        'create_roles' => true,
        
        // Ajouter automatiquement les utilisateurs au comité local
        'auto_add_to_committee' => true,
        
        // Marquer les emails comme vérifiés
        'verify_emails' => true,
        
        // Générer des tokens de rappel
        'generate_remember_tokens' => true,
    ],

    // Configuration des numéros de téléphone
    'phone' => [
        // Préfixe pour les numéros de test
        'prefix' => '0700000',
        
        // Numéro de départ
        'start_number' => 1,
        
        // Format du numéro (utilisé pour la génération)
        'format' => '070000000{number}',
    ],

    // Configuration des emails
    'email' => [
        // Domaine pour les emails de test
        'domain' => 'test.com',
        
        // Format des emails (utilisé pour la génération)
        'format' => '{username}@{domain}',
    ],

    // Messages de log
    'messages' => [
        'user_created' => 'Utilisateur {name} créé avec succès',
        'user_updated' => 'Utilisateur {name} mis à jour avec succès',
        'user_exists' => 'Utilisateur {name} existe déjà',
        'role_assigned' => 'Rôle {role} assigné à {name}',
        'committee_member_added' => 'Utilisateur {name} ajouté au comité local avec le rôle {role}',
        'locality_created' => 'Localité {name} créée avec succès',
        'committee_created' => 'Comité local {name} créé avec succès',
    ],
];
