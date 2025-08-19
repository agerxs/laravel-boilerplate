<?php
/**
 * Exemples de configuration pour les villages de test
 * 
 * Ce fichier montre comment personnaliser facilement le nombre et les noms des villages
 * Copiez la configuration souhaitée dans config/test-users.php
 */

return [
    // Configuration avec 3 villages (par défaut)
    'three_villages' => [
        'names' => [
            'Village Test 1',
            'Village Test 2', 
            'Village Test 3'
        ]
    ],

    // Configuration avec 5 villages
    'five_villages' => [
        'names' => [
            'Village Test 1',
            'Village Test 2', 
            'Village Test 3',
            'Village Test 4',
            'Village Test 5'
        ]
    ],

    // Configuration avec 2 villages
    'two_villages' => [
        'names' => [
            'Village Test 1',
            'Village Test 2'
        ]
    ],

    // Configuration avec des noms personnalisés
    'custom_names' => [
        'names' => [
            'Village Alpha',
            'Village Beta',
            'Village Gamma',
            'Village Delta'
        ]
    ],

    // Configuration avec des noms réalistes
    'realistic_names' => [
        'names' => [
            'Village de la Paix',
            'Village du Progrès',
            'Village de l\'Espoir'
        ]
    ],

    // Configuration avec des noms basés sur des régions
    'regional_names' => [
        'names' => [
            'Village Nord',
            'Village Sud',
            'Village Est',
            'Village Ouest',
            'Village Central'
        ]
    ]
];

/*
 * Pour utiliser une de ces configurations :
 * 
 * 1. Ouvrez config/test-users.php
 * 2. Remplacez la section 'village' par :
 * 
 * 'village' => [
 *     'names' => [
 *         'Village Test 1',
 *         'Village Test 2', 
 *         'Village Test 3'
 *         // Ajoutez ou supprimez des villages selon vos besoins
 *     ],
 *     'display_name' => 'Village',
 * ],
 * 
 * 3. Sauvegardez et relancez le seeder
 */
