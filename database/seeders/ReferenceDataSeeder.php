<?php

namespace Database\Seeders;

use App\Models\ContractType;
use App\Models\Organization;
use App\Models\JobFunction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class ReferenceDataSeeder extends Seeder
{
    public function run()
    {
        Log::info('Création des types de contrat');
        // Créer les types de contrat
        $contractTypes = [
            'CDI' => ContractType::create(['name' => 'CDI']),
            'CDD' => ContractType::create(['name' => 'CDD']),
            'Stagiaire' => ContractType::create(['name' => 'Stagiaire']),
            'Fonctionnaire' => ContractType::create(['name' => 'Fonctionnaire']),
            'Contractuel' => ContractType::create(['name' => 'Contractuel']),
            'OPT' => ContractType::create(['name' => 'OPT']),
        ];

        Log::info('Création des organisations');
        // Créer les organisations
        $organizations = [
            'CNAM' => Organization::create(['name' => 'CNAM']),
            'DRPS' => Organization::create(['name' => 'DRPS']),
            'DR MFFE' => Organization::create(['name' => 'DR MFFE']),
        ];

        Log::info('Création des fonctions');
        // Créer les fonctions
        $jobFunctions = [
            'OPT' => JobFunction::create(['name' => 'OPT']),
            'AGENT' => JobFunction::create(['name' => 'AGENT']),
            'DIRECTEUR' => JobFunction::create(['name' => 'DIRECTEUR']),
            'CHEF DE SERVICE' => JobFunction::create(['name' => 'CHEF DE SERVICE']),
            'OPERATEUR' => JobFunction::create(['name' => 'OPERATEUR']),
            'CHEF D\'EQUIPE' => JobFunction::create(['name' => 'CHEF D\'EQUIPE']),
        ];
    }
} 