<?php

namespace App\Http\Controllers;

use App\Models\Locality;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LocalityController extends Controller
{
    public function index()
    {
        $localities = Locality::with(['parent', 'children', 'type'])->get();

        return Inertia::render('Localities/Index', [
            'localities' => $localities
        ]);
    }

    public function show(Locality $locality)
    {
        $locality->load(['parent', 'children.type', 'type']);

        return Inertia::render('Localities/Show', [
            'locality' => $locality
        ]);
    }
} 