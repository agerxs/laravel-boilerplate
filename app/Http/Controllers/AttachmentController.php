<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class AttachmentController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
            ]);

            $file = $request->file('file');
            
            // Vérifier que le dossier existe
            if (!Storage::disk('public')->exists('attachments')) {
                Storage::disk('public')->makeDirectory('attachments');
            }

            $path = $file->store('attachments', 'public');

            if (!$path) {
                throw new \Exception('Impossible de sauvegarder le fichier');
            }

            $attachment = $meeting->attachments()->create([
                'name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            $attachment->load('uploader');

            return response()->json([
                'message' => 'Fichier ajouté avec succès',
                'attachment' => $attachment
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload du fichier: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Erreur lors de l\'upload du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Attachment $attachment)
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return response()->json(['message' => 'Fichier supprimé avec succès']);
    }

    public function download(Attachment $attachment)
    {
        // Vérifier que l'utilisateur a accès à cette pièce jointe
        if (!auth()->user()->can('view', $attachment->meeting)) {
            abort(403);
        }

        return Storage::disk('public')->download(
            $attachment->file_path,
            $attachment->name
        );
    }
} 