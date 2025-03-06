<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use App\Models\Locality;
use App\Models\LocalityType;

class AdministrativeDataService
{
    protected function getHierarchyData()
    {
        return Cache::rememberForever('administrative_hierarchy', function () {
            return json_decode(
                File::get(resource_path('data/hierarchy.json')),
                true
            );
        });
    }

    protected function getLocalitiesData()
    {
        return Cache::rememberForever('administrative_localities', function () {
            return json_decode(
                File::get(resource_path('data/localites.json')),
                true
            );
        });
    }

    public function getAllLocalities()
    {
        $localities = $this->getLocalitiesData();
        return collect($localities)->map(function ($locality) {
            return [
                'id' => $locality['code'] ?: $locality['hierarchy_level_name'],
                'name' => $locality['name'],
                'type' => $locality['hierarchy_level'],
                'parent_id' => $locality['parent_loc_code'],
                'hierarchy_level' => $locality['hierarchy_level'],
                'hierarchy_level_name' => $locality['hierarchy_level_name'],
                'is_active' => $locality['is_active'] === 'TRUE'
            ];
        })->filter(function ($locality) {
            return $locality['is_active'];
        });
    }

    public function getLocalitiesByLevel($level)
    {
        return $this->getAllLocalities()
            ->where('hierarchy_level', $level)
            ->values();
    }

    public function getLocalitiesByParent($parentCode)
    {
        return $this->getAllLocalities()
            ->where('parent_id', $parentCode)
            ->values();
    }

    public function getLocalityHierarchy()
    {
        $regions = Locality::whereHas('type', function($query) {
                $query->where('name', 'REGION');
            })
            ->with(['children' => function($query) {
                $query->whereHas('type', function($q) {
                    $q->where('name', 'DEPARTEMENT');
                })->with(['children' => function($q) {
                    $q->whereHas('type', function($sq) {
                        $sq->where('name', 'sub_prefecture');
                    })->with(['children' => function($sq) {
                        $sq->whereHas('type', function($tq) {
                            $tq->where('name', 'village');
                        });
                    }]);
                }]);
            }])
            ->get()
            ->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => 'REGION',
                    'children' => $region->children->map(function ($department) {
                        return [
                            'id' => $department->id,
                            'name' => $department->name,
                            'type' => 'DEPARTEMENT',
                            'children' => $department->children->map(function ($subPrefecture) {
                                return [
                                    'id' => $subPrefecture->id,
                                    'name' => $subPrefecture->name,
                                    'type' => 'sub_prefecture',
                                    'children' => $subPrefecture->children->map(function ($villageOrCommune) {
                                        return [
                                            'id' => $villageOrCommune->id,
                                            'name' => $villageOrCommune->name,
                                            'type' => $villageOrCommune->type->name
                                        ];
                                    })
                                ];
                            })
                        ];
                    })
                ];
            });

        return $regions;
    }
}
