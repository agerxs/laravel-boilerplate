<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function store(Request $request, Meeting $meeting)
    {
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'title' => 'required|string|max:255',
                'nature' => 'required|string|in:photo,document_justificatif,compte_rendu'
            ]);

            $file = $request->file('file');
            
            // Créer le nom du fichier avec le titre et l'horodatage
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('Y-m-d_His');
            $sanitizedTitle = Str::slug($request->title);
            $filename = "{$sanitizedTitle}_{$timestamp}.{$extension}";

            // Vérifier que le dossier existe
            if (!Storage::disk('public')->exists('attachments')) {
                Storage::disk('public')->makeDirectory('attachments');
            }

            // Sauvegarder le fichier avec le nouveau nom
            $path = Storage::disk('public')->putFileAs(
                'attachments',
                $file,
                $filename
            );

            if (!$path) {
                throw new \Exception('Impossible de sauvegarder le fichier');
            }

            $attachment = $meeting->attachments()->create([
                'title' => $request->title,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_type' => $file->getMimeType(),
                'nature' => $request->nature,
                'size' => $file->getSize(),
                'uploaded_by' => auth()->id(),
            ]);

            $attachment->load('uploader');

            // Recharger la réunion avec ses pièces jointes pour Inertia
            $meeting->load('attachments');

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Fichier ajouté avec succès',
                    'attachment' => $attachment,
                    'meeting' => $meeting
                ]);
            }

            return redirect()->back()->with('success', 'Fichier ajouté avec succès');

        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'upload du fichier'], 500);
        }
    }

    public function destroy(Attachment $attachment)
    {
        if (!auth()->user()->can('delete', $attachment)) {
            abort(403, 'Vous n\'avez pas l\'autorisation de supprimer ce fichier.');
        }

        try {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();

            return response()->json(['message' => 'Fichier supprimé avec succès']);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la suppression: ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de la suppression du fichier'], 500);
        }
    }

    public function download(Attachment $attachment)
    {
        try {
            // Vérifier que l'utilisateur a accès à cette pièce jointe
            if (!auth()->user()->can('view', $attachment)) {
                abort(403, 'Vous n\'avez pas l\'autorisation de télécharger ce fichier.');
            }

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                abort(404, 'Fichier non trouvé.');
            }

            return Storage::disk('public')->download(
                $attachment->file_path,
                $attachment->original_name
            );
        } catch (\Exception $e) {
            Log::error('Erreur lors du téléchargement: ' . $e->getMessage());
            abort(500, 'Erreur lors du téléchargement du fichier.');
        }
    }

    /**
     * Upload d'un fichier depuis le mobile (API)
     * Route: POST /api/attachments/upload
     */
    public function upload(Request $request)
    {
        info("tcho");
        info($request->all());
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'title' => 'nullable|string|max:255',
                'nature' => 'nullable|string|in:photo,document_justificatif,compte_rendu',
                'meeting_id' => 'nullable|integer|exists:meetings,id',
            ]);

            info("validé");
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('Y-m-d_His');
            $sanitizedTitle = $request->title ? \Illuminate\Support\Str::slug($request->title) : 'attachment';
            $filename = "{$sanitizedTitle}_{$timestamp}.{$extension}";
            info("alright");
            info($request->title);
            info($sanitizedTitle);
            if (!Storage::disk('public')->exists('attachments')) {
                Storage::disk('public')->makeDirectory('attachments');
            }

            $path = Storage::disk('public')->putFileAs(
                'attachments',
                $file,
                $filename
            );

            if (!$path) {
                throw new \Exception('Impossible de sauvegarder le fichier');
            }

            $attachment = new Attachment();
            $attachment->title = $request->title;
            $attachment->original_name = $file->getClientOriginalName();
            $attachment->file_path = $path;
            $attachment->file_type = $file->getMimeType();
            $attachment->nature = $request->nature;
            $attachment->size = $file->getSize();
            $attachment->meeting_id = $request->meeting_id;
            $attachment->uploaded_by = auth()->id();
            $attachment->save();

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'attachment' => $attachment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload (API mobile): ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'upload du fichier'], 500);
        }
    }

    public function uploadFromMobile(Request $request)
{
    try {
        // On récupère tous les fichiers du formulaire (peu importe le nom du champ)
        $allFiles = $request->allFiles();

        if (empty($allFiles)) {
            return response()->json(['message' => 'Aucun fichier reçu'], 400);
        }

        $attachments = [];

        foreach ($allFiles as $fieldName => $files) {
            // $files peut être un tableau ou un seul fichier
            $files = is_array($files) ? $files : [$files];

            foreach ($files as $file) {
                $originalName = $file->getClientOriginalName();
                $extension = $file->getClientOriginalExtension();
                $timestamp = now()->format('Y-m-d_His');
                $filename = pathinfo($originalName, PATHINFO_FILENAME) . "_{$timestamp}.{$extension}";

                if (!\Storage::disk('public')->exists('attachments')) {
                    \Storage::disk('public')->makeDirectory('attachments');
                }

                $path = \Storage::disk('public')->putFileAs(
                    'attachments',
                    $file,
                    $filename
                );

                if (!$path) {
                    throw new \Exception('Impossible de sauvegarder le fichier');
                }

                // On peut récupérer des infos supplémentaires dans $request->input()
                $attachment = new \App\Models\Attachment();
                $attachment->title = $request->input('title') ?? $originalName;
                $attachment->original_name = $originalName;
                $attachment->file_path = $path;
                $attachment->file_type = $file->getMimeType();
                $attachment->nature = $request->input('nature');
                $attachment->size = $file->getSize();
                $attachment->meeting_id = $request->input('meetingId');
                $attachment->uploaded_by = auth()->id();
                $attachment->save();

                $attachments[] = $attachment;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Fichier(s) uploadé(s) avec succès',
            'attachments' => $attachments
        ], 201);

    } catch (\Exception $e) {
        \Log::error('Erreur lors de l\'upload (API mobile): ' . $e->getMessage());
        return response()->json(['message' => 'Erreur lors de l\'upload du fichier'], 500);
    }
}

    public function uploadBak(Request $request)
    {
        info("tcho");
        info($request->all());
        try {
            $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'title' => 'nullable|string|max:255',
                'nature' => 'nullable|string|in:photo,document_justificatif,compte_rendu',
                'meeting_id' => 'nullable|integer|exists:meetings,id',
            ]);

            info("validé");
            $file = $request->file('file');
            $extension = $file->getClientOriginalExtension();
            $timestamp = now()->format('Y-m-d_His');
            $sanitizedTitle = $request->title ? \Illuminate\Support\Str::slug($request->title) : 'attachment';
            $filename = "{$sanitizedTitle}_{$timestamp}.{$extension}";
            info("alright");
            info($request->title);
            info($sanitizedTitle);
            if (!Storage::disk('public')->exists('attachments')) {
                Storage::disk('public')->makeDirectory('attachments');
            }

            $path = Storage::disk('public')->putFileAs(
                'attachments',
                $file,
                $filename
            );

            if (!$path) {
                throw new \Exception('Impossible de sauvegarder le fichier');
            }

            $attachment = new Attachment();
            $attachment->title = $request->title;
            $attachment->original_name = $file->getClientOriginalName();
            $attachment->file_path = "storage/$path";
            $attachment->file_type = $file->getMimeType();
            $attachment->nature = $request->nature;
            $attachment->size = $file->getSize();
            $attachment->meeting_id = $request->meeting_id;
            $attachment->uploaded_by = auth()->id();
            $attachment->save();

            return response()->json([
                'success' => true,
                'message' => 'Fichier uploadé avec succès',
                'attachment' => $attachment
            ], 201);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'upload (API mobile): ' . $e->getMessage());
            return response()->json(['message' => 'Erreur lors de l\'upload du fichier'], 500);
        }
    }
} 