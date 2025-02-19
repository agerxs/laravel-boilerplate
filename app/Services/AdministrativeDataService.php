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
                $query->where('name', 'region');
            })
            ->with(['children' => function($query) {
                $query->whereHas('type', function($q) {
                    $q->where('name', 'department');
                })->with(['children' => function($q) {
                    $q->whereHas('type', function($sq) {
                        $sq->where('name', 'sub_prefecture');
                    });
                }]);
            }])
            ->get()
            ->map(function ($region) {
                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'type' => 'region',
                    'children' => $region->children->map(function ($department) {
                        return [
                            'id' => $department->id,
                            'name' => $department->name,
                            'type' => 'department',
                            'children' => $department->children->map(function ($subPrefecture) {
                                return [
                                    'id' => $subPrefecture->id,
                                    'name' => $subPrefecture->name,
                                    'type' => 'sub_prefecture'
                                ];
                            })
                        ];
                    })
                ];
            });

        return $regions;
    }
} 