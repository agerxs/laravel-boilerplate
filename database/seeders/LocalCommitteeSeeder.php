<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Locality;
use App\Models\LocalCommittee;
use App\Models\LocalCommitteeMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class LocalCommitteeSeeder extends Seeder
{
    public function run()
    {
        Log::info('Début de la création des comités locaux');
        
        $subPrefecturesData = json_decode(file_get_contents(resource_path('data/sous-pref.json')), true);
        
        foreach ($subPrefecturesData as $data) {
            // Trouver la sous-préfecture
            $locality = Locality::whereHas('type', function($query) {
                $query->where('name', 'sub_prefecture');
            })
            ->where('name', trim($data['Sous-Préfecture']))
            ->first();

            if (!$locality) {
                Log::warning("Sous-préfecture non trouvée : {$data['Sous-Préfecture']}");
                continue;
            }

            // Trouver le secrétaire de la localité
            $secretary = User::role('secretaire')
                ->where('locality_id', $locality->id)
                ->first();

            if (!$secretary) {
                Log::warning("Pas de secrétaire trouvé pour la sous-préfecture : {$data['Sous-Préfecture']}");
                continue;
            }

            try {
                // Créer le comité local
                $committee = LocalCommittee::create([
                    'name' => "Comité Local de {$locality->name}",
                    'locality_id' => $locality->id,
                    'president_id' => $secretary->id,
                    'installation_date' => $this->parseDate($data['Date de planification de la tenue de la réunion d\'installation du COLOC']),
                    'ano_validation_date' => $this->parseDate($data['Date de validation de l\'ANO']),
                    'fund_transmission_date' => $this->parseDate($data['Date de transmission des fonds au Sous-Préfet']),
                    'villages_count' => $data['Nombre de villages'] ? intval($data['Nombre de villages']) : null,
                    'population_rgph' => $data['Population de la Sous-Préfecture au RGPH 21'] ? intval($data['Population de la Sous-Préfecture au RGPH 21']) : null,
                    'population_to_enroll' => $data['Population à enrôlées '] ? intval($data['Population à enrôlées ']) : null,
                    'status' => 'active'
                ]);

                // Créer les membres du comité
                $this->createCommitteeMembers($committee, $secretary);

                $message = "Comité local créé pour {$data['Sous-Préfecture']} avec le président {$secretary->name}";
                echo $message . "\n";
                Log::info($message);
            } catch (\Exception $e) {
                $message = "Erreur lors de la création du comité local pour {$data['Sous-Préfecture']} : {$e->getMessage()}";
                echo $message . "\n";
                Log::error($message);
            }
        }
        
        Log::info('Fin de la création des comités locaux');
    }

    private function createCommitteeMembers(LocalCommittee $committee, User $secretary)
    {
        // Ajouter le secrétaire comme président
        LocalCommitteeMember::create([
            'local_committee_id' => $committee->id,
            'user_id' => $secretary->id,
            'role' => 'president',
            'status' => 'active'
        ]);

        
    }

    private function parseDate(?string $date): ?string
    {
        if (empty($date)) return null;
        
        try {
            $date = trim($date);
            if (!preg_match('/^\d{1,2}\/\d{1,2}\/\d{2}$/', $date)) {
                return null;
            }
            
            $carbonDate = Carbon::createFromFormat('d/m/y', $date);
            if (!$carbonDate || $carbonDate->year < 2000) {
                return null;
            }
            
            return $carbonDate->format('Y-m-d');
        } catch (\Exception $e) {
            Log::warning("Erreur de parsing de la date: {$date}");
            return null;
        }
    }
} 