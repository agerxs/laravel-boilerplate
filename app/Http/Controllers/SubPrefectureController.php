<?php

namespace App\Http\Controllers;

use App\Models\Locality;
use Illuminate\Http\Request;

class SubPrefectureController extends Controller
{
    public function index()
    {
        $subPrefectures = Locality::whereHas('type', function($query) {
            $query->where('name', 'sub_prefecture');
        })->get();

        return response()->json($subPrefectures);
    }

    public function villages($subPrefectureId)
    {
        $villages = Locality::where('parent_id', $subPrefectureId)
            ->whereHas('type', function($query) {
                $query->where('name', 'village');
            })->with('representatives')->get();

        return response()->json($villages);
    }
} 