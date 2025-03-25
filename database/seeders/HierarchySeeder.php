<?php

namespace Database\Seeders;

use App\Models\Locality;
use App\Models\LocalityType;
use Illuminate\Database\Seeder;

class HierarchySeeder extends Seeder
{
    public function run()
    {
        // Créer les types de localités
        $types = [
            'region' => 'Région',
            'department' => 'Département',
            'sub_prefecture' => 'Sous-préfecture'
        ];

        $localityTypes = [];
        foreach ($types as $key => $displayName) {
            $localityTypes[$key] = LocalityType::create([
                'name' => $key,
                //'display_name' => $displayName
            ]);
        }

        // Charger les données
        $localitiesData = json_decode(file_get_contents(resource_path('data/localites_cnam.json')), true);

        // Organiser les données par région
        $regions = [];
        foreach ($localitiesData as $item) {
            $regionName = $item['REGIONS'];
            $departmentName = $item['DEPARTEMENTS'];
            $subPrefectureName = $item['SOUS-PREFECTURES'];

            if (!isset($regions[$regionName])) {
                $regions[$regionName] = [];
            }
            if (!isset($regions[$regionName][$departmentName])) {
                $regions[$regionName][$departmentName] = [];
            }
            if (!in_array($subPrefectureName, $regions[$regionName][$departmentName])) {
                $regions[$regionName][$departmentName][] = $subPrefectureName;
            }
        }

        // Créer la hiérarchie
        foreach ($regions as $regionName => $departments) {
            $region = Locality::create([
                'name' => $regionName,
                'locality_type_id' => $localityTypes['region']->id,
                'parent_id' => null
            ]);

            foreach ($departments as $departmentName => $subPrefectures) {
                $department = Locality::create([
                    'name' => $departmentName,
                    'locality_type_id' => $localityTypes['department']->id,
                    'parent_id' => $region->id
                ]);

                foreach ($subPrefectures as $subPrefectureName) {
                    Locality::create([
                        'name' => $subPrefectureName,
                        'locality_type_id' => $localityTypes['sub_prefecture']->id,
                        'parent_id' => $department->id
                    ]);
                }
            }
        }
    }
}
