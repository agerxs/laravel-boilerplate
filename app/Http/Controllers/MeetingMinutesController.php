<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingMinutes;
use Illuminate\Http\Request;

class MeetingMinutesController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $minutes = $meeting->minutes()->create([
            ...$validated,
            'status' => 'draft'
        ]);

        return response()->json([
            'message' => 'Compte rendu créé avec succès',
            'minutes' => $minutes
        ]);
    }

    public function update(Request $request, MeetingMinutes $minutes)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'status' => 'required|in:draft,published'
        ]);

        $minutes->update($validated);

        if ($validated['status'] === 'published' && !$minutes->published_at) {
            $minutes->update(['published_at' => now()]);
        }

        return response()->json([
            'message' => 'Compte rendu mis à jour avec succès',
            'minutes' => $minutes->fresh()
        ]);
    }

    public function import(Request $request, Meeting $meeting)
    {
        $request->validate([
            'file' => 'required|file|mimes:doc,docx'
        ]);

        $file = $request->file('file');
        
        // Utilisez PhpWord pour lire le fichier
        $phpWord = \PhpOffice\PhpWord\IOFactory::load($file->getPathname());
        
        // Convertir en HTML
        $htmlWriter = new \PhpOffice\PhpWord\Writer\HTML($phpWord);
        $html = '';
        ob_start();
        $htmlWriter->save('php://output');
        $html = ob_get_clean();
        
        return response()->json([
            'content' => $html
        ]);
    }
} 