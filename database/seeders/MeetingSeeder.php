<?php

namespace Database\Seeders;

use App\Models\Meeting;
use App\Models\LocalCommittee;
use App\Models\Locality;
use App\Models\LocalityType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class MeetingSeeder extends Seeder
{
    public function run()
    {
        echo "Début du seeding des réunions...\n";

        // Charger les données du fichier JSON
        $jsonData = json_decode(
            File::get(resource_path('data/sous-pref.json')), 
            true
        );
        echo "Fichier JSON chargé : " . count($jsonData) . " sous-préfectures trouvées\n";

        // Récupérer le type "sous-prefecture"
        $subPrefectureType = LocalityType::where('name', 'sub_prefecture')->first();
        if (!$subPrefectureType) {
            echo "ERREUR : Type 'sub_prefecture' non trouvé\n";
            return;
        }
        echo "Type sous-préfecture trouvé (id: {$subPrefectureType->id})\n";

        $reunionsCreated = 0;
        $localitiesNotFound = [];
        $committeesNotFound = [];

        // Parcourir les données du JSON
        foreach ($jsonData as $data) {
            echo "\nTraitement de la sous-préfecture : {$data['Sous-Préfecture']}\n";

            // Trouver la localité correspondante (uniquement les sous-préfectures)
            $locality = Locality::where('name', $data['Sous-Préfecture'])
                ->where('locality_type_id', $subPrefectureType->id)
                ->first();
            
            if (!$locality) {
                echo "- ATTENTION : Localité non trouvée\n";
                $localitiesNotFound[] = $data['Sous-Préfecture'];
                continue;
            }
            echo "- Localité trouvée (id: {$locality->id})\n";

            // Trouver le comité local associé à cette localité
            $committee = LocalCommittee::where('locality_id', $locality->id)->first();
            if (!$committee) {
                echo "- ATTENTION : Comité local non trouvé\n";
                $committeesNotFound[] = $locality->name;
                continue;
            }
            echo "- Comité local trouvé (id: {$committee->id})\n";

            // Créer les réunions à partir des champs reunion_X
            for ($i = 1; $i <= 6; $i++) {
                $reunionKey = "reunion_{$i}";
                if (!empty($data[$reunionKey])) {
                    $reunionDate = $this->parseDate($data[$reunionKey]);
                    if ($reunionDate) {
                        Meeting::create([
                            'title' => "Réunion {$i} - {$locality->name}",
                            'local_committee_id' => $committee->id,
                            'scheduled_date' => $reunionDate,
                            'status' => $reunionDate->isPast() ? 'completed' : 'planned'
                        ]);
                        $reunionsCreated++;
                        echo "- Réunion {$i} créée pour le {$reunionDate->format('d/m/Y')}\n";
                    } else {
                        Log::warning("Erreur de format de date", [
                            'sous_prefecture' => $data['Sous-Préfecture'],
                            'reunion' => $i,
                            'date' => $data[$reunionKey]
                        ]);
                        echo "- ATTENTION : Date invalide pour réunion {$i}: {$data[$reunionKey]}\n";
                    }
                }
            }
        }

        echo "\n=== Résumé ===\n";
        echo "Réunions créées : {$reunionsCreated}\n";
        if (count($localitiesNotFound) > 0) {
            echo "Localités non trouvées : " . implode(', ', $localitiesNotFound) . "\n";
        }
        if (count($committeesNotFound) > 0) {
            echo "Comités non trouvés : " . implode(', ', $committeesNotFound) . "\n";
        }
        echo "Seeding terminé\n";
    }


 private function parseDate($dateString)
 {
     $formats = ['m/d/Y', 'd-M', 'n/j/y', 'j/n/y', 'm/d/y', 'n/j/Y', 'd/m/Y'];
 
     foreach ($formats as $format) {
         try {
             $date = Carbon::createFromFormat($format, $dateString);
 
             if ($date) {
                 // Vérifier si la date est valide dans le calendrier
                 if (!checkdate($date->month, $date->day, $date->year)) {
                     return null; // Date invalide (ex: 30/02/2025)
                 }
 
                 // Si l'année est à deux chiffres, ajuster correctement
                 if ($date->year < 100) {
                     if ($format === 'm/d/y') {
                         $date->year += 2000; // Pour m/d/y, on considère l'année comme étant dans les années 2000
                     } else {
                         $date->year += ($date->year < 50) ? 2000 : 1900;
                     }
                 }
 
                 return $date;
             }
         } catch (\Exception $e) {
             continue;
         }
     }
     return null;
 }
 

} 
