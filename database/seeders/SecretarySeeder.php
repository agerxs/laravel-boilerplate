<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Locality;
use App\Models\Organization;
use App\Models\JobFunction;
use App\Models\ContractType;
use App\Models\AssignmentLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class SecretarySeeder extends Seeder
{
    public function run()
    {
        // Créer le rôle secrétaire s'il n'existe pas
        $secretaryRole = Role::firstOrCreate(['name' => 'secretaire']);
        
        // Analyse préliminaire pour trouver les doublons
        $secretariesData = json_decode(file_get_contents(resource_path('data/secretaires.json')), true);
        
        $emails = [];
        $cnis = [];
        $phones = [];
        
        Log::info('Début de l\'analyse des doublons');
        
        // Première passe : analyse des doublons pour information
        foreach ($secretariesData as $secretary) {
            $email = strtolower(trim($secretary['E-Mail']));
            $cni = preg_replace('/\s+/', '', $secretary['N° de CNI']);
            $phone = preg_replace('/[^0-9]/', '', $secretary['N° de Téléphone WhatsApp']);
            
            // Logger les emails en double (mais on les créera quand même)
            if (isset($emails[$email])) {
                $message = "Information - Email en double : {$email} utilisé par {$emails[$email]} et {$secretary['Nom et Prénom']}";
                echo $message . "\n";
                Log::info($message);
            }
            $emails[$email] = $secretary['Nom et Prénom'];
            
            // Logger les CNI en double (mais on les créera quand même)
            if (isset($cnis[$cni])) {
                $message = "Information - CNI en double : {$cni} utilisé par {$cnis[$cni]} et {$secretary['Nom et Prénom']}";
                echo $message . "\n";
                Log::info($message);
            }
            $cnis[$cni] = $secretary['Nom et Prénom'];

            // Vérifier les numéros de téléphone (seule contrainte d'unicité)
            if (isset($phones[$phone])) {
                $message = "ERREUR - Téléphone en double : {$phone} utilisé par {$phones[$phone]} et {$secretary['Nom et Prénom']}";
                echo $message . "\n";
                Log::error($message);
            }
            $phones[$phone] = $secretary['Nom et Prénom'];
        }
/*
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
        ]; */

        Log::info('Début de la création des utilisateurs');
        // Créer les utilisateurs
        foreach ($secretariesData as $secretary) {
            $phone = preg_replace('/[^0-9]/', '', $secretary['N° de Téléphone WhatsApp']);
            
            // Vérifier si l'utilisateur existe déjà par téléphone
            $existingUser = User::where('phone', $phone)->first();

            if ($existingUser) {
                $message = "Ignoré : {$secretary['Nom et Prénom']} - Numéro de téléphone déjà existant";
                echo $message . "\n";
                Log::info($message);
                continue;
            }

            $locality = Locality::whereHas('type', function($query) {
                $query->where('name', 'sub_prefecture');
            })
            ->where('name', trim($secretary['SOUS-PREFECTURES']))
            ->first();

            if (!$locality) {
                $message = "Ignoré : {$secretary['Nom et Prénom']} - Localité non trouvée : {$secretary['SOUS-PREFECTURES']}";
                echo $message . "\n";
                Log::warning($message);
                continue;
            }

            try {
                // Créer ou récupérer le lieu d'affectation
                $assignmentLocation = AssignmentLocation::firstOrCreate(
                    [
                        'name' => $secretary['Poste d\'affectation'],
                        'locality_id' => $locality->id
                    ]
                );

                $user = User::create([
                    'name' => $secretary['Nom et Prénom'],
                    'email' => strtolower(trim($secretary['E-Mail'])),
                    'password' => bcrypt('password'),
                    'locality_id' => $locality->id,
                    'organization_id' => $organizations[trim($secretary['Structure de rattachement'])]->id ?? null,
                    'job_function_id' => $jobFunctions[trim($secretary['Fonction'])]->id ?? null,
                    'contract_type_id' => $contractTypes[trim($secretary['Type de contrat'])]->id ?? null,
                    'assignment_location_id' => $assignmentLocation->id,
                    'cni_number' => preg_replace('/\s+/', '', $secretary['N° de CNI']),
                    'phone' => $phone,
                    'whatsapp' => $phone,
                    'email_verified_at' => now(),
                    'remember_token' => Str::random(10),
                ]);

                // Assigner le rôle secrétaire
                $user->assignRole('secretaire');
                
                $message = "Créé : {$secretary['Nom et Prénom']}";
                echo $message . "\n";
                Log::info($message);
            } catch (\Exception $e) {
                $message = "Erreur lors de la création de {$secretary['Nom et Prénom']} : {$e->getMessage()}";
                echo $message . "\n";
                Log::error($message);
            }
        }
        
        Log::info('Fin du seeding des secrétaires');
    }
} 