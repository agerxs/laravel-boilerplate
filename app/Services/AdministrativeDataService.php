<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

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
} 